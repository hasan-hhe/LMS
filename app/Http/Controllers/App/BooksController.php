<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookInstance;
use App\Models\InstanceState;
use Exception;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class BooksController extends Controller
{
    #[OA\Post(
        path: '/books/store',
        tags: ['Books'],
        summary: 'Add a new book',
        description: 'Requires LIBRARIAN or ADMIN role.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['ISBN', 'auther_id', 'catagory_id', 'publisher_id', 'title', 'discription', 'price', 'year_of_publishing', 'number_edition', 'copies_count', 'copy_condition'],
                    properties: [
                        new OA\Property(property: 'ISBN', type: 'integer'),
                        new OA\Property(property: 'auther_id', type: 'integer'),
                        new OA\Property(property: 'catagory_id', type: 'integer'),
                        new OA\Property(property: 'publisher_id', type: 'integer'),
                        new OA\Property(property: 'title', type: 'string'),
                        new OA\Property(property: 'discription', type: 'string'),
                        new OA\Property(property: 'price', type: 'number'),
                        new OA\Property(property: 'year_of_publishing', type: 'string'),
                        new OA\Property(property: 'number_edition', type: 'string'),
                        new OA\Property(property: 'copies_count', type: 'integer', minimum: 1, maximum: 500),
                        new OA\Property(property: 'copy_condition', type: 'string', enum: ['new', 'almost_new', 'worn']),
                        new OA\Property(property: 'cover_image', type: 'string', format: 'binary', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Book created'),
            new OA\Response(response: 403, description: 'Insufficient permissions'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request)
    {
        // Role guard — same pattern as MemberController
        $actor = $request->user();
        if (!in_array($actor->role, ['LIBRARIAN', 'ADMIN'])) {
            return response()->json(['body' => 'Unauthorized'], 403);
        }

        // Validate every required field the schema demands
        try {
            $request->validate([
                'ISBN'              => 'required|integer|unique:books,ISBN',
                'auther_id'         => 'required|integer|exists:authers,id',
                'catagory_id'       => 'required|integer|exists:catagories,id',
                'publisher_id'      => 'required|integer|exists:publishers,id',
                'title'             => 'required|string|max:255',
                'discription'       => 'required|string',
                'price'             => 'required|numeric|min:0',
                'year_of_publishing' => 'required|string|max:4',
                'number_edition'    => 'required|string',
                // copies_count drives how many BookInstance rows are created
                'copies_count'      => 'required|integer|min:1|max:500',
                'copy_condition'    => 'required|in:new,almost_new,worn',
                // optional cover image
                'cover_image'       => 'nullable|image|mimes:png,jpg,gif|max:4096',
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'body' => $e->getMessage()], 422);
        }

        // Handle optional cover upload (mirrors photo_url pattern in MemberController)
        $coverUrl = null;
        if ($request->hasFile('cover_image')) {
            $coverUrl = $request->file('cover_image')->store('Covers', 'public');
        }

        // Resolve the "available" state ID once
        $availableStateId = InstanceState::query()->where('state', 'available')->value('id');

        try {
            // 1. Create the book record
            $book = Book::create([
                'ISBN'               => $request->ISBN,
                'auther_id'          => $request->auther_id,
                'catagory_id'        => $request->catagory_id,
                'publisher_id'       => $request->publisher_id,
                'title'              => $request->title,
                'discription'        => $request->discription,
                'price'              => $request->price,
                'amount'             => $request->copies_count,   // total stock count
                'rate_avg'           => 0,
                'cover_url'          => $coverUrl,
                'year_of_publishing' => $request->year_of_publishing,
                'number_edition'     => $request->number_edition,
            ]);

            // 2. Create one BookInstance row per physical copy
            $instances = [];
            for ($i = 0; $i < $request->copies_count; $i++) {
                $instances[] = [
                    'book_ISBN'  => $book->ISBN,
                    'state_id'   => $availableStateId,
                    'condition'  => $request->copy_condition,
                ];
            }
            BookInstance::insert($instances);   // single bulk insert

        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'body' => $e->getMessage()], 500);
        }

        return response()->json([
            'body' => 'Book added successfully',
            'book' => $book->load('author', 'category', 'publisher'),
            'copies_created' => $request->copies_count,
        ], 201);
    }

    #[OA\Get(
        path: '/books/search',
        tags: ['Books'],
        summary: 'Search books',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'ISBN', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'title', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'auther_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'catagory_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'publisher_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'year', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'edition', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Books retrieved'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index(Request $request)
    {
        $query = Book::with('author', 'category', 'publisher');

        // Each filter is independently optional — combine freely
        if ($request->filled('ISBN')) {
            $query->where('ISBN', $request->ISBN);
        }
        if ($request->filled('title')) {
            // LIKE search so partial matches work
            $query->where('title', 'like', '%' . $request->title . '%');
        }
        if ($request->filled('auther_id')) {
            $query->where('auther_id', $request->auther_id);
        }
        if ($request->filled('catagory_id')) {
            $query->where('catagory_id', $request->catagory_id);
        }
        if ($request->filled('publisher_id')) {
            $query->where('publisher_id', $request->publisher_id);
        }
        if ($request->filled('year')) {
            $query->where('year_of_publishing', $request->year);
        }
        if ($request->filled('edition')) {
            $query->where('number_edition', $request->edition);
        }

        $books = $query->get();

        return response()->json([
            'total' => $books->count(),
            'books' => $books,
        ]);
    }

    #[OA\Get(
        path: '/books/{ISBN}/copies',
        tags: ['Books'],
        summary: 'List book copies and their states',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'ISBN', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Copies retrieved'),
            new OA\Response(response: 404, description: 'Book not found'),
        ]
    )]
    public function copies($ISBN)
    {
        $book = Book::with('author', 'category', 'publisher')->findOrFail($ISBN);

        // Load every instance with its state label
        $instances = BookInstance::with('state')
            ->where('book_ISBN', $ISBN)
            ->get()
            ->map(fn($i) => [
                'instance_id' => $i->id,
                'condition'   => $i->condition,
                'status'      => $i->state->state,   // e.g. "available", "borrowed"
            ]);

        // Summary counts per state for a quick dashboard view
        $summary = $instances->groupBy('status')
            ->map(fn($g) => $g->count());

        return response()->json([
            'book'      => $book,
            'summary'   => $summary,   // e.g. { "available": 3, "borrowed": 1 }
            'copies'    => $instances,
        ]);
    }

    #[OA\Get(
        path: '/books/{ISBN}',
        tags: ['Books'],
        summary: 'Get book by ISBN',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'ISBN', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Book found'),
            new OA\Response(response: 404, description: 'Book not found'),
        ]
    )]
    public function show($ISBN)
    {
        $book = Book::with('author', 'category', 'publisher')->findOrFail($ISBN);
        return response()->json(['book' => $book]);
    }

    #[OA\Post(
        path: '/books/update/{ISBN}',
        tags: ['Books'],
        summary: 'Update a book',
        description: 'Requires LIBRARIAN or ADMIN role.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'ISBN', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'discription', type: 'string'),
                    new OA\Property(property: 'price', type: 'number'),
                    new OA\Property(property: 'year_of_publishing', type: 'string'),
                    new OA\Property(property: 'number_edition', type: 'string'),
                    new OA\Property(property: 'auther_id', type: 'integer'),
                    new OA\Property(property: 'catagory_id', type: 'integer'),
                    new OA\Property(property: 'publisher_id', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Book updated'),
            new OA\Response(response: 403, description: 'Insufficient permissions'),
            new OA\Response(response: 404, description: 'Book not found'),
        ]
    )]
    public function update(Request $request, $ISBN)
    {
        $actor = $request->user();
        if (!in_array($actor->role, ['LIBRARIAN', 'ADMIN'])) {
            return response()->json(['body' => 'Unauthorized'], 403);
        }

        $book = Book::findOrFail($ISBN);
        $book->update($request->only([
            'title',
            'discription',
            'price',
            'year_of_publishing',
            'number_edition',
            'auther_id',
            'catagory_id',
            'publisher_id',
        ]));

        return response()->json(['body' => 'Book updated', 'book' => $book]);
    }

    #[OA\Post(
        path: '/books/destroy/{ISBN}',
        tags: ['Books'],
        summary: 'Delete a book',
        description: 'Requires ADMIN role.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'ISBN', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Book deleted'),
            new OA\Response(response: 403, description: 'Admin role required'),
            new OA\Response(response: 404, description: 'Book not found'),
        ]
    )]
    public function destroy(Request $request, $ISBN)
    {
        $actor = $request->user();
        if ($actor->role !== 'ADMIN') {
            return response()->json(['body' => 'Unauthorized'], 403);
        }
        Book::findOrFail($ISBN)->delete();
        return response()->json(['body' => 'Book deleted']);
    }
}

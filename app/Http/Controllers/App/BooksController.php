<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookInstance;
use App\Models\InstanceState;
use Exception;
use Illuminate\Http\Request;

class BooksController extends Controller
{
    // -------------------------------------------------------
    // FEATURE 1 — Add a book (librarian / admin only)
    // POST /api/books/store
    // -------------------------------------------------------
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

    // -------------------------------------------------------
    // FEATURE 2 — Search books (multiple ways)
    // GET /api/books?title=...&auther_id=...&catagory_id=...
    //              &publisher_id=...&year=...&ISBN=...
    // -------------------------------------------------------
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

    // -------------------------------------------------------
    // FEATURE 3 — View all copies of a book + their states
    // GET /api/books/{ISBN}/copies
    // -------------------------------------------------------
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

    // -------------------------------------------------------
    // Show a single book
    // GET /api/books/{ISBN}
    // -------------------------------------------------------
    public function show($ISBN)
    {
        $book = Book::with('author', 'category', 'publisher')->findOrFail($ISBN);
        return response()->json(['book' => $book]);
    }

    // -------------------------------------------------------
    // Update a book (librarian / admin only)
    // POST /api/books/update/{ISBN}
    // -------------------------------------------------------
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

    // -------------------------------------------------------
    // Delete a book (admin only)
    // POST /api/books/destroy/{ISBN}
    // -------------------------------------------------------
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

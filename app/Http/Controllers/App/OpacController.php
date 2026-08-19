<?php

namespace App\Http\Controllers\App;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\DigitalAssetResource;
use App\Models\Book;
use Illuminate\Http\Request;

class OpacController extends Controller
{
    public function index(Request $request)
    {
        $this->authenticateOptional($request);
        $query = Book::query()
            ->with(['author', 'digitalAsset'])
            ->withCount([
                'instances',
                'instances as available_count' => fn ($q) => $q->whereHas('state', fn ($state) => $state->where('state', 'available')),
            ]);

        $search = $request->input('q', $request->input('search'));
        if (filled($search)) {
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('ISBN', 'like', "%{$search}%")
                ->orWhereHas('author', fn ($author) => $author
                    ->where('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")));
        }
        if ($request->filled('title')) {
            $query->where('title', 'like', '%'.$request->input('title').'%');
        }
        if ($request->filled('author')) {
            $author = $request->input('author');
            $query->whereHas('author', fn ($q) => $q
                ->where('firstname', 'like', "%{$author}%")
                ->orWhere('lastname', 'like', "%{$author}%"));
        }
        if ($request->filled('isbn')) {
            $query->where('ISBN', 'like', '%'.$request->input('isbn').'%');
        }
        if ($request->filled('author_id')) {
            $query->where('auther_id', $request->integer('author_id'));
        }
        if ($request->filled('category_id')) {
            $query->where('catagory_id', $request->integer('category_id'));
        }
        if ($request->boolean('available_only')) {
            $query->whereHas('instances', fn ($q) => $q->whereHas('state', fn ($state) => $state->where('state', 'available')));
        }
        if ($request->boolean('digital_only')) {
            $query->whereHas('digitalAsset');
        }
        if ($request->filled('published_from')) {
            $year = substr((string) $request->input('published_from'), 0, 4);
            if (ctype_digit($year)) {
                $query->where('year_of_publishing', '>=', $year);
            }
        }

        $books = $query->paginate(min($request->integer('per_page', 15), 50))
            ->through(fn (Book $book) => $this->publicBook($book, $request));

        return ResponseHelper::success([
            'items' => array_values($books->items()),
            'data' => array_values($books->items()),
            'meta' => [
                'current_page' => $books->currentPage(),
                'last_page' => $books->lastPage(),
                'per_page' => $books->perPage(),
                'total' => $books->total(),
            ],
        ], 'تم جلب فهرس الكتب بنجاح');
    }

    public function show(Request $request, string $ISBN)
    {
        $this->authenticateOptional($request);
        $book = Book::with(['author', 'digitalAsset'])
            ->withCount([
                'instances',
                'instances as available_count' => fn ($q) => $q->whereHas('state', fn ($state) => $state->where('state', 'available')),
            ])->find($ISBN);

        return $book
            ? ResponseHelper::success($this->publicBook($book, $request), 'تم جلب الكتاب بنجاح')
            : ResponseHelper::notFound('الكتاب غير موجود');
    }

    private function publicBook(Book $book, Request $request): array
    {
        return [
            'isbn' => $book->ISBN,
            'title' => $book->title,
            'author' => $book->author?->fullName(),
            'cover_url' => $book->cover_url ? asset('storage/'.$book->cover_url) : null,
            'copies_count' => $book->instances_count,
            'available_count' => $book->available_count,
            'available_copies' => $book->available_count,
            'price_syp' => $book->price,
            'price_points' => $book->price_points,
            'rate_avg' => $book->rate_avg,
            'rating' => $book->rate_avg,
            'published_at' => $book->year_of_publishing
                ? $book->year_of_publishing.'-01-01'
                : null,
            'digital' => $book->digitalAsset
                ? (new DigitalAssetResource($book->digitalAsset))->resolve($request)
                : null,
        ];
    }

    private function authenticateOptional(Request $request): void
    {
        if ($request->user()) {
            return;
        }
        $user = $request->user('sanctum');
        if ($user) {
            $request->setUserResolver(static fn () => $user);
        }
    }
}

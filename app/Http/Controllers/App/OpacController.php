<?php

namespace App\Http\Controllers\App;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\DigitalAssetResource;
use App\Http\Resources\ReviewResource;
use App\Models\Book;
use App\Services\ReviewService;
use Illuminate\Http\Request;

class OpacController extends Controller
{
    public function __construct(private ReviewService $reviews) {}

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
        $minRating = $request->input('min_rating', $request->input('rating'));
        if ($minRating !== null && $minRating !== '') {
            $query->where('rate_avg', '>=', (float) $minRating);
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
        $book = Book::with(['author', 'digitalAsset', 'reviews.user'])
            ->withCount([
                'instances',
                'instances as available_count' => fn ($q) => $q->whereHas('state', fn ($state) => $state->where('state', 'available')),
            ])->find($ISBN);

        if (! $book) {
            return ResponseHelper::notFound('الكتاب غير موجود');
        }

        $payload = $this->publicBook($book, $request);
        $user = $request->user();
        $payload['can_review'] = $user
            ? $this->reviews->canReview((int) $user->id, $book->ISBN)
            : false;
        $payload['reviews'] = ReviewResource::collection($book->reviews)->resolve($request);
        $payload['reviews_count'] = $book->reviews->count();

        return ResponseHelper::success($payload, 'تم جلب الكتاب بنجاح');
    }

    private function publicBook(Book $book, Request $request): array
    {
        $saleStock = (int) ($book->amount ?? 0);
        $hasPdf = (bool) $book->digitalAsset?->hasPdf();
        $paidPdf = $hasPdf && ! (bool) $book->digitalAsset?->is_free;
        $formats = array_values(array_filter([
            $saleStock > 0 ? 'paper' : null,
            $paidPdf ? 'pdf' : null,
        ]));

        return [
            'isbn' => $book->ISBN,
            'title' => $book->title,
            'author' => $book->author?->fullName(),
            'cover_url' => $book->cover_url ? asset('storage/'.$book->cover_url) : null,
            'copies_count' => $book->instances_count,
            'available_count' => $book->available_count,
            'available_copies' => $book->available_count,
            'sale_stock' => $saleStock,
            'available_sale_copies' => $saleStock,
            'has_pdf' => $hasPdf,
            'available_formats' => $formats,
            'can_purchase' => $saleStock > 0 || $paidPdf,
            'price_syp' => $book->price,
            'price_points' => $book->price_points,
            'borrow_points' => (int) ($book?->borrow_points ?? 0),
            'has_borrow_points' => (int) ($book?->borrow_points ?? 0) > 0,
            'borrow_days' => $book?->loanPeriodDays() ?? 14,
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

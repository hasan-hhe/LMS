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
        $query = Book::query()
            ->with('author')
            ->withCount([
                'instances',
                'instances as available_count' => fn ($q) => $q->whereHas('state', fn ($state) => $state->where('state', 'available')),
            ]);

        if ($request->filled('q')) {
            $term = $request->string('q');
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$term}%")
                ->orWhere('ISBN', 'like', "%{$term}%")
                ->orWhereHas('author', fn ($author) => $author
                    ->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")));
        }
        if ($request->filled('title')) {
            $query->where('title', 'like', '%'.$request->input('title').'%');
        }
        if ($request->filled('author_id')) {
            $query->where('auther_id', $request->integer('author_id'));
        }
        if ($request->filled('category_id')) {
            $query->where('catagory_id', $request->integer('category_id'));
        }

        return ResponseHelper::success(
            $query->paginate(min($request->integer('per_page', 15), 50))->through(fn (Book $book) => $this->publicBook($book)),
            'تم جلب فهرس الكتب بنجاح'
        );
    }

    public function show(Request $request, string $ISBN)
    {
        $book = Book::with(['author', 'digitalAsset'])
            ->withCount([
                'instances',
                'instances as available_count' => fn ($q) => $q->whereHas('state', fn ($state) => $state->where('state', 'available')),
            ])->find($ISBN);

        return $book
            ? ResponseHelper::success([
                ...$this->publicBook($book),
                'digital' => $book->digitalAsset
                    ? (new DigitalAssetResource($book->digitalAsset))->resolve($request)
                    : null,
            ], 'تم جلب الكتاب بنجاح')
            : ResponseHelper::notFound('الكتاب غير موجود');
    }

    private function publicBook(Book $book): array
    {
        return [
            'isbn' => $book->ISBN,
            'title' => $book->title,
            'author' => $book->author?->fullName(),
            'cover_url' => $book->cover_url ? asset('storage/'.$book->cover_url) : null,
            'copies_count' => $book->instances_count,
            'available_count' => $book->available_count,
            'price_syp' => $book->price,
            'price_points' => $book->price_points,
            'rate_avg' => $book->rate_avg,
        ];
    }
}

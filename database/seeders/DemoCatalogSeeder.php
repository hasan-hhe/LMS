<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookInstance;
use App\Models\Category;
use App\Models\InstanceState;
use App\Models\Publisher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();
        try {
            $author1 = Author::firstOrCreate(
                ['firstname' => 'نجيب', 'lastname' => 'محفوظ'],
                ['nationality' => 'مصري']
            );
            $author2 = Author::firstOrCreate(
                ['firstname' => 'غسان', 'lastname' => 'كنفاني'],
                ['nationality' => 'فلسطيني']
            );
            $author3 = Author::firstOrCreate(
                ['firstname' => 'طه', 'lastname' => 'حسين'],
                ['nationality' => 'مصري']
            );

            $category1 = Category::firstOrCreate(
                ['title' => 'أدب'],
                ['discription' => 'الروايات والقصص الأدبية']
            );
            $category2 = Category::firstOrCreate(
                ['title' => 'تاريخ'],
                ['discription' => 'كتب التاريخ والحضارة']
            );
            $category3 = Category::firstOrCreate(
                ['title' => 'علوم'],
                ['discription' => 'كتب العلوم والتكنولوجيا']
            );

            $publisher1 = Publisher::firstOrCreate(
                ['name' => 'دار الشروق'],
                ['location' => 'القاهرة']
            );
            $publisher2 = Publisher::firstOrCreate(
                ['name' => 'دار الآداب'],
                ['location' => 'بيروت']
            );

            $books = [
                [
                    'ISBN'               => '978-1111111111',
                    'auther_id'          => $author1->id,
                    'catagory_id'        => $category1->id,
                    'publisher_id'       => $publisher1->id,
                    'title'              => 'أولاد حارتنا',
                    'discription'        => 'رواية أدبية للكاتب نجيب محفوظ',
                    'price'              => 45.00,
                    'amount'             => 5,
                    'rate_avg'           => 4.5,
                    'cover_url'          => '',
                    'year_of_publishing' => '1959',
                    'number_edition'     => '1',
                ],
                [
                    'ISBN'               => '978-2222222222',
                    'auther_id'          => $author2->id,
                    'catagory_id'        => $category1->id,
                    'publisher_id'       => $publisher2->id,
                    'title'              => 'رجال في الشمس',
                    'discription'        => 'مجموعة قصصية لغسان كنفاني',
                    'price'              => 30.00,
                    'amount'             => 4,
                    'rate_avg'           => 4.2,
                    'cover_url'          => '',
                    'year_of_publishing' => '1963',
                    'number_edition'     => '2',
                ],
                [
                    'ISBN'               => '978-3333333333',
                    'auther_id'          => $author3->id,
                    'catagory_id'        => $category2->id,
                    'publisher_id'       => $publisher1->id,
                    'title'              => 'الأيام',
                    'discription'        => 'سيرة ذاتية لطه حسين',
                    'price'              => 35.00,
                    'amount'             => 3,
                    'rate_avg'           => 4.8,
                    'cover_url'          => '',
                    'year_of_publishing' => '1929',
                    'number_edition'     => '5',
                ],
                [
                    'ISBN'               => '978-4444444444',
                    'auther_id'          => $author1->id,
                    'catagory_id'        => $category1->id,
                    'publisher_id'       => $publisher1->id,
                    'title'              => 'الثلاثية',
                    'discription'        => 'ثلاثية نجيب محفوظ الشهيرة',
                    'price'              => 90.00,
                    'amount'             => 2,
                    'rate_avg'           => 4.9,
                    'cover_url'          => '',
                    'year_of_publishing' => '1956',
                    'number_edition'     => '3',
                ],
                [
                    'ISBN'               => '978-5555555555',
                    'auther_id'          => $author3->id,
                    'catagory_id'        => $category3->id,
                    'publisher_id'       => $publisher2->id,
                    'title'              => 'مدخل إلى العلوم',
                    'discription'        => 'كتاب تعليمي في العلوم',
                    'price'              => 55.00,
                    'amount'             => 3,
                    'rate_avg'           => 4.0,
                    'cover_url'          => '',
                    'year_of_publishing' => '2020',
                    'number_edition'     => '1',
                ],
            ];

            foreach ($books as $bookData) {
                Book::updateOrCreate(['ISBN' => $bookData['ISBN']], $bookData);
            }

            $availableState = InstanceState::where('state', 'available')->firstOrFail();
            $borrowedState  = InstanceState::where('state', 'borrowed')->firstOrFail();
            $reservedState  = InstanceState::where('state', 'reserved')->firstOrFail();
            $damagedState   = InstanceState::where('state', 'damaged')->firstOrFail();
            $lostState      = InstanceState::where('state', 'lost')->firstOrFail();

            $instancePlan = [
                ['ISBN' => '978-1111111111', 'state' => $availableState, 'condition' => 'new', 'count' => 2],
                ['ISBN' => '978-2222222222', 'state' => $availableState, 'condition' => 'almost_new', 'count' => 2],
                ['ISBN' => '978-3333333333', 'state' => $availableState, 'condition' => 'new', 'count' => 1],
                ['ISBN' => '978-4444444444', 'state' => $availableState, 'condition' => 'worn', 'count' => 1],
                ['ISBN' => '978-5555555555', 'state' => $availableState, 'condition' => 'new', 'count' => 1],
                ['ISBN' => '978-1111111111', 'state' => $borrowedState, 'condition' => 'worn', 'count' => 1],
                ['ISBN' => '978-2222222222', 'state' => $borrowedState, 'condition' => 'almost_new', 'count' => 1],
                ['ISBN' => '978-3333333333', 'state' => $borrowedState, 'condition' => 'new', 'count' => 1],
                ['ISBN' => '978-4444444444', 'state' => $reservedState, 'condition' => 'new', 'count' => 1],
                ['ISBN' => '978-5555555555', 'state' => $damagedState, 'condition' => 'worn', 'count' => 1],
                ['ISBN' => '978-1111111111', 'state' => $lostState, 'condition' => 'worn', 'count' => 1],
            ];

            BookInstance::query()->delete();

            foreach ($instancePlan as $plan) {
                for ($i = 0; $i < $plan['count']; $i++) {
                    BookInstance::create([
                        'book_ISBN' => $plan['ISBN'],
                        'state_id'  => $plan['state']->id,
                        'condition' => $plan['condition'],
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

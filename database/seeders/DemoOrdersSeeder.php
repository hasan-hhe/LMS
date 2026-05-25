<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderState;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoOrdersSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();
        try {
            OrderItem::query()->delete();
            Order::query()->delete();

            $member1 = User::where('email', 'member1@lms.test')->firstOrFail();
            $member2 = User::where('email', 'member2@lms.test')->firstOrFail();

            $pendingState   = OrderState::where('state', 'pending')->firstOrFail();
            $confirmedState = OrderState::where('state', 'confirmed')->firstOrFail();

            $book1 = Book::findOrFail('978-1111111111');
            $book2 = Book::findOrFail('978-2222222222');
            $book3 = Book::findOrFail('978-3333333333');

            $pendingOrder = Order::create([
                'user_id'      => $member1->id,
                'state_id'     => $pendingState->id,
                'total_prices' => ($book1->price * 2) + ($book2->price * 1),
                'total_amount' => 3,
            ]);

            OrderItem::create([
                'order_id'   => $pendingOrder->id,
                'book_ISBN'  => $book1->ISBN,
                'price_once' => $book1->price,
                'count'      => 2,
            ]);

            OrderItem::create([
                'order_id'   => $pendingOrder->id,
                'book_ISBN'  => $book2->ISBN,
                'price_once' => $book2->price,
                'count'      => 1,
            ]);

            $confirmedOrder = Order::create([
                'user_id'      => $member2->id,
                'state_id'     => $confirmedState->id,
                'total_prices' => $book3->price * 2,
                'total_amount' => 2,
            ]);

            OrderItem::create([
                'order_id'   => $confirmedOrder->id,
                'book_ISBN'  => $book3->ISBN,
                'price_once' => $book3->price,
                'count'      => 2,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

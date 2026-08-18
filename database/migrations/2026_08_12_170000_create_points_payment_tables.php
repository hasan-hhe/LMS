<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', fn (Blueprint $table) => $table->unsignedInteger('price_points')->default(0)->after('price'));
        DB::table('books')->where('price', '>', 0)->orderBy('ISBN')->each(fn ($book) => DB::table('books')->where('ISBN', $book->ISBN)->update(['price_points' => max(1, (int) ceil($book->price / 100))])
        );

        Schema::create('user_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('balance')->default(0);
            $table->timestamps();
        });

        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('points');
            $table->enum('type', ['top_up', 'spend', 'reward', 'adjust']);
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('top_up_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedInteger('points_value');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_used')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('point_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
        });

        Schema::table('late_fines', fn (Blueprint $table) => $table->unsignedInteger('fine_points')->default(0)->after('fine'));
        DB::table('late_fines')->where('fine', '>', 0)->orderBy('id')->each(fn ($fine) => DB::table('late_fines')->where('id', $fine->id)->update(['fine_points' => max(1, (int) ceil($fine->fine / 100))])
        );

        Schema::table('orders', fn (Blueprint $table) => $table->unsignedInteger('total_points')->default(0)->after('total_prices'));
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('total_points'));
        Schema::table('late_fines', fn (Blueprint $table) => $table->dropColumn('fine_points'));
        Schema::dropIfExists('point_settings');
        Schema::dropIfExists('top_up_codes');
        Schema::dropIfExists('point_transactions');
        Schema::dropIfExists('user_points');
        Schema::table('books', fn (Blueprint $table) => $table->dropColumn('price_points'));
    }
};

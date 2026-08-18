<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('book_ISBN');
            $table->foreign('book_ISBN')->references('ISBN')->on('books')->cascadeOnDelete();
            $table->unique(['user_id', 'book_ISBN']);
            $table->timestamps();
        });

        Schema::create('digital_assets', function (Blueprint $table) {
            $table->id();
            $table->string('book_ISBN')->unique();
            $table->foreign('book_ISBN')->references('ISBN')->on('books')->cascadeOnDelete();
            $table->string('pdf_url')->nullable();
            $table->string('audio_url')->nullable();
            $table->boolean('is_free')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_assets');
        Schema::dropIfExists('favorites');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id('ISBN');
            $table->foreignId('auther_id')->constrained();
            $table->foreignId('catagory_id')->constrained();
            $table->foreignId('publisher_id')->constrained();
            $table->string('title');
            $table->string('discription');
            $table->double('price');
            $table->integer('amount');
            $table->double('rate_avg');
            $table->string('cover_url');
            $table->string('year_of_publishing');
            $table->string('number_edition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

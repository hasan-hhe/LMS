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
        Schema::create('book_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_ISBN')->constrained('books', 'ISBN')->onDelete('cascade');
            $table->foreignId('state_id')->constrained('instance_states');
            $table->enum('condition', ['new', 'worn', 'almost_new']);
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

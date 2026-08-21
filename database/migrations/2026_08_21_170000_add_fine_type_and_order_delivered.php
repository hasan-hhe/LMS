<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('late_fines', function (Blueprint $table) {
            $table->string('type')->default('late')->after('borrowing_id');
        });

        if (! DB::table('order_states')->where('state', 'delivered')->exists()) {
            DB::table('order_states')->insert(['state' => 'delivered']);
        }
    }

    public function down(): void
    {
        Schema::table('late_fines', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};

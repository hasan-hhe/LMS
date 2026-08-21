<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('late_fines', function (Blueprint $table) {
            if (! Schema::hasColumn('late_fines', 'accrued_until')) {
                $table->date('accrued_until')->nullable()->after('fine_points');
            }
            if (! Schema::hasColumn('late_fines', 'paid_via')) {
                $table->string('paid_via')->nullable()->after('paid_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('late_fines', function (Blueprint $table) {
            $table->dropColumn(['accrued_until', 'paid_via']);
        });
    }
};

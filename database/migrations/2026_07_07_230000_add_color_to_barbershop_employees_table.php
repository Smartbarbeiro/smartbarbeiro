<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private const DEFAULT_COLORS = [
        '#3B82F6',
        '#10B981',
        '#F59E0B',
        '#8B5CF6',
        '#EF4444',
        '#06B6D4',
        '#EC4899',
        '#84CC16',
    ];

    public function up(): void
    {
        Schema::table('barbershop_employees', function (Blueprint $table) {
            $table->string('color', 7)->default('#3B82F6')->after('commission_percent');
        });

        $employees = DB::table('barbershop_employees')->orderBy('sort_order')->orderBy('id')->get();

        foreach ($employees as $index => $employee) {
            DB::table('barbershop_employees')
                ->where('id', $employee->id)
                ->update([
                    'color' => self::DEFAULT_COLORS[$index % count(self::DEFAULT_COLORS)],
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('barbershop_employees', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};

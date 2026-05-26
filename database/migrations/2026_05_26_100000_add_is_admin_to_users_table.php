<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
        });

        $ownerEmails = collect(config('admin.owner_emails'))
            ->filter()
            ->map(fn (string $email) => strtolower($email))
            ->all();

        if ($ownerEmails !== []) {
            DB::table('users')
                ->whereIn(DB::raw('LOWER(email)'), $ownerEmails)
                ->update(['is_admin' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};

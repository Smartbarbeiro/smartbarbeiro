<?php

use App\Models\User;
use App\Services\UsernameGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        $generator = app(UsernameGenerator::class);

        foreach (User::query()->whereNull('username')->cursor() as $user) {
            $user->forceFill([
                'username' => $generator->uniqueFrom($user->name),
            ])->saveQuietly();
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};

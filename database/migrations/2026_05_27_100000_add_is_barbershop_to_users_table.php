<?php

use App\Models\ProfileSubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_barbershop')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_barbershop')->default(false)->after('name');
            });
        }

        $barbershopUserIds = ProfileSubscriptionPlan::query()
            ->pluck('user_id')
            ->merge(
                DB::table('profile_subscriptions')
                    ->distinct()
                    ->pluck('creator_user_id')
            )
            ->merge(
                DB::table('barbershop_memberships')
                    ->distinct()
                    ->pluck('barbershop_user_id')
            )
            ->merge(
                User::query()->where('is_admin', true)->pluck('id')
            )
            ->unique()
            ->values();

        User::query()->whereIn('id', $barbershopUserIds)->update(['is_barbershop' => true]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->change();
        });

        User::query()
            ->where('is_barbershop', false)
            ->update(['username' => null]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_barbershop');
        });
    }
};

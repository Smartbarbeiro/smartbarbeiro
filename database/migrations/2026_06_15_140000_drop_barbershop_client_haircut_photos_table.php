<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('barbershop_client_haircut_photos');
    }

    public function down(): void
    {
        // Feature removed — table is not recreated on rollback.
    }
};

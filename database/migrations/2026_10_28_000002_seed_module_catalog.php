<?php

use App\Services\ModuleCatalog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(ModuleCatalog::class)->sync();
    }

    public function down(): void {}
};

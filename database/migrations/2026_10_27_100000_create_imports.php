<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_imports', function (Blueprint $t) {
            $t->id();
            $t->ulid('public_id')->unique();
            $t->foreignId('business_id')->constrained();
            $t->foreignId('branch_id')->nullable()->constrained();
            $t->string('path');
            $t->string('status')->default('queued');
            $t->unsignedInteger('imported')->default(0);
            $t->unsignedInteger('skipped')->default(0);
            $t->json('errors')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_imports');
    }
};

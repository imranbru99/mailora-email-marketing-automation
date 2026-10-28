<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_checks', function (Blueprint $t) {
            $t->id();
            $t->ulid('public_id')->unique();
            $t->foreignId('business_id')->constrained();
            $t->foreignId('branch_id')->nullable()->constrained();
            $t->foreignId('contact_id')->constrained()->unique();
            $t->string('email');
            $t->string('status');
            $t->json('findings');
            $t->timestamps();
            $t->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_checks');
    }
};

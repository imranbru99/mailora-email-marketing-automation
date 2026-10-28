<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_exports', function (Blueprint $t) {
            $t->id();
            $t->ulid('public_id')->unique();
            $t->foreignId('business_id')->constrained();
            $t->foreignId('branch_id')->nullable()->constrained();
            $t->foreignId('user_id')->constrained();
            $t->string('type');
            $t->unsignedBigInteger('record_id')->nullable();
            $t->string('status')->default('queued');
            $t->string('path')->nullable();
            $t->string('error')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
            $t->index(['business_id', 'branch_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_exports');
    }
};

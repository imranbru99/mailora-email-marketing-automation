<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_comments', function (Blueprint $t) {
            $t->id();
            $t->ulid('public_id')->unique();
            $t->foreignId('business_id')->constrained();
            $t->foreignId('branch_id')->nullable()->constrained();
            $t->foreignId('campaign_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->text('body');
            $t->timestamps();
            $t->index(['business_id', 'campaign_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_comments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drutogoti_suggestions', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // query/cache/cookie/image/config
            $table->string('severity'); // critical/warning/info
            $table->string('title');
            $table->text('description');
            $table->text('fix_snippet')->nullable();
            $table->string('related_request_id')->nullable()->index();
            $table->boolean('is_resolved')->default(false);
            $table->boolean('is_dismissed')->default(false);
            $table->integer('occurrences_count')->default(1);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drutogoti_suggestions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drutogoti_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_id')->unique();
            $table->string('method')->nullable();
            $table->string('path')->nullable();
            $table->integer('status_code')->nullable();
            $table->float('total_duration_ms')->nullable();
            $table->float('memory_peak_mb')->nullable();
            $table->integer('query_count')->default(0);
            $table->integer('cache_hit_count')->default(0);
            $table->integer('cache_miss_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drutogoti_requests');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drutogoti_cache_events', function (Blueprint $table) {
            $table->id();
            $table->string('store');
            $table->string('key');
            $table->string('event'); // hit/miss/write/forget
            $table->integer('ttl_seconds')->nullable();
            $table->integer('size_bytes')->nullable();
            $table->string('request_id')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drutogoti_cache_events');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drutogoti_cookie_events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('size_bytes');
            $table->boolean('is_encrypted')->default(false);
            $table->boolean('is_http_only')->default(false);
            $table->boolean('is_secure')->default(false);
            $table->string('same_site')->nullable();
            $table->string('request_id')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drutogoti_cookie_events');
    }
};

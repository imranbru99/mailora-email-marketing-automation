<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drutogoti_query_logs', function (Blueprint $table) {
            $table->id();
            $table->text('sql');
            $table->json('bindings')->nullable();
            $table->float('duration_ms');
            $table->string('connection');
            $table->string('route')->nullable();
            $table->string('controller_action')->nullable();
            $table->string('request_id')->index();
            $table->boolean('is_duplicate')->default(false);
            $table->string('n_plus_one_group_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drutogoti_query_logs');
    }
};

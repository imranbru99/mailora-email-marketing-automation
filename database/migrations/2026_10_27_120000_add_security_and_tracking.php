<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->text('two_factor_secret')->nullable();
            $t->json('recovery_codes')->nullable();
            $t->unsignedBigInteger('last_totp_step')->nullable();
        });
        Schema::table('contacts', function (Blueprint $t) {
            $t->timestamp('paused_until')->nullable();
            $t->string('frequency')->default('weekly');
        });
        Schema::table('message_logs', function (Blueprint $t) {
            $t->longText('rendered_text')->nullable();
            $t->json('headers')->nullable();
        });
        Schema::table('campaigns', function (Blueprint $t) {
            $t->foreignId('automation_run_id')->nullable()->constrained();
        });
        Schema::create('tracking_links', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained();
            $t->foreignId('message_log_id')->constrained()->cascadeOnDelete();
            $t->string('token', 64)->unique();
            $t->text('destination');
            $t->timestamps();
        });
        Schema::create('business_roles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained();
            $t->string('name');
            $t->json('permissions');
            $t->timestamps();
            $t->unique(['business_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_roles');
        Schema::dropIfExists('tracking_links');
        Schema::table('campaigns', fn (Blueprint $t) => $t->dropConstrainedForeignId('automation_run_id'));
        Schema::table('message_logs', fn (Blueprint $t) => $t->dropColumn(['rendered_text', 'headers']));
        Schema::table('contacts', fn (Blueprint $t) => $t->dropColumn(['paused_until', 'frequency']));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['two_factor_secret', 'recovery_codes', 'last_totp_step']));
    }
};

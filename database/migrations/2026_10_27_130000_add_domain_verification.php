<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sending_identities', function (Blueprint $t) {
            $t->string('verification_token', 64)->nullable();
            $t->timestamp('dns_verified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sending_identities', fn (Blueprint $t) => $t->dropColumn(['verification_token', 'dns_verified_at']));
    }
};

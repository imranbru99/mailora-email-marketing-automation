<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_endpoints', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('secret');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('feedback_events', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('feedback_endpoint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_log_id')->constrained()->cascadeOnDelete();
            $table->string('external_event_id', 120);
            $table->string('type', 30);
            $table->string('status')->default('pending');
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['feedback_endpoint_id', 'external_event_id'], 'feedback_endpoint_event_unique');
            $table->index(['business_id', 'status', 'created_at']);
        });
        Schema::table('message_logs', function (Blueprint $table) {
            $table->string('recipient_email', 254)->nullable();
            $table->string('delivery_status', 30)->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('complained_at')->nullable();
        });
        Schema::table('campaigns', function (Blueprint $table) {
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('complaint_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_events');
        Schema::dropIfExists('feedback_endpoints');
        Schema::table('message_logs', fn (Blueprint $table) => $table->dropColumn(['recipient_email', 'delivery_status', 'delivered_at', 'bounced_at', 'complained_at']));
        Schema::table('campaigns', fn (Blueprint $table) => $table->dropColumn(['delivered_count', 'complaint_count']));
    }
};

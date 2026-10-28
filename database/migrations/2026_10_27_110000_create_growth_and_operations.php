<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('slug')->nullable();
            $t->unique(['business_id', 'slug']);
            $t->string('headline')->nullable();
            $t->longText('body')->nullable();
            $t->string('button_label')->nullable();
            $t->foreignId('signup_form_id')->nullable()->constrained('signup_forms');
            $t->boolean('published')->default(false);
            $t->text('seo_description')->nullable();
        });
        Schema::create('surveys', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('question')->nullable();
            $t->json('options')->nullable();
            $t->boolean('published')->default(false);
        });
        Schema::create('webinars', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->text('description')->nullable();
            $t->dateTime('starts_at')->nullable();
            $t->dateTime('ends_at')->nullable();
            $t->string('location')->nullable();
            $t->unsignedInteger('capacity')->default(0);
            $t->boolean('published')->default(false);
        });
        Schema::create('coupons', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('code')->nullable();
            $t->unique(['business_id', 'code']);
            $t->unsignedInteger('discount_percent')->default(0);
            $t->dateTime('expires_at')->nullable();
            $t->unsignedInteger('max_uses')->default(0);
            $t->unsignedInteger('uses')->default(0);
        });
        Schema::create('short_links', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('slug')->nullable();
            $t->unique(['business_id', 'slug']);
            $t->string('destination')->nullable();
            $t->unsignedInteger('clicks')->default(0);
            $t->boolean('active')->default(false);
        });
        Schema::create('snippets', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->longText('html')->nullable();
        });
        Schema::create('tickets', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('requester_email')->nullable();
            $t->text('description')->nullable();
            $t->string('status')->nullable();
            $t->string('priority')->nullable();
            $t->text('response')->nullable();
        });
        Schema::create('articles', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('slug')->nullable();
            $t->unique(['business_id', 'slug']);
            $t->string('category')->nullable();
            $t->longText('body')->nullable();
            $t->boolean('published')->default(false);
        });
        Schema::create('invoices', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('customer_name')->nullable();
            $t->string('customer_email')->nullable();
            $t->json('items')->nullable();
            $t->string('currency')->nullable();
            $t->unsignedInteger('tax_percent')->default(0);
            $t->string('status')->nullable();
            $t->dateTime('due_at')->nullable();
        });
        Schema::create('custom_fields', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('key')->nullable();
            $t->unique(['business_id', 'key']);
            $t->string('type')->nullable();
            $t->json('options')->nullable();
            $t->boolean('required')->default(false);
        });
        Schema::create('tags', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('color')->nullable();
        });
        Schema::create('attendance_entries', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('employee_code')->nullable();
            $t->dateTime('punched_at')->nullable();
            $t->string('direction')->nullable();
            $t->string('source')->nullable();
        });
        Schema::create('time_entries', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('project')->nullable();
            $t->unsignedInteger('minutes')->default(0);
            $t->date('worked_on')->nullable();
        });
        Schema::create('survey_responses', function (Blueprint $t) {
            $this->tenant($t);
            $t->foreignId('survey_id')->constrained();
            $t->string('answer');
            $t->string('visitor_hash', 64);
            $t->unique(['survey_id', 'visitor_hash']);
        });
        Schema::create('webinar_registrations', function (Blueprint $t) {
            $this->tenant($t);
            $t->foreignId('webinar_id')->constrained();
            $t->string('email');
            $t->string('name');
            $t->unique(['webinar_id', 'email']);
        });
        Schema::create('coupon_redemptions', function (Blueprint $t) {
            $this->tenant($t);
            $t->foreignId('coupon_id')->constrained();
            $t->foreignId('contact_id')->constrained();
            $t->unique(['coupon_id', 'contact_id']);
        });
    }

    private function tenant(Blueprint $t): void
    {
        $t->id();
        $t->ulid('public_id')->unique();
        $t->foreignId('business_id')->constrained()->cascadeOnDelete();
        $t->foreignId('branch_id')->nullable()->constrained();
        $t->timestamps();
        $t->softDeletes();
    }

    public function down(): void
    {
        foreach (['coupon_redemptions', 'webinar_registrations', 'survey_responses', 'time_entries', 'attendance_entries', 'tags', 'custom_fields', 'invoices', 'articles', 'tickets', 'snippets', 'short_links', 'coupons', 'webinars', 'surveys', 'landing_pages'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

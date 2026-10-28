<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $t) {
            $t->id();
            $t->ulid('public_id')->unique();
            $t->string('name');
            $t->string('preset');
            $t->json('settings')->nullable();
            $t->unsignedInteger('monthly_email_limit')->default(100000);
            $t->timestamps();
        });
        Schema::create('branches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->json('settings')->nullable();
            $t->timestamps();
        });
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('business_id')->nullable()->constrained();
            $t->foreignId('branch_id')->nullable()->constrained();
            $t->string('role')->default('Analyst');
            $t->string('locale')->default('en');
        });
        Schema::create('modules', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->string('name');
            $t->text('description');
            $t->string('category');
            $t->json('depends_on');
            $t->boolean('is_core')->default(false);
            $t->boolean('default_enabled')->default(false);
            $t->timestamps();
        });
        Schema::create('business_modules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->string('module_key');
            $t->boolean('enabled');
            $t->json('config')->nullable();
            $t->timestamp('enabled_at')->nullable();
            $t->foreignId('enabled_by')->nullable()->constrained('users');
            $t->unique(['business_id', 'module_key']);
            $t->timestamps();
        });
        Schema::create('branch_modules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $t->string('module_key');
            $t->boolean('enabled');
            $t->json('config')->nullable();
            $t->unique(['branch_id', 'module_key']);
            $t->timestamps();
        });
        Schema::create('contacts', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('email');
            $t->string('first_name');
            $t->string('last_name')->nullable();
            $t->string('phone')->nullable();
            $t->string('company')->nullable();
            $t->string('status')->default('subscribed');
            $t->string('locale')->default('en');
            $t->string('timezone')->default('UTC');
            $t->string('source')->default('manual');
            $t->integer('score')->default(0);
            $t->json('tags')->nullable();
            $t->json('custom_fields')->nullable();
            $t->text('notes')->nullable();
            $t->timestamp('consented_at')->nullable();
            $t->string('legal_basis')->nullable();
            $t->string('unsubscribe_token', 64)->unique();
            $t->softDeletes();
            $t->unique(['business_id', 'email']);
            $t->index(['business_id', 'branch_id', 'status']);
        });
        Schema::create('audience_lists', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->text('description')->nullable();
            $t->boolean('double_opt_in')->default(true);
            $t->softDeletes();
        });
        Schema::create('audience_list_contact', function (Blueprint $t) {
            $t->foreignId('audience_list_id')->constrained()->cascadeOnDelete();
            $t->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $t->primary(['audience_list_id', 'contact_id']);
        });
        Schema::create('segments', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->json('conditions');
            $t->softDeletes();
        });
        Schema::create('suppressions', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('email');
            $t->string('reason');
            $t->unique(['business_id', 'email']);
        });
        Schema::create('consent_records', function (Blueprint $t) {
            $this->tenant($t);
            $t->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $t->string('action');
            $t->string('source');
            $t->text('proof')->nullable();
        });
        Schema::create('templates', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('category');
            $t->string('subject');
            $t->longText('html');
            $t->json('blocks')->nullable();
            $t->softDeletes();
        });
        Schema::create('sending_identities', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('email');
            $t->string('domain');
            $t->timestamp('verified_at')->nullable();
            $t->text('postal_address');
        });
        Schema::create('campaigns', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('subject');
            $t->string('preheader')->nullable();
            $t->longText('html');
            $t->string('status')->default('draft');
            $t->foreignId('sending_identity_id')->nullable()->constrained();
            $t->foreignId('audience_list_id')->nullable()->constrained();
            $t->foreignId('segment_id')->nullable()->constrained();
            $t->boolean('tracking')->default(true);
            $t->timestamp('scheduled_at')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('users');
            $t->timestamp('sent_at')->nullable();
            $t->unsignedInteger('sent_count')->default(0);
            $t->unsignedInteger('open_count')->default(0);
            $t->unsignedInteger('click_count')->default(0);
            $t->unsignedInteger('bounce_count')->default(0);
            $t->softDeletes();
            $t->index(['business_id', 'status', 'scheduled_at']);
        });
        Schema::create('message_logs', function (Blueprint $t) {
            $this->tenant($t);
            $t->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $t->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $t->string('status')->default('pending');
            $t->string('reason')->nullable();
            $t->longText('rendered_html')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('opened_at')->nullable();
            $t->timestamp('clicked_at')->nullable();
            $t->unique(['campaign_id', 'contact_id']);
            $t->index(['business_id', 'created_at']);
        });
        Schema::create('email_events', function (Blueprint $t) {
            $this->tenant($t);
            $t->foreignId('message_log_id')->constrained()->cascadeOnDelete();
            $t->string('type');
            $t->boolean('is_bot')->default(false);
            $t->index(['message_log_id', 'type', 'created_at']);
        });
        Schema::create('automations', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('trigger')->default('contact.created');
            $t->string('status')->default('draft');
            $t->unsignedInteger('version')->default(1);
            $t->json('steps');
            $t->softDeletes();
        });
        Schema::create('automation_runs', function (Blueprint $t) {
            $this->tenant($t);
            $t->foreignId('automation_id')->constrained()->cascadeOnDelete();
            $t->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('version');
            $t->json('steps');
            $t->unsignedInteger('position')->default(0);
            $t->string('status')->default('running');
            $t->timestamp('next_run_at')->nullable();
            $t->json('history')->nullable();
            $t->unique(['automation_id', 'contact_id', 'version']);
        });
        Schema::create('signup_forms', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('headline');
            $t->text('description')->nullable();
            $t->string('button_label')->default('Subscribe');
            $t->boolean('published')->default(false);
            $t->boolean('double_opt_in')->default(true);
            $t->foreignId('audience_list_id')->nullable()->constrained();
            $t->softDeletes();
        });
        Schema::create('products', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->string('sku');
            $t->text('description')->nullable();
            $t->unsignedInteger('price_minor');
            $t->string('currency', 3)->default('USD');
            $t->unsignedInteger('stock')->default(0);
            $t->softDeletes();
            $t->unique(['business_id', 'sku']);
        });
        Schema::create('orders', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('number');
            $t->foreignId('contact_id')->constrained();
            $t->foreignId('campaign_id')->nullable()->constrained();
            $t->unsignedInteger('total_minor');
            $t->string('currency', 3)->default('USD');
            $t->string('status')->default('paid');
            $t->unique(['business_id', 'number']);
        });
        Schema::create('deals', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->foreignId('contact_id')->nullable()->constrained();
            $t->string('stage')->default('new');
            $t->unsignedInteger('value_minor')->default(0);
            $t->string('currency', 3)->default('USD');
            $t->date('expected_close')->nullable();
            $t->text('notes')->nullable();
            $t->softDeletes();
        });
        Schema::create('tasks', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('status')->default('todo');
            $t->string('priority')->default('normal');
            $t->timestamp('due_at')->nullable();
            $t->softDeletes();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->nullable()->constrained();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('action');
            $t->string('subject_type')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->json('metadata')->nullable();
            $t->timestamps();
        });
        Schema::create('usage_counters', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained();
            $t->string('period');
            $t->unsignedInteger('emails')->default(0);
            $t->unsignedInteger('ai_tokens')->default(0);
            $t->unsignedInteger('ai_cost_minor')->default(0);
            $t->unique(['business_id', 'period']);
            $t->timestamps();
        });
        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->unsignedInteger('price_minor');
            $t->string('currency', 3)->default('USD');
            $t->unsignedInteger('contacts_limit');
            $t->unsignedInteger('emails_limit');
            $t->json('features');
            $t->timestamps();
        });
        Schema::create('ai_generations', function (Blueprint $t) {
            $this->tenant($t);
            $t->string('type');
            $t->text('prompt');
            $t->longText('result')->nullable();
            $t->string('status')->default('queued');
        });
    }

    private function tenant(Blueprint $t): void
    {
        $t->id();
        $t->ulid('public_id')->unique();
        $t->foreignId('business_id')->constrained()->cascadeOnDelete();
        $t->foreignId('branch_id')->nullable()->constrained();
        $t->timestamps();
    }

    public function down(): void
    {
        foreach (['ai_generations', 'plans', 'usage_counters', 'audit_logs', 'tasks', 'deals', 'orders', 'products', 'signup_forms', 'automation_runs', 'automations', 'email_events', 'message_logs', 'campaigns', 'sending_identities', 'templates', 'consent_records', 'suppressions', 'segments', 'audience_list_contact', 'audience_lists', 'contacts', 'branch_modules', 'business_modules', 'modules'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('branch_id');
            $t->dropConstrainedForeignId('business_id');
            $t->dropColumn(['role', 'locale']);
        });
        Schema::dropIfExists('branches');
        Schema::dropIfExists('businesses');
    }
};

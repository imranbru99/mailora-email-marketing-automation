<?php

namespace Database\Seeders;

use App\Contracts\Slugger;
use App\Models\Article;
use App\Models\AttendanceEntry;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Coupon;
use App\Models\CustomField;
use App\Models\Invoice;
use App\Models\LandingPage;
use App\Models\ShortLink;
use App\Models\SignupForm;
use App\Models\Snippet;
use App\Models\Survey;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\Webinar;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;

class GrowthSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \RuntimeException('Demo seeders are unavailable in production.');
        }
        foreach (Business::all() as $business) {
            app(TenantContext::class)->run($business->id, null, function () use ($business) {
                if (LandingPage::exists()) {
                    return;
                }
                $branch = Branch::where('business_id', $business->id)->firstOrFail();
                for ($i = 0; $i < 3; $i++) {
                    LandingPage::create(['branch_id' => $branch->id, 'name' => ['Community newsletter', 'Autumn collection', 'The next chapter'][$i], 'slug' => app(Slugger::class)->slug(['Community newsletter', 'Autumn collection', 'The next chapter'][$i]), 'headline' => ['A little inspiration goes a long way.', 'Discover your everyday favorites.', 'Make room for something new.'][$i], 'body' => '<p>Join our community for stories, ideas, and thoughtful updates.</p>', 'button_label' => 'Stay connected', 'signup_form_id' => SignupForm::where('branch_id', $branch->id)->value('id'), 'published' => true]);
                }
                for ($i = 0; $i < 10; $i++) {
                    Survey::create(['name' => 'Community pulse '.($i + 1), 'question' => ['How helpful was our latest newsletter?', 'What would you like to hear more about?'][$i % 2], 'options' => $i % 2 ? ['Product updates', 'Customer stories', 'Practical guides'] : ['Very helpful', 'Somewhat helpful', 'Not helpful'], 'published' => true]);
                }
                Webinar::create(['name' => 'Better connections: a community workshop', 'description' => 'A practical conversation about building a thoughtful audience.', 'starts_at' => now()->addDays(10), 'ends_at' => now()->addDays(10)->addHour(), 'location' => 'Online workshop', 'capacity' => 100, 'published' => true]);
                Coupon::create(['name' => 'Welcome offer', 'code' => 'WELCOME15', 'discount_percent' => 15, 'expires_at' => now()->addDays(90), 'max_uses' => 1000]);
                ShortLink::create(['name' => 'Developer website', 'slug' => 'learn-more', 'destination' => 'https://imrandev.bd', 'active' => true]);
                foreach (['Welcome to your workspace', 'Create your first campaign', 'Understand subscriber consent'] as $name) {
                    Article::create(['name' => $name, 'slug' => app(Slugger::class)->slug($name), 'category' => 'Getting started', 'body' => '<p>Choose your branch, confirm your workspace settings, and review the relevant module. Save your changes before starting a new workflow.</p><p>Campaigns require a verified sender, approval, an unsubscribe link, and a consented audience. Sandbox deliveries are saved in message logs.</p>', 'published' => true]);
                }
                Invoice::create(['name' => 'INV-2026-001', 'customer_name' => 'Greenleaf Company', 'customer_email' => 'accounts@greenleaf.example.test', 'items' => [['description' => 'Campaign strategy and design', 'quantity' => 1, 'unit_price_minor' => 45000], ['description' => 'Monthly reporting', 'quantity' => 1, 'unit_price_minor' => 10000]], 'currency' => 'USD', 'tax_percent' => 0, 'status' => 'issued', 'due_at' => now()->addDays(30)]);
                Snippet::create(['name' => 'Friendly sign-off', 'html' => '<p>Thanks for being here,<br>The team</p>']);
                Ticket::create(['name' => 'Help preparing a new campaign', 'requester_email' => 'customer@example.test', 'description' => 'Please help us review our first newsletter.', 'status' => 'open', 'priority' => 'normal']);
                CustomField::create(['name' => 'Favorite category', 'key' => 'favorite_category', 'type' => 'select', 'options' => ['Design', 'Technology', 'Community']]);
                foreach (['VIP', 'Newsletter', 'New customer', 'Event attendee'] as $tag) {
                    Tag::create(['name' => $tag, 'color' => '#635bff']);
                }
                AttendanceEntry::create(['name' => 'Marketing team check-in', 'employee_code' => 'EMP-001', 'punched_at' => now()->startOfDay()->addHours(9), 'direction' => 'in', 'source' => 'demo']);
                TimeEntry::create(['name' => 'Autumn campaign planning', 'project' => 'Autumn launch', 'minutes' => 90, 'worked_on' => now()]);
            });
        }
    }
}

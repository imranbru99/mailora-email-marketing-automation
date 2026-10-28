<?php

namespace Database\Seeders;

use App\Models\AudienceList;
use App\Models\Automation;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Segment;
use App\Models\SendingIdentity;
use App\Models\SignupForm;
use App\Models\Task;
use App\Models\Template;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \RuntimeException('Demo seeding is disabled in production.');
        }
        foreach (config('modules') as $m) {
            Module::updateOrCreate(['key' => $m['key']], array_intersect_key($m, array_flip(['name', 'description', 'category', 'depends_on', 'is_core', 'default_enabled'])));
        }
        foreach (['Free', 'Starter', 'Growth', 'Pro', 'Enterprise'] as $i => $name) {
            Plan::firstOrCreate(['name' => $name], ['price_minor' => [0, 1900, 4900, 9900, 29900][$i], 'contacts_limit' => [500, 2500, 10000, 50000, 1000000][$i], 'emails_limit' => [1000, 10000, 50000, 250000, 1000000][$i], 'features' => ['Branch-aware workspace', 'Audience and campaign tools', 'Consent and suppression controls']]);
        }
        $businesses = [['Acme Retail Ltd', 'E-commerce store', ['Head Office', 'Dhaka Store', 'Online Store']], ['BrightPath SaaS', 'SaaS / Software company', ['Head Office', 'Customer Success']], ['PixelCraft Agency', 'Marketing agency', ['Studio North', 'Greenleaf Co.', 'Atlas Travel', 'Bloom & Co.']]];
        foreach ($businesses as $bi => [$name,$preset,$branches]) {
            $business = Business::firstOrCreate(['name' => $name], ['preset' => $preset, 'settings' => ['timezone' => 'Asia/Dhaka', 'locale' => 'en', 'currency' => 'USD', 'postal_address' => 'House 12, Road 5, Dhaka 1207, Bangladesh']]);
            $branchIds = [];
            foreach ($branches as $bn) {
                $branchIds[] = Branch::firstOrCreate(['business_id' => $business->id, 'name' => $bn], ['settings' => []])->id;
            }
            app(TenantContext::class)->run($business->id, null, function () use ($business, $bi, $branchIds) {
                foreach (Module::all() as $module) {
                    DB::table('business_modules')->updateOrInsert(['business_id' => $business->id, 'module_key' => $module->key], ['enabled' => $module->default_enabled, 'created_at' => now(), 'updated_at' => now()]);
                }
                if (Contact::exists()) {
                    return;
                }
                $count = (int) ceil(config('platform.contact_seed_count') / 3);
                $names = [['Ayesha', 'Rahman'], ['Oliver', 'Chen'], ['সাদিয়া', 'আহমেদ'], ['محمد', 'علي'], ['Emma', 'Wilson'], ['Arjun', 'Patel'], ['Nadia', 'Islam'], ['Lucas', 'Martin'], ['Sofia', 'Garcia'], ['Yuki', 'Tanaka']];
                for ($start = 0; $start < $count; $start += 500) {
                    $rows = [];
                    for ($i = $start; $i < min($start + 500, $count); $i++) {
                        $n = $names[$i % count($names)];
                        $rows[] = ['public_id' => (string) Str::ulid(), 'business_id' => $business->id, 'branch_id' => $branchIds[$i % count($branchIds)], 'email' => 'contact'.($i + 1).'@business'.($bi + 1).'.example.test', 'first_name' => $n[0], 'last_name' => $n[1], 'company' => ['Northstar', 'Greenleaf', 'Atlas', 'Bloom'][$i % 4], 'status' => $i % 31 === 0 ? 'unsubscribed' : 'subscribed', 'locale' => ['en', 'en', 'bn', 'ar'][$i % 4], 'timezone' => 'UTC', 'source' => ['signup form', 'import', 'checkout'][$i % 3], 'score' => $i % 101, 'tags' => json_encode([$i % 3 === 0 ? 'VIP' : 'Newsletter']), 'custom_fields' => '{}', 'consented_at' => now()->subDays(90), 'legal_basis' => 'consent', 'unsubscribe_token' => bin2hex(random_bytes(32)), 'created_at' => now()->subDays($i % 365), 'updated_at' => now()];
                    }DB::table('contacts')->insert($rows);
                }
                for ($i = 0; $i < 14; $i++) {
                    AudienceList::create(['name' => ['Newsletter', 'VIP customers', 'New subscribers', 'Product updates', 'Seasonal offers', 'Community', 'Events'][$i % 7].' '.($i + 1), 'description' => 'Demo audience group', 'branch_id' => $branchIds[$i % count($branchIds)]]);
                }
                $lists = AudienceList::pluck('id')->all();
                $contacts = Contact::select('id', 'branch_id')->get();
                $membership = [];
                foreach ($contacts as $i => $c) {
                    $membership[] = ['audience_list_id' => $lists[$i % count($lists)], 'contact_id' => $c->id];
                    if (count($membership) === 500) {
                        DB::table('audience_list_contact')->insert($membership);
                        $membership = [];
                    }
                }if ($membership) {
                    DB::table('audience_list_contact')->insert($membership);
                }
                foreach (['Highly engaged', 'English audience', 'New customers'] as $si => $sn) {
                    Segment::create(['name' => $sn, 'conditions' => ['match' => 'all', 'rules' => [['field' => ['score', 'locale', 'source'][$si], 'operator' => ['>', '=', 'contains'][$si], 'value' => ['70', 'en', 'form'][$si]]]]]);
                }
                $subjects = ['A warm welcome to your next chapter', 'Your weekly dose of inspiration', 'Something special, just for you', 'Meet your next favorite', 'You’re invited. Save your spot.', 'Let’s pick up where we left off'];
                $types = ['Welcome', 'Newsletter', 'Promotion', 'Commerce', 'Event', 'Retention'];
                for ($i = 0; $i < 24; $i++) {
                    $subject = $subjects[$i % 6];
                    Template::create(['name' => $types[$i % 6].' · '.['Modern', 'Minimal', 'Editorial', 'Classic'][intdiv($i, 6)], 'category' => $types[$i % 6], 'subject' => $subject, 'html' => '<h1>'.e($subject).'</h1><p>Hello {{first_name|friend}},</p><p>Thanks for being part of our community. Discover fresh ideas, thoughtful updates, and the stories we think you’ll love.</p><p><a href="https://imrandev.bd">Discover what’s new</a></p><p><a href="{{unsubscribe_url}}">Unsubscribe</a></p>']);
                }
                foreach ($branchIds as $branch) {
                    SendingIdentity::create(['branch_id' => $branch, 'name' => $business->name, 'email' => 'hello@business'.($bi + 1).'.example.test', 'domain' => 'business'.($bi + 1).'.example.test', 'verified_at' => now(), 'postal_address' => $business->settings['postal_address']]);
                }
                $ids = SendingIdentity::pluck('id', 'branch_id');
                $templates = Template::all();
                for ($i = 0; $i < 40; $i++) {
                    $branch = $branchIds[$i % count($branchIds)];
                    $t = $templates[$i % 24];
                    $status = ['sent', 'sent', 'sent', 'draft', 'scheduled', 'paused', 'pending approval', 'cancelled', 'failed', 'sending'][$i % 10];
                    $sent = $status === 'sent' ? 1500 + $i * 53 : 0;
                    Campaign::create(['branch_id' => $branch, 'name' => ['October inspiration', 'Fresh arrivals', 'Community notes', 'A little thank you', 'Your next chapter', 'Autumn essentials', 'The weekly edit', 'Member favorites'][$i % 8].' #'.($i + 1), 'subject' => $t->subject, 'html' => $t->html, 'sending_identity_id' => $ids[$branch], 'status' => $status, 'scheduled_at' => $status === 'scheduled' ? now()->addDays($i + 1) : null, 'approved_at' => $status === 'sent' ? now()->subDays($i) : null, 'sent_count' => $sent, 'open_count' => (int) ($sent * (0.25 + ($i % 10) / 100)), 'click_count' => (int) ($sent * .06), 'bounce_count' => (int) ($sent * .005), 'created_at' => now()->subDays($i * 9), 'sent_at' => $status === 'sent' ? now()->subDays($i * 9) : null]);
                }
                foreach (['Welcome series', 'VIP nurture', 'New subscriber follow-up', 'Customer onboarding', 'Re-engagement', 'Birthday reminder', 'Event follow-up', 'Renewal reminder', 'Team handoff'] as $i => $flow) {
                    Automation::create(['branch_id' => $branchIds[$i % count($branchIds)], 'name' => $flow, 'trigger' => 'form.submitted', 'status' => $i < 3 ? 'live' : 'draft', 'steps' => [['type' => 'tag', 'value' => 'Welcome'], ['type' => 'wait', 'value' => '1440'], ['type' => 'score', 'value' => '10'], ['type' => 'task', 'value' => 'Personal welcome follow-up']]]);
                }
                for ($i = 0; $i < 5; $i++) {
                    SignupForm::create(['branch_id' => $branchIds[$i % count($branchIds)], 'name' => 'Newsletter signup '.($i + 1), 'headline' => 'Good things, delivered to your inbox.', 'description' => 'Fresh ideas and thoughtful updates. Join our community.', 'published' => true, 'double_opt_in' => true]);
                }
                for ($i = 0; $i < 167; $i++) {
                    Product::create(['branch_id' => $branchIds[$i % count($branchIds)], 'name' => ['Everyday Tote', 'Linen Notebook', 'Studio Mug', 'Canvas Pouch', 'Desk Organizer'][$i % 5].' '.($i + 1), 'sku' => 'SKU-'.($i + 1), 'price_minor' => 1500 + ($i % 40) * 250, 'stock' => 20 + $i % 100]);
                }
                $campaignIds = Campaign::pluck('id')->all();
                $rows = [];
                for ($i = 0; $i < 1667; $i++) {
                    $contact = $contacts[$i % $count];
                    $rows[] = ['public_id' => (string) Str::ulid(), 'business_id' => $business->id, 'branch_id' => $contact->branch_id, 'number' => 'ORD-'.($bi + 1).'-'.($i + 1), 'contact_id' => $contact->id, 'campaign_id' => $campaignIds[$i % 40], 'total_minor' => 2500 + $i % 20000, 'currency' => 'USD', 'status' => 'paid', 'created_at' => now()->subDays($i % 365), 'updated_at' => now()];
                    if (count($rows) === 500) {
                        DB::table('orders')->insert($rows);
                        $rows = [];
                    }
                }if ($rows) {
                    DB::table('orders')->insert($rows);
                }
                foreach (['Partnership with Greenleaf', 'New customer onboarding', 'Holiday campaign proposal', 'Annual renewal'] as $i => $name) {
                    Deal::create(['name' => $name, 'stage' => ['new', 'qualified', 'proposal', 'won'][$i], 'value_minor' => 50000 * ($i + 1), 'expected_close' => now()->addDays(14)]);
                }
                foreach (['Review the autumn newsletter', 'Confirm sender details', 'Refresh welcome sequence', 'Follow up with VIP customers'] as $i => $name) {
                    Task::create(['name' => $name, 'status' => $i === 0 ? 'in_progress' : 'todo', 'due_at' => now()->addDays($i + 1)]);
                }
            });
        }
        $acme = Business::where('name', 'Acme Retail Ltd')->firstOrFail();
        $agency = Business::where('name', 'PixelCraft Agency')->firstOrFail();
        $roles = ['superadmin' => 'Super Admin', 'owner' => 'Business Owner', 'admin' => 'Admin', 'manager' => 'Branch Manager', 'marketer' => 'Marketing Manager', 'editor' => 'Campaign Editor', 'designer' => 'Content/Designer', 'automation' => 'Automation Specialist', 'analyst' => 'Analyst', 'sales' => 'Sales Rep', 'support' => 'Support Agent', 'finance' => 'Finance/Billing', 'client' => 'Client'];
        foreach ($roles as $key => $role) {
            $user = User::firstOrNew(['email' => $key.'@demo.test']);
            $user->forceFill(['name' => $role, 'password' => Hash::make('password'), 'role' => $role, 'business_id' => $key === 'client' ? $agency->id : $acme->id, 'branch_id' => $key === 'manager' ? Branch::where('business_id', $acme->id)->where('name', 'Dhaka Store')->value('id') : ($key === 'client' ? Branch::where('business_id', $agency->id)->value('id') : null), 'email_verified_at' => now()])->save();
        }
        $this->call(GrowthSeeder::class);
    }
}

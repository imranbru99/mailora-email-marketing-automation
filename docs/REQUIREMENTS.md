# MASTER PROMPT — Email Marketing Platform: Campaigns & Automation
### Complete, modular, multi-branch, end-to-end business application (Laravel + Filament)

---

## 0. YOUR ROLE AND MISSION

You are a senior full-stack engineering team (architect, backend, Filament UI, QA, DevOps, technical writer) working as one autonomous AI code agent.

Build **"Email Marketing Platform"** — a complete, production-grade, world-class business application for email marketing, campaigns and marketing automation — in **PHP / Laravel with Filament**.

This is NOT a demo or skeleton. It must be a **complete end-to-end business solution** that a real company can install, configure, load with demo data, and run on day one. Every business should be able to **turn each module ON or OFF** depending on what it needs, and the application must keep working correctly with any combination of modules enabled.

Aim: **the best email marketing platform in the world.** Build with that ambition: correctness, speed, deliverability, security, polish.

**Rules of engagement**
1. Do not stop at a plan. Write all the code, migrations, seeders, tests, views, docs.
2. Work in phases (Section 17). After each phase run migrations, seeders and tests and fix failures before moving on.
3. Never leave `TODO`, placeholder, lorem-ipsum-only or "implement later" code in delivered features.
4. If a requirement is ambiguous, choose the most sensible production default, document it in `docs/DECISIONS.md`, and continue.
5. Before using any of the listed packages, **read its Packagist page and README** (links in Section 2) and use its real public API. Do not invent method names. If a package lacks something needed, write a thin adapter in `app/Support/` rather than forking.

---

## 1. TECH STACK

- PHP 8.3+, Laravel (latest stable), Filament (latest stable, v3 or newer compatible with the dashboard package)
- MySQL 8 (primary), Redis (cache, queues, rate limits, sessions), Laravel Horizon for queues
- Laravel Reverb or Pusher-compatible broadcasting for live dashboards (optional module)
- Tailwind CSS, Alpine.js, Livewire (public pages + Filament)
- Pest or PHPUnit for tests; Larastan (level 6+) and Laravel Pint
- Docker + docker-compose for local; production notes for a VPS with nginx/FastPanel
- Queue-first architecture: anything slow (sending, imports, AI, scraping, PDFs, webhooks) runs in queues

---

## 2. REQUIRED PACKAGES (use them for what they are meant for)

| Purpose | Package | Use in this app |
|---|---|---|
| Speed | https://packagist.org/packages/imrandevbd/drutogoti | Performance layer: caching, query/response speed-up, fast page loads for public pages and admin |
| Dashboard | https://packagist.org/packages/imrandevbd/laravel-admin-panel | Base admin panel/dashboard shell; build all Filament resources and widgets on top of it |
| Scrape online data | https://packagist.org/packages/imrandevbd/laravel-ai-catalog | Lead/contact enrichment, competitor email inspiration, scraping public company data, website-to-template import |
| Any AI | https://packagist.org/packages/imrandevbd/laravel-ai-hub | All AI features: subject lines, copywriting, send-time hints, segmentation suggestions, reply classification, translations, image alt text |
| Attendance machines | https://packagist.org/packages/imrandevbd/laravel-attendance-hub | Staff attendance for the optional HR/Team module (agencies and in-house marketing teams) |
| PDF in any language | https://packagist.org/packages/imrandevbd/laravel-unicode-pdf | Campaign reports, invoices, client reports, contracts — Bangla, Arabic, CJK etc. |
| Slugs in any language | https://packagist.org/packages/imrandevbd/laravel-universal-slug | Slugs for landing pages, forms, blog, templates, campaigns, tags, help articles in any script |

Package integration rules:
- Wrap each package behind an interface in `app/Contracts` and an adapter in `app/Support/Integrations/*` so it can be swapped or disabled.
- AI calls must go through one `AiService` (backed by laravel-ai-hub) with: per-tenant provider/model settings, usage logging, cost tracking, per-plan limits, queued execution, graceful fallback when AI is off.
- Every package-powered feature is part of a module and respects its toggle.

---

## 3. CORE ARCHITECTURE PRINCIPLES

### 3.1 Multi-tenant + multi-branch
- **Tenant = Business (Organization/Workspace).** One installation serves many businesses (SaaS mode) or one business (self-hosted mode) — controlled by `config('app.mode')`.
- **Branch** belongs to a Business. Examples: Head Office, Dhaka Branch, Sydney Agency, Client A. Every business has ≥1 branch.
- Branch-scoped data (configurable per module): contacts, lists, campaigns, templates, automations, forms, landing pages, sending identities, reports, team members, budgets.
- Provide a global scope trait `BelongsToBusiness` and `BelongsToBranch`, plus branch switcher in the Filament top bar ("All branches" for owners/admins).
- Branch settings can **inherit** from business settings and **override** (sender identity, footer/address, timezone, language, currency, brand kit, sending limits).
- Cross-branch features: shared template library, shared suppression list (global opt-out), consolidated reports, contact transfer between branches, branch-level permissions.

### 3.2 Module system (the heart of the product)
Build a first-class **Module Manager**:
- `modules` table (key, name, description, category, icon, depends_on, is_core, default_enabled, sort).
- `business_modules` / `branch_modules` pivot: enabled flag, config JSON, enabled_at, enabled_by.
- A `ModuleRegistry` service. Each module is a folder `app/Modules/<ModuleName>/` with its own ServiceProvider, migrations, Filament resources/pages/widgets, routes, policies, events, listeners, translations, seeders, tests and `module.json` manifest.
- Toggling a module OFF must: hide its Filament navigation/resources/widgets, block its routes (403/404), pause its scheduled jobs and queues, hide its public pages, hide its dashboard widgets and API endpoints — **without deleting data**. Toggling ON restores everything.
- Dependency handling: enabling a module auto-suggests/enables required dependencies; disabling warns about dependents.
- Filament page **Settings → Modules** with search, categories, ON/OFF switches, dependency hints, per-module settings, and "Business type presets" (Section 5).
- Helper API: `module('automation')->enabled()`, Blade `@module('crm') ... @endmodule`, route middleware `module:automation`, policy gate integration, Filament `shouldRegisterNavigation()` integration.
- Core modules (cannot be disabled): Auth & Users, Business & Branches, Module Manager, Settings, Notifications, Audit Log.

### 3.3 Everything configurable, nothing hard-coded
Currency, timezone, locale, date formats, sender identities, limits, branding, legal footers, double opt-in on/off, tracking on/off, etc. live in a Settings system (business-level with branch override).

---

## 4. ROLES & PERMISSIONS

Use spatie/laravel-permission (or equivalent) with Filament Shield-style policy generation. Roles (seeded, editable, per business, optionally per branch):
Super Admin (platform), Business Owner, Admin, Branch Manager, Marketing Manager, Campaign Editor, Content/Designer, Automation Specialist, Analyst/Viewer, Support Agent, Sales Rep, Finance/Billing, Client (agency portal), API-only.

Granular permissions per module per action (view, create, edit, delete, export, send, approve, publish). Approval workflows: campaigns above a configurable audience size or by junior roles require approval before sending.

---

## 5. BUSINESS-TYPE PRESETS (quick-start module bundles)

On onboarding, owner picks a business type and the matching modules are enabled (all editable afterwards):
- E-commerce store
- SaaS / Software company
- Marketing agency (multi-client = multi-branch)
- Publisher / Newsletter creator
- Non-profit / Fundraising
- Education / Course creator
- Real estate
- Restaurant / Hospitality
- Healthcare / Clinic (with consent-heavy defaults)
- Events / Webinars
- Local services
- Enterprise (everything on)
- Custom (pick manually)

---

## 6. COMPLETE MODULE CATALOG
Implement **every** module below. Each is toggleable, branch-aware where noted, has Filament CRUD, permissions, demo data, tests, and appears on the public features pages.

### A. Audience & Contacts
1. **Contacts** — full profile (email, name, phone, company, job title, address, birthday, language, timezone, source, status, score, tags, custom fields, consent records, engagement history, notes, activity timeline), bulk actions, merge duplicates, soft deletes, GDPR export/erase.
2. **Custom Fields** — text, number, date, select, multi-select, boolean, URL, file; per business/branch; use in merge tags and segments.
3. **Lists & Groups** — static lists, multiple list membership, list settings (default sender, double opt-in, welcome email), archive.
4. **Tags** — manual and automation-driven tagging; tag-based segments.
5. **Segments** — visual condition builder (AND/OR groups) on any field, tag, list, activity (opened/clicked/purchased/visited), date ranges, location, score, engagement tier, predictive attributes; dynamic and snapshot segments; live count preview; segment export.
6. **Import / Export** — CSV/XLSX/Google Sheets-style paste import, field mapping wizard, dedupe, validation, error report, chunked queue import of millions of rows, scheduled/auto imports from URL/SFTP, export to CSV/XLSX/PDF.
7. **Email Verification & List Hygiene** — syntax/MX/disposable/role-account/typo checks, SMTP probe (optional driver), bulk verification, auto-clean of hard bounces, inactive contact sunset policy, spam-trap risk heuristics.
8. **Suppression & Compliance Lists** — global/branch suppression, unsubscribes, bounces, complaints, manual blocks, domain blocks; always enforced before send.
9. **Preference Center** — hosted public page where contacts choose topics/frequency/language, pause emails for N days, update details, unsubscribe with reason.
10. **Consent & Compliance (GDPR/CAN-SPAM/CASL/PECR)** — consent log with timestamp/IP/source/proof, double opt-in, legal basis fields, data-subject requests (export/delete) workflow, region-based rules, mandatory footer/address/unsubscribe enforcement, data retention policies, DPA/legal documents page.
11. **Lead Scoring** — rule-based scoring (opens, clicks, page visits, form fills, purchases), decay, grading, hot-lead alerts, AI-suggested scoring.
12. **Contact Enrichment** — via laravel-ai-catalog scraping + AI: company, industry, size, social links from email domain; opt-in and logged.
13. **Duplicate Management** — detection and merge UI.

### B. Campaigns
14. **Campaigns (Regular broadcasts)** — wizard: audience → sender → subject/preheader → content → tracking → schedule → review/test → send. Statuses: draft, pending approval, scheduled, sending, paused, sent, cancelled, failed.
15. **A/B & Multivariate Testing** — subject, sender name, content, send-time; test % split; auto-winner by opens/clicks/revenue after X hours; statistical significance display.
16. **Scheduled & Recurring Campaigns** — schedule by contact timezone, recurring digests, RSS-to-email campaigns (auto-pull latest posts).
17. **Send-Time Optimization** — per-contact best send time from engagement history (with AI assist).
18. **Throttling & Warm-up** — send rate limits per minute/hour/day, IP/domain warm-up schedules, gradual ramp-up, automatic pause on complaint/bounce spikes.
19. **Transactional Email** — API/SMTP relay + templates for receipts, password resets, notifications; separate stream/reputation; logs, search, resend, webhooks.
20. **Plain-text & Multipart** — auto-generated text version, AMP-for-email (optional), dark-mode previews.
21. **Campaign Approval Workflow** — request/approve/reject with comments, audit trail.
22. **Campaign Calendar** — month/week calendar of all campaigns and automations across branches (Filament calendar widget), drag to reschedule.
23. **Campaign Folders, Duplicate, Archive, Bulk actions**.
24. **Pre-send Checklist & Spam Score** — checks for broken links, missing alt text, spammy words, image/text ratio, missing unsubscribe, SPF/DKIM/DMARC status, link domain reputation, subject length; AI review; block send on critical errors.
25. **Test Sends & Inbox Preview** — send to seed list, device/client previews (desktop/mobile/dark mode), rendering checks, seed-list inbox placement tracking (manual + optional integration hook).
26. **SMS & WhatsApp Campaigns (omnichannel add-on)** — optional module: SMS/WhatsApp/push broadcasts with the same segmentation and automation, provider drivers (Twilio-compatible, plus local gateway interface for Bangladesh providers), opt-in tracking.
27. **Web Push Notifications (optional)**.

### C. Content & Design
28. **Drag-and-Drop Email Builder** — block-based editor (header, text, image, button, divider, spacer, columns, social, video thumbnail, countdown timer, product block, coupon, menu, footer, HTML block, dynamic content block), responsive output, global styles, undo/redo, save as reusable block, mobile/desktop/dark preview, MJML-compatible output pipeline or equivalent inline-CSS renderer.
29. **Code Editor** — raw HTML/CSS editor with live preview, CSS inliner, minification, validation.
30. **Template Library** — 60+ seeded professional templates across industries and purposes (welcome, newsletter, promo, abandoned cart, order confirmation, win-back, webinar invite, event, survey, birthday, review request, product launch, holiday sale, receipt, onboarding drip, etc.), categories, favorites, branch-shared and business-shared templates, import/export template JSON.
31. **Dynamic Content & Personalization** — merge tags `{{first_name|fallback}}`, conditional blocks by segment/tag/field, loops over products/posts/events, localized content variants per language, live data blocks (API/RSS), countdown timers, personalized images (text-on-image via GD/Imagick).
32. **Media Library** — uploads, folders, image optimization/resizing, CDN-ready URLs, stock/AI image generation via laravel-ai-hub, alt-text AI, usage tracking, asset reuse.
33. **Brand Kit** — logo, colors, fonts, footer, social links, default buttons per business/branch, auto-applied to templates.
34. **Snippets / Saved Blocks / Signatures**.
35. **Multi-language Content** — create translations of any email (AI-assisted), auto-send by contact language, RTL support (Arabic, Urdu, Hebrew), full Bangla/Unicode support.
36. **AI Content Studio** (via laravel-ai-hub) — subject line generator with variants and predicted performance, preheader, full email copy from brief, tone rewrite, shorten/expand, translate, CTA suggestions, spam-word fixer, image generation, campaign idea generator, newsletter from URL/blog (using laravel-ai-catalog to scrape), brand-voice memory, history of generations, token/cost usage.

### D. Marketing Automation
37. **Visual Automation Builder** — canvas flow editor: triggers, conditions (if/else, multi-branch splits), actions, delays/waits (fixed, until date/time, until event, business-hours), A/B split, goal/exit conditions, merge paths, loops with caps, per-contact run log, versioning, draft/live, test with a sample contact, pause/resume, safe edit of live flows.
38. **Triggers** — contact added to list, tag added/removed, form submitted, field changed, date-based (birthday/anniversary/renewal), email opened/clicked/replied, link clicked, page visited, e-commerce events (cart abandoned, order placed, product viewed, back in stock, price drop), webhook/API event, subscription start/cancel/expire, score threshold, manual enrollment, segment entry/exit, RSS item, calendar/booking event.
39. **Actions** — send email, send SMS/WhatsApp, add/remove tag, add/remove from list, update field, change score, move to branch, assign to owner, create task, create deal, notify team (email/Slack/Telegram webhook), call webhook, wait, split, AI-generate and send personalized email, update/unsubscribe contact, start/stop another automation.
40. **Prebuilt Automation Recipes** — one-click templates: Welcome series, Abandoned cart (3-step), Browse abandonment, Post-purchase + review request, Win-back/re-engagement, Birthday/anniversary, Lead nurture, Onboarding drip (SaaS trial), Webinar reminder series, Renewal reminder, Cold-to-warm sunset policy, VIP/loyalty, Back-in-stock, Price drop, Survey follow-up, Event follow-up, Referral invite. Each seeded and ready.
41. **Autoresponders & Drip Courses** — time-based sequences with per-contact start date, course-style delivery.
42. **Customer Journeys Map** — visual funnel/journey analytics: counts per step, drop-off, conversion, time-in-step.
43. **Event Tracking API & JS Snippet** — site tracking script (page views, custom events, identify), server-side events API, SDK docs; powers triggers and segments.
44. **Webhooks (in & out)** — signed, retried, logged; inbound webhooks create events/contacts.
45. **Workflow Tasks & Alerts** — tasks/notifications for sales reps from automation.

### E. Forms, Landing Pages & Growth
46. **Signup Forms Builder** — embedded, popup (exit-intent, timed, scroll), slide-in, bar, inline, full-page; fields, GDPR checkbox, double opt-in, success actions, targeting rules, A/B tests, spam protection (honeypot, reCAPTCHA/Turnstile), embed code + JS API + iframe + Livewire component.
47. **Landing Page Builder** — block-based pages with custom domain/slug (laravel-universal-slug for any language), templates, SEO meta, forms, countdown, pixel/analytics hooks, A/B tests, publish/unpublish, versioning.
48. **Surveys & Polls** — in-email and hosted surveys, NPS/CSAT, results dashboards, results → fields/tags.
49. **Lead Magnets / Content Gate** — gated downloads with auto-delivery email.
50. **Referral & Viral Programs** — unique referral links, rewards tracking, leaderboard, automation hooks.
51. **Giveaways / Contests** — entries, rules, winner picker, compliance copy.
52. **Webinar / Event Registration** — registration pages, reminders, attendance import, follow-up automations, calendar invites (.ics).
53. **Coupons & Promo Codes** — generate unique per-contact codes, expiry, redemption tracking.
54. **QR Codes & Short Links** — branded short domain, link management, QR generation, click stats.
55. **Social Sharing & "View in Browser" pages**, public archive of campaigns (optional).

### F. Sales, CRM & Commerce Light
56. **Mini-CRM** — companies, deals/pipelines with stages, activities, notes, tasks, owner assignment, deal value, win/lose reasons, email timeline per contact, sync with scoring and automation.
57. **Shared Inbox / Reply Management** — inbound replies (IMAP/webhook), threads, assignment, AI-classified intent (interested, unsubscribe, OOO, bounce), canned responses, SLA timers.
58. **Product Catalog** — products, categories, variants, prices, images, stock; product blocks in emails; import from CSV/URL/WooCommerce/Shopify connectors.
59. **Orders & Revenue Attribution** — orders, revenue per campaign/automation/segment, last-touch and multi-touch attribution, LTV, RFM segmentation, repeat-purchase analysis.
60. **E-commerce Connectors** — Shopify, WooCommerce, Magento, custom API: sync customers/orders/products/carts via webhooks and polling; abandoned cart tracking.
61. **Recommendations Engine** — "recommended for you", best sellers, recently viewed, cross-sell, via rules + AI.
62. **Subscriptions / Memberships (optional)** — paid newsletter or membership tiers, plan pages, renewals, dunning emails.

### G. Deliverability & Infrastructure
63. **Sending Identities & Domains** — multiple from-addresses/domains per branch, domain verification wizard (SPF, DKIM, DMARC, BIMI hints, return-path), DNS record generator and live checker, reputation notes.
64. **SMTP & Provider Connections** — drivers: SMTP, Amazon SES, Mailgun, SendGrid, Postmark, Brevo, SparkPost, Resend, Mailtrap, custom HTTP API, plus Postfix/own MTA hook; many connections, weighted routing, failover, per-branch assignment, per-provider rate limits, health checks.
65. **Bounce, Complaint & Feedback-Loop Processing** — webhooks per provider + IMAP bounce mailbox parser, hard/soft classification, retry rules, auto-suppression.
66. **Deliverability Dashboard** — delivery/bounce/complaint/open rates by domain (Gmail, Yahoo, Outlook…), reputation score, blacklist monitoring (DNSBL checks), DMARC report ingestion (aggregate XML parsing), warm-up progress, alerts.
67. **Dedicated IP / IP Pool Management (optional)** — pools, warm-up plans.
68. **Link Tracking & Custom Tracking Domain** — open pixel, click redirect with signed URLs, Apple MPP detection and filtering, bot-click filtering, UTM auto-append, per-campaign tracking toggle for privacy.
69. **Unsubscribe Handling** — List-Unsubscribe and one-click header (RFC 8058), hosted unsubscribe page, reason capture.
70. **Email Queue Engine** — high-throughput batched sending via queues/Horizon, idempotency, retry with backoff, dead-letter handling, throttle per provider, pause/resume/cancel mid-send, crash recovery, send logs. Target: millions of emails per campaign without memory blowups (chunked/lazy collections, cursor pagination).

### H. Analytics & Reporting
71. **Campaign Reports** — sent, delivered, opens (unique/total/real vs MPP), clicks (unique/total/by link/heatmap), unsubscribes, complaints, bounces, conversions, revenue, forwards, geography, devices, email clients, timeline charts, top links, top segments, comparison between campaigns, A/B results.
72. **Automation Reports** — per-flow and per-step metrics, revenue, journeys.
73. **Contact & Audience Analytics** — growth, churn, sources, engagement tiers, cohort analysis, list health score.
74. **Revenue & ROI Dashboard** — attributed revenue, cost per send (provider cost entry), ROI, LTV.
75. **Real-time Activity Feed** — live opens/clicks/signups (broadcast).
76. **Custom Reports & Dashboards** — drag-and-drop widgets, saved views, filters by branch/date/segment.
77. **Scheduled Reports** — daily/weekly/monthly email delivery of PDF/CSV reports (laravel-unicode-pdf for any language), client-branded for agencies.
78. **Exports** — CSV/XLSX/PDF everywhere; API access to metrics.
79. **AI Insights** — plain-language "what happened and what to do next" summaries, anomaly alerts, subject-line learnings, best-time suggestions (via laravel-ai-hub).
80. **Benchmarks** — compare against industry averages (seeded reference data) and across own branches.

### I. Team, Agency & Operations
81. **Team Management** — invitations, roles, branch assignment, activity, 2FA enforcement, SSO (Google/Microsoft/SAML-ready), session management.
82. **Agency / Client Portal** — each client = branch; white-label (logo, domain, colors, emails), client login with limited access, approvals by client, client reports, per-client billing/usage.
83. **Approvals & Comments** — comment threads on campaigns/templates/automations, @mentions, review requests.
84. **Tasks & Project Board** — marketing tasks, kanban, due dates, linked to campaigns.
85. **Attendance & HR (optional module)** — staff attendance using **laravel-attendance-hub** (any attendance machine: ZKTeco-style devices, etc.), shifts, leave, late/absent reports; intended for agencies/in-house teams; payroll export; PDF reports in any language.
86. **Time Tracking & Productivity (optional)**.
87. **Notifications Center** — in-app, email, Slack/Telegram/Discord webhook, browser push; per-user preferences.
88. **Audit Log & Activity Log** — who did what, when, from where; filters, export, retention.
89. **Knowledge Base / Help Center** (public + in-app) — articles with universal slugs, search, categories, feedback, AI answer helper.
90. **Support Tickets (optional)** — customers → platform staff in SaaS mode.

### J. Billing, Plans & Platform (SaaS mode)
91. **Plans & Subscriptions** — plan builder (limits: contacts, monthly emails, branches, users, modules included, AI credits, custom domains, API calls), trials, upgrades/downgrades, proration, coupons, annual discounts.
92. **Payment Gateways** — Stripe, PayPal, Paddle-ready interface; Bangladesh local gateways via adapter interface (bKash/Nagad/SSLCommerz-style); manual/bank payment with admin approval.
93. **Invoices & Receipts** — auto invoices, PDF via laravel-unicode-pdf in any language, tax/VAT/GST fields, credit notes.
94. **Usage Metering & Quotas** — real-time counters, soft/hard limits, overage billing or blocking, usage alerts.
95. **Email Credits / Pay-as-you-go (optional)** — buy email credits packs; also AI credits packs.
96. **Affiliate / Reseller Program (optional)** — referral tracking, commissions, payouts.
97. **Super Admin Console** — all businesses, plans, usage, health, impersonation (audited), global suppression, abuse monitoring (complaint-rate watchdog, auto-suspend rules), platform settings, announcement banners, feature flags, system health (queues, failed jobs, disk, providers).

### K. Platform Services
98. **Public REST API** — versioned (`/api/v1`), token (Sanctum) + scoped API keys, rate limits, OpenAPI/Swagger docs auto-generated, endpoints for contacts, lists, tags, segments, campaigns, automations, events, transactional send, templates, reports, webhooks.
99. **Webhooks Manager** — subscribe to events, secrets, delivery logs, replay.
100. **Integrations Hub** — Zapier/Make-compatible triggers/actions, Google Sheets, Slack, Telegram, WordPress plugin (subscribe form + WooCommerce events), Shopify, WooCommerce, Stripe, HubSpot/Salesforce-style CRM sync interface, generic OAuth connector framework.
101. **Data Scraping & Lead Intelligence** (via **laravel-ai-catalog**) — scrape public pages/directories into contact lists (with legal/compliance guardrails and rate limits), website-to-template, competitor newsletter analysis.
102. **Localization** — full i18n: English, Bangla, Arabic (RTL), Hindi, Spanish, French, German, Portuguese, Chinese, Japanese, Indonesian, Turkish, Russian, Urdu; language switcher; translation management UI; per-user and per-contact language.
103. **White-label & Custom Domains** — custom app domain, custom tracking/landing domains, brand per business/branch, hide platform branding on paid plans.
104. **Backup, Restore & Data Portability** — scheduled backups, per-business export, import from Mailchimp / Klaviyo / Sendinblue-style CSV exports (mapping helpers).
105. **Security Center** — 2FA, IP allowlist, API key rotation, session/device management, login alerts, password policy, field-level encryption for sensitive data, security headers, rate limiting, bot protection.
106. **Feature Flags & Experiments** (internal).
107. **Health & Monitoring** — queue lag, failed jobs, send throughput, provider latency, error tracking hooks (Sentry-compatible), uptime endpoint.
108. **Onboarding & Setup Wizard** — guided: business type → modules → branch → domain verification → provider connection → import contacts → first campaign; progress checklist and in-app tours.

---

## 7. PUBLIC WEBSITE (several public pages showing every feature)

Build a polished, fast, SEO-friendly, multilingual public site (Blade + Livewire + Tailwind, optimized with drutogoti). Pages:

1. **Home** — hero, product screenshots (render from demo), key benefits, module overview, social proof (seeded testimonials/logos), pricing teaser, CTA (Start free / Book demo / Try live demo).
2. **Features (index)** — grid of ALL modules grouped by category (A–K above), each linking to its detail page; search/filter.
3. **Feature detail pages** — one per module, auto-generated from module manifest + rich content: what it does, benefits, screenshots/illustrations, how it works, related modules, FAQs, CTA. Slugs via laravel-universal-slug.
4. **Solutions / Industries** — page per business-type preset showing recommended modules and sample workflows.
5. **Automation Recipes gallery** — public gallery of prebuilt automations with flow diagrams.
6. **Templates gallery** — browse/preview seeded email templates by category.
7. **Pricing** — dynamic from plans table, monthly/annual toggle, feature comparison matrix, module availability per plan, FAQ, currency switcher.
8. **Compare / Why us** — comparison table vs generic competitors (neutral, factual placeholders editable in admin), migration guide.
9. **Live Demo page** — "Try with demo account" one-click login (Section 9) with reset notice.
10. **Integrations** page, **API & Developers** page (docs, code samples, Postman/OpenAPI download), **Deliverability** page, **Security & Compliance** page (GDPR, CAN-SPAM, data handling).
11. **Blog** (optional module) — posts, categories, authors, SEO, RSS.
12. **Help Center / Docs**, **Changelog / Roadmap**, **About**, **Contact** (form → CRM/inbox), **Careers** (optional), **Partners/Affiliates**, **Status page**.
13. **Legal** — Terms, Privacy, Cookies, DPA, Acceptable Use/Anti-Spam Policy (all editable).
14. **Auth pages** — Login, Register (with business-type onboarding), Forgot password, 2FA, Accept invitation.
15. **Public utility pages** — hosted unsubscribe & preference center, view-in-browser, hosted forms/landing pages/surveys, referral landing, short-link redirects, 404/500 branded pages.

Public-site requirements: SEO meta + Open Graph + JSON-LD, sitemap.xml, robots.txt, hreflang for languages, Core Web Vitals-friendly, accessible (WCAG AA), dark mode, cookie consent banner, analytics hooks, all content manageable from admin (Pages/CMS module), and every page respects the module/branch toggles (e.g. disabled blog hides the blog).

---

## 8. FILAMENT ADMIN EXPERIENCE

- Built on **laravel-admin-panel** shell; customize via Filament panels: `admin` (business app), `superadmin` (platform), `client` (agency portal).
- Navigation grouped by module category; entries appear only for enabled modules and permitted roles.
- Global search across contacts, campaigns, templates, automations.
- Branch switcher + business switcher in header; quick-create menu; command palette (Cmd/Ctrl+K).
- Dashboard: widgets for sends today, growth, revenue, engagement, deliverability health, active automations, upcoming campaigns calendar, tasks, AI insights, quota usage — all filterable by branch/date, each widget gated by its module.
- Every resource: filters, saved views, bulk actions, import/export actions, relation managers, infolists, activity timeline, soft delete/restore, keyboard friendly, responsive.
- Custom Filament pages: Module Manager, Email Builder, Automation Canvas, Segment Builder, Deliverability Center, Setup Wizard, Report Builder.
- Dark/light theme, RTL support, accessible components, consistent empty states and onboarding hints.
- Performance: eager loading, indexes, cached counts, lazy tables, queued heavy actions, drutogoti speed layer; admin pages should load < 300 ms server time on seeded demo data.

---

## 9. DEMO DATA & DEMO USERS (mandatory)

Create seeders (`php artisan migrate:fresh --seed` gives a fully populated system; also `php artisan demo:reset` and a scheduled hourly reset for the public demo):

**Businesses (3)**
1. **Acme Retail Ltd** (E-commerce) — 3 branches: Head Office, Dhaka Store, Online Store; ecommerce, forms, automation, products, SMS modules ON.
2. **BrightPath SaaS** (SaaS) — 2 branches; trial-onboarding automations, CRM, API, transactional ON; ecommerce OFF.
3. **PixelCraft Agency** (Agency) — 4 client branches + client portal ON, white-label ON, attendance ON, approvals ON.

**Demo users (password for all: `password` — shown on demo login page)**
| Email | Role |
|---|---|
| superadmin@demo.test | Super Admin (platform) |
| owner@demo.test | Business Owner (Acme) |
| admin@demo.test | Admin |
| manager@demo.test | Branch Manager (Dhaka Store) |
| marketer@demo.test | Marketing Manager |
| editor@demo.test | Campaign Editor |
| designer@demo.test | Content/Designer |
| automation@demo.test | Automation Specialist |
| analyst@demo.test | Analyst (read-only) |
| sales@demo.test | Sales Rep |
| support@demo.test | Support Agent |
| finance@demo.test | Finance/Billing |
| client@demo.test | Agency Client (portal) |

**Demo content**: ≥ 25,000 contacts (realistic names incl. Bangla/English/Arabic), 40 lists/tags/segments, 120 campaigns in all statuses with realistic stats history over 12 months, 25 automations (all recipes) with run history, 60+ templates, 15 forms, 8 landing pages, 500 products, 5,000 orders with attribution, 30 surveys, tickets/inbox threads, deliverability and DMARC sample data, invoices, plans (Free, Starter, Growth, Pro, Enterprise), usage data, activity logs, notifications, KB articles, blog posts.
Demo sending uses a safe **log/sandbox driver** (no real emails), with simulated opens/clicks/bounces generated by a `demo:simulate-engagement` command so charts look alive.

---

## 10. DATABASE DESIGN REQUIREMENTS

- Use UUID or ULID public IDs; bigint internal keys; every table has `business_id`, and `branch_id` where branch-scoped; proper foreign keys, soft deletes where sensible, composite indexes for hot queries (business_id+email unique, campaign_id+contact_id, event timestamps).
- High-volume tables (`email_events`, `message_logs`, `contact_activities`) must be designed for scale: partition-ready (monthly), narrow rows, aggregated rollup tables (`stats_hourly`, `stats_daily`) updated by jobs, retention policy settings.
- Contact deduplication unique constraint per business (case-insensitive normalized email).
- JSON columns for settings/custom fields with generated/indexed columns where filtered often.
- Provide ERD (`docs/ERD.md` with Mermaid) and a data dictionary.

---

## 11. NON-FUNCTIONAL REQUIREMENTS

- **Performance**: handle ≥ 1,000,000 contacts per business and ≥ 500 emails/second sustained (provider permitting); lazy collections, chunking, cursor pagination, Redis caching, read-optimized report tables, N+1 prevention (enable `Model::preventLazyLoading()` in non-production).
- **Reliability**: idempotent jobs, retries with backoff, circuit breakers per provider, failover, graceful shutdown, resume after crash, no duplicate sends (unique send keys).
- **Security**: OWASP Top 10 coverage, policies on every model, CSRF/XSS/SQLi protections, signed URLs for tracking/unsubscribe, encrypted secrets (provider credentials, API keys), webhook signature verification, rate limits, audit logging, 2FA, secure headers/CSP, file upload validation, SSRF protection for scrapers and webhooks, secrets never logged.
- **Privacy**: GDPR tooling, data minimization, configurable tracking (disable opens/clicks per campaign/business), IP anonymization option.
- **Observability**: structured logs, Horizon dashboards, health endpoint, failed-job UI, send-pipeline metrics.
- **Accessibility & i18n**: WCAG AA, RTL, Unicode everywhere (emails, PDFs, slugs).
- **Compatibility**: emails tested for Gmail, Outlook (incl. VML fallbacks), Apple Mail, Yahoo; dark-mode safe.
- **Code quality**: PSR-12 via Pint, Larastan level 6+, service/action classes, DTOs, events/listeners, form requests, policies, resources — no fat controllers; ≥ 80% coverage on core domains (sending engine, segmentation, automation engine, module manager, tracking, compliance, billing).

---

## 12. KEY ENGINES TO BUILD CAREFULLY

1. **Module Manager & Registry** (Section 3.2) — with tests proving each module can be toggled OFF/ON without errors across navigation, routes, jobs, widgets, API, public pages.
2. **Sending Engine** — campaign → audience resolution (with suppression applied) → batching → per-contact rendering (merge tags, dynamic blocks, tracking links, unsubscribe headers) → provider routing/throttle → send → log → events. Exactly-once semantics.
3. **Segmentation Engine** — compile visual conditions to optimized SQL; supports event-based conditions via indexed event tables; cached counts.
4. **Automation Engine** — durable state machine: contact enrollments, step scheduling via delayed jobs/scheduler, concurrency-safe, versioned flows, exit rules, re-entry rules, recoverable.
5. **Tracking Service** — pixel/click endpoints (fast, minimal bootstrap), signed payloads, bot/MPP filtering, event ingestion via queue, aggregation.
6. **Template Renderer** — Blade/Twig-safe sandboxed merge-tag engine, CSS inliner, text-version generator, link wrapper, preview.
7. **Compliance Guard** — a single choke point that every send passes (consent, suppression, footer/address, unsubscribe link, regional rules, plan limits, approval status). If it says no, nothing sends.
8. **Usage/Quota Meter** — atomic counters (Redis + DB sync), enforcement hooks.
9. **AI Service** — via laravel-ai-hub, with prompt templates, brand-voice injection, caching, cost limits, audit of generations, safe-mode.

---

## 13. DEVELOPER EXPERIENCE & DOCUMENTATION (mandatory deliverables)

1. **`README.md`** — the product's front page. MUST contain: product overview; **complete feature list grouped by module category (all 108 modules, each with 1–2 lines)**; screenshots placeholders; business-type presets; architecture overview diagram; module system explanation; demo credentials table; quick start (Docker and manual); required packages and why; environment variables; queue/scheduler/Horizon setup; mail provider setup; DNS/deliverability guide; production deployment (nginx/FastPanel/VPS, supervisor, cron, Redis, backups); testing; contributing; license; support links.
2. `docs/` folder: `MODULES.md` (every module: purpose, dependencies, settings, permissions, events), `ARCHITECTURE.md`, `API.md` (+ generated OpenAPI file), `DELIVERABILITY.md`, `COMPLIANCE.md`, `AUTOMATION_RECIPES.md`, `SECURITY.md`, `DEPLOYMENT.md`, `DECISIONS.md`, `ERD.md`, `CHANGELOG.md`, `USER_GUIDE.md`, `ADMIN_GUIDE.md`.
3. `.env.example` fully documented; `docker-compose.yml`; `Makefile`/composer scripts (`setup`, `demo`, `test`, `lint`, `queue`).
4. Artisan commands: `app:install`, `demo:seed`, `demo:reset`, `demo:simulate-engagement`, `modules:list|enable|disable`, `stats:rollup`, `deliverability:check`, `contacts:verify`, `send:resume`, `health:check`.
5. In-app contextual help links to the Help Center.

---

## 14. TESTING & QUALITY GATES

- Unit + feature tests for every engine and module; browser tests (Pest Browser/Dusk) for the signup form, builder save, campaign send wizard, automation publish, unsubscribe flow, module toggle, and public pages.
- Load-test scripts (k6 or similar) for tracking endpoints and send pipeline, with documented results.
- Test matrix: module combinations (all ON, all OFF except core, each preset), single-branch vs multi-branch, SaaS vs self-hosted mode.
- CI workflow (GitHub Actions): lint, static analysis, tests, build assets.
- **Definition of Done** for each module: migrations + models + policies + Filament UI + routes/API + seeders/demo data + translations (en + bn minimum) + tests + docs + public feature page + module toggle behavior verified.

---

## 15. UI/UX DIRECTION

- Clean, modern, high-trust SaaS look; consistent design tokens; delightful micro-interactions; skeleton loaders; helpful empty states with "Create demo data / Import / Learn more".
- Email builder and automation canvas must feel best-in-class: smooth, fast, keyboard shortcuts, autosave, version history.
- Mobile-friendly admin for reports, approvals, inbox and quick sends.
- Public site design should be premium and conversion-focused with real product visuals generated from demo data.

---

## 16. SPECIAL RULES

- **Anti-abuse**: complaint/bounce watchdog auto-pauses campaigns, blocks purchased-list patterns (high invalid rate on import), requires domain verification before sending, enforces unsubscribe link, rate-limits new accounts until reviewed (configurable).
- **No dark patterns**: unsubscribe must always work in one click.
- Every destructive action is confirmed, audited and (where possible) reversible.
- All timestamps stored UTC, displayed in user/business/contact timezone as appropriate.
- Every user-facing string is translatable.
- Scraping features via laravel-ai-catalog must honor robots.txt settings, rate limits and show a compliance warning; scraped contacts default to "not consented" and cannot be emailed until a lawful basis is recorded.
- Money handling with integer minor units and currency codes; multi-currency display.

---

## 17. EXECUTION PLAN (work in this order; keep the app runnable after every phase)

**Phase 1 — Foundation**: install Laravel + Filament + all 7 packages; base config; auth/2FA; business + branch tenancy; roles/permissions; Module Manager & Registry; settings system; audit log; notifications; i18n skeleton; Docker; CI; README skeleton.
**Phase 2 — Audience**: contacts, custom fields, lists, tags, segments, import/export, suppression, consent, preference center, verification, scoring.
**Phase 3 — Content**: media library, brand kit, template library (60+), drag-and-drop builder, code editor, personalization engine, AI Content Studio.
**Phase 4 — Sending**: provider connections, domains/identities, compliance guard, sending engine, tracking, bounce/complaint processing, campaigns wizard, A/B tests, scheduling, throttling, approvals, transactional email, pre-send checks.
**Phase 5 — Automation**: event tracking API/JS, triggers/actions, canvas builder, recipes, autoresponders, webhooks, journeys analytics.
**Phase 6 — Growth**: forms, landing pages, surveys, referral, giveaways, webinars, coupons, QR/short links.
**Phase 7 — CRM & Commerce**: mini-CRM, inbox, products, orders, attribution, connectors, recommendations, subscriptions.
**Phase 8 — Analytics & Deliverability**: stats rollups, all reports, dashboards, scheduled PDF reports, deliverability center, blacklist/DMARC monitoring, AI insights, benchmarks.
**Phase 9 — Team/Agency/HR**: client portal, white-label, approvals, tasks, attendance (laravel-attendance-hub), knowledge base, support tickets.
**Phase 10 — SaaS Platform**: plans, billing, gateways, invoices (unicode PDF), usage metering, super admin console, API + docs, integrations hub, security center, backups, health.
**Phase 11 — Public Website**: all pages from Section 7, CMS, SEO, blog, legal, demo login, gallery pages.
**Phase 12 — Demo, Hardening & Docs**: full demo seeders and simulators, performance tuning with drutogoti, load tests, security review, module-toggle matrix tests, final README/docs, `php artisan app:install` flow, release checklist.

After each phase output a short report: what was built, tests passing, known gaps (should be none by Phase 12).

---

## 18. FINAL ACCEPTANCE CHECKLIST

The work is complete only when ALL are true:
- [ ] `composer install && php artisan app:install --demo` yields a working app with all demo businesses, branches and users listed in Section 9.
- [ ] All 108 modules exist, are listed in Settings → Modules, and each can be switched ON/OFF with no errors anywhere in the app, API or public site.
- [ ] Every business-type preset boots into a coherent, usable product.
- [ ] Multi-branch works: scoping, switcher, inheritance/override, cross-branch reports, branch permissions.
- [ ] A full campaign can be built, approved, scheduled, sent (sandbox), tracked, reported on; automations run end-to-end from trigger to goal; forms/landing pages capture contacts that flow into automations.
- [ ] Compliance Guard blocks non-compliant sends; unsubscribe, preference center, GDPR export/erase work.
- [ ] Public website shows every feature, with pricing, templates gallery, recipes gallery, docs, legal pages, live demo login.
- [ ] All 7 packages are used in their intended places (speed, dashboard, scraping, AI, attendance, unicode PDF, universal slug) behind adapters.
- [ ] Bangla, Arabic (RTL) and English fully work in UI, emails, PDFs and slugs.
- [ ] README.md lists every feature and module and is accurate; all docs in Section 13 exist.
- [ ] Tests, lint and static analysis pass in CI; performance targets in Section 11 are met and documented.

Begin now with Phase 1. Do not ask for permission between phases; proceed autonomously, record decisions in `docs/DECISIONS.md`, and deliver the complete application.
# Delivery status and remaining scope

This is the implementation inventory as of 2026-10-28, checked against the code and the supplied [requirements](REQUIREMENTS.md). It is a working aid for continued development, not a declaration that the complete 108-module specification is finished.

The core campaign and tenant workflows are executable. Many broad requirements have partial implementations or record-management screens; some have only a catalog entry. No paid provider, hardware device, real SMTP inbox delivery, production load, payment gateway or full multilingual PDF/font matrix has been validated by the local automated suite.

## Status meanings

- **Partial flow:** an executable user or service workflow implements the scope explicitly described in the row. The complete module specification is still unfinished.
- **Basic records:** domain tables and CRUD exist; the specialized business workflow remains limited.
- **Adapter:** an interface and package integration exist but no complete end-user workflow is wired to them.
- **Catalog only:** manifest/flag/feature-page entry exists, possibly alongside a related primitive. The requested module is not implemented.

None of these labels means the complete corresponding paragraph in the original brief is delivered. Several rows share one underlying feature (for example campaign approval, multipart output and pre-send checks); their separate catalog flags do not imply separately implemented toggle boundaries. Unsubscribe is a deliberate always-available safety exception. Production readiness requires further engineering and deployment validation.

## Foundation delivered and structural gaps

Business/branch scoping, fail-closed tenant queries, session branch selection, inherited settings, role checks, module dependencies, module presets, registration, authentication, TOTP, public marketing pages and the main queue/scheduler paths are present. Implemented API tokens are user-scoped and permission-constrained.

Application code and migrations are centrally organized. `app/Modules/*` currently contains manifests, not 108 isolated providers/resources/migrations/tests/translation sets. The installed admin-panel package supplies palette data; Filament supplies the actual shell. `APP_MODE` is configuration, not a complete distinction between commercial SaaS and one-business operational behavior. Public pricing and demo counters are examples rather than a functioning billing subscription system.

## Module matrix

| # | Module / key | Status | Implemented scope | Remaining scope / practical limit |
|---|---|---|---|---|
| 1 | Contacts (`contacts`) | Partial flow | Profiles, consent fields, tags/custom JSON, list membership, soft deletion, CSV/profile export and erasure action. | No duplicate merge, complete activity timeline, address/birthday UI or complete dependent-data erasure workflow. |
| 2 | Custom Fields (`custom-fields`) | Basic records | Custom-field definition CRUD plus contact custom JSON. | Definitions are not a complete typed field validation/rendering or segmentation system. |
| 3 | Lists & Groups (`lists`) | Partial flow | List CRUD, membership persistence and contact membership editing; campaign list audience selection. | No complete per-list sender/welcome policy, archive or membership management suite. |
| 4 | Tags (`tags`) | Basic records | Tag definitions and contact tags; automation adds/removes tags. | No unified tag-definition lifecycle, usage reporting or full tag segment UI. |
| 5 | Segments (`segments`) | Partial flow | AND/OR field-rule segments with whitelisted query operators. | No nested visual groups, behavior/commerce/predictive conditions or snapshots. |
| 6 | Import / Export (`imports`) | Partial flow | Private queued streaming CSV import, validation/dedupe and error summary; CSV exports. | No XLSX, paste mapping wizard, SFTP/URL schedules or proven million-row capacity. |
| 7 | Verification | Partial flow | Queued syntax/DNS routing checks, typo/role/disposable flags, findings UI. | No mailbox probe, exhaustive disposable database, sunset policy, or spam-trap detection. |
| 8 | Suppression & Compliance Lists (`suppression`) | Partial flow | Business suppression records and fresh pre-send enforcement; normalized email lookup. | No domain suppression, provider complaint ingestion or complete bounce classification. |
| 9 | Preference Center (`preferences`) | Partial flow | Hosted token page for locale, frequency preference, pause and unsubscribe. | Frequency is stored, not enforced by a digest engine; no topic lists or reason workflow. |
| 10 | Consent & Compliance (GDPR/CAN-SPAM/CASL/PECR) (`consent`) | Partial flow | Consent records, signed double opt-in, footer/unsubscribe guards and contact export/erase. | No complete statutory rules engine, DSR case management, retention policies or legal document workflow. |
| 11 | Lead Scoring (`scoring`) | Partial flow | Contact score field and bounded score automation action. | No event rule engine, decay, grading or hot-lead/AI alerts. |
| 12 | Contact Enrichment (`enrichment`) | Adapter | Tenant-labelled, module-gated AI Catalog text adapter. | No completed enrichment UI, scraping queue, consent/audit workflow or company data synchronization. |
| 13 | Duplicate Management (`duplicates`) | Catalog only | Unique email and import duplicate prevention. | No duplicate candidate/merge UI. |
| 14 | Campaigns (Regular broadcasts) (`campaigns`) | Partial flow | Campaign wizard, audience/sender/content, approval/review, UTC schedule, queue, pause/resume/cancel and sandbox/live SMTP. | No complete inbox test workflow or all brief-level delivery options. |
| 15 | A/B & Multivariate Testing (`experiments`) | Catalog only | Catalog and module flag. | No experimental split, automatic winner or significance engine. |
| 16 | Scheduled & Recurring Campaigns (`scheduling`) | Partial flow | One-time UTC campaign schedule through delayed jobs. | No contact-timezone scheduling, recurring digests or RSS ingestion. |
| 17 | Send-Time Optimization (`send-time`) | Catalog only | Catalog and module flag. | No per-contact send-time model or scheduling. |
| 18 | Throttling & Warm-up (`warmup`) | Partial flow | Business per-minute attempt limit and monthly allowance checks. | No IP/domain warm-up plans, daily budgets or bounce/complaint-driven pauses. |
| 19 | Transactional Email (`transactional`) | Catalog only | Signup confirmation uses a queued mail path. | No general transactional API, SMTP relay, separate stream or resend UI. |
| 20 | Plain-text & Multipart (`multipart`) | Partial flow | Generated plain text and HTML in live SMTP payloads. | No AMP or dark-mode/client rendering previews; part of the campaign sender. |
| 21 | Campaign Approval Workflow (`approvals`) | Partial flow | Role-checked approval, review request, changes-requested action/reason, approval invalidation on edit and audit. | No configurable thresholds or multi-stage reviews; approval remains required for every send. |
| 22 | Campaign Calendar (`calendar`) | Partial flow | Month grid of branch-scoped scheduled campaigns with previous/next navigation. | No week view, automation calendar or drag-to-reschedule. |
| 23 | Campaign Folders, Duplicate, Archive, Bulk actions (`campaign-folders`) | Partial flow | Campaign duplicate action resets status, schedule and counters. | No folders, archive or complete bulk-action workflow. |
| 24 | Pre-send Checklist & Spam Score (`checklist`) | Partial flow | Send guard plus advisory checklist for links/protocols, alt text, subject/preheader, urgent wording and image-heavy content. | No network link checks, numerical spam/reputation score, DKIM test or AI review; static recommendations are advisory. |
| 25 | Test Sends & Inbox Preview (`test-sends`) | Partial flow | Protected preview of rendered delivery-log messages. | No seed-list sending, inbox placement or multi-client/device preview service. |
| 26 | SMS & WhatsApp Campaigns (omnichannel add-on) (`sms`) | Catalog only | Catalog and module flag. | No SMS or WhatsApp delivery drivers/campaigns. |
| 27 | Web Push Notifications (optional) (`push`) | Catalog only | Catalog and module flag. | No web push subscriptions or delivery. |
| 28 | Drag-and-Drop Email Builder (`email-builder`) | Partial flow | Reorderable heading, text, button, image and divider blocks rendered to email HTML. | No full canvas, columns, undo/redo, MJML pipeline or comprehensive email-client validation. |
| 29 | Code Editor (`code-editor`) | Partial flow | HTML textarea, template rendering and sanitizer. | No syntax-highlighted editor, live side-by-side preview, CSS inliner or minifier. |
| 30 | Template Library (`templates`) | Partial flow | Template CRUD, categories, shared templates and 72 seeded records across businesses. | Seeds reuse six content structures; no 60 unique professional designs, favorites or JSON interchange. |
| 31 | Dynamic Content & Personalization (`personalization`) | Partial flow | Escaped field merge tags, fallback values and unsubscribe/preference URLs. | No conditional content, loops, live blocks or personalized images. |
| 32 | Media Library (`media`) | Catalog only | Image URL block exists in templates. | No media uploads/library, optimization, CDN or AI images. |
| 33 | Brand Kit (`brand`) | Catalog only | Workspace and branch settings hold basic defaults. | No visual brand-kit editor or automatic template styling system. |
| 34 | Snippets / Saved Blocks / Signatures (`snippets`) | Basic records | Snippet/signature CRUD with content. | No complete block insertion/reuse integration in the template editor. |
| 35 | Multi-language Content (`translations`) | Partial flow | Unicode data, locale preference, RTL public layout support and text translation request type. | No full content variants with language-based email selection. |
| 36 | AI Content Studio (`ai-studio`) | Partial flow | Queued AI text requests, provider/model selection, history, token/cost counters and disabled fallback. | No image generation, forecasting, brand memory or every requested specialist tool; no live provider validation. |
| 37 | Visual Automation Builder (`automation`) | Partial flow | Ordered-step editor, versions/snapshots, waits, tag conditions, bounded loops and execution history. | No graphical canvas, full goal/exit engine, arbitrary branching or sample-contact debugger. |
| 38 | Triggers (`triggers`) | Partial flow | Contact-created, confirmed-form and manual event enrollment. | No broad ecommerce/date/behavior/segment/booking trigger suite. |
| 39 | Actions (`actions`) | Partial flow | Email, wait, add/remove tag, score, selected field update, task, condition and unsubscribe actions. | No SMS, list/branch moves, webhook, notifications, deal or child-flow actions. |
| 40 | Prebuilt Automation Recipes (`recipes`) | Partial flow | Nine named sample automation records per business. | Samples reuse tag/wait/score/task steps; not complete ready-to-send recipes for all listed scenarios. |
| 41 | Autoresponders & Drip Courses (`drip`) | Partial flow | Per-contact runs support relative waits and template email actions. | No dedicated course/drip editor or enrollment calendar. |
| 42 | Customer Journeys Map (`journeys`) | Catalog only | Catalog and module flag. | No journey/funnel visualization. |
| 43 | Event Tracking API & JS Snippet (`events`) | Partial flow | Authenticated API accepts three supported enrollment event names. | No browser SDK, general event store, page tracking or arbitrary custom event payloads. |
| 44 | Webhooks (in & out) (`webhooks`) | Catalog only | Catalog and module flag. | No signed inbound/outbound webhook delivery system. |
| 45 | Workflow Tasks & Alerts (`workflow-tasks`) | Partial flow | Automation creates tenant/branch-scoped tasks. | No notification destinations, escalation or alert routing. |
| 46 | Signup Forms Builder (`forms`) | Partial flow | Hosted signup form CRUD, publication, consent checkbox, honeypot, throttling and queued signed double opt-in. | No popup/embedded targeting engine, A/B tests, CAPTCHA integrations or custom field builder. |
| 47 | Landing Page Builder (`landing-pages`) | Partial flow | Landing-page records with sanitized HTML, publication and signup-form link. | No block canvas, custom domains, arbitrary slug routing, versioning or A/B tests. |
| 48 | Surveys & Polls (`surveys`) | Partial flow | Published single-question surveys with selectable options and response persistence; session duplicate guard. | No full NPS/CSAT reporting, multi-question branching or field/tag result actions. |
| 49 | Lead Magnets / Content Gate (`lead-magnets`) | Catalog only | Catalog and module flag. | No gated download and delivery workflow. |
| 50 | Referral & Viral Programs (`referrals`) | Catalog only | Catalog and module flag. | No referral attribution or reward program. |
| 51 | Giveaways / Contests (`giveaways`) | Catalog only | Catalog and module flag. | No giveaway entry/winner workflow. |
| 52 | Webinar / Event Registration (`webinars`) | Partial flow | Published event page, capacity-checked registration, duplicate handling and ICS download. | No reminder/follow-up scheduling or attendance import. |
| 53 | Coupons & Promo Codes (`coupons`) | Partial flow | Coupon CRUD and atomic redemption service with expiry, usage limit and per-contact uniqueness. | Redemption is not wired into a public checkout/API; no per-contact code generation workflow. |
| 54 | QR Codes & Short Links (`short-links`) | Partial flow | Short-link CRUD, published redirect and click counter. | No QR images, custom short domain or detailed link analytics. |
| 55 | Social Sharing & "View in Browser" pages (`archives`) | Catalog only | Protected internal delivery preview exists. | No published campaign archive, public message view or social sharing controls. |
| 56 | Mini-CRM (`crm`) | Basic records | Deal CRUD with stages, value, expected close and notes; task records. | No company CRM, kanban, integrated timeline, owner pipeline or win/loss analysis. |
| 57 | Shared Inbox / Reply Management (`inbox`) | Catalog only | Support tickets have a basic response field. | No IMAP ingestion, shared inbox threads, assignments, SLA or reply classification. |
| 58 | Product Catalog (`products`) | Basic records | Product CRUD with SKU, description, minor-unit price, currency and stock. | No variants, images, categories, imports or storefront connector. |
| 59 | Orders & Revenue Attribution (`orders`) | Basic records | Order CRUD with contact/campaign reference, total, currency and status. | No automated attribution, LTV, RFM, multi-touch analysis or order synchronization. |
| 60 | E-commerce Connectors (`commerce-connectors`) | Catalog only | Catalog and module flag. | No Shopify, WooCommerce, Magento or cart connector. |
| 61 | Recommendations Engine (`recommendations`) | Catalog only | Catalog and module flag. | No product recommendation service. |
| 62 | Subscriptions / Memberships (optional) (`memberships`) | Catalog only | Catalog and module flag. | No paid membership lifecycle. |
| 63 | Sending Identities & Domains (`identities`) | Partial flow | Sender CRUD, sandbox verification and live TXT ownership/SPF/DMARC checks with expiry/invalidation. | No DKIM selector verification, BIMI, return-path wizard or reputation analysis. |
| 64 | SMTP & Provider Connections (`providers`) | Partial flow | Installation-wide Laravel SMTP adapter behind live-mode guard. | No tenant provider records, HTTP provider drivers, routing, failover or provider health UI. |
| 65 | Feedback | Partial flow | Generic HMAC receipts, timestamp validation, deduplication, queued processing, hard-bounce/complaint suppression, secret rotation. | No native provider translators/IMAP parser; no automatic soft-bounce resend. |
| 66 | Deliverability Dashboard (`deliverability`) | Catalog only | Basic campaign counters are visible. | No deliverability dashboard, DNSBL monitoring or DMARC aggregate processing. |
| 67 | Dedicated IP / IP Pool Management (optional) (`ip-pools`) | Catalog only | Catalog and module flag. | No IP pool management or warm-up planner. |
| 68 | Link Tracking & Custom Tracking Domain (`tracking`) | Partial flow | Signed pixel/click tracking, stored redirect destinations, unique counters and basic bot heuristics. | No custom tracking domains, reliable MPP classification, UTM engine or complete bot filtering. |
| 69 | Unsubscribe Handling (`unsubscribe`) | Partial flow | Hosted GET/POST unsubscribe, business suppression and RFC 8058-style headers. | No reason collection; unsubscribe remains active even when modules are disabled. |
| 70 | Email Queue Engine (`queue`) | Partial flow | Chunked audience preparation, unique message keys, queued processing, durable live claim, rate/quota checks and pause/resume. | Uncertain SMTP outcomes need manual review; no exactly-once delivery, provider dead-letter UI or measured million-message capacity. |
| 71 | Campaign Reports (`campaign-reports`) | Partial flow | Campaign counters, open rate, date filters, dashboard charts, CSV and queued campaign PDF report. | Generic signed receipts provide delivery events; no heatmaps, client/geography, A/B or comprehensive conversion analysis; seeds are illustrative. |
| 72 | Automation Reports (`automation-reports`) | Partial flow | Run history and state are persisted. | No dedicated per-step/revenue automation report UI. |
| 73 | Contact & Audience Analytics (`audience-reports`) | Partial flow | Audience totals and subscription-status chart. | No growth cohorts, churn/engagement tier or list-health analysis suite. |
| 74 | Revenue & ROI Dashboard (`revenue-reports`) | Catalog only | Order totals and campaign references exist. | No ROI/cost attribution/LTV dashboard. |
| 75 | Real-time Activity Feed (`activity`) | Catalog only | Audit and delivery logs are queryable. | No live broadcast activity feed. |
| 76 | Custom Reports & Dashboards (`report-builder`) | Catalog only | Fixed dashboard widgets exist. | No user-designed dashboards or custom report builder. |
| 77 | Scheduled Reports (`scheduled-reports`) | Catalog only | Ad hoc report export jobs exist. | No scheduled report delivery or branded client scheduling. |
| 78 | Exports (`exports`) | Partial flow | Queued contacts/campaign CSV and campaign PDF, private expiring user exports, profile JSON and invoice PDF. | No XLSX or universal export/API metrics layer; invoice PDF remains synchronous. |
| 79 | AI Insights (`ai-insights`) | Catalog only | General AI text studio exists. | No analytical insight generation or anomaly detection. |
| 80 | Benchmarks (`benchmarks`) | Catalog only | Catalog and module flag. | No curated reference benchmark or comparative benchmark report. |
| 81 | Team Management (`team`) | Partial flow | Business user CRUD, branch assignment, role defaults/custom permissions and optional TOTP. | No invitation acceptance, SSO, session inventory or organization MFA enforcement. |
| 82 | Agency / Client Portal (`client-portal`) | Partial flow | Branch-scoped client login and read permissions. | No distinct client portal, branded domains, approval exchange or client billing. |
| 83 | Approvals & Comments (`comments`) | Partial flow | Campaign review-comment history, authorized posting and changes-requested reason. | No replies/thread nesting, mentions or template/automation comment coverage. |
| 84 | Tasks & Project Board (`tasks`) | Basic records | Task CRUD with status, priority and due date; automation task creation. | No kanban/project board, assignment or campaign-linked project workflow. |
| 85 | Attendance & HR (optional module) (`attendance`) | Basic records + adapter | Manual attendance CRUD and gated device gateway adapter. | No hardware sync UI, shift/leave/payroll pipeline or hardware validation. |
| 86 | Time Tracking & Productivity (optional) (`time-tracking`) | Basic records | Time entry CRUD. | No timer, productivity analytics or billing integration. |
| 87 | Notifications Center (`notifications`) | Partial flow | Filament action notifications and seeded/core catalog flag. | No persistent multi-channel notification center or user preferences. |
| 88 | Audit Log & Activity Log (`audit`) | Partial flow | Business-filtered audit page and explicit logging for key actions. | Not every model mutation is audited; no retention/export suite or tamper-evident ledger. |
| 89 | Knowledge Base / Help Center (`knowledge-base`) | Partial flow | Article CRUD, publication and sanitized public article page. | No complete search/category/feedback or AI help system; public routes use IDs. |
| 90 | Support Tickets (optional) (`tickets`) | Basic records | Ticket CRUD with requester, status, priority and response. | No customer submission portal, conversation threads or platform-support routing. |
| 91 | Plans & Subscriptions (`plans`) | Basic records | Five sample plan records and pricing page; business monthly email allowance. | No checkout, subscription state, trial lifecycle, proration or complete plan entitlement administration. |
| 92 | Payment Gateways (`payments`) | Catalog only | Catalog and module flag. | No payment provider, settlement, payment approval or refund flow. |
| 93 | Invoices & Receipts (`invoices`) | Partial flow | Invoice line items, integer minor-unit totals/tax rounding, statuses and PDF download. | No automated billing, credit notes or payment reconciliation. |
| 94 | Usage Metering & Quotas (`usage`) | Partial flow | Monthly email and AI usage counters and guard limits. | No full contact/branch/user/module/API enforcement, overages or billing alerts. |
| 95 | Email Credits / Pay-as-you-go (optional) (`credits`) | Catalog only | Catalog and module flag. | No purchasable credit ledger/packs. |
| 96 | Affiliate / Reseller Program (optional) (`affiliates`) | Catalog only | Catalog and module flag. | No affiliate commissions or payout workflow. |
| 97 | Super Admin Console (`superadmin`) | Partial flow | Super Admin account role and restricted Horizon access. | No cross-business console, impersonation, abuse monitoring or platform billing administration. |
| 98 | Public REST API (`api`) | Partial flow | Sanctum API tokens with abilities/expiry/revocation; contacts list/create and supported trigger endpoint. | No broad CRUD API or generated OpenAPI schema; token UI issues current-user tokens only. |
| 99 | Webhooks Manager (`webhook-manager`) | Catalog only | Catalog and module flag. | No webhook subscription, replay or delivery manager. |
| 100 | Integrations Hub (`integrations`) | Catalog only | Package contracts are available. | No OAuth connector hub or external business-service synchronizers. |
| 101 | Data Scraping & Lead Intelligence (`scraping`) | Adapter | AI Catalog text adapter is bound. | No URL crawler, directory scraper, website-to-template or enrichment job workflow. |
| 102 | Localization (`localization`) | Partial flow | Unicode storage, locale settings, sample English/Bangla/Arabic data and RTL layout support. | Admin/public strings are mostly English; no complete translations or translation management UI. |
| 103 | White-label & Custom Domains (`white-label`) | Catalog only | Basic workspace/branch settings exist. | No custom-domain provisioning or full white-label tenant branding. |
| 104 | Backup, Restore & Data Portability (`backups`) | Partial flow | Contact/profile exports and documented server backup procedures. | No scheduled backup/restore product UI, per-business full archive or migration mapping helpers. |
| 105 | Security Center (`security`) | Partial flow | Encrypted TOTP, replay protection, recovery codes, scoped API tokens, password checks, throttles and security headers. | No SSO, IP allowlist, forced-MFA policies, device/session management or login alerts. |
| 106 | Feature Flags & Experiments (`feature-flags`) | Catalog only | Business/branch module flags exist. | No independent experiment/feature-flag management service. |
| 107 | Health & Monitoring (`health`) | Partial flow | Basic health endpoint/command and installed Horizon. | No queue-lag/provider-latency/throughput observability dashboard or alert integrations. |
| 108 | Onboarding | Partial flow | Registration/presets plus seven-step checklist reading scoped records. | No provider-credential wizard or in-app tours. |

## Work needed for the complete brief

The remaining work is larger than UI polish. Complete delivery requires implementing each catalog-only workflow, extending the partial flows to their stated scope, and making the module boundaries consistent across routes, widgets, permissions, jobs and public surfaces. Major unfinished areas include native provider integrations/advanced deliverability, commerce integrations, all messaging channels beyond SMTP, experiment/warm-up engines, payment subscriptions, graphical builders, fully translated interfaces, collaboration, white-label domains, and platform-wide operations.

The original seed-quality expectations also remain larger than the current examples: named automation samples and repeated template structures must become distinct, functional recipes and designs. Every newly completed module needs its own tenant/branch boundary, authorization, disabling/re-enabling, external-failure and workflow tests. Existing broad tests are not a substitute for full acceptance coverage of 108 modules.

Performance targets remain unmeasured. Chunking and cursor iteration reduce memory pressure in implemented jobs, but do not prove throughput or database behavior at millions of records. Validate MySQL/Redis under representative concurrent load, SMTP acceptance/reconciliation, retention/backup restoration and the actual target browsers before operational rollout.

## Verification record

The repository provides a PHPUnit feature suite, Laravel Pint and Larastan configuration. Execute the commands in the root README on the current checkout; this document does not hard-code a stale passing test count. A test with a mocked external driver proves local control flow, not successful behavior of a live service. Deployment and rendering evidence should be recorded for the environment being released.

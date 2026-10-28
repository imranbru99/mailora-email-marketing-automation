# Operations runbook

## Processes

The application needs HTTP, a queue worker and the scheduler. The default local setup uses SQLite and Laravel's database queue/cache/session stores. For a deployed installation use [DEPLOYMENT.md](DEPLOYMENT.md), persistent storage, supervision and backups.

```bash
php artisan queue:work --tries=5 --timeout=240
php artisan schedule:work
```

In production invoke `php artisan schedule:run` every minute with cron. The queue configuration uses `retry_after=300` seconds with a 240-second worker timeout; export jobs have a 180-second timeout. Preserve the ordering `retry_after > worker/job timeout` when changing them and account for SMTP timeout. Restart long-running workers after code or configuration changes:

```bash
php artisan queue:restart
```

Horizon is installed and requires Redis queues. Set `QUEUE_CONNECTION=redis` and run `php artisan horizon` under supervision instead of another worker consuming the same queue. The dashboard is restricted to Super Admin. Configure environment-specific workers and timeouts before production use.

## From sandbox to SMTP

Keep sandbox enabled until the local workflow is verified. Sandbox messages are stored in the delivery log and consume the monthly allowance; campaign SMTP is not called. Other Laravel mail paths use the configured mailer, so keep `MAIL_MAILER=log` in a local demo.

1. Set a public HTTPS `APP_URL`; configure DNS, TLS and the reverse proxy. Signed URLs must use the external origin.
2. Set `MAIL_MAILER=smtp`, host, port, scheme, username and password through environment/secrets management, using your provider's documented settings.
3. Create an identity with a sender address, matching domain and postal address. Publish the ownership TXT challenge at `_mailora.<domain>`, an appropriate SPF record and a DMARC record. Run its DNS verification action. Verification must be less than 30 days old. Changing the email/domain invalidates verification.
4. Configure DKIM, return-path and provider account approval separately. The current ownership checker does not prove DKIM alignment or guarantee deliverability.
5. Start with an audience containing only addresses you control and documented consent. Approve the campaign, then set `MAIL_SANDBOX=false`, clear/rebuild configuration as appropriate and restart workers before queuing it.
6. Inspect delivery logs and provider records after the controlled test. Re-enable sandbox if configuration or outcomes are unclear.

The adapter supports the installation-wide Laravel SMTP connection. Generic HMAC-signed bounce/complaint/delivery receipts are supported; see [FEEDBACK.md](FEEDBACK.md). There is no per-tenant SMTP credential vault, weighted routing, failover, or native vendor webhook translation. Sandbox verification is insufficient for live delivery.

## Message states and recovery

| State | Meaning | Operator action |
|---|---|---|
| `pending` | No delivery claim made | Normal queued work can revisit after eligibility is restored |
| `sandbox` | Rendered locally; campaign SMTP not called | Inspect preview; do not interpret as inbox delivery |
| `suppressed` | Recipient failed eligibility | Inspect reason; preserve valid opt-outs |
| `dispatching` | Live attempt durably claimed; no final outcome | Investigate a possible process crash or active worker |
| `sent` | SMTP transport returned successfully | Verify provider/inbox evidence separately |
| `uncertain` | Attempt or final bookkeeping raised an exception | Campaign pauses; reconcile with provider before recovery |

`send:resume` requeues pending records of active campaigns; it does not reset `dispatching`, `uncertain`, `sent` or `sandbox`. Resuming a paused campaign also does not automatically retry uncertain delivery.

```bash
php artisan send:resume BUSINESS_ID
php artisan queue:failed
```

For `uncertain` or stale `dispatching`, use the stable message public ID and SMTP Message-ID to investigate provider logs. Preserve evidence and the current record. Do not bulk reset these rows to `pending`: the provider might already have accepted them. There is no reconciliation UI; a reviewed, tenant-scoped maintenance operation is needed after establishing the outcome. If evidence is unavailable, leave it unresolved rather than assuming it was never sent.

The queue can retry failures before the durable send claim. Retrying a failed Laravel job does not authorize another SMTP attempt after a claim. SMTP has no universal exactly-once guarantee.

## Checks and quotas

Every recipient is checked at processing time. A campaign must be sending, approved, have a verified sender/postal address and include `{{unsubscribe_url}}`. A contact must be subscribed with consent and a legal basis, have no active pause, and not be on the business suppression list. These checks also apply to automation email steps.

A business has a monthly email limit. Usage is grouped by `YYYY-MM`. `send_per_minute` defaults to 120 in effective settings. This is a business limit; there are no hourly/day warm-up schedules or provider/IP budgets. A frequency preference is stored for operators; a digest scheduler does not automatically enforce it.

Every campaign requires approval. Review requests, changes-requested reasons and campaign comments are available through their modules. There is no configurable audience threshold or multi-stage approval chain. The pre-send checklist offers static content recommendations; the delivery guard enforces the essential consent/sender/unsubscribe rules. Disabling campaigns prevents later queued work from sending but cannot recall messages already handed to SMTP.

## Imports and consent

Audience import accepts CSV with `email,first_name`; optional headers are `last_name,company,phone`. Uploads are private, limited to 10 MB by the form, and queued. Emails are normalized; invalid/duplicate rows are skipped and a bounded error list is stored. XLSX/SFTP/Google Sheets imports are not implemented.

Imported and API-created contacts are pending. Presence in a spreadsheet is not treated as proof of marketing consent. Use the signup/confirmation flow or record valid consent through the authorized contact workflow. Existing opt-outs are not silently revived by signup.

## Export files

Reports → Exports queues contact/campaign CSV files; Campaign reports can queue an individual PDF. Files are stored privately and downloads are restricted to the requesting user in the appropriate tenant/branch, with current module and permission checks. Ready files expire after 24 hours. The scheduler runs `exports:prune` hourly to remove expired files. A failed export shows an error state; inspect server logs for the cause without exposing sensitive data.

## Modules and diagnostics

Settings → Modules changes business flags and applies 13 presets. Applying a preset replaces the current selection; review it before applying to an active workspace. Data survives disablement. Catalog-only entries remain catalog entries even when enabled.

```bash
php artisan modules:sync
php artisan modules:list
php artisan modules:enable BUSINESS_ID campaigns
php artisan modules:disable BUSINESS_ID tracking
php artisan health:check
php artisan automations:tick
php artisan route:list
```

Dependencies are enabled automatically. Disable dependents first when a prerequisite cannot be disabled. Branch overrides are understood by the registry; the module page is not a complete branch configuration editor. Unsubscribe continues working regardless of toggles.

`/health` and `health:check` provide basic database checks, not complete worker/provider observability. They do not prove SMTP delivery, queue performance or integration health. Check Laravel logs without exposing contact content, credentials or bearer tokens in tickets.

`demo:seed` creates samples outside production. `demo:reset` requires demo mode, refuses production, and deletes database tables before reseeding. It prompts unless `--force` is supplied. `demo:simulate-engagement` modifies sample campaign metrics, not real tracking events. Never run demo commands against operational customer data.

## Security and maintenance

Use `APP_ENV=production`, `APP_DEBUG=false`, secure session cookies and HTTPS. Preserve APP_KEY securely alongside recovery procedures; losing it makes encrypted TOTP secrets unreadable. Do not rotate it casually on a populated installation. Disable demo shortcuts and remove demo accounts in production.

Enable TOTP in Security and save recovery codes securely. Codes are shown once and consumed once. Organization enforcement, SSO, device inventory and IP allowlists remain outside scope.

Commit Composer/npm lockfiles. Run tests, static analysis and asset build before deployment. Review dependency upgrades explicitly, especially packages from development branches. Audit coverage is not exhaustive across all CRUD actions and is not a tamper-evident compliance ledger.

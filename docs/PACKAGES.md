# Package integrations

Required packages are installed through Composer and pinned by `composer.lock`. Several are development branches: deploy the lockfile and review upgrades explicitly. A package dependency or adapter is not equivalent to all features requested for that package.

Contracts live in `app/Contracts`, adapters in `app/Support/Integrations`, and bindings in `AppServiceProvider`. Package authorship, licenses and notices remain in the dependencies. First-party application developer: Imran Ahmed, [imrandev.bd](https://imrandev.bd), [me@imrandev.bd](mailto:me@imrandev.bd).

| Package | Contract / adapter | Actual integration and limit |
|---|---|---|
| `imrandevbd/drutogoti` | `PerformanceInspector` / `DrutogotiAdapter` | Reads unresolved suggestions if enabled and migrated. Package migrations are included. No automatic optimization pass or measured performance improvement is claimed; there is no completed admin performance console. |
| `imrandevbd/laravel-admin-panel` | `AdminTheme` / `AdminPanelAdapter` | Uses the package's public palette data for the Filament theme. Its separate admin provider is excluded from autodiscovery to avoid mounting an unscoped second admin surface. The actual application shell is Filament; this is not a complete nested admin-panel integration. |
| `imrandevbd/laravel-ai-catalog` | `CatalogEnricher` / `CatalogAdapter` | Module-gated, tenant-labelled text-to-catalog adapter. No end-user company scraper, URL crawler, enrichment queue or website-to-template workflow is wired to it. |
| `imrandevbd/laravel-ai-hub` | `TextGenerator` / `AiHubAdapter` | Queued text generation through `AiService`, per-business settings, generation history and token/cost counters. Provider credentials must be configured. No image generation, forecasting, or full autonomous campaign creation. |
| `imrandevbd/laravel-attendance-hub` | `AttendanceGateway` / `AttendanceAdapter` | Module-gated device connect/pull/disconnect adapter, plus manual attendance records. No device provisioning UI, scheduled hardware synchronization, shift/leave rules or payroll processing. No device was contacted for validation. |
| `imrandevbd/laravel-unicode-pdf` | `ReportPdf` / `UnicodePdfAdapter` | A4 Blade-view PDF rendering for invoices and campaign exports. Supports the configured package engine/fonts. Multilingual shaping and complex layout require rendered validation for the selected fonts/engine. |
| `imrandevbd/laravel-universal-slug` | `Slugger` / `UniversalSlugAdapter` | Wraps Unicode-aware slug generation. Public growth routes currently use opaque public IDs; the adapter is not yet a complete custom-domain/slug routing layer. |

## AI flow

AI Studio queues a generation record. `GenerateCopy` establishes its tenant context and calls `AiService`. The service checks its module, effective `ai_enabled` setting and a monthly token allowance. A claim reserves a conservative token allowance before the provider call. Completed requests record output and reported usage/cost; disabled or failed requests show a status instead of fabricated copy.

The adapter calls `AIHub::provider(...)->prompt(...)->maxTokens(1000)` and optionally selects a model. Credentials are installation configuration, while effective business settings select whether AI is enabled, its provider/model and token limit. There is no tenant credential vault. Inspect the installed package's provider configuration before enabling a real account.

Failed requests can retain reserved quota because the external outcome is uncertain. Cost counters are rounded to minor USD units and depend on provider/package reporting; they are not an invoice-grade reconciliation system. Queue workers are required. Live paid-provider calls were not used for the local tests.

The catalog adapter has no completed user-facing enrichment flow. It should not be called directly as a shortcut around `AiService` when adding a feature; implement consistent tenant budgets, queueing and audit behavior first.

## PDF setup

The installed Unicode PDF package includes a native engine. Optional alternative engines include Dompdf, mPDF, TCPDF and Browsershot and require their own dependencies. Diagnose the actual installation before selecting an engine:

```bash
php artisan unicode-pdf:diagnose
php artisan unicode-pdf:fonts
```

Fonts must cover the scripts in your documents. The package provides `unicode-pdf:font:install` for font setup; follow its installed README and keep the downloaded fonts' licenses. Test Bangla/Arabic/CJK documents with the intended font stack and inspect the generated PDF. Do not infer complete multilingual rendering support from a successful ASCII invoice alone.

The adapter requires the `exports` module. Invoice download also checks its invoice route permissions/module. Campaign report exports run through the queued export workflow and private storage. Invoice download is currently synchronous. No remote image/font fetching is needed by the shipped report templates.

## Optional package surfaces

The default environment disables package-specific AI Studio/API and attendance ADMS/Filament interfaces (`AI_HUB_UI`, `AI_HUB_FILAMENT`, `AI_CATALOG_API`, `AI_CATALOG_STUDIO`, `ATTENDANCE_ADMS_ENABLED`, `ATTENDANCE_FILAMENT_ENABLED`). Use the application's tenant-aware entry points. Enabling a package's separate interface requires reviewing its authentication, tenancy and authorization first.

Mail delivery is an additional application contract, `MailDelivery`, implemented by `SmtpDelivery`. It uses Laravel's configured SMTP transport and never runs in campaign sandbox mode. See [OPERATIONS.md](OPERATIONS.md) for its persisted claim and ambiguous-failure handling.

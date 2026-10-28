# Deployment and operations

Mailora is developed by [Imran Ahmed](https://imrandev.bd). The supplied stack runs PHP 8.3 FPM, nginx, MySQL 8.4, Redis 7.4, a Horizon worker, and a scheduler. The app is in sandbox delivery mode by default. See [STATUS.md](STATUS.md) for implementation scope before deploying.

## Local development

Requirements: PHP 8.3 or 8.4, Composer 2, Node 22.12+, npm, SQLite or MySQL, and the PHP extensions listed in `Dockerfile`. GD is needed for PDF/image functionality. Redis is required for Horizon; the regular Laravel queue worker also supports the database queue used by `.env.example`.

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan app:install
npm ci
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Run these in separate terminals:

```sh
php artisan queue:work --sleep=1 --tries=3 --timeout=240
php artisan schedule:work
```

The public website is at `/`, the panel at `/admin`, and registration at `/admin/register`. For demo data, set `APP_DEMO=true` in the local `.env`, keep `APP_ENV=local` and `MAIL_SANDBOX=true`, then run `php artisan demo:seed`. Demo credentials are public; never seed them into an Internet-facing production database. `demo:reset --force` destroys the local demo database and is deliberately unavailable in production.

For frontend development use `npm run dev`. Filament's packaged assets and the public site's stylesheet are also included; Vite builds the application's JS/CSS entry points.

## Container installation

Use Docker Engine with Compose v2 and OpenSSL. Run all commands from the repository root. `docker/configure.sh` creates `docker/.env` with a random application key, database passwords, and Redis password, using restrictive file permissions. It refuses to overwrite an existing file. Keep this file outside source control and back up its application key securely.

```sh
sh docker/configure.sh
```

Review `docker/.env`. Set `APP_URL` to the URL you will actually use. The default web listener is `127.0.0.1:8080`, appropriate for local evaluation or a host reverse proxy. Then:

```sh
docker compose --env-file docker/.env config --quiet
docker compose --env-file docker/.env build app web
docker compose --env-file docker/.env up -d mysql redis
docker compose --env-file docker/.env run --rm app php artisan migrate --force
docker compose --env-file docker/.env up -d
docker compose --env-file docker/.env exec app php artisan health:check
```

Open `http://localhost:8080/admin/register` and create the first business owner. Container startup does not run migrations, create demo users, rotate keys, or reset data. Apply schema migrations explicitly during deployments.

Always include `--env-file docker/.env`; otherwise Compose may read the local development `.env`. The app, worker and scheduler share one image, environment configuration and persistent storage. Their processes run as `www-data`. The web process runs as nginx's unprivileged user. MySQL and Redis are available only on the Compose network, without host port mappings.

Three named volumes persist data: `mailora_mysql-data`, `mailora_redis-data` and `mailora_app-storage`. The last holds public uploads, private CSV imports, private exports and logs. nginx mounts storage read-only and serves only the `storage/app/public` symlink; private imports and exports are not public files. `docker compose down` retains these volumes. Adding `--volumes` deletes them.

## HTTPS and live delivery

Terminate TLS at an operator-managed reverse proxy. Keep the app bound to loopback when using a host proxy; if using a container proxy, place it on the appropriate private network. Set:

```dotenv
APP_URL=https://mail.example.com
SESSION_SECURE_COOKIE=true
TRUSTED_PROXIES=172.20.0.1
```

The proxy address above is illustrative: use the actual IP or CIDR of the upstream proxy, not a wildcard or every Internet address. Forward `Host`, `X-Forwarded-For`, `X-Forwarded-Proto`, and `X-Forwarded-Port`. Trust only the proxy network you control. Recreate app, worker and scheduler after environment changes so every process uses the same URL and settings.

Sandbox sends create delivery records without contacting SMTP. To enable live SMTP deliberately, configure `MAIL_MAILER=smtp`, `MAIL_SCHEME=smtp` (STARTTLS) or `smtps` (implicit TLS), the actual `MAIL_HOST`, `MAIL_PORT`, username/password and sender address, then set `MAIL_SANDBOX=false`. Verify the sending domain in the panel, supply a business postal address, and approve campaigns. The application requires HTTPS and recent DNS verification for live sending. Configure your mail provider's SPF, DKIM and DMARC records; the application does not provision the provider or its DKIM keys.

SMTP has no universal exactly-once delivery guarantee. A worker records an attempt before sending; an ambiguous transport failure is held as `uncertain` and the campaign is paused. Inspect the provider's delivery logs before manually resolving uncertain attempts. Do not change these rows to pending just to make a queue look empty. No real recipient delivery was used during local validation.

The default `log` mailer writes signup confirmations to application logs. Use SMTP when real subscribers need confirmation email, and treat logs as sensitive. Do not expose diagnostic logs publicly.

## Workers, scheduling and diagnostics

`worker` runs Horizon on the Redis `default` queue. The configured worker timeout is 240 seconds; Redis visibility retry is 300 seconds, and Compose allows 250 seconds for shutdown. CSV exports have an individual 180-second timeout. For a local database queue, set `DB_QUEUE_RETRY_AFTER=300` as well. Keep the visibility interval longer than the worker/job timeout when changing these settings. Run one scheduler instance per installation; the schedule uses a shared Redis lock for overlapping automation ticks.

```sh
docker compose --env-file docker/.env ps
docker compose --env-file docker/.env logs --tail=100 app worker scheduler
docker compose --env-file docker/.env exec worker php artisan horizon:status
docker compose --env-file docker/.env exec app php artisan queue:failed
docker compose --env-file docker/.env exec app php artisan schedule:list
curl --fail http://127.0.0.1:8080/up
```

`/up` checks that Laravel boots. `health:check` also checks the database; neither proves DNS, SMTP delivery, queue latency or provider reputation. Monitor these separately. Horizon access is restricted to the superadmin role. Do not grant that role merely to view business campaign reports.

Review failed jobs before retrying a specific ID with `queue:retry`. `send:resume BUSINESS_ID` reconstructs pending work for active campaigns while preserving message idempotency keys. It does not resolve `uncertain` SMTP attempts. A disabled module is rechecked by jobs, so queued campaign work cannot bypass a subsequent module disable.

## Upgrades and rollback

Back up the database, persistent storage, Redis queue data and application key before applying a release. Build images first, then schedule a short maintenance window:

```sh
docker compose --env-file docker/.env build app web
docker compose --env-file docker/.env exec app php artisan down
docker compose --env-file docker/.env stop worker scheduler
docker compose --env-file docker/.env run --rm app php artisan migrate --force
docker compose --env-file docker/.env up -d --force-recreate app web worker scheduler
docker compose --env-file docker/.env exec app php artisan up
```

Verify login, one read-only report, Horizon and `/up`. An image rollback does not reverse database migrations. Restore a consistent backup in an isolated environment first if the old image is incompatible with the new schema. Do not rotate `APP_KEY` casually: encrypted two-factor secrets, sessions and signed URLs depend on it.

## Backups

Store backups outside the repository on encrypted storage with restricted access. Take a consistent backup during maintenance with workers and scheduler stopped. For example, after `artisan down` and stopping workers:

```sh
umask 077
mkdir -p backups
docker compose --env-file docker/.env exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" exec mysqldump --single-transaction --no-tablespaces --user="$MYSQL_USER" "$MYSQL_DATABASE"' > backups/database.sql
docker run --rm -v mailora_app-storage:/source:ro docker.io/library/alpine:3.22 tar -czf - -C /source . > backups/storage.tar.gz
docker compose --env-file docker/.env stop redis
docker run --rm -v mailora_redis-data:/source:ro docker.io/library/alpine:3.22 tar -czf - -C /source . > backups/redis.tar.gz
docker compose --env-file docker/.env up -d redis worker scheduler
docker compose --env-file docker/.env exec app php artisan up
```

Protect `docker/.env` separately in a secret manager; it is not included in these archives. The SQL dump contains personal data and should have a retention policy. A restore drill should create fresh volumes, import the SQL into an empty matching-version database, unpack the storage/Redis archives with original ownership, restore the matching application key, start in `MAIL_SANDBOX=true`, and verify data and jobs before enabling any live delivery. Do not run restored production queues against real SMTP during a drill.

## Validation

The CI workflow checks Composer metadata, Pint, PHPStan, application tests and the frontend build on PHP 8.3 and 8.4. A separate container job builds both targets, migrates MySQL, checks HTTP login/health and verifies Horizon startup. The presence of a workflow is not evidence that remote CI has run.

Local validation on 2026-10-28 built the PHP 8.3 and nginx images, applied every migration to MySQL 8.4, checked catalog synchronization and fresh owner registration, and started Horizon and the scheduler against authenticated Redis 7.4. Public, login, registration and health URLs returned HTTP 200; nginx configuration validation passed. The test suite also passed against that isolated MySQL database using the host PHP runtime. Clean `npm ci` and `npm run build` passed. No real SMTP delivery was attempted.

Run the local checks with:

```sh
composer validate --strict
vendor/bin/pint --test
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
php artisan test --compact
npm ci
npm run build
```

In a restricted environment that prevents PHPStan's local worker socket, add `--debug` to run its analysis serially. The installed runtime here exposes Docker via rootless Podman and lacks its `pasta` network helper. Compose configuration validation passed, but the normal Compose network could not be exercised locally. Image builds and runtime smoke tests used Podman's host-network mode with isolated test credentials and loopback database ports; the shipped Compose configuration retains private container networking. The CI container job covers the normal Docker Compose path.

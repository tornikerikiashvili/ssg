# SmartSoft Client Area

Laravel 13 with a Filament 5 admin panel and a Blade client interface. Local development runs in Docker using Laravel Sail, PHP 8.5, PostgreSQL 18, Redis 7, and Node 24. Vue and client-side Livewire are not used.

## Current milestone

The CMS includes editable companies, partner users, games, resource library, announcements, roadmap entries, and engagement tools. Blade pages display published content with company-scoped access, searchable games/resources, game details, and protected demo downloads. The client shell reuses fonts, artwork, and colors from the supplied HTML; the complete reference page conversion remains a later milestone. Public registration is disabled.

Dropbox synchronization, bulk ZIP downloads, regional/group/product entitlements beyond company visibility, per-user notification state, and Jira are not implemented yet. Resource downloads currently use allowlisted local demo files; the CMS does not upload physical files. Email writes to Laravel's log. Password login follows the supplied HTML; OTP/invitations and password recovery remain future work.

## Start locally

Requirements: Docker Desktop running, Bash, and Git if cloning. Host PHP, Composer, and Node are not required.

```bash
./bin/setup
./vendor/bin/sail artisan db:seed --class=PortalDemoSeeder
```

The first setup builds the Docker runtime and can take several minutes. Dependency bootstrap temporarily ignores only `intl` and `gd` extension checks in the lightweight Composer container; both extensions are present and checked in the actual runtime. Composer and npm lockfiles keep application dependencies reproducible.

Default addresses:

- Client: http://localhost:8095/login
- Admin: http://localhost:8095/admin
- Health: http://localhost:8095/up
- PostgreSQL for desktop clients: `127.0.0.1:5448`, database/user `smartsoft`; password is `DB_PASSWORD` in `.env`.

The application connects to PostgreSQL internally at `pgsql:5432`. PostgreSQL is stored in the dedicated `smartsoft_pgsql-data` volume. PostgreSQL 18 mounts its data volume at `/var/lib/postgresql`.

Local demo logins are `admin@smartsoft.test` and `client@smartsoft.test`. The portal seeder also adds 10 games (8 shared, 1 restricted, 1 unpublished), 23 resources, 5 announcements, 4 roadmap entries, and 3 engagement tools. Demo content is labeled and fictional; sample certificates have no validity. Re-running it preserves existing CMS edits and creates only missing records.

Random passwords are written to the git-ignored `.local-credentials` file. Re-running the seeder preserves existing accounts and passwords. Local account creation refuses to run outside `APP_ENV=local`. Content seeding allows only local and testing environments. Do not commit `.env` or `.local-credentials`.

To create a real administrator interactively:

```bash
./vendor/bin/sail artisan smartsoft:create-admin
```

Use this command instead of `make:filament-user`, since SmartSoft explicitly requires `is_admin` for administration access.

## Daily commands

```bash
./vendor/bin/sail up -d
./vendor/bin/sail down
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm run dev
./vendor/bin/sail npm run build
./vendor/bin/sail artisan queue:restart
./vendor/bin/sail logs -f
```

The Vite watcher runs in the foreground at port 5195. Built assets work without the watcher. If a terminated watcher leaves `public/hot`, remove that file to return to built assets. Restart the queue after changing job code; the scheduler is a separate service.

Host ports are configurable in `.env`: `APP_PORT`, `VITE_PORT`, `FORWARD_DB_PORT`. Update `APP_URL` when changing the application port. HTTP and PostgreSQL ports bind to localhost only. Redis has no published host port. The Compose project name is `smartsoft`, isolating containers and volumes from other projects.

`docker compose down` preserves data. Do not add `-v` unless you intend to erase this project's local database and Redis data. Changing `DB_PASSWORD` after database initialization also requires changing the database role password.

## Verification

```bash
./vendor/bin/sail artisan test
./vendor/bin/sail pint --test
./vendor/bin/sail composer validate --strict
./vendor/bin/sail composer check-platform-reqs
./vendor/bin/sail npm run build
```

Tests run against the separate PostgreSQL database `smartsoft_testing`, created during initial database startup. The test suite refreshes that database, never the development database. If using an existing volume without it, create it once:

```bash
docker compose exec pgsql sh -c 'createdb -U "$POSTGRES_USER" smartsoft_testing'
```

The tests cover authentication, company visibility, protected downloads, content escaping, CMS create/edit actions, password preservation, and repeatable demo seeding. CMS records can be unpublished without deleting them; companies and users can be disabled to revoke access.

## Structure

- `app/Providers/Filament/AdminPanelProvider.php`: admin panel configuration.
- `app/Models`: users and companies; business models will follow.
- `app/Http/Controllers/Auth`: Blade session login/logout.
- `app/Http/Middleware/EnsureClientAccess.php`: checks access on every client request.
- `resources/views/layouts`: shared Blade layouts.
- `resources/views/auth` and `resources/views/client`: client pages.
- `resources/css/app.css` and `resources/js/app.js`: client styling and small JavaScript enhancements.
- `compose.yaml`: application, PostgreSQL, Redis, queue, and scheduler services.

The original HTML and product document outside this repository are unchanged. Dropbox remains the planned source of physical files; the application will store metadata and folder mappings. This Compose stack is for local development, not production deployment.

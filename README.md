# Bangladesh API Platform

Multi-service PHP/Slim API that powers Banglade.sh micro-apps. The platform shares a central auth domain (users, tokens, roles) and introduces dedicated PostgreSQL schemas for each vertical (e.g., `video_portal`).

## Modules

| Module | Description | Documentation |
| --- | --- | --- |
| Auth Service | Centralized SSO (registration, login, refresh, profile, roles) | [`docs/auth-service.md`](docs/auth-service.md) |
| Video Portal | Video catalog + bookmarks/history backing StreamVibe | [`docs/video-portal.md`](docs/video-portal.md) |

More micro-apps (posts, files, commerce, etc.) will follow the same pattern—each with its own schema, repositories, and controller surface.

## Quick Start

### Prerequisites

- PHP 8.1+
- PostgreSQL 13+
- Composer

### Install & Configure

```bash
git clone <repo>
cd service-api
composer install
cp .env.example .env    # edit DB + mail + CORS values
```

### Database & Migrations

```bash
php migrate.php migrate
```

The migration runner seeds the shared `public` tables (users, refresh tokens, etc.) plus the `video_portal` schema introduced in `006_create_video_portal_schema.sql`.

### Run Locally

```bash
php -S localhost:8080 -t public
```

The API is now available at `http://localhost:8080`.

## Response Envelope

```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "timestamp": "2026-01-29T12:00:00Z"
  }
}
```

Errors follow the same structure with `success: false` and an `error` object (`code`, `message`, optional `details`).

## Project Layout

```
service-api/
├── public/                 # Slim entry point
├── src/
│   ├── Application/        # Middleware & use-cases
│   ├── Domain/             # Entities + interfaces (Auth, Video, ...)
│   ├── Infrastructure/     # Repositories, migrations, adapters
│   ├── Presentation/       # Controllers per module
│   └── Shared/             # Cross-cutting helpers
├── docs/                   # Module-specific READMEs
├── config/                 # app/jwt/mail configs
├── migrate.php             # Migration runner
└── README.md
```

## Dedicated Schemas

- `public` – original auth tables (`users`, `user_roles`, `user_permissions`, `refresh_tokens`, `password_resets`).
- `video_portal` – catalog + engagement tables for StreamVibe (`videos`, `video_assets`, `video_ingest_jobs`, `video_categories`, `user_video_bookmarks`, `user_video_history`).

Each future micro-app can add its own schema via a migration (e.g., `posts`, `files`), keeping concerns separate while reusing the shared auth domain.

## Security Highlights

- HTTPS + CORS enforcement (see `.env` `CORS_ALLOWED_ORIGINS`).
- JWT validation middleware on protected routes.
- Refresh token hashing + rotation, logout revocation.
- Bcrypt password hashing (cost 12).
- Role/permission middleware for admin/creator operations.

## Additional Docs

- [`ARCHITECTURE.md`](ARCHITECTURE.md) – ERDs, flow diagrams, deployment topology.
- [`docs/auth-service.md`](docs/auth-service.md) – detailed auth endpoints & samples.
- [`docs/video-portal.md`](docs/video-portal.md) – catalog/bookmark/history APIs and schema notes.

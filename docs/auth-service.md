# Auth Service (SSO)

Centralized authentication for the Banglade.sh ecosystem. Provides user registration/login, password flows, token management, and profile APIs consumed by every micro-app.

## Capabilities

- JWT access tokens (15 min) + rotating refresh tokens (7 days)
- Registration, login, logout, refresh
- Password reset + change-password
- Email verification + resend
- Role/permission assignments for downstream authorization
- SMTP integration (Gmail / Mailtrap)
- CORS-safe for all approved micro-apps

## API Surface

| Method | Endpoint | Description | Auth |
| --- | --- | --- | --- |
| POST | `/api/v1/auth/register` | Register user | No |
| POST | `/api/v1/auth/login` | Login user | No |
| POST | `/api/v1/auth/refresh` | Rotate tokens | No (requires refresh token) |
| POST | `/api/v1/auth/logout` | Revoke refresh token | No (requires refresh token) |
| POST | `/api/v1/auth/forgot-password` | Start password reset | No |
| POST | `/api/v1/auth/reset-password` | Complete password reset | No |
| POST | `/api/v1/auth/resend-verification` | Resend verification email | Yes |
| POST | `/api/v1/auth/verify-email` | Verify email token | No |
| POST | `/api/v1/auth/change-password` | Change password | Yes |

| Method | Endpoint | Description | Auth |
| --- | --- | --- | --- |
| GET | `/api/v1/users/me` | Current user profile | Yes |
| PUT | `/api/v1/users/me` | Update profile | Yes |
| GET | `/api/v1/users` | List users | Yes (admin) |
| GET | `/api/v1/users/{id}` | Fetch user | Yes (admin) |

> Admin-only routes require the `admin` role in the JWT claims.

## Request Samples

### Register

```bash
curl -X POST http://localhost:8080/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "SecurePass123",
    "first_name": "John",
    "last_name": "Doe"
  }'
```

### Login

```bash
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "SecurePass123"
  }'
```

### Refresh Tokens

```bash
curl -X POST http://localhost:8080/api/v1/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{"refresh_token": "<refresh_token>"}'
```

### Forgot Password

```bash
curl -X POST http://localhost:8080/api/v1/auth/forgot-password \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com"}'
```

### Get Current Profile

```bash
curl -X GET http://localhost:8080/api/v1/users/me \
  -H "Authorization: Bearer <access_token>"
```

## Response Format

```json
{
  "success": true,
  "data": { ... },
  "meta": { "timestamp": "2026-01-29T12:00:00Z" }
}
```

Errors follow the same envelope with an `error` object and `success: false`.

## Database Tables (Public Schema)

- `users`
- `user_roles`
- `user_permissions`
- `refresh_tokens`
- `password_resets`

See `ARCHITECTURE.md` for ERDs and flow diagrams.

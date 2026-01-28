# Bangladesh CMS - System Architecture

## Overview

This document provides a visual overview of the Bangladesh CMS architecture and how different components interact.

## System Context

```
┌──────────────────────────────────────────────────────────────────┐
│                        Bangladesh CMS                             │
│                     api.banglade.sh                               │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │              Central Authentication Service              │    │
│  │                                                          │    │
│  │   • User Registration & Login                           │    │
│  │   • JWT Token Generation                                │    │
│  │   • Role & Permission Management                        │    │
│  │   • Shared User Database                                │    │
│  └──────────────────┬───────────────────────────────────────┘    │
│                     │                                             │
│                     ▼                                             │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │               PostgreSQL Database                        │   │
│  │  ┌─────────┐  ┌──────────┐  ┌───────────────┐          │   │
│  │  │  users  │  │user_roles│  │user_permissions│          │   │
│  │  └─────────┘  └──────────┘  └───────────────┘          │   │
│  │  ┌──────────────┐  ┌────────────────┐                  │   │
│  │  │refresh_tokens│  │password_resets │                  │   │
│  │  └──────────────┘  └────────────────┘                  │   │
│  └──────────────────────────────────────────────────────────┘   │
└──────────────────────┬────────────────────────────────────────────┘
                       │
        ┌──────────────┴──────────────┬──────────────┬─────────────┐
        │                             │              │             │
        ▼                             ▼              ▼             ▼
┌───────────────┐         ┌──────────────┐    ┌──────────┐  ┌──────────┐
│ Posts Service │         │Files Service │    │Comments  │  │Analytics │
│               │         │              │    │Service   │  │Service   │
│ Future        │         │ Future       │    │Future    │  │Future    │
└───────────────┘         └──────────────┘    └──────────┘  └──────────┘
```

## Authentication Flow

```
┌────────┐                 ┌─────────────┐                 ┌──────────┐
│ Client │                 │   API       │                 │PostgreSQL│
│        │                 │(Slim + PHP) │                 │          │
└───┬────┘                 └──────┬──────┘                 └────┬─────┘
    │                             │                             │
    │  1. POST /auth/register     │                             │
    │─────────────────────────────>│                             │
    │     {email, password}        │                             │
    │                              │  2. Hash password (bcrypt)  │
    │                              │                             │
    │                              │  3. INSERT INTO users       │
    │                              │─────────────────────────────>│
    │                              │                             │
    │                              │  4. Return user data        │
    │  5. 201 Created              │<─────────────────────────────│
    │<─────────────────────────────│                             │
    │  {user: {...}}               │                             │
    │                              │                             │
    │  6. POST /auth/login         │                             │
    │─────────────────────────────>│                             │
    │     {email, password}        │                             │
    │                              │  7. SELECT * FROM users     │
    │                              │─────────────────────────────>│
    │                              │                             │
    │                              │  8. Return user + roles     │
    │                              │<─────────────────────────────│
    │                              │                             │
    │                              │  9. Verify password         │
    │                              │  10. Generate JWT           │
    │                              │      - Access (15 min)      │
    │                              │      - Refresh (7 days)     │
    │  11. 200 OK                  │                             │
    │<─────────────────────────────│                             │
    │  {access_token, refresh_token}                             │
    │                              │                             │
    │  12. GET /users/me           │                             │
    │─────────────────────────────>│                             │
    │  Authorization: Bearer xxx   │                             │
    │                              │  13. Validate JWT           │
    │                              │  14. Extract user_id        │
    │                              │                             │
    │                              │  15. SELECT * FROM users    │
    │                              │─────────────────────────────>│
    │                              │                             │
    │                              │  16. Return user data       │
    │  17. 200 OK                  │<─────────────────────────────│
    │<─────────────────────────────│                             │
    │  {user: {...}}               │                             │
    │                              │                             │
```

## Application Architecture (Layered)

```
┌────────────────────────────────────────────────────────────┐
│                    Presentation Layer                      │
│                                                            │
│  ┌──────────────────┐         ┌──────────────────┐       │
│  │ AuthController   │         │  UserController  │       │
│  │                  │         │                  │       │
│  │ • register()     │         │ • me()           │       │
│  │ • login()        │         │ • updateMe()     │       │
│  │ • refresh()      │         │ • list()         │       │
│  └──────────────────┘         └──────────────────┘       │
└───────────────┬────────────────────────┬───────────────────┘
                │                        │
┌───────────────▼────────────────────────▼───────────────────┐
│                   Application Layer                        │
│                                                            │
│  ┌──────────────┐  ┌──────────────┐  ┌────────────────┐  │
│  │    CORS      │  │     JWT      │  │  Validation    │  │
│  │  Middleware  │  │  Middleware  │  │   Middleware   │  │
│  └──────────────┘  └──────────────┘  └────────────────┘  │
└───────────────┬────────────────────────┬───────────────────┘
                │                        │
┌───────────────▼────────────────────────▼───────────────────┐
│                      Domain Layer                          │
│                  (Business Logic)                          │
│                                                            │
│  ┌──────────────┐  ┌──────────────┐  ┌────────────────┐  │
│  │  JwtService  │  │   Password   │  │     User       │  │
│  │              │  │   Service    │  │    Entity      │  │
│  │ • generate() │  │ • hash()     │  │                │  │
│  │ • validate() │  │ • verify()   │  │ • toArray()    │  │
│  └──────────────┘  └──────────────┘  └────────────────┘  │
│                                                            │
│  ┌─────────────────────────────────────────────────────┐  │
│  │          UserRepository (Interface)                 │  │
│  │  • create() • findById() • update() • delete()      │  │
│  └─────────────────────────────────────────────────────┘  │
└───────────────┬────────────────────────────────────────────┘
                │
┌───────────────▼────────────────────────────────────────────┐
│                  Infrastructure Layer                      │
│                                                            │
│  ┌─────────────────────────────────────────────────────┐  │
│  │        PgUserRepository (Implementation)            │  │
│  │  • Concrete PostgreSQL implementation               │  │
│  │  • SQL queries with prepared statements             │  │
│  └──────────────────┬──────────────────────────────────┘  │
│                     │                                      │
│  ┌──────────────────▼──────────────────────────────────┐  │
│  │            Database Connection                      │  │
│  │  • Singleton PDO instance                           │  │
│  │  • Transaction support                              │  │
│  └─────────────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────────┘
```

## Database Schema (ERD)

```
┌──────────────────────────┐
│         users            │
│──────────────────────────│
│ PK  id (UUID)            │
│ UK  email                │
│     password_hash        │
│     first_name           │
│     last_name            │
│     phone                │
│     avatar_url           │
│     email_verified       │
│     is_active            │
│     created_at           │
│     updated_at           │
│     last_login_at        │
└──────┬────────────────┬──┘
       │                │
       │                │
   ┌───▼────────┐   ┌───▼───────────────┐
   │ user_roles │   │ user_permissions  │
   │────────────│   │───────────────────│
   │ PK  id     │   │ PK  id            │
   │ FK  user_id│   │ FK  user_id       │
   │     role   │   │     permission    │
   └────────────┘   │     resource_type │
                    └───────────────────┘
   ┌────────────────────┐
   │  refresh_tokens    │
   │────────────────────│
   │ PK  id             │
   │ FK  user_id        │──┐
   │     token_hash     │  │
   │     expires_at     │  │
   │     revoked_at     │  │
   │ FK  replaced_by    │──┘ (self-reference)
   └────────────────────┘

   ┌────────────────────┐
   │  password_resets   │
   │────────────────────│
   │ PK  id             │
   │ FK  user_id        │
   │     token_hash     │
   │     expires_at     │
   │     used_at        │
   └────────────────────┘
```

## JWT Token Structure

```
Access Token (15 min expiry)
┌─────────────────────────────────┐
│ Header                          │
│ {                               │
│   "alg": "HS256",               │
│   "typ": "JWT"                  │
│ }                               │
├─────────────────────────────────┤
│ Payload                         │
│ {                               │
│   "iss": "api.banglade.sh",     │
│   "sub": "user-uuid",           │
│   "iat": 1234567890,            │
│   "exp": 1234568790,            │
│   "email": "user@example.com",  │
│   "roles": ["admin"],           │
│   "permissions": [],            │
│   "type": "access"              │
│ }                               │
├─────────────────────────────────┤
│ Signature                       │
│ HMACSHA256(                     │
│   base64(header) + "." +        │
│   base64(payload),              │
│   secret                        │
│ )                               │
└─────────────────────────────────┘
```

## Request/Response Flow

```
HTTP Request
    │
    ▼
┌────────────────┐
│ CORS Middleware│  ← Add CORS headers
└───────┬────────┘
        │
        ▼
┌────────────────┐
│ Routing        │  ← Match route pattern
└───────┬────────┘
        │
        ▼
┌────────────────┐
│ JWT Middleware │  ← Validate token (if protected route)
│                │  ← Extract user info
└───────┬────────┘
        │
        ▼
┌────────────────┐
│   Controller   │  ← Business logic
│                │  ← Call services
│                │  ← Call repositories
└───────┬────────┘
        │
        ▼
┌────────────────┐
│  Repository    │  ← Database queries
└───────┬────────┘
        │
        ▼
┌────────────────┐
│   Database     │  ← PostgreSQL
└───────┬────────┘
        │
        ▼
┌────────────────┐
│ JSON Response  │  ← Format response
└───────┬────────┘
        │
        ▼
HTTP Response
```

## Future Micro-App Integration

```
┌─────────────────────────────────────────────────────────────┐
│                 api.banglade.sh (Port 80)                   │
│                                                             │
│  Nginx Reverse Proxy                                        │
│                                                             │
│  /api/v1/auth/*    ──────> Auth Service (Port 8080)        │
│  /api/v1/users/*   ──────> Auth Service (Port 8080)        │
│  /api/v1/posts/*   ──────> Posts Service (Port 8081)       │
│  /api/v1/files/*   ──────> Files Service (Port 8082)       │
│  /api/v1/comments/*──────> Comments Service (Port 8083)    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
                              │
                              │ All services share
                              ▼
                    ┌──────────────────┐
                    │   PostgreSQL     │
                    │                  │
                    │ • users (shared) │
                    │ • posts          │
                    │ • files          │
                    │ • comments       │
                    └──────────────────┘
```

## Deployment Architecture

```
┌────────────────────────────────────────────────────────────┐
│                     Production Environment                  │
│                                                            │
│  ┌────────────────┐                                        │
│  │  Load Balancer │ (Nginx/HAProxy)                       │
│  └────────┬───────┘                                        │
│           │                                                │
│  ┌────────▼────────┬─────────────┬─────────────┐          │
│  │                 │             │             │          │
│  ▼                 ▼             ▼             ▼          │
│  App Server 1   App Server 2  App Server 3  App Server N  │
│  (PHP-FPM)      (PHP-FPM)     (PHP-FPM)     (PHP-FPM)     │
│                                                            │
│  └────────┬────────┴─────────────┴─────────────┘          │
│           │                                                │
│  ┌────────▼─────────────┐    ┌───────────────────┐        │
│  │  PostgreSQL Master   │───>│ PostgreSQL Replica│        │
│  │  (Read/Write)        │    │ (Read Only)       │        │
│  └──────────────────────┘    └───────────────────┘        │
│                                                            │
│  ┌────────────────┐                                        │
│  │  Redis Cache   │  (Session, Rate Limiting)             │
│  └────────────────┘                                        │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

## Security Layers

```
Request Journey:

1. HTTPS (TLS/SSL)
   └─> Encrypted transport

2. CORS Middleware
   └─> Origin validation

3. Rate Limiting (Future)
   └─> Prevent abuse

4. JWT Validation
   └─> Verify token signature
   └─> Check expiration
   └─> Extract user claims

5. Authorization Check
   └─> Verify user roles
   └─> Verify permissions

6. Input Validation
   └─> Sanitize input
   └─> Validate types

7. SQL Prepared Statements
   └─> Prevent SQL injection

8. Password Hashing
   └─> Bcrypt (cost 12)
   └─> Never return hashes
```

## Directory Structure Map

```
auth.banglade.sh/
│
├── public/                    ← Web server document root
│   └── index.php             ← Application entry point
│
├── src/
│   ├── Application/          ← HTTP, Middleware, Routing
│   ├── Domain/               ← Business logic, Entities
│   ├── Infrastructure/       ← Database, External services
│   ├── Presentation/         ← Controllers, Request/Response
│   └── Shared/              ← Cross-cutting concerns
│
├── config/                   ← Configuration files
├── logs/                     ← Application logs
├── vendor/                   ← Composer dependencies
│
├── migrate.php              ← Database migration CLI
├── composer.json            ← PHP dependencies
├── .env                     ← Environment variables
│
├── README.md               ← Quick start guide
├── DEVELOPER_GUIDE.md      ← This file
└── postman_collection.json ← API testing
```

---

## Key Takeaways

1. **Clean Architecture**: Separation of concerns with clear layers
2. **Shared Database**: All micro-apps use same PostgreSQL instance
3. **JWT Authentication**: Stateless, scalable token-based auth
4. **Extensible**: Easy to add new endpoints and micro-apps
5. **Secure**: Multiple security layers from transport to database

For detailed implementation instructions, see [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md)

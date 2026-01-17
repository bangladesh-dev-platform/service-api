# Bangladesh Digital Auth API (Node.js)

Production-ready authentication API built with Node.js, Express, TypeScript, and PostgreSQL.

## Features

- ✅ Clean Architecture (Layered DDD)
- ✅ JWT Authentication (Access + Refresh Tokens)
- ✅ Zod Validation (Type-safe)
- ✅ Standardized API Responses
- ✅ Proper Error Handling Middleware
- ✅ PostgreSQL Migration System
- ✅ Role-Based Access Control (RBAC)

## Tech Stack

- **Runtime:** Node.js
- **Language:** TypeScript
- **Framework:** Express.js
- **Database:** PostgreSQL
- **Auth:** JWT + bcrypt
- **Validation:** Zod

## Getting Started

### 1. Installation

```bash
cd bd-api-node
npm install
```

### 2. Configuration

Copy `.env.example` to `.env` and fill in your details:

```bash
cp .env.example .env
```

### 3. Database Migrations

```bash
npm run migrate
```

### 4. Development

```bash
npm run dev
```

### 5. Production

```bash
npm run build
npm start
```

## API Endpoints

### Auth
- `POST /api/v1/auth/register` - Register new user
- `POST /api/v1/auth/login` - Login user
- `POST /api/v1/auth/refresh` - Refresh access token
- `POST /api/v1/auth/logout` - Logout

### Users
- `GET /api/v1/users/me` - Get profile
- `PUT /api/v1/users/me` - Update profile
- `GET /api/v1/users` - List users (Admin only)

## Project Structure

```
src/
  ├── application/    # DTOs and Middleware
  ├── domain/         # Entities and Service interfaces
  ├── infrastructure/ # Database and Repositories
  ├── presentation/   # Controllers and Routes
  ├── shared/         # Common utilities and error classes
  └── config/         # App configurations
```

## License

MIT

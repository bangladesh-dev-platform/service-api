# Bangladesh Auth API

Centralized authentication REST API for Bangladesh CMS micro-apps ecosystem.

**Domain:** `api.banglade.sh`

## Features

- ✅ JWT-based authentication with access and refresh tokens
- ✅ User registration and login
- ✅ Role-based access control (RBAC)
- ✅ Fine-grained permissions system
- ✅ Password strength validation
- ✅ PostgreSQL database with UUID primary keys
- ✅ RESTful API design with standardized responses
- ✅ CORS support for micro-app integration

## Tech Stack

- **Framework**: PHP Slim 4
- **Database**: PostgreSQL
- **Authentication**: JWT (firebase/php-jwt)
- **Dependency Injection**: PHP-DI
- **Validation**: Respect/Validation

## Installation

### Prerequisites

- PHP 8.1 or higher
- PostgreSQL 13 or higher
- Composer

### Setup

1. **Clone the repository**
```bash
git clone <repository-url>
cd auth.banglade.sh
```

2. **Install dependencies**
```bash
composer install
```

3. **Configure environment**
```bash
cp .env.example .env
# Edit .env with your database credentials and settings
```

4. **Create database**
```bash
psql -U postgres
CREATE DATABASE cms_db;
CREATE USER cms_user WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE cms_db TO cms_user;
```

5. **Run migrations**
```bash
php migrate.php migrate
```

6. **Start development server**
```bash
php -S localhost:8080 -t public
```

The API will be available at `http://localhost:8080`

## API Endpoints

### Authentication

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/v1/auth/register` | Register new user | No |
| POST | `/api/v1/auth/login` | Login user | No |

### Users

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/users/me` | Get current user | Yes |
| PUT | `/api/v1/users/me` | Update current user | Yes |
| GET | `/api/v1/users` | List all users | Yes |
| GET | `/api/v1/users/{id}` | Get user by ID | Yes |

### Health Check

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/health` | API health status | No |

## Usage Examples

### Register User
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

### Get Current User Profile
```bash
curl -X GET http://localhost:8080/api/v1/users/me \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

## Response Format

### Success Response
```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "timestamp": "2026-01-15T15:29:27+00:00"
  }
}
```

### Error Response
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Error message",
    "details": { ... }
  },
  "meta": {
    "timestamp": "2026-01-15T15:29:27+00:00"
  }
}
```

## Project Structure

```
auth.banglade.sh/
├── public/
│   └── index.php              # Application entry point
├── src/
│   ├── Application/           # Application layer (middleware, handlers)
│   ├── Domain/                # Domain layer (business logic)
│   ├── Infrastructure/        # Infrastructure layer (database, repositories)
│   ├── Presentation/          # Presentation layer (controllers)
│   └── Shared/                # Shared utilities
├── config/                    # Configuration files
├── migrate.php                # Migration CLI tool
├── composer.json              # PHP dependencies
└── .env                       # Environment configuration
```

## Database Schema

- **users** - User accounts (global table)
- **user_roles** - User role assignments
- **user_permissions** - Fine-grained permissions
- **refresh_tokens** - JWT refresh tokens
- **password_resets** - Password reset tokens

## Security Features

- Bcrypt password hashing (cost factor 12)
- JWT with short-lived access tokens (15 minutes)
- Refresh token rotation (7 days)
- Password strength validation
- CORS protection
- UUID instead of auto-increment IDs

## Development

### Running Tests
```bash
composer test
```

### Static Analysis
```bash
composer analyze
```

## Micro-App Integration

This auth service is designed to be used by multiple micro-apps. Other services should:

1. Validate JWT tokens from this service
2. Use the `/api/v1/users/{id}` endpoint to fetch user details
3. Check user roles and permissions for authorization
4. Share the same users database table

## License 

MIT

## Support

For issues and questions, please create an issue in the repository.

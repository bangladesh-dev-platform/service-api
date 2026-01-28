# Bangladesh CMS - Developer Guide

## 📚 Table of Contents
- [App Overview](#app-overview)
- [Architecture Vision](#architecture-vision)
- [Project Structure](#project-structure)
- [How to Extend](#how-to-extend)
- [Adding New Endpoints](#adding-new-endpoints)
- [Database Guidelines](#database-guidelines)
- [Security Best Practices](#security-best-practices)
- [Deployment Guide](#deployment-guide)

---

## App Overview

### Vision

Bangladesh CMS is a **centralized authentication system** designed to serve multiple micro-apps in the Bangladesh digital ecosystem. The core philosophy is:

- **Single Source of Truth**: One authentication service for all micro-apps
- **Shared User Base**: All micro-apps share the same user accounts
- **Scalable Architecture**: Easy to add new micro-apps without duplicating auth logic
- **Role-Based Access**: Fine-grained permissions for different resource types

### Current Implementation

**What's Built:**
- ✅ Centralized authentication API (`api.banglade.sh`)
- ✅ User registration and login with JWT
- ✅ Role-based access control (RBAC)
- ✅ Fine-grained permissions system
- ✅ PostgreSQL database with 5 core tables

**Domain:** `api.banglade.sh`

**Endpoints Implemented:**
- `POST /api/v1/auth/register` - User registration
- `POST /api/v1/auth/login` - User login
- `GET /api/v1/users/me` - Current user profile
- `PUT /api/v1/users/me` - Update profile
- `GET /api/v1/users` - List users (paginated)
- `GET /api/v1/users/{id}` - Get user by ID

### Future Micro-Apps

**Planned Extensions:**
1. **Posts Service** - Blog posts, articles, news
2. **Files Service** - File uploads, media management
3. **Comments Service** - Comments on posts, files, etc.
4. **Media Service** - Image gallery, video hosting
5. **Analytics Service** - User activity tracking

All these services will:
- Use the same authentication API
- Validate JWT tokens from this service
- Reference the shared `users` table
- Check user roles and permissions

---

## Architecture Vision

### Micro-App Architecture

```
┌─────────────────────────────────────────────────────────┐
│                   api.banglade.sh                       │
│              (Central Auth Service)                     │
│                                                         │
│  ┌──────────┐  ┌──────────┐  ┌────────────┐           │
│  │  Users   │  │  Roles   │  │ Permissions│           │
│  │  Table   │  │  Table   │  │   Table    │           │
│  └──────────┘  └──────────┘  └────────────┘           │
│         │              │              │                │
│         └──────────────┴──────────────┘                │
│                     │                                  │
│              JWT Token Generator                       │
└────────────────────┬────────────────────────────────────┘
                     │
         ┌───────────┴───────────┬────────────┬──────────┐
         │                       │            │          │
         ▼                       ▼            ▼          ▼
┌─────────────────┐   ┌──────────────┐   ┌─────────┐  ┌─────────┐
│ Posts Service   │   │Files Service │   │Comments │  │Analytics│
│                 │   │              │   │Service  │  │Service  │
│ /api/v1/posts/* │   │/api/v1/files/*│  │/api/v1/ │  │/api/v1/ │
│                 │   │              │   │comments/*│  │stats/*  │
└─────────────────┘   └──────────────┘   └─────────┘  └─────────┘
```

### Database Strategy

**Shared Database Approach:**
- All micro-apps share the **same PostgreSQL database**
- `users`, `user_roles`, `user_permissions` are **global tables**
- Each micro-app has its own set of tables (e.g., `posts`, `files`, `comments`)
- Foreign keys reference the shared `users` table

**Example:**
```sql
-- Shared (in this codebase)
users
user_roles
user_permissions

-- Posts Service (future)
posts (author_id -> users.id)
post_categories
post_tags

-- Files Service (future)
files (uploaded_by -> users.id)
file_shares (shared_with -> users.id)

-- Comments Service (future)
comments (user_id -> users.id)
comment_reactions (user_id -> users.id)
```

### Authentication Flow

```
1. Client -> POST /api/v1/auth/login
2. API -> Validates credentials
3. API -> Generates JWT (contains user_id, roles, permissions)
4. Client -> Stores JWT token
5. Client -> Requests to any micro-app with JWT in header
6. Micro-app -> Validates JWT signature
7. Micro-app -> Extracts user info from JWT claims
8. Micro-app -> Checks permissions
9. Micro-app -> Allows/Denies request
```

---

## Project Structure

### Current Directory Layout

```
auth.banglade.sh/
├── public/
│   └── index.php              # Application entry point
│
├── src/
│   ├── Application/           # Application layer
│   │   └── Middleware/
│   │       ├── JwtAuthMiddleware.php    # JWT validation
│   │       └── CorsMiddleware.php       # CORS handling
│   │
│   ├── Domain/                # Domain layer (business logic)
│   │   ├── Auth/
│   │   │   ├── JwtService.php          # Token operations
│   │   │   └── PasswordService.php     # Password handling
│   │   └── User/
│   │       ├── User.php                # User entity
│   │       └── UserRepository.php      # Repository interface
│   │
│   ├── Infrastructure/        # Infrastructure layer
│   │   ├── Database/
│   │   │   ├── Connection.php          # DB singleton
│   │   │   ├── MigrationRunner.php     # Migration tool
│   │   │   └── Migrations/
│   │   │       ├── 001_create_users_table.sql
│   │   │       ├── 002_create_refresh_tokens_table.sql
│   │   │       ├── 003_create_password_resets_table.sql
│   │   │       ├── 004_create_user_roles_table.sql
│   │   │       └── 005_create_user_permissions_table.sql
│   │   └── Repositories/
│   │       └── PgUserRepository.php    # PostgreSQL impl
│   │
│   ├── Presentation/          # Presentation layer
│   │   └── Controllers/
│   │       ├── AuthController.php      # Auth endpoints
│   │       └── UserController.php      # User endpoints
│   │
│   └── Shared/                # Shared utilities
│       ├── Exceptions/
│       │   ├── ValidationException.php
│       │   ├── AuthenticationException.php
│       │   └── AuthorizationException.php
│       └── Response/
│           └── JsonResponse.php        # Standardized responses
│
├── config/
│   ├── app.php                # App configuration
│   ├── database.php           # Database config
│   └── jwt.php                # JWT settings
│
├── migrate.php                # CLI migration tool
├── composer.json              # Dependencies
├── .env.example               # Environment template
└── README.md                  # Documentation
```

### Design Patterns

**1. Repository Pattern**
- `UserRepository` interface defines contract
- `PgUserRepository` implements PostgreSQL-specific logic
- Easy to swap database implementations

**2. Dependency Injection**
- Services registered in DI container (`public/index.php`)
- Controllers receive dependencies via constructor
- Testable and maintainable

**3. Middleware Pattern**
- JWT authentication as middleware
- CORS as middleware
- Easy to add new middleware (rate limiting, logging, etc.)

**4. Domain-Driven Design**
- Clear separation of concerns
- Domain entities independent of infrastructure
- Business logic in domain layer

---

## How to Extend

### 1. Adding New Authentication Features

#### Example: Add Email Verification

**Step 1: Create Migration**
```bash
# Already exists: email_verified and email_verified_at in users table
```

**Step 2: Add Service Method**
Create `src/Domain/Auth/EmailVerificationService.php`:
```php
<?php
namespace App\Domain\Auth;

class EmailVerificationService
{
    private PasswordService $passwordService;
    
    public function generateVerificationToken(): string
    {
        return $this->passwordService->generateToken(32);
    }
    
    public function hashToken(string $token): string
    {
        return $this->passwordService->hashToken($token);
    }
}
```

**Step 3: Add Controller Method**
In `src/Presentation/Controllers/AuthController.php`:
```php
public function verifyEmail(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
{
    $data = $request->getParsedBody();
    $token = $data['token'] ?? '';
    
    // Verify token and update user
    // ...
    
    return JsonResponse::success(new Response(), ['message' => 'Email verified']);
}
```

**Step 4: Add Route**
In `public/index.php`:
```php
$app->post('/auth/verify-email', [AuthController::class, 'verifyEmail']);
```

### 2. Adding New User Features

#### Example: Add User Avatar Upload

**Step 1: Add to User Model**
Already exists: `avatar_url` field in users table

**Step 2: Create File Upload Service**
Create `src/Domain/Upload/FileUploadService.php`:
```php
<?php
namespace App\Domain\Upload;

class FileUploadService
{
    public function upload(UploadedFileInterface $file, string $userId): string
    {
        // Handle file upload, return URL
        $filename = $userId . '_' . time() . '.' . $file->getClientFilename();
        $file->moveTo('/uploads/avatars/' . $filename);
        return '/uploads/avatars/' . $filename;
    }
}
```

**Step 3: Add Controller Endpoint**
In `src/Presentation/Controllers/UserController.php`:
```php
public function uploadAvatar(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
{
    $userId = $request->getAttribute('user_id');
    $uploadedFiles = $request->getUploadedFiles();
    
    $avatarUrl = $this->fileUploadService->upload($uploadedFiles['avatar'], $userId);
    
    // Update user avatar_url
    // ...
    
    return JsonResponse::success(new Response(), ['avatar_url' => $avatarUrl]);
}
```

**Step 4: Add Route**
```php
$app->post('/users/me/avatar', [UserController::class, 'uploadAvatar'])
    ->add(new JwtAuthMiddleware($container->get(JwtService::class)));
```

### 3. Adding New Micro-App (Posts Service)

#### Step 1: Create Database Migration

Create `006_create_posts_tables.sql`:
```sql
CREATE TABLE posts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    author_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT,
    status VARCHAR(20) DEFAULT 'draft', -- draft, published, archived
    published_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_posts_author_id ON posts(author_id);
CREATE INDEX idx_posts_slug ON posts(slug);
CREATE INDEX idx_posts_status ON posts(status);
CREATE INDEX idx_posts_published_at ON posts(published_at);
```

#### Step 2: Create Domain Model

Create `src/Domain/Post/Post.php`:
```php
<?php
namespace App\Domain\Post;

class Post
{
    private ?string $id;
    private string $authorId;
    private string $title;
    private string $slug;
    private ?string $content;
    private string $status;
    // ... constructor, getters, toArray()
}
```

#### Step 3: Create Repository

Create `src/Domain/Post/PostRepository.php`:
```php
<?php
namespace App\Domain\Post;

interface PostRepository
{
    public function create(Post $post): Post;
    public function findById(string $id): ?Post;
    public function findBySlug(string $slug): ?Post;
    public function update(Post $post): Post;
    public function delete(string $id): bool;
    public function findByAuthor(string $authorId, int $limit, int $offset): array;
}
```

Create `src/Infrastructure/Repositories/PgPostRepository.php`:
```php
<?php
namespace App\Infrastructure\Repositories;

use App\Domain\Post\Post;
use App\Domain\Post\PostRepository;
use PDO;

class PgPostRepository implements PostRepository
{
    private PDO $pdo;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    public function create(Post $post): Post
    {
        $sql = "INSERT INTO posts (author_id, title, slug, content, status) 
                VALUES (:author_id, :title, :slug, :content, :status) 
                RETURNING *";
        // ... implementation
    }
    
    // ... other methods
}
```

#### Step 4: Create Controller

Create `src/Presentation/Controllers/PostController.php`:
```php
<?php
namespace App\Presentation\Controllers;

use App\Domain\Post\PostRepository;
use App\Shared\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class PostController
{
    private PostRepository $postRepository;
    
    public function __construct(PostRepository $postRepository)
    {
        $this->postRepository = $postRepository;
    }
    
    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        $userRoles = $request->getAttribute('user_roles');
        
        // Check permission
        if (!in_array('author', $userRoles) && !in_array('admin', $userRoles)) {
            return JsonResponse::forbidden(new Response(), 'You need author role to create posts');
        }
        
        $data = $request->getParsedBody();
        
        // Validate and create post
        // ...
        
        return JsonResponse::success(new Response(), $post->toArray(), [], 201);
    }
    
    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // List posts with pagination
        // ...
    }
    
    public function getById(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        // Get post by ID
        // ...
    }
}
```

#### Step 5: Register in DI Container

In `public/index.php`:
```php
use App\Domain\Post\PostRepository;
use App\Infrastructure\Repositories\PgPostRepository;
use App\Presentation\Controllers\PostController;

// Add to container
$container->set(PostRepository::class, function(ContainerInterface $c) {
    return new PgPostRepository($c->get(PDO::class));
});

$container->set(PostController::class, function(ContainerInterface $c) {
    return new PostController($c->get(PostRepository::class));
});
```

#### Step 6: Add Routes

In `public/index.php`:
```php
// Posts routes (auth required)
$app->group('/posts', function ($app) use ($container) {
    $app->get('', [PostController::class, 'list']);
    $app->post('', [PostController::class, 'create']);
    $app->get('/{id}', [PostController::class, 'getById']);
    $app->put('/{id}', [PostController::class, 'update']);
    $app->delete('/{id}', [PostController::class, 'delete']);
})->add(new JwtAuthMiddleware($container->get(JwtService::class)));
```

---

## Adding New Endpoints

### Quick Reference Checklist

When adding a new endpoint, follow these steps:

- [ ] **1. Database**: Create migration if new tables needed
- [ ] **2. Domain**: Create entity class (e.g., `Post.php`)
- [ ] **3. Repository**: Create interface and implementation
- [ ] **4. Controller**: Create controller with methods
- [ ] **5. Container**: Register services in DI container
- [ ] **6. Routes**: Add routes in `public/index.php`
- [ ] **7. Middleware**: Apply auth/permission middleware if needed
- [ ] **8. Test**: Test with Postman or curl

### Example: Add "Get User Activity" Endpoint

```php
// 1. No new tables needed (reading from existing data)

// 2 & 3. No new domain models needed

// 4. Add to UserController
public function activity(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
{
    $userId = $request->getAttribute('user_id');
    
    // Get user's posts, comments, etc.
    $activity = [
        'posts' => $this->postRepository->findByAuthor($userId, 10, 0),
        'comments' => $this->commentRepository->findByUser($userId, 10, 0),
    ];
    
    return JsonResponse::success(new Response(), $activity);
}

// 5. Already in container

// 6. Add route
$app->get('/users/me/activity', [UserController::class, 'activity'])
    ->add(new JwtAuthMiddleware($container->get(JwtService::class)));
```

---

## Database Guidelines

### Migration Best Practices

1. **Always use migrations** - Never modify database manually
2. **Sequential naming** - Use `NNN_description.sql` format
3. **Include rollback info** - Add comments about how to rollback
4. **Test before commit** - Run migration on test database first

### Table Design Rules

1. **Use UUID for PKs** - More secure than auto-increment
   ```sql
   id UUID PRIMARY KEY DEFAULT gen_random_uuid()
   ```

2. **Always add indexes** - On foreign keys and frequently queried columns
   ```sql
   CREATE INDEX idx_posts_author_id ON posts(author_id);
   ```

3. **Add timestamps** - `created_at` and `updated_at` on all tables
   ```sql
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   ```

4. **Use triggers for auto-update** - Already set up for `updated_at`
   ```sql
   CREATE TRIGGER update_posts_updated_at BEFORE UPDATE ON posts
       FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
   ```

5. **Reference users table** - All user-related FKs point to `users.id`
   ```sql
   author_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE
   ```

### Permission Design

**Format:** `resource.action`

Examples:
- `posts.create` - Can create posts
- `posts.edit.own` - Can edit own posts
- `posts.edit.any` - Can edit any post
- `posts.delete.own` - Can delete own posts
- `files.upload` - Can upload files
- `comments.moderate` - Can moderate comments

**Checking Permissions in Code:**
```php
$userPermissions = $request->getAttribute('user_permissions');

if (in_array('posts.create', $userPermissions)) {
    // Allow post creation
}
```

---

## Security Best Practices

### 1. Input Validation

Always validate user input:
```php
// Bad
$email = $data['email'];

// Good
if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    return JsonResponse::validationError(new Response(), ['email' => 'Invalid email']);
}
```

### 2. SQL Injection Prevention

Always use prepared statements:
```php
// Bad
$sql = "SELECT * FROM users WHERE email = '{$email}'";

// Good
$stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
```

### 3. Password Security

Never log or return passwords:
```php
// Bad
return $user->toArray(true); // includes password_hash

// Good
return $user->toArray(); // excludes password by default
```

### 4. Authorization Checks

Check permissions before actions:
```php
public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
{
    $userId = $request->getAttribute('user_id');
    $userRoles = $request->getAttribute('user_roles');
    
    $post = $this->postRepository->findById($args['id']);
    
    // Check if user is post author or admin
    if ($post->getAuthorId() !== $userId && !in_array('admin', $userRoles)) {
        return JsonResponse::forbidden(new Response(), 'Cannot delete this post');
    }
    
    // Proceed with deletion
}
```

### 5. Rate Limiting (Future)

Add rate limiting middleware:
```php
class RateLimitMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        
        // Check rate limit for user
        if ($this->rateLimiter->isExceeded($userId)) {
            return JsonResponse::error(
                new Response(),
                'RATE_LIMIT_EXCEEDED',
                'Too many requests',
                null,
                429
            );
        }
        
        return $handler->handle($request);
    }
}
```

---

## Deployment Guide

### Environment Configuration

**Development:**
```bash
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8080
JWT_SECRET=dev-secret-key
```

**Production:**
```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.banglade.sh
JWT_SECRET=<strong-random-secret-from-env>
```

### Database Setup

**Production PostgreSQL:**
```sql
-- Create database
CREATE DATABASE cms_db_production;

-- Create user with strong password
CREATE USER cms_user WITH PASSWORD '<strong-random-password>';

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE cms_db_production TO cms_user;

-- Enable extensions
\c cms_db_production
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
```

### Migration on Production

```bash
# Backup database first!
pg_dump -U cms_user cms_db_production > backup.sql

# Run migrations
php migrate.php migrate

# Verify
psql -U cms_user -d cms_db_production -c "\dt"
```

### Web Server Configuration

**Nginx:**
```nginx
server {
    listen 80;
    server_name api.banglade.sh;
    root /var/www/auth.banglade.sh/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

**Apache (.htaccess in public/):**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

---

## Quick Start for New Developers

### 1. Clone and Setup
```bash
git clone <repo-url> auth.banglade.sh
cd auth.banglade.sh
composer install
cp .env.example .env
# Edit .env with your database credentials
```

### 2. Database Setup
```bash
# Create database
createdb cms_db
# Run migrations
php migrate.php migrate
```

### 3. Start Development
```bash
php -S localhost:8080 -t public
```

### 4. Test API
```bash
# Register
curl -X POST http://localhost:8080/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"dev@test.com","password":"Test1234"}'

# Login
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"dev@test.com","password":"Test1234"}'
```

---

## FAQs

**Q: How do I add a new role?**
A: Simply insert into `user_roles` table. No code changes needed.
```sql
INSERT INTO user_roles (user_id, role, assigned_by) 
VALUES ('user-uuid', 'editor', 'admin-uuid');
```

**Q: How do I add a new permission?**
A: Insert into `user_permissions` table with `resource.action` format.
```sql
INSERT INTO user_permissions (user_id, permission, resource_type) 
VALUES ('user-uuid', 'create', 'posts');
```

**Q: Can I use a different database?**
A: Yes, implement new repository classes (e.g., `MySqlUserRepository`) that implement the repository interfaces.

**Q: How do I enable HTTPS?**
A: Configure your web server (Nginx/Apache) with SSL certificate. Use Let's Encrypt for free SSL.

**Q: How do I scale this?**
A: 
- Add Redis for caching
- Use connection pooling (PgBouncer)
- Load balance with Nginx
- Use read replicas for database

---

## Support & Contribution

For questions or issues:
1. Check this guide first
2. Review the implementation plan
3. Check existing code examples
4. Create an issue in the repository

**Happy Coding! 🚀**

# Deploying BLIS Backend to Laravel Cloud

The BLIS backend is production-ready. Follow these steps to deploy it securely to **Laravel Cloud**.

## 1. Environment Variables Setup
When creating your environment in Laravel Cloud, make sure to set the following Environment Variables in the UI:

```env
APP_NAME=Blis
APP_ENV=production
APP_KEY=base64:... # Generate a new key in Laravel Cloud UI or run php artisan key:generate
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

# Frontend Connections
FRONTEND_URL=https://yourdomain.com
SANCTUM_STATEFUL_DOMAINS=yourdomain.com

# PostgreSQL Database (Provided by Laravel Cloud Database or external like Supabase)
DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_PORT=5432
DB_DATABASE=blis
DB_USERNAME=your-user
DB_PASSWORD=your-password
```

## 2. CORS & API Token Configuration
Because we are using token-based authentication (Bearer Tokens) alongside Next.js, `config/cors.php` has been configured to accept `supports_credentials => true` and `allowed_origins => ['*']` by default. 

**For Production:** You should update `config/cors.php` on your production branch to restrict `allowed_origins` strictly to your production Next.js domain (e.g., `https://yourdomain.com`).

## 3. Deployment Hooks
Ensure that your Laravel Cloud deployment script runs the following standard commands upon deployment:

```bash
composer install --no-interaction --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Once deployed, your Next.js application (Phase 3) will be able to connect to `https://api.yourdomain.com/api/v1` to authenticate and fetch live stats!

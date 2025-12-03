# Backend Environment Setup Guide

## Current Configuration
✅ **LOCAL DEVELOPMENT MODE** is active
- App URL: `http://13.220.190.184` (can be changed to localhost)
- Database: Local MySQL
- CORS: Allows frontend and mobile app

---

## Environment Variables Explained

### File: `backend/.env`

### App Configuration
```env
APP_NAME="IELTS Learning App"
APP_ENV=local                    # local | production
APP_DEBUG=true                   # true for development, false for production
APP_URL=http://13.220.190.184   # Your backend URL
```

**For Local Development:**
```env
APP_URL=http://localhost:8000
```

**For Server/Production:**
```env
APP_URL=http://13.220.190.184
APP_ENV=production
APP_DEBUG=false
```

---

### CORS Configuration (Important!)

#### For Local Development (Current)
```env
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:8100,http://127.0.0.1:8100
```

#### For Server/Production
```env
CORS_ALLOWED_ORIGINS=http://ielts-ui.s3-website-us-east-1.amazonaws.com,http://13.220.190.184
```

#### For Both (Recommended during development)
```env
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:8100,http://127.0.0.1:8100,http://ielts-ui.s3-website-us-east-1.amazonaws.com,http://13.220.190.184
```

**Explanation:**
- `localhost:3000` - Frontend web app
- `localhost:8100` - Mobile app (Ionic dev server)
- `127.0.0.1:8100` - Mobile app alternative
- Server URLs - Production frontend and API

---

### Sanctum Configuration
```env
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000,localhost:8100,127.0.0.1:8100,13.220.190.184
```

**Add your domains here** if you have authentication issues.

---

## Switching Environments

### Local Development → Server/Production

1. **Edit `backend/.env`:**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=http://13.220.190.184
   
   CORS_ALLOWED_ORIGINS=http://ielts-ui.s3-website-us-east-1.amazonaws.com,http://13.220.190.184
   ```

2. **Clear cache:**
   ```bash
   cd backend
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   ```

3. **Restart server**

### Server/Production → Local Development

1. **Edit `backend/.env`:**
   ```env
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost:8000
   
   CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:8100,http://127.0.0.1:8100
   ```

2. **Clear cache:**
   ```bash
   cd backend
   php artisan config:clear
   php artisan cache:clear
   ```

3. **Restart server:**
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

---

## Running the Backend

### Local Development
```bash
cd backend
php artisan serve --host=0.0.0.0 --port=8000
```

**Why `--host=0.0.0.0`?**
- Allows access from mobile devices on same network
- Frontend can connect from different ports

### Production Server
Use a proper web server like Nginx or Apache.

---

## Database Configuration

### Local Development
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ielts_learning_app
DB_USERNAME=root
DB_PASSWORD=
```

### Server/Production
```env
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=ielts_learning_app
DB_USERNAME=your-db-user
DB_PASSWORD=your-secure-password
```

---

## Troubleshooting

### CORS Errors

**Symptom:** Frontend/Mobile app shows "CORS policy" error

**Solution:**
1. Check `CORS_ALLOWED_ORIGINS` in `.env`
2. Add your frontend/mobile URL
3. Clear config cache:
   ```bash
   php artisan config:clear
   ```
4. Restart server

### Authentication Issues

**Symptom:** Login works but subsequent requests fail

**Solution:**
1. Check `SANCTUM_STATEFUL_DOMAINS` includes your domain
2. Verify `SESSION_DOMAIN` is correct
3. Clear cache and restart

### Database Connection Failed

**Solution:**
1. Check MySQL is running:
   ```bash
   sudo systemctl status mysql
   ```
2. Verify credentials in `.env`
3. Test connection:
   ```bash
   php artisan migrate:status
   ```

---

## Quick Commands Reference

```bash
# Start backend server
php artisan serve --host=0.0.0.0 --port=8000

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Check current configuration
php artisan config:show cors
php artisan config:show app

# View routes
php artisan route:list
```

---

## Environment Checklist

### Before Starting Development
- [ ] `.env` file exists and configured
- [ ] Database credentials correct
- [ ] CORS origins include `localhost:3000` and `localhost:8100`
- [ ] APP_KEY is generated (`php artisan key:generate`)
- [ ] Migrations run (`php artisan migrate`)

### Before Deploying to Server
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` set to server URL
- [ ] CORS origins updated for production
- [ ] Database credentials for production
- [ ] All caches cleared
- [ ] Proper web server configured (Nginx/Apache)

---

## Related Files

- `backend/.env` - Main environment configuration
- `backend/config/cors.php` - CORS configuration
- `backend/config/sanctum.php` - Authentication configuration
- `backend/config/app.php` - Application configuration

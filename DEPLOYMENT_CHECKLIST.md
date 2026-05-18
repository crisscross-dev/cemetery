# Hostinger Deployment Checklist

## Important Steps for Images to Work

### 1. Update .env file on Hostinger

```env
APP_URL=https://yourdomain.com
```

Replace `yourdomain.com` with your actual domain.

### 2. Create Storage Link

Run this command via SSH or File Manager terminal:

```bash
php artisan storage:link
```

### 3. Set Correct Permissions

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R username:username storage bootstrap/cache
```

Replace `username` with your Hostinger username.

### 4. Verify Directories Exist

Make sure these directories exist:

-   `storage/app/public/graves`
-   `public/storage` (symbolic link created by step 2)

### 5. Clear Cache

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Troubleshooting

### Images still not showing?

1. **Check if storage link exists:**

    ```bash
    ls -la public/ | grep storage
    ```

    Should show: `storage -> ../storage/app/public`

2. **Check uploaded images:**

    ```bash
    ls -la storage/app/public/graves/
    ```

3. **Test image URL directly:**
   Open in browser: `https://yourdomain.com/storage/graves/filename.jpg`

4. **Check .htaccess in public folder** - make sure it exists and has:
    ```apache
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
    ```

### If symbolic link doesn't work on Hostinger:

Some shared hosting doesn't allow symlinks. Alternative solution - copy files instead:

```bash
cp -r storage/app/public/* public/storage/
```

Or modify the controller to save directly to public folder (not recommended for security).

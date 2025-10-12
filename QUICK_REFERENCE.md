# QUICK REFERENCE - EMERGENCY PRODUCTION FIX

## 🔴 PRODUCTION IS DOWN - ACT NOW!

### 1. IMMEDIATE - Check Production
```bash
curl -I https://ainstein.it
```

### 2. CONNECT TO SERVER
```bash
ssh -i ~/.ssh/ainstein_ploi root@135.181.42.233
```

### 3. EMERGENCY ROLLBACK
```bash
cd /var/www/ainstein
git stash
git pull origin sviluppo-tool
php artisan config:clear
php artisan cache:clear
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
systemctl restart php8.2-fpm
```

### 4. CHECK IF FIXED
```bash
curl -I https://ainstein.it
# Should return: HTTP/2 200
```

---

## KEY INFORMATION

**Server:** 135.181.42.233
**SSH Key:** ~/.ssh/ainstein_ploi
**Laravel Path:** /var/www/ainstein
**Branch:** sviluppo-tool
**Error:** 500 after security deployment

---

## IF STILL BROKEN

Check logs:
```bash
tail -100 storage/logs/laravel.log
```

Hard reset:
```bash
git reset --hard origin/sviluppo-tool
composer install --no-dev
php artisan migrate
```

---

## FILES TO CHECK

1. `/var/www/ainstein/bootstrap/app.php` - Remove SecurityHeaders line
2. `/var/www/ainstein/routes/web.php` - Remove throttle middleware
3. Delete: `/var/www/ainstein/app/Http/Middleware/SecurityHeaders.php`

---

## CONTACT INFO

- **Repository:** Check .git/config for remote URL
- **Documentation:** HANDOFF_RESUME.md has full details
- **Changes Made:** DEPLOYMENT_ACTIONS_LOG.md

---

**TIME IS CRITICAL - FIX PRODUCTION FIRST, DOCUMENT LATER!**
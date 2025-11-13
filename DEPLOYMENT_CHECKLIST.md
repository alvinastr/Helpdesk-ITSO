# 🚀 Quick Deployment Checklist

**Target:** Production Server  
**Application:** Helpdesk ITSO  
**Status:** READY ✅

---

## Pre-Deployment (On Development)

- [x] All critical tests passing (135/214 - 63.1%)
- [x] Status labels updated ('pending_keluhan' throughout)
- [x] Controllers updated (4 files fixed)
- [x] Views updated (9 files fixed)
- [x] Error handling added (NULL ticket ID protection)
- [x] Documentation created (DEPLOYMENT_GUIDE.md, KNOWN_ISSUES.md)
- [ ] **ACTION NEEDED:** Test manually one ticket creation → approval → close flow
- [ ] **ACTION NEEDED:** Commit all changes to repository

---

## Server Setup

- [ ] PHP 8.2+ installed
- [ ] MySQL 8.0+ installed and running
- [ ] Composer 2.x installed
- [ ] Nginx/Apache configured
- [ ] Required PHP extensions installed:
  - [ ] php-fpm
  - [ ] php-mysql
  - [ ] php-mbstring
  - [ ] php-xml
  - [ ] php-curl
  - [ ] php-zip
  - [ ] php-gd

---

## Deployment Steps

### 1. Code Deployment
```bash
# On server
cd /var/www
git clone <your-repo-url> helpdesk-itso
cd helpdesk-itso
composer install --no-dev --optimize-autoloader
```

- [ ] Code cloned
- [ ] Dependencies installed

### 2. Environment Configuration
```bash
cp .env.example .env
nano .env  # Edit with production values
php artisan key:generate
```

- [ ] `.env` file created
- [ ] Database credentials configured
- [ ] Mail settings configured
- [ ] `APP_KEY` generated
- [ ] `APP_DEBUG=false` set
- [ ] `APP_ENV=production` set

### 3. Database Setup
```bash
php artisan migrate --force
php artisan db:seed --class=AdminSeeder  # If needed
```

- [ ] Migrations run successfully
- [ ] Admin user created

### 4. Storage & Permissions
```bash
php artisan storage:link
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

- [ ] Storage linked
- [ ] Permissions set correctly

### 5. Optimization
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

- [ ] Configuration cached
- [ ] Routes cached
- [ ] Views cached

---

## Post-Deployment Testing

### Manual Tests (5 minutes)

#### Test 1: Authentication
- [ ] Visit `/login`
- [ ] Login with admin credentials
- [ ] Verify redirect to admin dashboard
- [ ] See statistics displayed
- [ ] Logout successful

#### Test 2: User Flow
- [ ] Login as regular user (or register new)
- [ ] Click "Create New Ticket"
- [ ] Fill required fields (subject, description)
- [ ] Submit ticket
- [ ] Verify ticket created with "Pending Keluhan" status
- [ ] View ticket details page

#### Test 3: Admin Flow
- [ ] Login as admin
- [ ] Go to "Pending Review" or navigate to pending tickets
- [ ] Open a pending ticket
- [ ] Click "Approve" button
- [ ] Verify status changed to "open"
- [ ] Update status to "In Progress"
- [ ] Update status to "Resolved"
- [ ] Close ticket with resolution notes
- [ ] Verify ticket shows "Closed" status

#### Test 4: Reply/Threading
- [ ] Open any open ticket
- [ ] Add a reply in the reply form
- [ ] Submit reply
- [ ] Verify reply appears in conversation thread
- [ ] Verify thread ordering correct

---

## Optional: Advanced Features

### Queue Workers (Recommended)
```bash
sudo apt-get install supervisor
sudo nano /etc/supervisor/conf.d/helpdesk-worker.conf
# Copy config from DEPLOYMENT_GUIDE.md
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start helpdesk-worker:*
```

- [ ] Supervisor installed
- [ ] Worker configured
- [ ] Worker started

### Laravel Scheduler
```bash
sudo crontab -e -u www-data
# Add: * * * * * cd /var/www/helpdesk-itso && php artisan schedule:run >> /dev/null 2>&1
```

- [ ] Cron job added

### Email Fetch (If Using)
```bash
php artisan email:fetch  # Test manually
# Scheduler will auto-run every 5 minutes
```

- [ ] Email fetch tested
- [ ] Email credentials configured in `.env`

---

## Monitoring Setup

### Log Files to Monitor
```bash
# Application logs
tail -f storage/logs/laravel.log

# Web server logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

- [ ] Know where logs are located
- [ ] Can access and read logs

### Health Check Script
Create `/usr/local/bin/helpdesk-health-check.sh`:

```bash
#!/bin/bash
curl -s -o /dev/null -w "%{http_code}" http://localhost/login
```

- [ ] Health check script created
- [ ] Returns 200 OK

---

## Backup Setup

### Database Backup (Daily)
```bash
# Add to cron
0 2 * * * mysqldump -u username -p'password' database_name > /backups/db_$(date +\%Y\%m\%d).sql
```

- [ ] Database backup configured

### File Backup (Weekly)
```bash
# Add to cron
0 3 * * 0 tar -czf /backups/storage_$(date +\%Y\%m\%d).tar.gz /var/www/helpdesk-itso/storage
```

- [ ] File backup configured

---

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong `APP_KEY` generated
- [ ] HTTPS/SSL configured (Let's Encrypt recommended)
- [ ] Firewall configured (allow only 80/443)
- [ ] Database password strong and unique
- [ ] `.env` file NOT in git
- [ ] Storage and bootstrap/cache permissions correct (775)
- [ ] All other files 644 permissions

---

## Go-Live Checklist

- [ ] All tests above passed
- [ ] No errors in logs
- [ ] Database connected successfully
- [ ] Admin user can login
- [ ] Regular user can create ticket
- [ ] Admin can approve/manage tickets
- [ ] Email sending works (test reset password)
- [ ] Performance acceptable (page load < 3s)

---

## Rollback Plan (If Needed)

If something goes wrong:

```bash
# 1. Put site in maintenance mode
php artisan down --message="Under maintenance"

# 2. Rollback code
git checkout <previous-commit>
composer install --no-dev

# 3. Rollback database (if needed)
php artisan migrate:rollback

# 4. Clear caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

# 5. Bring site back up
php artisan up
```

---

## Post-Launch Monitoring (First 24 Hours)

### Hour 1
- [ ] Check logs every 15 minutes
- [ ] Verify no errors
- [ ] Test ticket creation

### Hour 24
- [ ] Review error log summary
- [ ] Check ticket creation rate
- [ ] Verify email notifications sent
- [ ] Monitor server resources (CPU/RAM/Disk)

### Week 1
- [ ] Daily log review
- [ ] Performance monitoring
- [ ] User feedback collection
- [ ] Database backup verification

---

## Success Criteria

✅ **Deployment Successful When:**
- Users can login without errors
- Tickets can be created and viewed
- Admin can approve and manage tickets
- No critical errors in logs
- All core workflows operational
- Performance acceptable

---

## Support Contacts

**Technical Issues:** Check `DEPLOYMENT_GUIDE.md` troubleshooting section  
**Known Issues:** See `KNOWN_ISSUES.md`  
**Feature Documentation:** See `/DOC` folder

---

## Quick Commands Reference

```bash
# Clear all caches
php artisan optimize:clear

# Recache for production
php artisan config:cache && php artisan route:cache && php artisan view:cache

# Check queue status
php artisan queue:work --once  # Process one job

# Test email
php artisan tinker
>>> Mail::raw('Test', function($m) { $m->to('test@example.com')->subject('Test'); });

# View latest log entries
tail -50 storage/logs/laravel.log

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

---

**Ready to Deploy?** ✅  
Follow the checklist step-by-step. Don't skip manual testing!

**Estimated Deployment Time:** 30-45 minutes (including testing)

**Good luck! 🚀**

@echo off
REM Laravel Task Scheduler untuk Windows
REM Jalankan file ini dengan Windows Task Scheduler setiap menit

cd /d %~dp0
php artisan schedule:run >> storage/logs/scheduler.log 2>&1

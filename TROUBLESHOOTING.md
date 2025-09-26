# Smart Queue Registration Troubleshooting Guide

## Issue: Registration shows "Registration failed. Please try again."

### Step 1: Check XAMPP MySQL Service
1. Open XAMPP Control Panel
2. Make sure **MySQL** service is **STARTED** (green)
3. If not started, click "Start" button next to MySQL

### Step 2: Verify Database Setup
1. Visit: `http://localhost/queue_app/mysql_status.php`
2. This will show:
   - ✅ MySQL connection status
   - ✅ Available databases
   - ✅ Table structure
   - ❌ Any issues found

### Step 3: Run Database Setup (if needed)
1. Visit: `http://localhost/queue_app/setup_new.php`
2. This will create:
   - `queue_db` database
   - All required tables
   - Sample data and default accounts

### Step 4: Test Registration Process
1. Visit: `http://localhost/queue_app/test_registration_direct.html`
2. Click "Test Database First" to verify connection
3. Try registration with debug information

### Step 5: Check Registration Page
1. Visit: `http://localhost/queue_app/register.html`
2. Try to register a new customer account
3. Check browser console (F12) for any JavaScript errors

## Default Test Accounts (created during setup):
- **Admin**: `admin@restaurant.com` / `admin123`
- **Customer**: `customer@example.com` / `customer123`

## Files to Check:
- `mysql_status.php` - MySQL service status
- `setup_new.php` - Database initialization
- `test_registration_direct.html` - Registration testing
- `debug_register.php` - Detailed registration debug

## Common Issues:
1. **XAMPP MySQL not running** → Start MySQL in XAMPP Control Panel
2. **Database not created** → Run `setup_new.php`
3. **Wrong database name** → Should be `queue_db`
4. **Table structure mismatch** → Re-run `setup_new.php`

## Next Steps:
1. Follow steps 1-5 above
2. If still failing, check the debug information from test pages
3. Verify exact error messages in browser console

---
*Created: September 26, 2025*
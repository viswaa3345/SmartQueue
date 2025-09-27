Smart Queue Token Booking System - Complete Implementation
CRITICAL: Fix Registration and Login Issues
IMMEDIATE PRIORITY: The current system has registration failures and missing admin registration. Please implement the following fixes:
1. User Registration System
Create a complete user registration system with:

Customer Registration Form: Full registration page with validation
Admin Registration Form: Separate admin account creation interface
Database User Table: Proper schema with all required fields
Registration Validation: Server-side PHP validation with error handling
Password Encryption: Use PHP password_hash() for secure password storage
Role Management: Clear distinction between 'admin' and 'customer' roles

2. Database Schema Requirements
sql-- Users table with proper structure
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'customer') DEFAULT 'customer',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin account
INSERT INTO users (username, email, password, full_name, role) 
VALUES ('admin', 'admin@restaurant.com', '$2y$10$example_hashed_password', 'System Administrator', 'admin');
3. Complete File Structure
Generate ALL necessary files with proper functionality:
smart_queue_system/
├── config/
│   ├── database.php          # Database connection
│   └── config.php           # System configuration
├── includes/
│   ├── header.php           # Common header
│   ├── footer.php           # Common footer
│   └── functions.php        # Utility functions
├── auth/
│   ├── login.php           # Login page for both roles
│   ├── register.php        # Customer registration
│   ├── admin_register.php  # Admin registration (protected)
│   ├── logout.php          # Logout functionality
│   └── authenticate.php    # Login processing
├── customer/
│   ├── dashboard.php       # Customer dashboard
│   ├── book_token.php      # Token booking
│   ├── my_tokens.php       # View customer tokens
│   └── profile.php         # Customer profile
├── admin/
│   ├── dashboard.php       # Admin dashboard
│   ├── manage_tokens.php   # Token management
│   ├── manage_food.php     # Food item management
│   ├── manage_users.php    # User management
│   └── settings.php        # System settings
├── assets/
│   ├── css/
│   │   └── style.css       # Complete styling
│   ├── js/
│   │   └── script.js       # JavaScript functionality
│   └── images/             # System images
├── api/
│   ├── location.php        # Location tracking API
│   ├── notifications.php   # Notification system
│   └── queue_status.php    # Real-time queue updates
├── index.php               # Landing page
└── install.php             # Database setup script
4. Registration Forms Requirements
Customer Registration Form (auth/register.php):

Full name, email, username, password, confirm password, phone
Client-side validation with JavaScript
Server-side validation with proper error messages
Success/error message display
Redirect to login after successful registration

Admin Registration Form (auth/admin_register.php):

Same fields as customer but with admin role assignment
Additional security (maybe admin key verification)
Accessible only via direct URL or existing admin account

5. Authentication System Features
Login System (auth/login.php):

Single login page with role detection
Remember me functionality
Session management with proper security
Redirect to appropriate dashboard based on role
Clear error messages for failed attempts

Session Management:

Secure session handling
Auto-logout after inactivity
Role-based access control
CSRF protection

6. Core System Features to Implement
Customer Dashboard:

Food menu display with images and prices
Token booking interface
Current queue position display
Real-time waiting time updates
Location sharing permission request
Notification preferences

Admin Dashboard:

Live queue management interface
Token calling system (Next, Skip, Complete)
Customer location monitoring
Food item management (CRUD operations)
System analytics and reports
Notification management

7. Location Tracking System

HTML5 Geolocation API integration
Real-time location updates
Proximity calculations
Auto-cancel for out-of-range customers
Location boundary settings
GPS accuracy handling

8. Notification System

Browser push notifications
In-app notification center
5-minute advance alerts
SMS integration (optional)
Email notifications
Admin notification dashboard

9. Database Complete Schema
sql-- Additional required tables
CREATE TABLE food_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    category VARCHAR(50),
    availability BOOLEAN DEFAULT TRUE,
    preparation_time INT DEFAULT 15,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    food_item_id INT NOT NULL,
    quantity INT DEFAULT 1,
    status ENUM('active', 'called', 'completed', 'cancelled') DEFAULT 'active',
    estimated_time INT,
    actual_time INT,
    location_lat DECIMAL(10, 8),
    location_lng DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    called_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (food_item_id) REFERENCES food_items(id)
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_id INT,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    status ENUM('unread', 'read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (token_id) REFERENCES tokens(id)
);

CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('restaurant_name', 'Smart Queue Restaurant', 'Restaurant name'),
('pickup_radius', '100', 'Pickup radius in meters'),
('notification_advance_time', '5', 'Minutes before token is called'),
('max_queue_size', '50', 'Maximum tokens in queue'),
('default_prep_time', '15', 'Default preparation time in minutes');
10. UI/UX Design Requirements

Color Scheme: Warm food service colors (oranges, reds, yellows)
Responsive Design: Mobile-first approach with Bootstrap 5
Real-time Updates: AJAX polling every 30 seconds
Professional Look: Clean, modern interface suitable for restaurants
Accessibility: WCAG compliant with proper contrast and navigation

11. Security Features

Password hashing with PHP password_hash()
SQL injection prevention with prepared statements
XSS protection with input sanitization
CSRF token protection
Session security with HTTP-only cookies
Input validation on both client and server side

12. Installation and Setup
Create an install.php script that:

Creates database tables automatically
Sets up default admin account
Configures initial system settings
Checks server requirements
Provides setup completion confirmation

TESTING REQUIREMENTS
Ensure the system includes:

Registration form validation testing
Login/logout functionality testing
Token booking and management testing
Location tracking accuracy testing
Notification system testing
Cross-browser compatibility testing

DEPLOYMENT CHECKLIST

All files properly organized in directory structure
Database connection properly configured
Default admin account created successfully
Customer registration working without errors
Login system redirecting to correct dashboards
All security measures implemented
Mobile responsiveness verified

CRITICAL SUCCESS CRITERIA:

Customer can register new account successfully
Admin registration interface is accessible and functional
Login system works for both roles
Dashboard redirection works properly
No "registration failed" errors
All database operations complete successfully

Generate the complete, functional code for this system ensuring all registration and login issues are resolved.
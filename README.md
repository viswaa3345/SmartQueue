# Smart Queue Token Booking System

A complete web-based queue management system for food service establishments with dual login functionality, real-time location tracking, and comprehensive admin controls.

## Features

### 🔐 Dual Login System
- **Admin Login**: Complete system management and control
- **Customer Login**: Token booking and status tracking
- Secure session management with role-based access

### 👤 Customer Features
- **Food Menu Browsing**: View available items with prices and preparation times
- **Token Booking**: Generate unique tokens with queue position
- **Real-time Status**: Live updates on token status and waiting time
- **Location Tracking**: GPS-based location monitoring
- **Notifications**: In-app alerts for token updates
- **Mobile Responsive**: Works seamlessly on all devices

### 👨‍💼 Admin Features
- **Queue Management**: View and control all active tokens
- **Token Actions**: Call, complete, cancel, or reactivate tokens
- **Menu Management**: Add, edit, or remove food items
- **Dashboard Analytics**: Real-time statistics and overview
- **Customer Monitoring**: Track customer locations and status

### 🌍 Location-Based Features
- **GPS Tracking**: Automatic customer location detection
- **Proximity Alerts**: Monitor customer distance from pickup point
- **Location Status**: Real-time location updates

## Installation

### Prerequisites
- XAMPP with Apache and MySQL running
- PHP 7.4 or higher
- Modern web browser

### Setup Instructions

1. **Start XAMPP Services**
   - Start Apache and MySQL services in XAMPP Control Panel

2. **Database Setup**
   - Open your browser and navigate to: `http://localhost/queue_app/setup.php`
   - This will automatically create the database and tables with sample data

3. **Access the System**
   - Navigate to: `http://localhost/queue_app/`
   - Use the login credentials provided after setup

## Default Login Credentials

### Admin Access
- **Username**: admin
- **Password**: admin123

### Customer Access
- Register a new account using the "Create Account" option on the login page

## System Architecture

### Database Tables
- **users**: Admin and customer account information
- **food_items**: Menu items with prices and preparation times
- **tokens**: Queue tokens with status and timestamps
- **locations**: Customer location tracking data
- **notifications**: System messages and alerts
- **settings**: System configuration parameters

### API Endpoints
- `api/authenticate.php` - User authentication
- `api/register.php` - Customer registration
- `api/book_token.php` - Token booking and retrieval
- `api/cancel_token.php` - Token cancellation
- `api/admin_token.php` - Admin token management
- `api/food_items.php` - Menu management
- `api/check_session.php` - Session validation

## Usage Guide

### For Customers
1. **Login/Register**: Create an account or login with existing credentials
2. **Browse Menu**: View available food items with prices
3. **Select Item**: Choose desired food item and quantity
4. **Book Token**: Generate your unique queue token
5. **Track Status**: Monitor your token status in real-time
6. **Location Sharing**: Allow location access for proximity tracking

### For Administrators
1. **Login**: Use admin credentials to access the admin dashboard
2. **Monitor Queue**: View all active tokens and customer information
3. **Manage Tokens**: Call, complete, cancel, or reactivate tokens
4. **Update Menu**: Add new items or modify existing menu items
5. **View Analytics**: Monitor system statistics and performance

## Technical Specifications

### Frontend
- **HTML5/CSS3**: Modern, responsive design
- **Bootstrap 5**: Mobile-first responsive framework
- **JavaScript ES6+**: Modern JavaScript features
- **Font Awesome**: Professional icons
- **AJAX**: Asynchronous data communication

### Backend
- **PHP 7.4+**: Server-side processing
- **MySQL**: Relational database management
- **PDO**: Secure database connectivity
- **Session Management**: Secure user sessions
- **JSON API**: RESTful API endpoints

### Security Features
- **Password Hashing**: Secure bcrypt password encryption
- **SQL Injection Protection**: Prepared statements
- **Session Security**: Secure session management
- **Role-based Access**: Admin/customer access control
- **Input Validation**: Server-side data validation

## Color Scheme
- **Primary Orange**: #ff6b35 (Brand color)
- **Secondary Orange**: #f7931e (Accent color)
- **Success Green**: #28a745 (Success states)
- **Warning Yellow**: #ffc107 (Warning states)
- **Danger Red**: #dc3545 (Error states)

## Browser Support
- Chrome (recommended)
- Firefox
- Safari
- Edge
- Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Ensure MySQL service is running in XAMPP
   - Check database credentials in `api/db.php`

2. **Permission Denied**
   - Verify XAMPP is running with proper permissions
   - Check file permissions in htdocs folder

3. **Location Not Working**
   - Ensure HTTPS is enabled for location services
   - Allow location permission in browser

4. **Session Issues**
   - Clear browser cache and cookies
   - Check PHP session configuration

### Support
For technical support or feature requests, please check the system logs and ensure all prerequisites are met.

## File Structure
```
queue_app/
├── index.html              # Login page
├── user_dashboard.html     # Customer dashboard
├── admin_dashboard.html    # Admin dashboard
├── setup.php              # Database setup script
├── api/                   # Backend API endpoints
│   ├── db.php             # Database configuration
│   ├── headers.php        # CORS headers
│   ├── auth_helper.php    # Authentication utilities
│   ├── authenticate.php   # Login endpoint
│   ├── register.php       # Registration endpoint
│   ├── book_token.php     # Token booking
│   ├── cancel_token.php   # Token cancellation
│   ├── admin_token.php    # Admin token management
│   ├── food_items.php     # Menu management
│   └── check_session.php  # Session validation
```

## Version
Current Version: 1.0.0

## License
This project is for educational and demonstration purposes.
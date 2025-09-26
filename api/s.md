Create a complete smart queue token booking system with the following specifications:
Core Requirements
1. Dual Login System

Admin Login: Full system access and management
Customer Login: Booking and status checking only
Use session management to maintain login states
Redirect to appropriate dashboards after login

2. Customer Features
Create customer dashboard with:
- Food item browsing and selection interface
- Token booking system with unique token generation
- Real-time waiting time display
- Current queue position indicator
- Notification system for 5-minute alerts before their turn
- Automatic location tracking (GPS/device location)
- Order status: Active, Called, Cancelled, Completed
3. Admin Features
Create admin dashboard with:
- Token management system (call next token, skip token)
- Customer location monitoring with proximity alerts
- Queue management (view all active tokens)
- Food item management (add/edit/delete items)
- Waiting time estimation settings
- Notification management system
- Location boundary settings for auto-cancellation
4. Location-Based Features
Implement:
- Real-time location tracking for customers
- Proximity detection (when customer is within pickup range)
- Auto-skip/cancel tokens if customer is outside defined radius
- Re-queue system for returning customers
- Distance calculation from pickup point
5. Notification System
Create notification features:
- 5-minute advance alerts before token is called
- SMS/email notifications (optional)
- In-app notifications
- Push notifications for mobile browsers
- Admin alerts for customer proximity status
6. Database Structure
Create tables for:
- users (admin/customer accounts)
- food_items (menu with prices, availability)
- tokens (booking details, status, timestamps)
- locations (customer coordinates, proximity settings)
- notifications (message queue, delivery status)
7. UI/UX Requirements
Design with:
- Modern, clean interface suitable for food service
- Color scheme: warm colors (orange, red, yellow themes)
- Mobile-responsive design
- Real-time updates using AJAX/WebSocket
- Professional food service background images
- Easy-to-read token numbers and status indicators
8. Technical Stack Integration
Continue using:
- PHP with XAMPP server
- MySQL database
- HTML/CSS/JavaScript for frontend
- Bootstrap or similar framework for styling
- Location API (HTML5 Geolocation)
- Real-time updates (consider WebSocket or polling)
Implementation Priority Order

Set up dual login system with role-based access
Create basic customer dashboard with food selection
Implement token generation and queue system
Add admin dashboard with token management
Integrate location tracking and proximity features
Implement notification system
Add auto-cancellation logic
Style with appropriate food service theme

Additional Features to Consider

Token expiry system
Peak hours management
Customer feedback system
Order history tracking
Analytics dashboard for admin

Please generate the complete code structure with all necessary files, database schema, and implementation details for this smart queue token booking system.
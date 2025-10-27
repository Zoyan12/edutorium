# Maintenance Mode Feature

A comprehensive maintenance mode system for the Edutorium platform that allows administrators to temporarily disable the site for maintenance while providing a professional maintenance page to users.

## Features

### ✅ Complete Functionality
- **Admin Control Panel**: Full maintenance mode management in the admin panel
- **Custom Messages**: Editable maintenance messages and expected resolution times
- **Admin Bypass**: Admins can bypass maintenance mode to test and monitor
- **Responsive Design**: Beautiful, responsive maintenance page with gradient background
- **Database Persistence**: All settings stored in database for persistence across restarts
- **Client-Side Checking**: JavaScript utilities for real-time maintenance status checking
- **Page Exclusions**: Smart exclusion of admin, API, and static resource pages

### 🎨 Visual Design
- **Gradient Background**: Modern gradient background with glassmorphism effects
- **Center-Aligned Layout**: Professional center-aligned message box
- **Responsive Design**: Works perfectly on all device sizes
- **Loading Animations**: Smooth loading animations and transitions
- **Status Indicators**: Clear visual indicators for maintenance status

### 🔧 Technical Features
- **Database Integration**: Uses Supabase for data persistence
- **Session Management**: Proper session handling for admin authentication
- **Cookie-Based Bypass**: Secure bypass mechanism using HTTP cookies
- **API Endpoints**: RESTful API for maintenance mode management
- **Error Handling**: Comprehensive error handling and fallbacks

## Installation

### 1. Database Setup

Run the SQL setup script to create the necessary tables and settings:

```sql
-- Run this script in your Supabase database
-- File: client/sql/maintenance_mode_setup.sql
```

### 2. File Structure

The following files have been created/modified:

```
client/
├── admin/
│   ├── maintenance.php              # Admin maintenance management page
│   ├── api/maintenance.php          # Maintenance API endpoints
│   └── includes/sidebar.php        # Updated with maintenance link
├── includes/
│   └── maintenance-check.php       # Maintenance mode middleware
├── js/utils/
│   └── maintenanceChecker.js        # Client-side maintenance checker
├── pages/
│   ├── dashboard.php               # Updated with maintenance check
│   └── battle.php                  # Updated with maintenance check (unified for all battle types)
├── maintenance.php                  # Public maintenance page
├── index.php                       # Updated main page
└── test-maintenance-mode.php        # Test suite
```

### 3. Configuration

No additional configuration is required. The system uses existing Supabase configuration and admin authentication.

## Usage

### Admin Panel

1. **Access Admin Panel**: Navigate to `/admin/maintenance.php`
2. **Enable Maintenance Mode**:
   - Enter custom message for users
   - Set expected resolution time
   - Specify reason for maintenance
   - Set duration in minutes
   - Click "Enable Maintenance Mode"

3. **Disable Maintenance Mode**: Click "Disable Maintenance Mode" button

4. **Preview Maintenance Page**: Click "Preview Maintenance Page" to see how it looks

### User Experience

When maintenance mode is active:
- **Regular Users**: Redirected to `/maintenance.php` with custom message
- **Admin Users**: Can bypass maintenance mode using the bypass button
- **Excluded Pages**: Admin, API, and static resource pages remain accessible

### Maintenance Page Features

The maintenance page includes:
- **Custom Message**: Displayed prominently to users
- **Expected Resolution**: Shows when maintenance should be complete
- **Reason**: Explains why maintenance is happening
- **Countdown Timer**: Shows estimated time remaining
- **Refresh Button**: Allows users to check status
- **Contact Support**: Link to contact support
- **Admin Bypass**: For admin users to access the site

## API Endpoints

### Public Endpoints

- `GET /admin/api/maintenance.php?action=current` - Get current maintenance status (public)

### Admin Endpoints (Require Authentication)

- `GET /admin/api/maintenance.php?action=status` - Get detailed maintenance status
- `GET /admin/api/maintenance.php?action=history` - Get maintenance history
- `POST /admin/api/maintenance.php` - Enable maintenance mode
- `PATCH /admin/api/maintenance.php` - Update/disable maintenance mode
- `DELETE /admin/api/maintenance.php` - Disable maintenance mode

## Database Schema

### maintenance_mode Table

```sql
CREATE TABLE public.maintenance_mode (
  id integer NOT NULL DEFAULT nextval('maintenance_mode_id_seq'::regclass),
  is_active boolean NOT NULL DEFAULT false,
  start_time timestamp with time zone NOT NULL DEFAULT now(),
  duration_minutes integer NOT NULL DEFAULT 60,
  started_by uuid,
  reason text NOT NULL DEFAULT 'Scheduled maintenance',
  user_message text NOT NULL DEFAULT 'We are currently performing scheduled maintenance. Please check back later.',
  expected_resolution text NOT NULL DEFAULT 'Soon',
  created_at timestamp with time zone NOT NULL DEFAULT now(),
  updated_at timestamp with time zone NOT NULL DEFAULT now(),
  CONSTRAINT maintenance_mode_pkey PRIMARY KEY (id),
  CONSTRAINT maintenance_mode_started_by_fkey FOREIGN KEY (started_by) REFERENCES auth.users(id)
);
```

### Settings Integration

The system also uses the existing `settings` table for default values:
- `maintenance_mode_enabled` - Global toggle
- `maintenance_mode_message` - Default message
- `maintenance_mode_duration` - Default duration
- `maintenance_mode_reason` - Default reason

## Testing

Run the comprehensive test suite:

```bash
# Navigate to the test file in your browser
http://your-domain/test-maintenance-mode.php
```

The test suite checks:
- Database connectivity
- API endpoints
- Maintenance check functions
- Page integration
- Admin panel integration
- JavaScript utilities
- Settings integration

## Security Features

### Admin Authentication
- Requires valid admin session
- Checks `is_admin` flag in profiles table
- Session-based authentication

### Bypass Security
- Cookie-based bypass mechanism
- 1-hour expiration for bypass cookies
- Only available to authenticated admin users

### Page Exclusions
- Admin pages remain accessible during maintenance
- API endpoints continue to function
- Static resources (CSS, JS, images) remain accessible

## Customization

### Styling
The maintenance page uses CSS custom properties for easy customization:

```css
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --maintenance-bg: rgba(255, 255, 255, 0.95);
    --text-color: #333;
    --accent-color: #667eea;
}
```

### Messages
All maintenance messages are customizable through the admin panel:
- User message (main content)
- Expected resolution time
- Reason for maintenance
- Duration

### Excluded Pages
Modify the exclusion list in `includes/maintenance-check.php`:

```php
$excludedPages = [
    '/maintenance.php',
    '/admin/',
    '/api/',
    // Add more pages as needed
];
```

## Troubleshooting

### Common Issues

1. **Maintenance mode not working**
   - Check database connection
   - Verify maintenance_mode table exists
   - Check admin authentication

2. **Admin bypass not working**
   - Verify admin session is active
   - Check `is_admin` flag in profiles table
   - Clear browser cookies and try again

3. **Pages not redirecting**
   - Ensure `maintenance-check.php` is included in PHP pages
   - Check page exclusion list
   - Verify maintenance mode is actually active

### Debug Mode

Enable debug logging by setting `APP_ENV` to `development` in `config.php`:

```php
define('APP_ENV', 'development');
```

## Support

For issues or questions:
1. Check the test suite results
2. Review the troubleshooting section
3. Check browser console for JavaScript errors
4. Verify database connectivity and permissions

## Future Enhancements

Potential future improvements:
- **Scheduled Maintenance**: Set maintenance windows in advance
- **Email Notifications**: Notify users of upcoming maintenance
- **Maintenance Logs**: Detailed logging of maintenance activities
- **Multiple Language Support**: Internationalization for maintenance messages
- **Custom Themes**: Multiple maintenance page themes
- **Progress Indicators**: Show maintenance progress to users

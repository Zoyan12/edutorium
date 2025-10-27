# Admin Panel - Edutorium

A comprehensive admin panel for managing the Edutorium learning platform.

## Features

### 🏠 Dashboard
- Real-time statistics (users, questions, battles, friendships)
- Recent activity overview
- Quick action buttons
- System status indicators

### 👥 User Management
- View all users with pagination
- Search and filter users by field, admin status
- Edit user profiles (username, field, points, admin status)
- Delete users with confirmation
- Toggle admin privileges

### ❓ Question Management
- Grid view of all questions with images
- Filter by subject and difficulty
- Add new questions with image upload
- Edit existing questions
- Delete questions (with image cleanup)
- Image preview functionality

### 🎮 Battle Records
- View all battle history
- Filter by mode (arena/quick), result, player, date range
- Detailed battle statistics
- Export to CSV functionality
- Battle duration and performance metrics

### 👫 Friendship Management
- View all friendship relationships
- Filter by status (pending/accepted)
- Approve pending friend requests
- Delete friendships
- Bulk approve all pending requests

### ⚙️ System Settings
- Manage system configuration
- Add/edit/delete custom settings
- Quick settings for common configurations
- WebSocket URL management
- Maintenance mode toggle

## Technical Details

### Authentication
- Uses existing Supabase authentication
- Checks `is_admin` flag from profiles table
- Redirects non-admins to main dashboard
- Session-based admin verification

### API Endpoints
- RESTful API design
- JSON responses
- Error handling with proper HTTP status codes
- Input validation and sanitization

### Security
- Admin-only access control
- CSRF token validation
- Input sanitization
- Row Level Security (RLS) policies
- File upload validation

### Database Integration
- Uses existing Supabase configuration
- Leverages existing `supabaseRequest()` function
- Maintains data consistency
- Proper foreign key relationships

## File Structure

```
/admin/
├── index.php              # Dashboard
├── users.php              # User management
├── questions.php          # Question management
├── battles.php            # Battle records viewer
├── friendships.php        # Friendship management
├── settings.php           # System settings
├── test.php               # Test page
├── includes/
│   ├── auth-check.php     # Admin authentication
│   ├── sidebar.php        # Navigation sidebar
│   └── header.php         # Page header
├── api/
│   ├── users.php          # User CRUD API
│   ├── questions.php      # Question CRUD API
│   ├── battles.php        # Battle data API
│   ├── friendships.php    # Friendship CRUD API
│   └── settings.php       # Settings CRUD API
├── js/
│   └── utils.js           # Shared utilities
└── css/
    └── admin.css          # Admin panel styling
```

## Usage

### Accessing the Admin Panel
1. Log in as a user with `is_admin = true` in the profiles table
2. The admin panel button will appear in the main dashboard sidebar
3. Click "Admin Panel" to access the admin interface

### Making a User Admin
1. Go to Users management page
2. Find the user you want to make admin
3. Click the edit button
4. Check the "Admin User" checkbox
5. Save changes

### Adding Questions
1. Go to Questions management page
2. Click "Add New Question"
3. Upload an image or provide image URL
4. Set correct answer (A, B, C, or D)
5. Select subject and difficulty
6. Save the question

### Managing Settings
1. Go to Settings page
2. View all current settings
3. Edit existing settings or add new ones
4. Use quick settings for common configurations

## Browser Support
- Modern browsers with ES6 support
- Responsive design for mobile devices
- Font Awesome icons
- Chart.js for statistics (if needed)

## Dependencies
- Supabase JavaScript client
- Font Awesome icons
- Existing Edutorium CSS framework
- PHP 7.4+ with cURL support

## Installation Notes
- No additional installation required
- Uses existing project structure
- Integrates with current authentication system
- Maintains existing styling consistency

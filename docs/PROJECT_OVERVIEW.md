# Edutorium - Project Overview

## 📚 About Edutorium

Edutorium is a real-time educational battle platform where students compete in knowledge-based challenges. Users can join battles, answer questions across various subjects, and compete against other players in real-time.

## 🏗️ Project Structure

```
C:\xampp\htdocs\
├── client/                          # Main application
│   ├── pages/                       # Frontend pages
│   │   ├── dashboard.php           # Main dashboard
│   │   ├── battle.php              # Battle interface (to be rebuilt)
│   │   ├── login.html              # Authentication
│   │   └── signup.html             # User registration
│   │
│   ├── admin/                       # Admin panel
│   │   ├── index.php               # Admin dashboard
│   │   ├── users.php                # User management
│   │   ├── questions.php           # Question management
│   │   ├── battles.php             # Battle history
│   │   ├── friendships.php         # Friend requests
│   │   └── maintenance.php         # Maintenance mode
│   │
│   ├── includes/                   # Shared PHP files
│   │   ├── config.php              # App configuration
│   │   ├── functions.php           # Helper functions
│   │   └── maintenance-check.php  # Maintenance middleware
│   │
│   ├── js/                          # JavaScript modules
│   │   ├── auth.js                 # Authentication
│   │   ├── battle-websocket.js     # WebSocket client
│   │   ├── main.js                 # Main app logic
│   │   └── components/             # UI components
│   │
│   ├── css/                         # Stylesheets
│   ├── sql/                         # Database migrations
│   ├── api/                         # REST API endpoints
│   └── battle-server.php            # WebSocket server (PHP)
│
├── server/                          # Standalone WebSocket server
│   ├── battle-server.php           # WebSocket handler
│   ├── config/                      # Server configuration
│   └── Dockerfile                   # Container config
│
└── ...
```

## ✅ What's Currently Implemented

### 1. **Authentication System**
- ✅ User registration and login via Supabase Auth
- ✅ JWT token-based authentication
- ✅ Session management
- ✅ Profile creation and management

### 2. **Dashboard**
- ✅ User dashboard with statistics
- ✅ Friend system integration
- ✅ Battle history
- ✅ Profile management
- ✅ Points system

### 3. **Admin Panel**
- ✅ Complete admin interface
- ✅ User management (CRUD operations)
- ✅ Question management with image upload
- ✅ Battle records viewer
- ✅ Friendship management
- ✅ Maintenance mode control
- ✅ System settings management

### 4. **Maintenance Mode**
- ✅ Database-driven maintenance system
- ✅ Admin bypass functionality
- ✅ Customizable maintenance messages
- ✅ Automatic redirection to maintenance page
- ✅ API and static resource exclusions

### 5. **Database**
- ✅ Profiles table (users, points, stats)
- ✅ Questions table (questions, answers, images)
- ✅ Battle records table
- ✅ Battle responses table
- ✅ Friend relationships table
- ✅ Settings table
- ✅ Maintenance logs table

### 6. **WebSocket Infrastructure**
- ✅ WebSocket server setup (PHP/Ratchet)
- ✅ Separate server deployment capability
- ✅ Health check endpoints
- ✅ Docker containerization
- ⚠️ Battle logic needs rebuilding

## 🔧 Technology Stack

| Component | Technology |
|-----------|-----------|
| **Frontend** | HTML5, CSS3, JavaScript (ES6), Font Awesome |
| **Backend** | PHP 8.2, Apache |
| **Database** | Supabase (PostgreSQL) |
| **Real-time** | WebSocket (Ratchet PHP library) |
| **Auth** | Supabase Auth with JWT |
| **Deployment** | Docker, Docker Compose |
| **Package Manager** | Composer |

## 📊 Database Schema

### Core Tables

#### `profiles`
- User profiles with points, stats, avatar
- Linked to Supabase Auth users

#### `questions`
- Educational questions with images
- Multiple choice format (A/B/C/D)
- Subject and difficulty categorization

#### `battle_records`
- Records of all battles
- Tracks players, scores, results
- Supports arena and quick battle modes

#### `battle_responses`
- Individual answers for each battle
- Tracks correctness and response time

#### `friend_relationships`
- Friend connections between users
- Status: pending/accepted

#### `settings`
- Key-value configuration storage
- WebSocket URLs, maintenance status, etc.

## 🎯 Current Status

### ✅ Working Features
1. **User Authentication** - Complete login/signup flow
2. **Dashboard** - Functional user dashboard
3. **Admin Panel** - Full admin functionality
4. **Friend System** - Add/accept friends
5. **Maintenance Mode** - Admin-controlled site maintenance
6. **Database** - All tables properly configured

### ⚠️ Needs Rebuilding
1. **Battle System** - Currently being redesigned
   - Battle page UI
   - Question display logic
   - Real-time synchronization
   - Score calculation

## 🚀 Quick Start

### Prerequisites
- XAMPP (PHP 8.2, Apache)
- Composer
- Supabase account
- Git

### Setup

1. **Install Dependencies**
   ```bash
   cd client
   composer install
   ```

2. **Configure Supabase**
   - Update `includes/config.php` with your Supabase credentials
   - Run database migration scripts in `sql/` folder

3. **Start WebSocket Server**
   ```bash
   php battle-server.php
   ```
   Or use: `start-battle-server.bat`

4. **Access Application**
   - Dashboard: `http://localhost/client/pages/dashboard.php`
   - Admin Panel: `http://localhost/client/admin/`
   - Login: `http://localhost/client/pages/login.html`

## 📁 Key Files

| File | Purpose |
|------|---------|
| `client/pages/dashboard.php` | Main user dashboard |
| `client/pages/battle.php` | Battle interface (to be rebuilt) |
| `client/admin/index.php` | Admin dashboard |
| `client/battle-server.php` | WebSocket server |
| `server/battle-server.php` | Standalone WebSocket server |
| `client/includes/config.php` | Application configuration |
| `client/sql/` | Database migration scripts |

## 🔐 Security Features

- ✅ Supabase Row Level Security (RLS)
- ✅ JWT token authentication
- ✅ Session management
- ✅ Admin-only endpoints
- ✅ Input sanitization
- ✅ CSRF protection on admin panel

## 🎨 UI/UX

- Modern gradient designs
- Responsive layout
- Toast notifications
- Loading animations
- Clean, professional interface
- Font Awesome icons

## 📝 Next Steps

1. **Rebuild Battle System** - Complete battle interface with proper question display
2. **Real-time Sync** - Synchronize battle state between players
3. **Opponent Tracking** - Track opponent progress and scores
4. **Result Modal** - Finalize battle results display
5. **Testing** - Comprehensive testing of all features

## 🛠️ Development

### Running the Server
```bash
# Windows
start-battle-server.bat

# Linux/Mac
php battle-server.php

# Or with Docker
docker-compose up -d
```

### Database Migrations
All SQL files are in `client/sql/` folder. Run them in Supabase SQL Editor.

## 📞 Support

For issues or questions, refer to:
- `WEBSOCKET_TROUBLESHOOTING.md`
- `MAINTENANCE_MODE_README.md`
- `COOLIFY_TROUBLESHOOTING.md`

---

**Last Updated:** 2024
**Version:** 1.0
**Status:** Battle System in Development


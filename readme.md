# Edutorium Battle System

This is a real-time educational battle system for Edutorium, allowing users to compete in knowledge challenges against each other.

## Features

- Real-time matchmaking and battles via WebSockets
- Subject, question count, and difficulty selection
- Live battle with opponent
- Score tracking and statistics
- Friend system integration

## Setup

### Prerequisites

- PHP 7.4 or higher
- Composer
- Supabase account and project
- Web server (Apache/Nginx)

### Installation

1. Clone the repository
2. Install dependencies:
   ```
   composer install
   ```
3. Update Supabase credentials in your HTML files:
   - In `dashboard.html`
   - In `battle.html`

4. Start the WebSocket server:
   ```
   php battle-server.php
   ```
   This will start the WebSocket server on port 8080.

### Database Requirements

The system uses Supabase and requires these tables:
- `profiles` - User profiles with points and stats
- `friend_relationships` - Friend connections between users
- `battle_history` - Records of completed battles

## How It Works

### Battle Flow

1. User clicks on Battle in the dashboard
2. User selects:
   - Subject(s)
   - Number of questions
   - Difficulty level
3. Matchmaking begins
4. When opponent is found, both users confirm readiness
5. Battle starts with synchronized questions
6. Players answer questions and see results
7. Final score is calculated and stored in the database

### WebSocket Communication

The WebSocket server (`battle-server.php`) handles:
- User authentication
- Matchmaking
- Battle synchronization
- Score calculation
- Result storage

Messages follow a standardized format with an `action` field indicating the message type.

### Files

- `dashboard.html` - Main interface with battle initiation
- `battle.html` - Battle interface
- `battle-server.php` - WebSocket server for real-time communication
- `composer.json` - Dependencies configuration

## Extending

To add more features:
- Add new question types by extending the question rendering logic
- Create additional battle modes by modifying the matchmaking system
- Implement team battles by extending the player grouping logic

## Troubleshooting

If you encounter connection issues:
1. Ensure the WebSocket server is running
2. Check that port 8080 is open and accessible
3. Verify Supabase credentials are correct
4. Check browser console for error messages

# Edutorium Battle Server

This is the real-time battle server for Edutorium, allowing users to compete in educational battles.

## Setup

### Requirements
- PHP 7.4 or higher
- Composer

### Installation

#### Windows
1. Run `setup-battle-server.bat` to install dependencies.
2. Run `start-battle-server.bat` to start the server.

#### Linux/Mac
1. Install dependencies: `composer install`
2. Make the start script executable: `chmod +x start-battle-server.sh`
3. Run the server: `./start-battle-server.sh`

## Server Information
- The WebSocket server runs on port 8080
- Client pages connect to `ws://hostname:8080`

## Troubleshooting

### WebSocket Connection Failures
- Make sure the server is running
- Check if port 8080 is available and not blocked by a firewall
- Verify that your browser supports WebSockets

### "Connection refused" error
- The server might be down or not running
- Try restarting the battle server using the script

### "Already in use" error for port 8080
- Another application may be using port 8080
- Stop the other application or modify the port number in battle-server.php and update the client connections 
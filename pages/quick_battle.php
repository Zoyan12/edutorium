<?php
// Include the functions file to get getWebSocketUrl()
require_once '../includes/functions.php';

// Get the WebSocket URL from the database
$websocketUrl = getWebSocketUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Battle - Edutorium</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #45a049;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --text-color: #333;
            --bg-color: #f5f5f5;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
            --border-color: #e9ecef;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 8px rgba(0,0,0,0.1);
            --shadow-lg: 0 8px 16px rgba(0,0,0,0.1);
            --transition-speed: 0.3s;
            --border-radius: 10px;
            --card-border-radius: 15px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }
        
        /* Animation Keyframes */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Header */
        .battle-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            background-size: 200% 200%;
            animation: gradientAnimation 10s ease infinite;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
            z-index: 10;
            text-align: center;
            box-shadow: var(--shadow-md);
        }
        
        .battle-header h1 {
            font-size: 1.8rem;
            margin: 0;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.6s ease-out;
        }
        
        .battle-header h1 i {
            margin-right: 10px;
            color: var(--warning-color);
        }
        
        /* Battle Status Bar */
        .battle-status-bar {
            background-color: white;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
            border-bottom: 1px solid var(--border-color);
            animation: slideIn 0.5s ease-out;
        }
        
        .battle-status-bar .player-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px;
            border-radius: var(--border-radius);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .battle-status-bar .player-info:hover {
            background-color: rgba(0, 0, 0, 0.02);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        
        .battle-status-bar .player-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--primary-color);
            position: relative;
            background-color: white;
            box-shadow: var(--shadow-sm);
        }
        
        .battle-status-bar .player-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .battle-status-bar .player-name {
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .battle-status-bar .player-score {
            background-color: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
            min-width: 36px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            animation: pulse 2s infinite;
        }
        
        .battle-center-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            background-color: rgba(0, 0, 0, 0.02);
            padding: 8px 15px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
        }
        
        .connection-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            padding: 4px 10px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.8);
            box-shadow: var(--shadow-sm);
        }
        
        .connection-status.connected {
            color: var(--success-color);
        }
        
        .connection-status.connecting {
            color: var(--warning-color);
        }
        
        .connection-status.disconnected {
            color: var(--danger-color);
        }
        
        .battle-timer {
            background: rgba(0, 0, 0, 0.05);
            padding: 6px 12px;
            border-radius: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
            box-shadow: var(--shadow-sm);
        }
        
        .battle-timer i {
            color: var(--primary-color);
        }
        
        /* Player status indicators */
        .player-status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
            vertical-align: middle;
        }
        
        .player-status-indicator.active {
            background-color: var(--success-color);
            box-shadow: 0 0 6px var(--success-color);
            animation: pulse 2s infinite;
        }
        
        .player-status-indicator.waiting {
            background-color: var(--warning-color);
            box-shadow: 0 0 6px var(--warning-color);
        }
        
        .player-status-indicator.inactive {
            background-color: var(--secondary-color);
        }
        
        /* Opponent info alignment */
        .opponent-info {
            flex-direction: row-reverse;
            text-align: right;
        }
        
        /* Main container */
        .battle-container {
            flex: 1;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 20px;
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Updated question container */
        .question-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            padding: 0;
            position: relative;
            overflow: hidden;
            margin-top: 15px;
            border: none;
            transition: all 0.3s ease;
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            background-color: #f9f9f9;
            align-items: center;
        }
        
        .question-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            background-color: #222;
            color: white;
            font-weight: bold;
            border-radius: 5px;
            font-size: 1.2rem;
        }
        
        .question-type {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            font-size: 0.9rem;
            color: #555;
        }
        
        .question-actions {
            display: flex;
            align-items: center;
        }
        
        .question-actions-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #555;
            cursor: pointer;
            padding: 5px;
        }
        
        .question-content {
            padding: 25px 30px;
        }
        
        .question-text {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #333;
            margin-bottom: 30px;
            font-weight: 400;
        }
        
        .answers-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .answer-option {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 0;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: stretch;
            background-color: white;
            box-shadow: none;
        }
        
        .answer-option:hover {
            border-color: #ccc;
            background-color: #f9f9f9;
            transform: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .answer-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            min-width: 45px;
            font-weight: bold;
            color: #333;
            background-color: #f5f5f5;
            border-right: 1px solid #e0e0e0;
            font-size: 1.1rem;
        }
        
        .answer-text-container {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            flex: 1;
        }
        
        .answer-text {
            font-size: 1rem;
            font-weight: normal;
            color: #333;
        }
        
        .answer-radio {
            margin-left: auto;
            width: 20px;
            height: 20px;
            border: 2px solid #ddd;
            border-radius: 50%;
            padding: 2px;
            transition: all 0.2s ease;
        }
        
        .answer-option.selected .answer-radio {
            background-color: white;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        }
        
        .answer-option.selected .answer-radio::after {
            content: '';
            display: block;
            width: 10px;
            height: 10px;
            background-color: var(--primary-color);
            border-radius: 50%;
            margin: 3px;
        }
        
        .answer-option.correct {
            border-color: var(--success-color);
            background-color: rgba(40, 167, 69, 0.05);
        }
        
        .answer-option.incorrect {
            border-color: var(--danger-color);
            background-color: rgba(220, 53, 69, 0.05);
        }
        
        .question-navigation {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            margin-top: 20px;
        }
        
        .nav-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f0f0f0;
            color: #555;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .nav-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .nav-btn.right {
            background-color: var(--primary-color);
            color: white;
        }
        
        .nav-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .question-content {
                padding: 20px;
            }
            
            .question-text {
                font-size: 1rem;
                margin-bottom: 20px;
            }
            
            .answer-option {
                border-radius: 6px;
            }
            
            .answer-number {
                width: 40px;
                min-width: 40px;
            }
            
            .answer-text-container {
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 480px) {
            .question-header {
                padding: 12px 15px;
            }
            
            .question-number {
                width: 30px;
                height: 30px;
                font-size: 1rem;
            }
            
            .question-type {
                padding: 4px 10px;
                font-size: 0.8rem;
            }
            
            .question-content {
                padding: 15px;
            }
            
            .question-text {
                font-size: 0.95rem;
                margin-bottom: 15px;
            }
            
            .answer-number {
                width: 35px;
                min-width: 35px;
                font-size: 0.9rem;
            }
            
            .answer-text-container {
                padding: 10px 12px;
            }
            
            .answer-text {
                font-size: 0.9rem;
            }
            
            .answer-radio {
                width: 18px;
                height: 18px;
            }
            
            .answer-option.selected .answer-radio::after {
                width: 8px;
                height: 8px;
                margin: 3px;
            }
        }
        
        /* New animation for correct/incorrect answers */
        @keyframes pulseAnswer {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        .answer-option.correct {
            animation: pulseAnswer 0.5s ease-in-out 3;
        }
        
        .answer-option.incorrect {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
        
        /* Enhanced design for smaller phones */
        @media (max-width: 360px) {
            .question-container {
                padding: 15px 12px;
            }
            
            .question-text {
                font-size: 1rem;
            }
            
            .answer-option {
                padding: 10px;
            }
            
            .answer-prefix {
                width: 24px;
                height: 24px;
                font-size: 0.8rem;
            }
            
            .answer-text {
                font-size: 0.9rem;
            }
        }
        
        /* Add a subtle highlight for active questions */
        .question-container.active {
            box-shadow: 0 15px 30px rgba(76, 175, 80, 0.15);
            border-left-color: var(--success-color);
        }
        
        /* Responsive for landscape orientation on mobile */
        @media (max-height: 500px) and (orientation: landscape) {
            .question-container {
                padding: 15px;
                margin-top: 5px;
            }
            
            .question-header {
                margin-bottom: 10px;
                padding-bottom: 10px;
            }
            
            .question-text {
                font-size: 1rem;
                margin-bottom: 15px;
            }
            
            .answers-container {
                grid-template-columns: 1fr 1fr; /* Keep 2 columns for landscape */
                gap: 10px;
            }
            
            .answer-option {
                padding: 10px;
            }
        }
        
        /* Battle footer with progress indicators */
        .battle-footer {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .player-progress-container {
            flex: 1;
            max-width: 40%;
        }
        
        .player-progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .player-progress {
            height: 8px;
            background-color: #eee;
            border-radius: 4px;
            overflow: hidden;
        }
        
        #playerProgress {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }
        
        #opponentProgress {
            background: linear-gradient(90deg, var(--warning-color), #ff9800);
        }
        
        /* Add some margin to the battle-status-bar */
        .battle-status-bar {
            margin-bottom: 10px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        
        /* Opponent info alignment */
        .opponent-info {
            flex-direction: row-reverse;
            text-align: right;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .battle-footer {
                flex-direction: column;
                gap: 15px;
            }
            
            .player-progress-container {
                max-width: 100%;
                width: 100%;
            }
            
            .battle-btn {
                width: 100%;
                justify-content: center;
            }
            
            .question-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .question-counter {
                align-self: center;
            }
        }
        
        @media (max-width: 480px) {
            .battle-status-bar {
                flex-wrap: wrap;
                gap: 10px;
                padding: 8px;
                justify-content: center;
            }
            
            .battle-center-info {
                width: 100%;
                order: -1;
                margin-bottom: 5px;
                display: flex;
                flex-direction: row;
                justify-content: center;
                gap: 10px;
            }
            
            .battle-status-bar .player-info {
                flex: 1;
                min-width: 100px;
            }
            
            .battle-status-bar .player-name {
                font-size: 0.8rem;
                white-space: nowrap;
            overflow: hidden;
                text-overflow: ellipsis;
                max-width: 80px;
            }
        }
        
        .battle-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, transparent 30%, rgba(0, 0, 0, 0.1) 70%);
            pointer-events: none;
        }
        
        .battle-header h1 i {
            margin-right: 10px;
            color: var(--warning-color);
        }
        
        .battle-players-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .header-player-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-right: 20px;
        }
        
        .header-vs {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--light-color);
        }
        
        .battle-timer-container {
            position: relative;
            z-index: 1;
        }
        
        .connection-status {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
        }
        
        .connection-status i {
            color: var(--success-color);
        }
/*         
        .connection-status span {
            font-size: 0.9rem;
            color: var(--light-color);
        }
         */
        .battle-timer {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(5px);
        }
        
        .battle-timer i {
            color: var(--warning-color);
        }
        
        /* Battle container styling */
        .battle-container {
            flex: 1;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        /* Updated question container */
        .question-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            padding: 0;
            position: relative;
            overflow: hidden;
            margin-top: 15px;
            border: none;
            transition: all 0.3s ease;
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            background-color: #f9f9f9;
            align-items: center;
        }
        
        .question-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            background-color: #222;
            color: white;
            font-weight: bold;
            border-radius: 5px;
            font-size: 1.2rem;
        }
        
        .question-type {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            font-size: 0.9rem;
            color: #555;
        }
        
        .question-actions {
            display: flex;
            align-items: center;
        }
        
        .question-actions-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #555;
            cursor: pointer;
            padding: 5px;
        }
        
        .question-content {
            padding: 25px 30px;
        }
        
        .question-text {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #333;
            margin-bottom: 30px;
            font-weight: 400;
        }
        
        .answers-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .answer-option {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 0;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: stretch;
            background-color: white;
            box-shadow: none;
        }
        
        .answer-option:hover {
            border-color: #ccc;
            background-color: #f9f9f9;
            transform: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .answer-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            min-width: 45px;
            font-weight: bold;
            color: #333;
            background-color: #f5f5f5;
            border-right: 1px solid #e0e0e0;
            font-size: 1.1rem;
        }
        
        .answer-text-container {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            flex: 1;
        }
        
        .answer-text {
            font-size: 1rem;
            font-weight: normal;
            color: #333;
        }
        
        .answer-radio {
            margin-left: auto;
            width: 20px;
            height: 20px;
            border: 2px solid #ddd;
            border-radius: 50%;
            padding: 2px;
            transition: all 0.2s ease;
        }
        
        .answer-option.selected .answer-radio {
            background-color: white;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        }
        
        .answer-option.selected .answer-radio::after {
            content: '';
            display: block;
            width: 10px;
            height: 10px;
            background-color: var(--primary-color);
            border-radius: 50%;
            margin: 3px;
        }
        
        .answer-option.correct {
            border-color: var(--success-color);
            background-color: rgba(40, 167, 69, 0.05);
        }
        
        .answer-option.incorrect {
            border-color: var(--danger-color);
            background-color: rgba(220, 53, 69, 0.05);
        }
        
        .question-navigation {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            margin-top: 20px;
        }
        
        .nav-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f0f0f0;
            color: #555;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .nav-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .nav-btn.right {
            background-color: var(--primary-color);
            color: white;
        }
        
        .nav-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .question-content {
                padding: 20px;
            }
            
            .question-text {
                font-size: 1rem;
                margin-bottom: 20px;
            }
            
            .answer-option {
                border-radius: 6px;
            }
            
            .answer-number {
                width: 40px;
                min-width: 40px;
            }
            
            .answer-text-container {
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 480px) {
            .question-header {
                padding: 12px 15px;
            }
            
            .question-number {
                width: 30px;
                height: 30px;
                font-size: 1rem;
            }
            
            .question-type {
                padding: 4px 10px;
                font-size: 0.8rem;
            }
            
            .question-content {
                padding: 15px;
            }
            
            .question-text {
                font-size: 0.95rem;
                margin-bottom: 15px;
            }
            
            .answer-number {
                width: 35px;
                min-width: 35px;
                font-size: 0.9rem;
            }
            
            .answer-text-container {
                padding: 10px 12px;
            }
            
            .answer-text {
                font-size: 0.9rem;
            }
            
            .answer-radio {
                width: 18px;
                height: 18px;
            }
            
            .answer-option.selected .answer-radio::after {
                width: 8px;
                height: 8px;
                margin: 3px;
            }
        }
        
        /* Players section */
        .players-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .players-container {
                display: flex;
                flex-direction: row;
                flex-wrap: nowrap;
                overflow-x: auto;
                gap: 15px;
                padding: 5px 0;
                margin-bottom: 15px;
                -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
                scrollbar-width: thin;
            }
            
            .player-card {
                flex: 0 0 80%;
                max-width: 300px;
                padding: 15px;
            }
        }
        
        .player-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            padding: 25px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .player-card.active {
            box-shadow: 0 10px 25px rgba(0, 195, 255, 0.2);
            border: 2px solid var(--neon-blue);
        }
        
        .player-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--neon-blue));
        }
        
        .player-info {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .player-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--primary-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .player-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .player-status {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: var(--success-color);
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .player-details {
            flex: 1;
        }
        
        .player-name {
            font-size: 1.3rem;
            font-weight: bold;
            margin: 0 0 5px 0;
        }
        
        .player-status-text {
            font-size: 0.9rem;
            color: var(--success-color);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .player-score {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
            position: absolute;
            top: 25px;
            right: 25px;
            background: rgba(0, 123, 255, 0.1);
            padding: 5px 15px;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        
        .player-progress-container {
            margin-top: 10px;
        }
        
        .player-progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .player-progress {
            width: 100%;
            height: 12px;
            background: #eee;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .player-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--neon-blue), var(--primary-color));
            width: 0%;
            transition: width 0.5s ease, box-shadow 0.3s ease;
            position: relative;
            box-shadow: 0 0 10px var(--neon-blue);
        }
        
        .player-progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                rgba(255, 255, 255, 0.1) 0%, 
                rgba(255, 255, 255, 0.3) 50%, 
                rgba(255, 255, 255, 0.1) 100%);
            animation: shine 2s infinite linear;
        }
        
        .player-badge {
            position: absolute;
            bottom: 20px;
            right: 20px;
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 15px;
            background-color: var(--light-color);
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }
        
        /* Battle arena section */
        .battle-arena {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 10px;
        }
        
        .question-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(180deg, var(--primary-color), var(--neon-blue));
        }
        
        .question-metadata {
            display: flex;
            gap: 15px;
        }
        
        .question-subject {
            background: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .question-difficulty {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .question-difficulty.easy {
            background: #d4edda;
            color: #155724;
        }
        
        .question-difficulty.medium {
            background: #fff3cd;
            color: #856404;
        }
        
        .question-difficulty.hard {
            background: #f8d7da;
            color: #721c24;
        }
        
        .question-timer {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--light-color);
            padding: 8px 15px;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-left: auto;
        }
        
        .question-timer-progress {
            width: 120px;
            height: 10px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .question-timer-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--danger-color), var(--warning-color));
            width: 100%;
            transition: width 1s linear;
        }
        
        .question-timer-text {
            font-weight: bold;
            color: var(--dark-color);
        }
        
        .question-text {
            font-size: 1.4rem;
            margin-bottom: 30px;
            line-height: 1.6;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .answers-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .answer-option {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
        }
        
        .answer-option:hover {
            background: #e8f4ff;
            border-color: #cce5ff;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        }
        
        .answer-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-color);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .answer-option:hover::before {
            opacity: 1;
        }
        
        .answer-prefix {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 36px;
            height: 36px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            margin-right: 15px;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .answer-text {
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .answer-option.selected {
            background: #cce5ff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px var(--primary-color), 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .answer-option.selected .answer-prefix {
            background-color: white;
            color: var(--primary-color);
        }
        
        .answer-option.selected::before {
            opacity: 1;
        }
        
        .answer-option.correct {
            background: #d4edda;
            border-color: var(--success-color);
            box-shadow: 0 0 0 2px var(--success-color), 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .answer-option.correct .answer-prefix {
            background-color: var(--success-color);
        }
        
        .answer-option.correct::before {
            background: var(--success-color);
            opacity: 1;
        }
        
        .answer-option.incorrect {
            background: #f8d7da;
            border-color: var(--danger-color);
            box-shadow: 0 0 0 2px var(--danger-color), 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .answer-option.incorrect .answer-prefix {
            background-color: var(--danger-color);
        }
        
        .answer-option.incorrect::before {
            background: var(--danger-color);
            opacity: 1;
        }
        
        .battle-footer {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        .question-counter {
            font-size: 1.1rem;
            font-weight: 500;
            background: var(--light-color);
            padding: 10px 20px;
            border-radius: 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        
        .battle-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }
        
        .battle-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(76, 175, 80, 0.4);
        }
        
        .battle-btn:disabled {
            background: #adb5bd;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .battle-btn i {
            font-size: 0.9rem;
        }
        
        /* Bottom Section */
        .battle-bottom-section {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 30px;
            margin-top: 20px;
        }
        
        /* Chat Section */
        .battle-chat {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            height: 300px;
            overflow: hidden;
        }
        
        .chat-header {
            background: var(--primary-color);
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .chat-header h3 {
            margin: 0;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .chat-message {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            max-width: 80%;
        }
        
        .chat-message.outgoing {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        
        .chat-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .chat-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .chat-bubble {
            background: #f1f3f5;
            padding: 12px 15px;
            border-radius: 18px;
            position: relative;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .chat-message.outgoing .chat-bubble {
            background: #e3f2fd;
            color: #0c5460;
        }
        
        .chat-message-text {
            margin: 0;
            line-height: 1.4;
        }
        
        .chat-message-time {
            font-size: 0.75rem;
            color: #adb5bd;
            margin-top: 5px;
            text-align: right;
        }
        
        .chat-input-container {
            padding: 15px;
            border-top: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chat-input {
            flex: 1;
            border: 1px solid #ced4da;
            border-radius: 25px;
            padding: 10px 15px;
            outline: none;
            transition: border-color 0.3s;
        }
        
        .chat-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        }
        
        .chat-send-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
        }
        
        .chat-send-btn:hover {
            background: #0069d9;
            transform: scale(1.05);
        }
        
        /* Battle Controls */
        .battle-controls {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            padding: 25px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .control-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .control-title i {
            color: var(--primary-color);
        }
        
        .control-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            border: none;
        }
        
        .leave-btn {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .leave-btn:hover {
            background: #f5c6cb;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.2);
        }
        
        .ready-btn {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .ready-btn:hover {
            background: #c3e6cb;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.2);
        }
        
        .spectate-btn {
            background: #e2e3e5;
            color: #383d41;
            border: 1px solid #d6d8db;
        }
        
        .spectate-btn:hover {
            background: #d6d8db;
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.2);
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
            z-index: 10000;
            padding: 15px;
            overflow-y: auto;
        }
        
        /* Add these styles to override any conflicts */
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        #battleResultModal.active {
            opacity: 1;
            visibility: visible;
            display: flex !important;
        }
        
        #battleResultModal .battle-result-modal {
            position: relative;
            z-index: 10001;
            margin: auto;
        }
        
        @media (max-width: 480px) {
            .battle-result-modal {
                width: 100%;
                border-radius: 15px;
                max-height: 90vh;
            }
            
            .battle-result-content {
                padding: 20px 12px;
            }
            
            .battle-stat {
                padding: 15px;
                min-width: unset;
                width: 100%;
            }
            
            .player-result-avatar {
                width: 60px;
                height: 60px;
            }
            
            .result-icon {
                font-size: 4rem;
                margin-bottom: 15px;
            }
            
            #resultTitle {
                font-size: 1.4rem;
            }
            
            #resultMessage {
                font-size: 0.9rem;
            }
        }
        
        .battle-result-modal {
            background: white;
            border-radius: 20px;
            width: 95%;
            max-width: 600px;
            overflow: hidden;
            transform: scale(0.8);
            transition: transform 0.3s;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }
        
        .modal-overlay.active .battle-result-modal {
            transform: scale(1);
        }
        
        .battle-result-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .battle-result-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, transparent 30%, rgba(0, 0, 0, 0.1) 70%);
            pointer-events: none;
        }
        
        .battle-result-header h2 {
            margin: 0;
            font-size: 1.8rem;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .battle-result-content {
            padding: 35px;
            text-align: center;
            overflow-y: auto;
        }
        
        @media (max-width: 768px) {
            .battle-result-modal {
                width: 95%;
                max-height: 85vh;
            }
            
            .battle-result-content {
                padding: 25px 15px;
            }
            
            .battle-players-result {
                flex-direction: column;
                gap: 25px;
            }
            
            .battle-stats {
                flex-direction: column;
                gap: 15px;
            }
            
            .battle-stat {
                width: 100%;
                min-width: initial;
            }
            
            .battle-result-actions {
                flex-direction: column;
                gap: 15px;
            }
            
            .result-btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        .result-icon {
            font-size: 5rem;
            margin-bottom: 25px;
        }
        
        .result-icon.win {
            color: var(--success-color);
        }
        
        .result-icon.win i {
            text-shadow: 0 0 20px rgba(40, 167, 69, 0.4);
            animation: pulse 2s infinite;
        }
        
        .result-icon.lose {
            color: var(--danger-color);
        }
        
        .result-icon.tie {
            color: var(--warning-color);
        }
        
        .battle-players-result {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin: 30px 0;
        }
        
        .player-result {
            text-align: center;
        }
        
        .player-result-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 15px;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .player-result-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .player-result-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .player-result-score {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .battle-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 30px 0;
        }
        
        .battle-stat {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            min-width: 120px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .battle-stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .battle-stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .battle-result-actions {
            margin-top: 35px;
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        
        .result-btn {
            padding: 12px 25px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .rematch-btn {
            background: var(--primary-color);
            color: white;
        }
        
        .rematch-btn:hover {
            background: #0069d9;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 123, 255, 0.3);
        }
        
        .dashboard-btn {
            background: #f8f9fa;
            color: #343a40;
            border: 1px solid #dee2e6;
        }
        
        .dashboard-btn:hover {
            background: #e2e6ea;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        /* Matchmaking Screen */
        .matchmaking-screen {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            padding: 30px;
            text-align: center;
            margin: 50px auto;
            max-width: 600px;
        }
        
        .matchmaking-icon {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        .matchmaking-title {
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        
        .matchmaking-subtitle {
            color: #6c757d;
            margin-bottom: 30px;
        }
        
        .matchmaking-stats {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
        }
        
        .matchmaking-stat {
            text-align: center;
        }
        
        .matchmaking-stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .matchmaking-stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .matchmaking-progress {
            width: 100%;
            height: 10px;
            background: #eee;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 30px;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .matchmaking-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--neon-blue));
            width: 20%;
            box-shadow: 0 0 10px var(--neon-blue);
            animation: progressPulse 2s infinite ease-in-out;
        }
        
        .opponent-found-state {
            display: none; /* Initially hidden */
            animation: fadeIn 0.5s;
        }
        
        .opponent-profile {
            display: flex;
            align-items: center;
            gap: 20px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .opponent-found-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .opponent-found-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .opponent-found-details {
            flex: 1;
            text-align: left;
        }
        
        .opponent-found-name {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .opponent-found-status {
            font-size: 0.9rem;
            color: var(--success-color);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .ready-button {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            margin: 0 auto;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 15px rgba(0, 123, 255, 0.2);
        }
        
        .ready-button:hover {
            background: #0069d9;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 123, 255, 0.3);
        }
        
        .ready-button.ready {
            background: var(--success-color);
            box-shadow: 0 8px 15px rgba(40, 167, 69, 0.2);
        }
        
        .ready-button.ready:hover {
            background: #218838;
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
        }
        
        .opponent-ready-indicator {
            background: var(--success-color);
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.2);
            animation: slideInUp 0.5s;
        }
        
        .waiting-message, .opponent-answered-message {
            background: rgba(255, 255, 255, 0.9);
            padding: 15px 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            font-weight: 500;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s;
        }
        
        .waiting-message {
            color: var(--primary-color);
            border: 1px solid #cce5ff;
        }
        
        .opponent-answered-message {
            color: var(--success-color);
            border: 1px solid #d4edda;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideInUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(50px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
        
        @keyframes progressPulse {
            0% {
                width: 20%;
                box-shadow: 0 0 5px var(--neon-blue);
            }
            50% {
                width: 60%;
                box-shadow: 0 0 15px var(--neon-blue);
            }
            100% {
                width: 20%;
                box-shadow: 0 0 5px var(--neon-blue);
            }
        }
        
        @keyframes shine {
            0% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(100%);
            }
        }
        
        /* Responsive Styles */
        @media (max-width: 992px) {
            .battle-bottom-section {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .players-container {
                display: flex;
                flex-direction: row;
                flex-wrap: nowrap;
                overflow-x: auto;
                gap: 15px;
                padding: 5px 0;
                margin-bottom: 15px;
                -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
                scrollbar-width: thin;
            }
            
            .player-card {
                flex: 0 0 80%;
                max-width: 300px;
                padding: 15px;
            }
            
            .question-container {
                padding: 25px 20px;
            }
            
            .question-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .question-text {
                font-size: 1.2rem;
                margin-bottom: 25px;
            }
            
            .answers-container {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .answer-option {
                padding: 15px;
            }
            
            .battle-bottom-section {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .battle-stats {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }
            
            .battle-result-actions {
                flex-direction: column;
                gap: 15px;
            }
            
            .result-btn {
                width: 100%;
                justify-content: center;
            }
            
            .battle-footer {
                flex-direction: column;
                gap: 15px;
            }
        }
        
        @media (max-width: 480px) {
            .question-container {
                padding: 20px 15px;
                margin-top: 15px;
            }
            
            .question-text {
                font-size: 1.1rem;
                margin-bottom: 20px;
            }
            
            .battle-container {
                padding: 15px 10px;
            }
        }
        
        /* Toast Notifications */
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: rgba(40, 167, 69, 0.95);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideInRight 0.3s, fadeOut 0.3s 4.7s;
            z-index: 9999;
            max-width: 350px;
        }
        
        .toast-notification.error {
            background-color: rgba(220, 53, 69, 0.95);
        }
        
        .toast-notification.info {
            background-color: rgba(0, 123, 255, 0.95);
        }
        
        .toast-notification-icon {
            font-size: 1.5rem;
        }
        
        .toast-notification-content {
            flex: 1;
        }
        
        .toast-notification-title {
            font-weight: 600;
            margin: 0 0 5px 0;
        }
        
        .toast-notification-message {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        /* Player status indicators */
        .player-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
            margin-left: auto;
            display: flex;
            align-items: center;
        }
        
        .player-status::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .player-status.connected {
            color: var(--success-color);
            background-color: rgba(40, 167, 69, 0.1);
        }
        
        .player-status.connected::before {
            background-color: var(--success-color);
        }
        
        .player-status.disconnected {
            color: var(--danger-color);
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .player-status.disconnected::before {
            background-color: var(--danger-color);
        }
        
        .player-status.waiting {
            color: var(--warning-color);
            background-color: rgba(255, 193, 7, 0.1);
        }
        
        .player-status.waiting::before {
            background-color: var(--warning-color);
        }
        
        /* Scrollbar styling for players container */
        .players-container::-webkit-scrollbar {
            height: 5px;
        }
        
        .players-container::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
        }
        
        .players-container::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }
        
        .players-container::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }
        
        @media (max-width: 768px) {
            .players-container {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
                margin-bottom: 15px;
            }
            
            .player-card {
                padding: 12px;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
                border-radius: 12px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                height: 100%;
            }
            
            .player-info {
                flex-direction: column;
                gap: 10px;
                text-align: center;
                margin-bottom: 12px;
            }
            
            .player-avatar {
                width: 50px;
                height: 50px;
                margin: 0 auto;
            }
            
            .player-details {
                width: 100%;
                text-align: center;
            }
            
            .player-name {
                font-size: 0.9rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 100%;
            }
            
            .player-score {
                font-size: 1.4rem;
                padding: 5px;
                text-align: center;
            }
            
            .player-status-text {
                font-size: 0.8rem;
            }
            
            .player-progress-container {
                margin-top: 8px;
            }
            
            .player-progress-label {
                font-size: 0.8rem;
                margin-bottom: 3px;
            }
            
            .player-progress {
                height: 6px;
            }
            
            .player-badge {
                font-size: 0.75rem;
                padding: 3px 8px;
                margin-top: 8px;
            }
            
            .battle-players-result {
                flex-direction: column;
                gap: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .players-container {
                gap: 8px;
            }
            
            .player-card {
                padding: 10px;
            }
            
            .player-avatar {
                width: 40px;
                height: 40px;
                border-width: 2px;
            }
            
            .player-details {
                gap: 2px;
            }
            
            .player-name {
                font-size: 0.8rem;
                margin-bottom: 2px;
            }
            
            .player-status-text {
                font-size: 0.7rem;
            }
            
            .player-status-text i {
                font-size: 0.6rem;
                margin-right: 2px;
            }
            
            .player-score {
                font-size: 1.2rem;
                padding: 3px;
                margin: 3px 0;
            }
            
            .player-progress-label {
                font-size: 0.7rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .player-progress {
                height: 5px;
            }
            
            .player-badge {
                font-size: 0.7rem;
                padding: 2px 6px;
                margin-top: 5px;
            }
        }
        
        /* For very small screens */
        @media (max-width: 360px) {
            .player-avatar {
                width: 35px;
                height: 35px;
            }
            
            .player-name {
                font-size: 0.75rem;
            }
            
            .player-status-text {
                font-size: 0.65rem;
            }
            
            .player-score {
                font-size: 1.1rem;
            }
            
            .player-progress-label,
            .player-badge {
                font-size: 0.65rem;
            }
        }
        
        /* Waiting and answered messages */
        .waiting-message, .opponent-answered-message {
            background: rgba(255, 255, 255, 0.9);
            padding: 15px 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            font-weight: 500;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s;
        }
        
        .waiting-message {
            color: var(--primary-color);
            border: 1px solid #cce5ff;
        }
        
        .opponent-answered-message {
            color: var(--success-color);
            border: 1px solid #d4edda;
        }
        
        /* Show player status in the status bar */
        .player-status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
            vertical-align: middle;
        }
        
        .player-status-indicator.active {
            background-color: var(--success-color);
            box-shadow: 0 0 5px var(--success-color);
        }
        
        .player-status-indicator.waiting {
            background-color: var(--warning-color);
            box-shadow: 0 0 5px var(--warning-color);
        }
        
        .player-status-indicator.inactive {
            background-color: var(--secondary-color);
        }

        .answer-option.player-selected::after,
        .answer-option.opponent-selected::after {
            position: absolute;
            right: 15px;
            bottom: 5px;
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 500;
        }

        .answer-option.player-selected::after {
            content: 'You';
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--primary-color);
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .answer-option.opponent-selected::after {
            content: 'Opponent';
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .answer-option.player-selected.opponent-selected::after {
            content: 'Both';
            background-color: rgba(0, 123, 255, 0.1);
            color: var(--blue);
            border: 1px solid rgba(0, 123, 255, 0.3);
        }

        /* Fix for mobile */
        @media (max-width: 480px) {
            .answer-option.player-selected::after,
            .answer-option.opponent-selected::after {
                font-size: 0.7rem;
                padding: 1px 6px;
                right: 10px;
                bottom: 3px;
            }
        }
        
        /* Question image styles */
        .question-image-container {
            margin: 15px 0;
            text-align: center;
            background-color: #f8f9fa;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: none; /* Hidden by default, shown when needed */
        }

        .question-image {
            max-width: 100%;
            max-height: 300px;
            display: block;
            margin: 0 auto;
            border-radius: 8px;
        }

        .question-image.loading {
            opacity: 0.7;
            filter: blur(2px);
        }

        @keyframes imageLoad {
            from { opacity: 0.5; }
            to { opacity: 1; }
        }

        .question-image-container img {
            animation: imageLoad 0.5s ease-in-out;
        }

        .friend-action-status.success {
            background: #e8f5e9;
            color: var(--primary-color);
        }

        /* Question loader styles */
        .question-loader {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #f5f5f5;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            min-height: 200px;
            margin: 20px 0;
        }

        .question-loader .spinner-border {
            width: 3rem;
            height: 3rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .question-loader p {
            font-size: 1.1rem;
            color: #666;
            margin: 0;
        }

        /* Spinner animation for loader */
        .spinner-border {
            display: inline-block;
            width: 2rem;
            height: 2rem;
            border: 0.25em solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spinner 0.75s linear infinite;
        }

        @keyframes spinner {
            to {
                transform: rotate(360deg);
            }
        }

        .visually-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Additional styles for loader */
        .question-loader {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px;
            color: #4b5563;
            font-size: 16px;
            background-color: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            margin: 10px 0;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .question-loader i {
            margin-right: 12px;
            font-size: 22px;
            color: #2563eb;
            animation: spin 1.5s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-question {
            background-color: #f3f4f6;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s ease;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .question-image.loading {
            opacity: 0.7;
            filter: blur(2px);
            transition: all 0.3s ease;
        }
        
        .error-message {
            color: #ef4444;
            padding: 15px;
            background-color: #fee2e2;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 14px;
            border: 1px solid #fecaca;
            text-align: center;
        }
        
        .no-image-message {
            padding: 50px 20px;
            background-color: #f3f4f6;
            color: #6b7280;
            text-align: center;
            border-radius: 8px;
            border: 2px dashed #d1d5db;
            margin: 20px 0;
            font-size: 16px;
        }
        
        .question-image-container {
            width: 100%;
            max-width: 600px;
            margin: 20px auto;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            min-height: 200px; /* Ensure the container always has a minimum height */
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
        }
        
        .question-image {
            width: 100%;
            height: auto;
            display: block;
            transition: all 0.3s ease;
        }
        
        /* Styles for the countdown timer */
        .question-timer {
            background-color: #f3f4f6;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 18px;
            font-family: monospace;
            font-weight: bold;
            color: #1f2937;
            border: 2px solid #e5e7eb;
            margin-left: auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .timer-warning {
            background-color: #fef3c7;
            color: #92400e;
            border-color: #fbbf24;
        }
        
        .timer-critical {
            background-color: #fee2e2;
            color: #b91c1c;
            border-color: #f87171;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        /* Styles for the skip button */
        .skip-btn {
            background-color: #e5e7eb;
            color: #4b5563;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            margin-left: 10px;
            transition: all 0.2s ease;
        }
        
        .skip-btn:hover {
            background-color: #d1d5db;
        }
        
        .skip-btn i {
            margin-right: 6px;
        }
        
        .skip-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Styles for answer options */
        .answer-option {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .answer-option.selected {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }
        
        .answer-option.correct {
            border-color: #10b981;
            background-color: #ecfdf5;
        }
        
        .answer-option.incorrect {
            border-color: #ef4444;
            background-color: #fee2e2;
        }
        
        .answer-option.disabled {
            pointer-events: none;
            opacity: 0.8;
        }
        
        /* Question navigation flexbox */
        .question-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        /* Add a timer element to the UI */
        .add-timer-element {
            /* Add your styles here */
        }
        
        /* Add a more visible skip button directly to the UI */
        .add-visible-skip-button {
            /* Add your styles here */
        }
        
        /* Styles for the prominent skip button */
        .prominent-skip-btn {
            display: block;
            width: 100%;
            max-width: 300px;
            margin: 20px auto;
            padding: 12px 20px;
            background-color: #f97316;
            color: white;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            animation: attention-pulse 2s infinite;
            position: relative;
            z-index: 100;
        }
        
        .prominent-skip-btn:hover {
            background-color: #ea580c;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }
        
        .prominent-skip-btn:active {
            transform: translateY(1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .prominent-skip-btn i {
            margin-right: 8px;
        }
        
        /* Pulse animation for skip button */
        @keyframes attention-pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.5);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(249, 115, 22, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(249, 115, 22, 0);
            }
        }
        
        /* Make sure skip button is visible in quick battle mode */
        body.quick-battle-mode .prominent-skip-btn {
            display: block !important;
        }
        
        /* Improved styles for the smaller skip button in navigation */
        .skip-btn {
            background-color: #f97316;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            margin-left: auto;
            margin-right: auto;
            transition: all 0.2s ease;
        }
        
        .skip-btn:hover {
            background-color: #ea580c;
            transform: translateY(-2px);
        }
        
        .skip-btn:active {
            transform: translateY(1px);
        }
        
        .skip-btn i {
            margin-right: 6px;
        }
        
        .skip-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <!-- WebSocket URL (hidden) -->
    <span id="websocket-url" style="display:none;"><?php echo $websocketUrl; ?></span>
    
    <div class="battle-header">
        <h1><i class="fas fa-gamepad"></i> Quick Battle</h1>
    </div>
    
    <div class="battle-status-bar">
        <!-- Player info (left side) -->
                    <div class="player-info">
                        <div class="player-avatar">
                            <img src="../assets/default.png" alt="You" id="playerAvatar">
                        </div>
            <div class="player-name">
                <span class="player-status-indicator active" id="playerStatusIndicator"></span>
                <span id="playerName">You</span>
                    </div>
                    <div class="player-score" id="playerScore">0</div>
                        </div>
        
        <!-- Battle info (center) -->
        <div class="battle-center-info">
            <div class="connection-status">
                <i class="fas fa-wifi"></i>
                <span id="connectionStatus" class="connecting">Connecting...</span>
                        </div>
            <div class="battle-timer">
                <i class="fas fa-clock"></i>
                <span id="battleTimer">00:00</span>
                    </div>
                </div>
                
        <!-- Opponent info (right side) -->
        <div class="player-info opponent-info">
            <div class="player-score" id="opponentScore">0</div>
            <div class="player-name">
                <span id="opponentName">Opponent</span>
                <span class="player-status-indicator waiting" id="opponentStatusIndicator"></span>
            </div>
                        <div class="player-avatar">
                            <img src="../assets/default.png" alt="Opponent" id="opponentAvatar">
                    </div>
                </div>
            </div>
            
    <!-- Main Container -->
    <div class="battle-container">
        <!-- Battle Arena (Hidden until battle starts) -->
        <div class="battle-arena" id="battleArena" style="display: none;">
            <!-- Question Container -->
            <div class="question-container">
                <div class="question-header">
                    <div class="question-number" id="questionCounter">2</div>
                    <div class="question-type">Type : Single</div>
                    <div class="question-actions">
                        <button class="question-actions-btn"><i class="fas fa-ellipsis-v"></i></button>
                        </div>
                    </div>
                    
                <div class="question-content">
                <div class="question-text" id="questionText">
                    <!-- Question text will be dynamically loaded -->
                </div>
                
                <!-- Add an initial image container with a loader -->
                <div class="question-image-container">
                    <div class="question-loader">
                        <i class="fas fa-spinner fa-spin"></i> Loading question...
                    </div>
                </div>
                
                <div class="answers-container" id="answersContainer">
                    <!-- Answers will be dynamically loaded -->
                </div>
                    
                    <div class="question-navigation">
                        <button class="nav-btn"><i class="fas fa-arrow-left"></i></button>
                        <button class="nav-btn right"><i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
                
                <div class="battle-footer">
                    <div class="player-progress-container">
                        <div class="player-progress-label">
                            <span>Your Progress</span>
                            <span id="playerProgressText">0%</span>
                    </div>
                        <div class="player-progress">
                            <div class="player-progress-fill" id="playerProgress"></div>
                        </div>
                    </div>
                    
                    <button id="nextQuestionBtn" class="battle-btn" disabled>
                        <span>Next Question</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                    
                    <div class="player-progress-container">
                        <div class="player-progress-label">
                            <span>Opponent Progress</span>
                            <span id="opponentProgressText">0%</span>
                        </div>
                        <div class="player-progress">
                            <div class="player-progress-fill" id="opponentProgress"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bottom Section -->
            <div class="battle-bottom-section">
                <!-- Chat Section -->
                <div class="battle-chat">
                    <div class="chat-header">
                        <h3><i class="fas fa-comments"></i> Live Chat</h3>
                        <span id="chatStatus">Connected</span>
                    </div>
                    <div class="chat-messages" id="chatMessages">
                        <div class="chat-message">
                            <div class="chat-avatar">
                                <img src="../assets/default.png" alt="System">
                            </div>
                            <div class="chat-bubble">
                                <p class="chat-message-text">Welcome to the battle! Good luck!</p>
                                <div class="chat-message-time">System • just now</div>
                            </div>
                        </div>
                    </div>
                    <div class="chat-input-container">
                        <input type="text" class="chat-input" id="chatInput" placeholder="Type your message...">
                        <button class="chat-send-btn" id="chatSendBtn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Battle Controls -->
                <div class="battle-controls">
                    <h3 class="control-title"><i class="fas fa-cogs"></i> Battle Controls</h3>
                    
                    <button class="control-btn ready-btn" id="readyBtn">
                        <i class="fas fa-check-circle"></i> Ready for Next Round
                    </button>
                    
                    <button class="control-btn leave-btn" id="leaveBtn">
                        <i class="fas fa-sign-out-alt"></i> Leave Battle
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Matchmaking Screen (Shown until battle starts) -->
        <div class="matchmaking-screen" id="matchmakingScreen">
            <div class="matchmaking-icon">
                <i class="fas fa-search"></i>
            </div>
            <h2 class="matchmaking-title">Finding an Opponent</h2>
            <p class="matchmaking-subtitle">Please wait while we match you with another player...</p>
            
            <div class="matchmaking-stats">
                <div class="matchmaking-stat">
                    <div class="matchmaking-stat-value" id="activeSearchers">1</div>
                    <div class="matchmaking-stat-label">Players Searching</div>
                </div>
                <div class="matchmaking-stat">
                    <div class="matchmaking-stat-value" id="averageWaitTime">00:15</div>
                    <div class="matchmaking-stat-label">Avg. Wait Time</div>
                </div>
            </div>
            
            <div class="matchmaking-progress">
                <div class="matchmaking-progress-fill"></div>
            </div>
            
            <button class="battle-btn" id="cancelMatchmakingBtn">
                <i class="fas fa-times"></i>
                <span>Cancel Matchmaking</span>
            </button>
            
            <!-- Opponent Found State (Initially hidden) -->
            <div class="opponent-found-state" id="opponentFoundState">
                <h3>Opponent Found!</h3>
                <div class="opponent-profile">
                    <div class="opponent-found-avatar">
                        <img src="../assets/default.png" alt="Opponent" id="opponentFoundAvatar">
                    </div>
                    <div class="opponent-found-details">
                        <h4 class="opponent-found-name" id="opponentFoundName">Player Name</h4>
                        <div class="opponent-found-status">
                            <i class="fas fa-circle"></i> Online
                        </div>
                    </div>
                </div>
                
                <button class="ready-button" id="readyButton" disabled>
                    <i class="fas fa-check"></i><span>Ready</span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Battle Result Modal -->
    <div class="modal-overlay" id="battleResultModal">
        <div class="battle-result-modal">
            <div class="battle-result-header">
                <h2>Battle Results</h2>
            </div>
            <div class="battle-result-content">
                <div class="result-icon win" id="resultIcon">
                    <i class="fas fa-trophy"></i>
                </div>
                <h3 id="resultTitle">You Won!</h3>
                <p id="resultMessage">Congratulations! You've won the battle.</p>
                
                <div class="battle-players-result">
                    <div class="player-result">
                        <div class="player-result-avatar">
                            <img src="../assets/default.png" alt="You" id="resultPlayerAvatar">
                        </div>
                        <div class="player-result-name" id="resultPlayerName">You</div>
                        <div class="player-result-score" id="resultPlayerScore">120</div>
                    </div>
                    
                    <div class="player-result">
                        <div class="player-result-avatar">
                            <img src="../assets/default.png" alt="Opponent" id="resultOpponentAvatar">
                        </div>
                        <div class="player-result-name" id="resultOpponentName">Opponent</div>
                        <div class="player-result-score" id="resultOpponentScore">90</div>
                    </div>
                </div>
                
                <div class="battle-stats">
                    <div class="battle-stat">
                        <div class="battle-stat-value" id="correctAnswers">4</div>
                        <div class="battle-stat-label">Correct Answers</div>
                    </div>
                    <div class="battle-stat">
                        <div class="battle-stat-value" id="pointsEarned">120</div>
                        <div class="battle-stat-label">Points Earned</div>
                    </div>
                    <div class="battle-stat">
                        <div class="battle-stat-value" id="timeUsed">01:45</div>
                        <div class="battle-stat-label">Time Used</div>
                    </div>
                </div>
                
                <div class="battle-result-actions">
                    <button class="result-btn rematch-btn" id="rematchBtn">
                        <i class="fas fa-redo"></i>
                        <span>Rematch</span>
                    </button>
                    <button class="result-btn dashboard-btn" id="dashboardBtn">
                        <i class="fas fa-home"></i>
                        <span>Back to Dashboard</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confetti Canvas for Win Animation -->
    <canvas id="confetti-canvas" style="position: fixed; top: 0; left: 0; pointer-events: none; z-index: 9999; display: none;"></canvas>

    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <script>
        // Restrict direct access to the battle page
        (function() {
            const params = new URLSearchParams(window.location.search);
            const referer = document.referrer;
            const hasValidParams = params.has('matchId') || params.has('direct');
            const isFromDashboard = referer.includes('dashboard.php') || referer.includes('dashboard.html');
            
            // If user tries to access directly without parameters or proper referrer
            if (!hasValidParams && !isFromDashboard) {
                // Redirect to dashboard with error message
                window.location.href = 'dashboard.php?error=direct_access_denied';
            }
        })();

        document.addEventListener('DOMContentLoaded', async function() {
            // Initialize WebSocket connection
            let socket = null;
            let battleStarted = false;
            let currentQuestion = null;
            let questionTimer = null;
            let questionTimeLimit = 15; // seconds
            let questionTimeRemaining = 0;
            
            // Parse battle parameters from URL
            const params = new URLSearchParams(window.location.search);
            const battleConfig = {
                subjects: [], // Use empty array to get random questions across all subjects
                questions: 3, // Always use 3 questions for Quick Battle
                difficulty: params.get('difficulty') || 'medium',
                battleType: 'quick' // Always set battle type to 'quick'
            };
            
            // Check if we're coming from dashboard with match ID or in direct battle mode
            const directBattle = params.get('direct') === 'true';
            const matchId = params.get('matchId');
            const isComingFromDashboard = !!matchId;
            
            // Supabase configuration
            const supabaseUrl = 'https://ratxqmbqzwbvfgsonlrd.supabase.co';
            const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InJhdHhxbWJxendidmZnc29ubHJkIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDQyMDI0NDAsImV4cCI6MjA1OTc3ODQ0MH0.HJ9nQbvVvVisvQb6HMVMlmQBVmW7Ie42Z6Afdwn8W2M';
            const supabaseClient = supabase.createClient(supabaseUrl, supabaseKey);
            
            // Initialize game state
            const gameState = {
                currentQuestion: 0,
                playerScore: 0,
                opponentScore: 0,
                startTime: null,
                questionStartTime: null,
                questions: [],
                playerAnswers: [],
                opponentAnswers: [],
                waitingForOpponent: false,
                opponentInfo: null,
                chatMessages: [],
                connectionStatus: 'connecting',
                playerId: null,
                playerName: null,
                playerAvatar: null,
                player1Id: null,
                player2Id: null,
                player1InitialPoints: 0,
                player2InitialPoints: 0,
                opponentId: null,
                opponentName: null,
                opponentAvatar: null,
                battleRecordId: null,
                battleId: null,
                originalBattleId: null,
                battleEnded: false,
                inBattle: false,
                lastConnectionTime: Date.now(),
                reconnectAttempts: 0,
                maxReconnectAttempts: 5
            };
            
            // DOM Elements
            const battleArena = document.getElementById('battleArena');
            const matchmakingScreen = document.getElementById('matchmakingScreen');
            const opponentFoundState = document.getElementById('opponentFoundState');
            const battleResultModal = document.getElementById('battleResultModal');
            const confettiCanvas = document.getElementById('confetti-canvas');
            
            // Forward declarations for functions used before their definitions
            let initWebSocket;
            let sendToServer;
            let startHeartbeat;
            let loginToBattleServer;
            let updateConnectionStatus;
            
            // Add WebSocket URL element
            // This must be added as a server-side include that calls getWebSocketUrl()
            // For example: <span id="websocket-url" style="display:none;"><?php echo getWebSocketUrl(); ?></span>
            
            // Update battle timer
            const updateBattleTimer = () => {
                if (!gameState.startTime) return;
                
                // Check if the battle timer element exists
                const battleTimer = document.getElementById('battleTimer');
                if (!battleTimer) {
                    console.warn("Battle timer element not found in DOM");
                    return;
                }
                
                const elapsed = Math.floor((new Date() - gameState.startTime) / 1000);
                const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
                const seconds = (elapsed % 60).toString().padStart(2, '0');
                battleTimer.textContent = `${minutes}:${seconds}`;
            };
            
            // Skip matchmaking and show battle UI immediately in all cases
            // Hide matchmaking screen and show battle arena
            matchmakingScreen.style.display = 'none';
            battleArena.style.display = 'block';
            
            // Set battle as started
            battleStarted = true;
            gameState.startTime = new Date();
            
            // Start battle timer
            updateBattleTimer();
            // Set interval only once to avoid multiple timers
            if (!gameState.battleTimerInterval) {
                gameState.battleTimerInterval = setInterval(updateBattleTimer, 1000);
            }
            
            // Variable needed for all socket operations
            let heartbeatInterval = null;
            let connectionTimeout;
            
            // Initialize WebSocket for communication
            initWebSocket = () => {
                try {
                    console.log('Initializing WebSocket connection...');
                    
                    // Close existing connection if any
                    if (socket) {
                        console.log('Closing existing WebSocket connection');
                        socket.close();
                    }
                    
                    // Get the WebSocket URL from the element
                    const urlElement = document.getElementById('websocket-url');
                    console.log('WebSocket URL element found:', urlElement);
                    
                    if (urlElement) {
                        console.log('WebSocket URL element content:', urlElement.textContent);
                    } else {
                        console.error('WebSocket URL element not found in the DOM');
                    }
                    
                    // Get the URL from the element or use the fallback
                    const websocketUrl = urlElement && urlElement.textContent ? 
                        urlElement.textContent.trim() : 
                        `ws://${window.location.hostname}:8080`;
                    
                    console.log('Using WebSocket URL:', websocketUrl);
                    
                    // Create new WebSocket connection using the URL from settings
                    console.log('Creating WebSocket with URL:', websocketUrl);
                    socket = new WebSocket(websocketUrl);
                    
                    // Add connection timeout handler
                    connectionTimeout = setTimeout(() => {
                        console.error('Connection timeout - trying to reconnect');
                        showToast('Connection Timeout', 'Trying to reconnect to the battle server...', 'warning');
                        
                        // Try to reconnect
                        initWebSocket();
                    }, 5000);
                    
                    // Connection opened
                    socket.addEventListener('open', (event) => {
                        console.log('Connected to battle server');
                        // Clear connection timeout
                        clearTimeout(connectionTimeout);
                        
                        // Check if this is a reconnection
                        const wasDisconnected = gameState.connectionStatus === 'disconnected';
                        
                        // Update connection status if available
                        updateConnectionStatus('connected');
                        gameState.connectionStatus = 'connected';
                        
                        // Show toast based on connection status
                        if (wasDisconnected) {
                            showToast('Reconnected', 'Your connection has been restored', 'success');
                            addSystemChatMessage('Your connection has been restored');
                        } else {
                        showToast('Connected to battle server', 'Connection established', 'info');
                        }
                        
                        // Start the heartbeat
                        startHeartbeat();
                        
                        // Login to battle server with user info
                        loginToBattleServer();
                    });
                    
                    // Connection closed
                    socket.addEventListener('close', (event) => {
                        console.log('Disconnected from battle server');
                        
                        // Update connection status if available
                        updateConnectionStatus('disconnected');
                        gameState.connectionStatus = 'disconnected';
                        
                        // Store the current time of disconnection
                        gameState.lastConnectionTime = Date.now();
                        
                        // Increment reconnection attempts counter
                        gameState.reconnectAttempts++;
                        
                        // Check if we're in an active battle
                        if (battleStarted && gameState.battleId) {
                            // Save the battle ID for reconnection
                            const currentBattleId = gameState.battleId;
                            console.log(`Connection lost during active battle (ID: ${currentBattleId}). Will attempt to rejoin same battle.`);
                            
                            showToast('Disconnected', 'Lost connection to battle server. Trying to reconnect to your battle...', 'error');
                            
                            // Add message to chat
                            addSystemChatMessage('Connection lost. Attempting to reconnect to your battle...');
                            
                            // Try to reconnect after a short delay with exponential backoff
                            const delay = Math.min(3000 * Math.pow(1.5, gameState.reconnectAttempts - 1), 10000);
                            
                            setTimeout(() => {
                                console.log(`Attempting to reconnect to battle ${currentBattleId} (attempt ${gameState.reconnectAttempts})...`);
                                initWebSocket();
                            }, delay);
                        } else {
                            showToast('Disconnected', 'Lost connection to battle server. Trying to reconnect...', 'error');
                            
                            // Try to reconnect after 3 seconds if battle is still relevant
                            if (battleStarted) {
                                setTimeout(() => {
                                    console.log('Attempting to reconnect...');
                                    initWebSocket();
                                }, 3000);
                            }
                        }
                    });
                    
                    // Connection error
                    socket.addEventListener('error', (event) => {
                        console.error('WebSocket error:', event);
                        
                        // Update connection status if available
                        updateConnectionStatus('error');
                        gameState.connectionStatus = 'error';
                        
                        showToast('Connection Error', 'Failed to connect to battle server. Trying again...', 'error');
                        
                        // Add error message to chat
                        addSystemChatMessage('Connection error. Attempting to reconnect...');
                        
                        // Try to reconnect after a short delay
                        setTimeout(() => {
                            console.log('Attempting to reconnect after error...');
                            initWebSocket();
                        }, 5000);
                    });
                    
                    // Listen for messages
                    socket.addEventListener('message', (event) => {
                        try {
                            const data = JSON.parse(event.data);
                            handleServerMessage(data);
                        } catch (error) {
                            console.error('Error parsing server message:', error);
                        }
                    });
                } catch (error) {
                    console.error('WebSocket initialization failed:', error);
                    showToast('Connection Error', 'Failed to connect to battle server. Please try again.', 'error');
                }
            };
            
            // Initialize the WebSocket connection
            initWebSocket();
            
            // Heartbeat system to maintain connection
            startHeartbeat = () => {
                // Clear any existing heartbeat interval
                stopHeartbeat();
                
                // Send a heartbeat every 30 seconds
                heartbeatInterval = setInterval(() => {
                    if (socket && socket.readyState === WebSocket.OPEN) {
                        console.log('Sending heartbeat ping to server');
                        sendToServer({
                            action: 'heartbeat',
                            timestamp: Date.now()
                        });
                    } else {
                        console.warn('Cannot send heartbeat - socket not connected');
                        // Attempt to reconnect if socket is closed
                        if (!socket || socket.readyState === WebSocket.CLOSED) {
                            console.log('Socket is closed, attempting to reconnect');
                            initWebSocket();
                        }
                    }
                }, 30000); // 30 seconds
                
                console.log('Started heartbeat system');
            };
            
            // Stop the heartbeat system
            stopHeartbeat = () => {
                if (heartbeatInterval) {
                    clearInterval(heartbeatInterval);
                    heartbeatInterval = null;
                    console.log('Stopped heartbeat system');
                }
            };
            
            // Update connection status in UI
            updateConnectionStatus = (status) => {
                // Update the connection status indicator in the battle header
                const statusIndicator = document.getElementById('connectionStatus');
                if (!statusIndicator) return;
                
                // Remove all status classes
                statusIndicator.classList.remove('connected', 'disconnected', 'connecting', 'error');
                
                // Add appropriate class
                statusIndicator.classList.add(status);
                
                // Update text
                const statusText = {
                    'connected': 'Connected',
                    'disconnected': 'Disconnected',
                    'connecting': 'Connecting...',
                    'error': 'Connection Error'
                };
                
                statusIndicator.textContent = statusText[status] || 'Unknown';
            };
            
            // Send message to server
            sendToServer = (message) => {
                if (socket && socket.readyState === WebSocket.OPEN) {
                    socket.send(JSON.stringify(message));
                } else {
                    console.error('WebSocket is not connected');
                    showToast('Connection Error', 'Not connected to battle server', 'error');
                }
            };
            
            // Handle messages from server
            const handleServerMessage = (data) => {
                console.log('Server message:', data);
                
                switch (data.type) {
                    case 'loginSuccess':
                        // Login successful, either join match or find one
                        gameState.playerId = data.userId;
                        gameState.playerName = data.username;
                        gameState.playerAvatar = data.avatar;
                        
                        // Update player info
                        document.getElementById('playerName').textContent = data.username;
                        const playerAvatar = document.querySelector('.player-info .player-avatar img');
                        if (playerAvatar) {
                            playerAvatar.src = data.avatar;
                        }
                        
                        // Update connection status
                        updateConnectionStatus('connected');
                        gameState.connectionStatus = 'connected';
                        
                        // Start heartbeat system
                        startHeartbeat();
                        
                        // If we have a match ID, join it
                        if (matchId) {
                            sendToServer({
                                action: 'join_match',
                                matchId: matchId,
                                battleType: 'quick' // Specify this is a quick battle
                            });
                        } else if (directBattle) {
                            // Direct battle with current configuration
                            sendToServer({
                                action: 'create_direct_battle',
                                config: battleConfig,
                                battleType: 'quick' // Specify this is a quick battle
                            });
                        }
                        break;
                        
                    case 'heartbeat_ack':
                        // Server acknowledged our heartbeat ping
                        console.log('Received heartbeat acknowledgment from server');
                        // Update connection status to show active connection
                        updateConnectionStatus('connected');
                        break;
                        
                    case 'opponent_reconnected':
                    case 'opponentReconnected':
                        // Opponent has reconnected to the battle
                        console.log('Opponent reconnected to the battle');
                        showToast('Opponent Reconnected', data.message, 'success');
                        addSystemChatMessage(data.message);
                        
                        // Update opponent status
                        const opponentStatus = document.getElementById('opponentStatus');
                        if (opponentStatus) {
                            opponentStatus.textContent = 'Connected';
                            opponentStatus.className = 'player-status connected';
                        }
                        break;
                        
                    case 'opponentDisconnected':
                        // Opponent has disconnected from the battle
                        console.log('Opponent disconnected from the battle');
                        showToast('Opponent Disconnected', data.message, 'warning');
                        addSystemChatMessage(data.message);
                        
                        // Update opponent status
                        const opponentDisconnectedStatus = document.getElementById('opponentStatus');
                        if (opponentDisconnectedStatus) {
                            opponentDisconnectedStatus.textContent = 'Disconnected';
                            opponentDisconnectedStatus.className = 'player-status disconnected';
                        }
                        break;
                        
                    case 'loginSuccess':
                        // After successful login, we don't automatically find a match anymore
                        // as we've already shown the battle UI
                        showToast('Connected', 'Successfully connected to battle server', 'success');
                        
                        // If we have matchId in URL, join that match now that we're logged in
                        const params = new URLSearchParams(window.location.search);
                        const matchId = params.get('matchId');
                        if (matchId) {
                            console.log(`Joining existing match: ${matchId}`);
                            sendToServer({
                                action: 'join_match',
                                matchId: matchId,
                                battleType: 'quick' // Specify this is a quick battle
                            });
                        }
                        break;
                        
                    case 'joinMatchSuccess':
                        // Successfully joined an existing match
                        showToast('Joined Battle', 'Successfully joined the battle', 'success');
                        break;
                        
                    case 'waitingCount':
                        // Update active searchers count
                        if (document.getElementById('activeSearchers')) {
                            document.getElementById('activeSearchers').textContent = data.count;
                        }
                        break;
                        
                    case 'matchFound':
                        // Show opponent found state
                        if (data.opponent) {
                        showOpponentFound(data.opponent);
                        } else {
                            console.error('No opponent data in matchFound message');
                            // Use placeholder data as fallback
                            showOpponentFound({
                                id: 'unknown',
                                username: 'Opponent',
                                avatar: '../assets/default.png'
                            });
                        }
                        break;
                        
                    case 'opponent_ready':
                        // Show that opponent is ready
                        showOpponentReady();
                        break;
                        
                    case 'battleStart':
                        // Battle is starting with first question
                        if (!data.question) {
                            console.error('No question data in battleStart message');
                            
                            // Create a placeholder question instead of just returning
                            data.question = {
                                id: 1,
                                text: "Waiting for question data...",
                                options: ["Please wait", "Question loading", "Server is preparing question", "Reconnecting"],
                                number: 1
                            };
                            
                            // Show toast notification to inform user
                            showToast('Battle Start', 'Waiting for question data from server...', 'warning');
                            
                            // Try to request the first question
                            sendToServer({
                                action: 'request_question',
                                current: 1
                            });
                        }
                        
                        // Start the battle with the given question
                        startBattle(data.question, data.current || 1, data.total || 5);
                        
                        // Record battle start in database only if we have player data
                        if (data.players && data.players.length >= 2) {
                            console.log('Battle starting with players:', {
                                player1: data.players[0].username,
                                player1Id: data.players[0].userId,
                                player2: data.players[1].username,
                                player2Id: data.players[1].userId,
                                currentPlayerId: gameState.playerId
                            });
                            
                            // Store battle ID in game state
                            gameState.battleId = data.battleId;
                            
                            // Store player IDs in gameState for future reference
                            gameState.player1Id = data.players[0].userId;
                            gameState.player2Id = data.players[1].userId;
                            gameState.player1InitialPoints = 0; // Will be updated by recordBattleStart
                            gameState.player2InitialPoints = 0; // Will be updated by recordBattleStart
                            
                            // Make sure players[0] is valid
                            if (!data.players[0].username) {
                                data.players[0].username = 'Player 1';
                            }
                            if (!data.players[0].avatar) {
                                data.players[0].avatar = '../assets/default.png';
                            }
                            if (!data.players[0].userId) {
                                console.error('Missing userId for player 1');
                                data.players[0].userId = 'unknown_player1';
                            }
                            
                            // Make sure players[1] is valid
                            if (!data.players[1].username) {
                                data.players[1].username = 'Player 2';
                            }
                            if (!data.players[1].avatar) {
                                data.players[1].avatar = '../assets/default.png';
                            }
                            if (!data.players[1].userId) {
                                console.error('Missing userId for player 2');
                                data.players[1].userId = 'unknown_player2';
                            }
                            
                            // Identify which player is the current user
                            if (gameState.playerId) {
                                if (gameState.playerId === data.players[0].userId) {
                                    console.log('Current player is Player 1');
                                } else if (gameState.playerId === data.players[1].userId) {
                                    console.log('Current player is Player 2');
                                } else {
                                    console.warn('Current player ID does not match either battle player');
                                }
                            } else {
                                console.error('Missing gameState.playerId when starting battle');
                            }
                            
                            // Try to record the battle start in Supabase
                            try {
                                console.log('Recording battle start with id:', data.battleId);
                                recordBattleStart(
                                    data.battleId,
                                    data.players[0],
                                    data.players[1],
                                    data.total || 5,
                                    gameState.difficulty || 'medium',
                                    gameState.subject || 'general'
                                );
                            } catch (error) {
                                console.error('Failed to record battle start:', error);
                                
                                // Try again after a short delay
                                setTimeout(() => {
                                    try {
                                        console.log('Retrying battle record creation...');
                                        recordBattleStart(
                                            data.battleId,
                                            data.players[0],
                                            data.players[1],
                                            data.total || 5,
                                            gameState.difficulty || 'medium',
                                            gameState.subject || 'general'
                                        );
                                    } catch (retryError) {
                                        console.error('Retry failed to record battle start:', retryError);
                                    }
                                }, 2000);
                            }
                        } else {
                            console.warn('Missing player data in battleStart message');
                        }
                        break;
                        
                    case 'answer_received':
                        // Server acknowledged our answer
                        showAnswerReceived();
                        break;
                        
                    case 'opponent_answered':
                        // Opponent has answered
                        showOpponentAnswered();
                        break;
                        
                    case 'answer_results':
                        // Both players answered, show results
                        showAnswerResults(data);
                        break;
                        
                    case 'question':
                        // Load new question or update current question
                        loadQuestion(data.question);
                        
                        // Safely update question number if present
                        if (data.current) {
                            const currentQuestionEl = document.getElementById('currentQuestion');
                            if (currentQuestionEl) {
                                currentQuestionEl.textContent = data.current;
                            }
                        }
                        
                        // Safely update total questions if present
                        if (data.total) {
                            const totalQuestionsEl = document.getElementById('totalQuestions');
                            if (totalQuestionsEl) {
                                totalQuestionsEl.textContent = data.total;
                            }
                        }
                        break;
                        
                    case 'next_question':
                        // Load next question
                        loadNextQuestion(data.question, data.current, data.total);
                        break;
                        
                    case 'waiting_for_opponent_next':
                        // Waiting for opponent to be ready for next question
                        showWaitingForOpponentNext();
                        break;
                        
                    case 'opponent_ready_next':
                        // Opponent is ready for next question
                        showOpponentReadyNext();
                        break;
                        
                    case 'battleEnd':
                        // Check if we've already processed this end event to prevent double processing
                        if (gameState.battleEnded) {
                            console.log('Battle already ended, ignoring duplicate event');
                            return;
                        }
                        
                        // Mark battle as ended to prevent duplicate processing
                        gameState.battleEnded = true;
                        console.log('Battle ended:', data);
                        
                        // Calculate correct answers
                        const yourCorrectAnswers = data.stats && data.stats.correctAnswers !== undefined
                            ? data.stats.correctAnswers
                            : (gameState.playerAnswers && gameState.playerAnswers.filter
                               ? gameState.playerAnswers.filter(a => a.correct).length 
                               : 0);
                               
                        const opponentCorrectAnswers = data.stats && data.stats.opponentCorrectAnswers !== undefined
                            ? data.stats.opponentCorrectAnswers
                            : (gameState.opponentAnswers && gameState.opponentAnswers.filter
                               ? gameState.opponentAnswers.filter(a => a.correct).length 
                               : 0);
                        
                        const player1CorrectAnswers = gameState.player1Id === gameState.playerId 
                            ? yourCorrectAnswers : opponentCorrectAnswers;
                        
                        const player2CorrectAnswers = gameState.player1Id === gameState.playerId 
                            ? opponentCorrectAnswers : yourCorrectAnswers;
                            
                        const player1Score = data.stats && data.stats.your_score && gameState.player1Id === gameState.playerId
                            ? data.stats.your_score : (data.stats && data.stats.opponent_score && gameState.player1Id !== gameState.playerId
                               ? data.stats.opponent_score : 0);
                               
                        const player2Score = data.stats && data.stats.opponent_score && gameState.player1Id === gameState.playerId
                            ? data.stats.opponent_score : (data.stats && data.stats.your_score && gameState.player1Id !== gameState.playerId
                               ? data.stats.your_score : 0);
                        
                        // Get the actual result and reason
                        const battleResult = data.result || 'incomplete';
                        const battleReason = data.reason || 'Success';
                        
                        // Store additional data for potential retries
                        gameState.lastBattleEndData = {
                            player1CorrectAnswers,
                            player2CorrectAnswers,
                            player1Score,
                            player2Score,
                            battleResult,
                            battleReason
                        };
                        
                        // Update battle record in Supabase 
                        if (gameState.battleRecordId || gameState.battleId) {
                            console.log('Updating battle record in Supabase...');
                            
                            // Call updateBattleEnd without await, using promise then/catch instead
                            updateBattleEnd(
                                battleResult,
                                battleReason,
                                player1Score,
                                player2Score,
                                player1CorrectAnswers,
                                player2CorrectAnswers
                            ).then(() => {
                                console.log('Battle record updated successfully');
                            }).catch(error => {
                                console.error('Failed to update battle record:', error);
                                
                                // Show error message to user
                                showToast('Database Error', 'Failed to update battle statistics. Your results may not be saved.', 'error');
                                
                                // Try to retry once after a delay
                                setTimeout(() => {
                                    if (gameState.lastBattleEndData) {
                                        console.log('Retrying battle record update...');
                                        updateBattleEnd(
                                            gameState.lastBattleEndData.battleResult,
                                            gameState.lastBattleEndData.battleReason,
                                            gameState.lastBattleEndData.player1Score,
                                            gameState.lastBattleEndData.player2Score,
                                            gameState.lastBattleEndData.player1CorrectAnswers,
                                            gameState.lastBattleEndData.player2CorrectAnswers
                                        ).catch(retryError => {
                                            console.error('Retry failed to update battle record:', retryError);
                                        });
                                    }
                                }, 3000);
                            });
                        } else {
                            console.warn('No battle record ID or battle ID found, skipping database update');
                        }
                        
                        // Show battle result UI
                        if (typeof endBattle === 'function') {
                        endBattle(data.result, data.stats);
                        } else {
                            console.error('endBattle function not found');
                            // Fallback display
                            showToast('Battle Ended', 'The battle has ended.', 'info');
                        }
                        break;
                        
                    case 'answerResult':
                        // Show answer result
                        handleAnswerResult(data);
                        break;
                        
                    case 'opponentAnswer':
                        // Opponent answered the current question
                        handleOpponentAnswer(data);
                        break;
                        
                    case 'chat_message':
                        // Display chat message from opponent
                        if (data.userId && data.username) {
                        displayChatMessage(data.userId, data.username, data.avatar, data.message, false);
                        } else {
                            console.error('Invalid chat message format:', data);
                        }
                        break;
                        
                    case 'opponentTemporarilyDisconnected':
                        // Opponent temporarily disconnected
                        showOpponentTemporaryDisconnect(data.message);
                        break;
                        
                    case 'opponentReconnected':
                        // Opponent reconnected
                        showOpponentReconnected(data.message);
                        break;
                        
                    case 'opponentDisconnected':
                        // Opponent disconnected permanently (timeout)
                        showOpponentPermanentDisconnect(data.message);
                        break;
                        
                    case 'opponent_disconnected':
                        // Legacy handler for backward compatibility
                        handleOpponentDisconnect(data.result, data.reason);
                        break;
                        
                    case 'opponentQuit':
                        // Opponent quit the battle
                        showOpponentQuit(data.message);
                        break;
                        
                    default:
                        console.warn('Unhandled message type:', data.type);
                        break;
                }
            };
            
            // Show opponent temporary disconnect
            const showOpponentTemporaryDisconnect = (message) => {
                // Add system message to chat
                addSystemChatMessage(message || 'Your opponent has temporarily disconnected. The battle will continue when they reconnect.');
                
                // Show a status indicator on the opponent card
                const opponentCard = document.getElementById('opponentCard');
                if (opponentCard) {
                    opponentCard.classList.add('disconnected');
                    const statusIndicator = document.querySelector('#opponentCard .player-status');
                    if (statusIndicator) {
                        statusIndicator.style.backgroundColor = 'orange';
                    }
                    const statusText = document.querySelector('#opponentCard .player-status-text');
                    if (statusText) {
                        statusText.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Disconnected';
                        statusText.style.color = 'orange';
                    }
                }
                
                // Show toast notification
                showToast('Opponent Disconnected', 'Your opponent has temporarily disconnected. The battle will continue when they reconnect.', 'warning');
            };
            
            // Show opponent reconnected
            const showOpponentReconnected = (message) => {
                // Add system message to chat
                addSystemChatMessage(message || 'Your opponent has reconnected. The battle will continue.');
                
                // Update status indicator on the opponent card
                const opponentCard = document.getElementById('opponentCard');
                if (opponentCard) {
                    opponentCard.classList.remove('disconnected');
                    const statusIndicator = document.querySelector('#opponentCard .player-status');
                    if (statusIndicator) {
                        statusIndicator.style.backgroundColor = 'green';
                    }
                    const statusText = document.querySelector('#opponentCard .player-status-text');
                    if (statusText) {
                        statusText.innerHTML = '<i class="fas fa-circle"></i> Connected';
                        statusText.style.color = 'green';
                    }
                }
                
                // Show toast notification
                showToast('Opponent Reconnected', 'Your opponent has reconnected. The battle will continue.', 'success');
            };
            
            // Show opponent permanent disconnect
            const showOpponentPermanentDisconnect = (message) => {
                // Add system message to chat
                addSystemChatMessage(message || 'Your opponent has disconnected. You win the battle!');
                
                // Show toast notification
                showToast('Opponent Left', 'Your opponent has disconnected. You win the battle!', 'info');
            };
            
            // Show opponent quit
            const showOpponentQuit = (message) => {
                // Add system message to chat
                addSystemChatMessage(message || 'Your opponent has quit the battle. You win!');
                
                // Show toast notification
                showToast('Opponent Quit', 'Your opponent has quit the battle. You win!', 'info');
            };
            
            // Login to battle server
            loginToBattleServer = () => {
                // Get current user from Supabase
                supabaseClient.auth.getUser().then(({ data: { user } }) => {
                    if (user) {
                        // Store user ID immediately to ensure it's available
                        gameState.playerId = user.id;
                        console.log('User authenticated, set playerId:', user.id);
                        
                        // Get profile info
                        supabaseClient
                            .from('profiles')
                            .select('username, avatar_url, points')
                            .eq('user_id', user.id)
                            .single()
                            .then(({ data: profile }) => {
                                if (profile) {
                                    // Update gameState with real user data
                                    gameState.playerName = profile.username || 'You';
                                    gameState.playerAvatar = profile.avatar_url || '../assets/default.png';
                                    gameState.playerPoints = profile.points || 0;
                                    
                                    // Update UI with real player data
                                    document.getElementById('playerName').textContent = gameState.playerName;
                                    document.getElementById('playerAvatar').src = gameState.playerAvatar;
                                    document.getElementById('playerPoints').textContent = gameState.playerPoints;
                                    
                                    const playerNameElements = document.querySelectorAll('.player-name');
                                    playerNameElements.forEach(el => {
                                        if (el.closest('.player-card:first-child')) {
                                            el.textContent = gameState.playerName;
                                        }
                                    });
                                    
                                    const playerAvatarElements = document.querySelectorAll('.player-avatar img');
                                    playerAvatarElements.forEach(el => {
                                        if (el.closest('.player-card:first-child')) {
                                            el.src = gameState.playerAvatar;
                                        }
                                    });
                                    
                                    // Create login message with complete user details
                                    const loginMessage = {
                                        action: 'login',
                                        userId: user.id,
                                        username: profile.username || 'Player',
                                        avatar: profile.avatar_url || '../assets/default.png',
                                        battleType: 'quick'
                                    };
                                    
                                    // If we have a matchId in URL or stored in gameState, add it to the login message
                                    // This helps reconnect to the same battle after a connection loss
                                    const params = new URLSearchParams(window.location.search);
                                    const matchId = params.get('matchId') || gameState.battleId;
                                    
                                    if (matchId) {
                                        loginMessage.battleId = matchId;
                                        console.log('Attempting to reconnect to battle:', matchId);
                                    }
                                    
                                    // Send login message
                                    sendToServer(loginMessage);
                                    
                                    console.log('Login sent with profile data:', profile.username);
                                } else {
                                    // No profile found, still send login but with limited data
                                    const loginMessage = {
                                        action: 'login',
                                        userId: user.id,
                                        username: 'Player',
                                        avatar: '../assets/default.png',
                                        battleType: 'quick'
                                    };
                                    
                                    // Add battleId if available
                                    const params = new URLSearchParams(window.location.search);
                                    const matchId = params.get('matchId') || gameState.battleId;
                                    
                                    if (matchId) {
                                        loginMessage.battleId = matchId;
                                        console.log('Attempting to reconnect to battle:', matchId);
                                    }
                                    
                                    sendToServer(loginMessage);
                                    console.log('Login sent with default data (no profile found)');
                                }
                            })
                            .catch(error => {
                                console.error('Error fetching profile:', error);
                                // Send basic login as fallback
                                const loginMessage = {
                                    action: 'login',
                                    userId: user.id,
                                    username: 'Player',
                                    avatar: '../assets/default.png',
                                    battleType: 'quick'
                                };
                                
                                // Add battleId if available
                                const params = new URLSearchParams(window.location.search);
                                const matchId = params.get('matchId') || gameState.battleId;
                                
                                if (matchId) {
                                    loginMessage.battleId = matchId;
                                    console.log('Attempting to reconnect to battle:', matchId);
                                }
                                
                                sendToServer(loginMessage);
                            });
                    } else {
                        console.error('User not authenticated');
                        showToast('Authentication Error', 'You must be logged in to battle', 'error');
                    }
                }).catch(error => {
                    console.error('Error checking authentication:', error);
                });
            };
            
            // Find a match - only called explicitly when needed
            const findMatch = () => {
                sendToServer({
                    action: 'find_match',
                    config: battleConfig,
                    battleType: 'quick'
                });
                
                showToast('Matchmaking', 'Looking for an opponent...', 'info');
            };
            
            // Cancel matchmaking
            const cancelMatchmaking = () => {
                sendToServer({
                    action: 'cancel_matchmaking'
                });
                
                showToast('Cancelled', 'Matchmaking cancelled', 'info');
                
                // Redirect to dashboard
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 1000);
            };
            
            // Show opponent found state
            const showOpponentFound = (opponent) => {
                console.log('Opponent found:', opponent);
                
                if (!opponent || !opponent.username) {
                    console.error('Received incomplete opponent data:', opponent);
                    opponent = opponent || {};
                    opponent.username = opponent.username || 'Opponent';
                    opponent.avatar = opponent.avatar || '../assets/default.png';
                    opponent.id = opponent.id || 'unknown';
                }
                
                // Store opponent info in gameState
                gameState.opponentId = opponent.id;
                gameState.opponentName = opponent.username;
                gameState.opponentAvatar = opponent.avatar;
                gameState.opponentInfo = opponent; // Store complete opponent data
                
                // Thoroughly update all opponent UI elements
                const opponentNameElements = document.querySelectorAll('#opponentName, .player-card:last-child .player-name, #headerOpponentName, #resultOpponentName');
                opponentNameElements.forEach(el => {
                    if (el) el.textContent = opponent.username;
                });
                
                const opponentAvatarElements = document.querySelectorAll('#opponentAvatar, .player-card:last-child .player-avatar img, #resultOpponentAvatar');
                opponentAvatarElements.forEach(el => {
                    if (el) el.src = opponent.avatar || '../assets/default.png';
                });
                
                // Update opponent connection status
                const opponentStatus = document.getElementById('opponentStatus');
                if (opponentStatus) {
                    opponentStatus.textContent = 'Connected';
                    opponentStatus.className = 'player-status connected';
                }
                
                // Update header player names if the function exists
                if (typeof updateHeaderPlayerNames === 'function') {
                updateHeaderPlayerNames();
                }
                
                // Show toast
                showToast('Opponent Found', `You're playing against ${opponent.username}`, 'success');
                
                // Add system message to chat
                addSystemChatMessage(`You're now in a battle with ${opponent.username}`);
                
                // Show battle UI
                document.getElementById('matchmakingSection').style.display = 'none';
                document.getElementById('battleSection').style.display = 'flex';
                document.getElementById('leaveBtn').style.display = 'block';
            };
            
            // Update opponent info in battle UI
            const updateOpponentInfo = (opponent) => {
                // Update opponent avatar in battle
                const opponentAvatar = document.getElementById('opponentAvatar');
                if (opponentAvatar) {
                    opponentAvatar.src = opponent.avatar || '../assets/default.png';
                }
                
                // Update opponent name in battle
                const opponentName = document.getElementById('opponentName');
                if (opponentName) {
                    opponentName.textContent = opponent.username || 'Opponent';
                }
                
                // Update opponent info in result modal
                const resultOpponentAvatar = document.getElementById('resultOpponentAvatar');
                const resultOpponentName = document.getElementById('resultOpponentName');
                
                if (resultOpponentAvatar) {
                    resultOpponentAvatar.src = opponent.avatar || '../assets/default.png';
                }
                
                if (resultOpponentName) {
                    resultOpponentName.textContent = opponent.username || 'Opponent';
                }
            };
            
            // Update header player names
            const updateHeaderPlayerNames = () => {
                // Get player name from player card
                const playerName = document.getElementById('playerName').textContent;
                
                // Get opponent name
                const opponentName = gameState.opponentInfo?.username || 'Opponent';
                
                // Update header player names
                document.getElementById('headerPlayerName').textContent = playerName;
                document.getElementById('headerOpponentName').textContent = opponentName;
            };
            
            // Mark player as ready
            const markAsReady = () => {
                // Update button style
                const readyButton = document.getElementById('readyButton');
                readyButton.classList.add('ready');
                readyButton.disabled = true;
                readyButton.innerHTML = '<i class="fas fa-check-circle"></i><span>Ready!</span>';
                
                // Tell server we're ready
                sendToServer({
                    action: 'player_ready'
                });
                
                showToast('Ready', 'You are ready for battle!', 'success');
            };
            
            // Show opponent is ready
            const showOpponentReady = () => {
                // Show indicator that opponent is ready
                if (!opponentFoundState.querySelector('.opponent-ready-indicator')) {
                    const opponentReadyIndicator = document.createElement('div');
                    opponentReadyIndicator.className = 'opponent-ready-indicator';
                    opponentReadyIndicator.innerHTML = '<i class="fas fa-check-circle"></i> Opponent is ready!';
                    opponentFoundState.appendChild(opponentReadyIndicator);
                }
                
                showToast('Opponent Ready', 'Your opponent is ready for battle!', 'info');
            };
            
            // Start battle with first question
            const startBattle = (question, current, total) => {
                // Only set battleStarted if it's not already set to avoid duplicate initialization
                if (!battleStarted) {
                battleStarted = true;
                gameState.startTime = new Date();
                
                // Hide matchmaking screen and show battle arena
                    if (document.getElementById('matchmakingScreen')) {
                        document.getElementById('matchmakingScreen').style.display = 'none';
                    }
                
                    if (document.getElementById('battleArena')) {
                        document.getElementById('battleArena').style.display = 'block';
                    }
                
                    // Add chat message only once
                    addSystemChatMessage('Battle started! Good luck to both players!');
                    showToast('Battle Started', 'The battle has begun!', 'success');
                
                // Start battle timer
                updateBattleTimer();
                    // Set interval only once to avoid multiple timers
                    if (!gameState.battleTimerInterval) {
                        gameState.battleTimerInterval = setInterval(updateBattleTimer, 1000);
                    }
                }
                
                // Always update these values which may change between questions
                gameState.currentQuestion = current;
                currentQuestion = question;
                
                // Update question total in UI
                if (document.getElementById('totalQuestions')) {
                    document.getElementById('totalQuestions').textContent = total;
                }
                
                // Load question
                loadQuestion(question);
            };
            
            // Update question timer display
            const updateQuestionTimer = () => {
                const timerFill = document.getElementById('questionTimerFill');
                const timerText = document.getElementById('questionTimer');
                
                // Check if timer elements exist before updating them
                if (!timerFill || !timerText) {
                    console.warn("Timer elements not found in the DOM");
                    return; // Exit the function if elements don't exist
                }
                
                // Update timer text
                timerText.textContent = `${questionTimeRemaining}s`;
                
                // Update timer fill
                const percentage = (questionTimeRemaining / questionTimeLimit) * 100;
                timerFill.style.width = `${percentage}%`;
                
                // Change color based on time remaining
                if (questionTimeRemaining <= 5) {
                    timerFill.style.background = 'linear-gradient(90deg, #dc3545, #ff6b6b)';
                    timerText.style.color = '#dc3545';
                } else if (questionTimeRemaining <= 10) {
                    timerFill.style.background = 'linear-gradient(90deg, #ffc107, #ffdb58)';
                    timerText.style.color = '#856404';
                }
            };
            
            // Load a question
            const loadQuestion = (question) => {
                // Set current question as in-progress
                gameState.questionStarted = true;
                gameState.questionStartTime = new Date();
                gameState.selectedAnswer = null;
                
                // Update question text and number
                const questionText = document.getElementById('questionText');
                if (questionText) {
                    questionText.textContent = question.text || 'Loading question...';
                }
                
                // Get or create image container
                let imageContainer = document.querySelector('.question-image-container');
                
                // Create image container if it doesn't exist
                if (!imageContainer) {
                    imageContainer = document.createElement('div');
                    imageContainer.className = 'question-image-container';
                    
                    // Find the question container to add the image container
                    const questionContent = document.querySelector('.question-content');
                    if (questionContent) {
                        // Insert after question text
                        if (questionText) {
                            questionText.after(imageContainer);
                        } else {
                            // Or append to question content if there's no question text
                            questionContent.appendChild(imageContainer);
                        }
                    }
                }
                
                // Update or show loader for the image
                if (imageContainer) {
                    // Display a loading spinner instead of trying to load question images
                    imageContainer.innerHTML = `
                        <div class="question-loader">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p>Loading question...</p>
                        </div>
                    `;
                    imageContainer.style.display = 'block';
                }
                
                // Clear previous answers
                const answersContainer = document.getElementById('answersContainer');
                if (!answersContainer) {
                    console.error("Answers container not found in DOM");
                    return;
                }
                
                answersContainer.innerHTML = '';
                
                // Get options
                const options = question.options || ['Option A', 'Option B', 'Option C', 'Option D'];
                
                // Create options
                for (let i = 0; i < options.length; i++) {
                    const option = options[i];
                    const optionId = String.fromCharCode(97 + i); // a, b, c, d
                    const optionNumber = i + 1; // 1, 2, 3, 4
                    
                    const optionElement = document.createElement('div');
                    optionElement.className = 'answer-option';
                    optionElement.setAttribute('data-answer', optionId);
                    optionElement.innerHTML = `
                        <div class="answer-number">${optionNumber}</div>
                        <div class="answer-text-container">
                        <div class="answer-text">${option}</div>
                            <div class="answer-radio"></div>
                        </div>
                    `;
                    
                    optionElement.addEventListener('click', () => selectAnswer(optionId));
                    answersContainer.appendChild(optionElement);
                }
                
                // Reset next button - safely handle potentially missing element
                const nextQuestionBtn = document.getElementById('nextQuestionBtn');
                if (nextQuestionBtn) {
                    nextQuestionBtn.disabled = true;
                }
                
                // Reset waiting state
                gameState.waitingForOpponent = false;
                
                // Remove any previous waiting messages
                const waitingMessage = document.querySelector('.waiting-message');
                if (waitingMessage) waitingMessage.remove();
                
                const opponentAnsweredMessage = document.querySelector('.opponent-answered-message');
                if (opponentAnsweredMessage) opponentAnsweredMessage.remove();
                
                // Start timer for this question
                startQuestionTimer();
            };
            
            // Handle answer selection
            const originalSelectAnswer = window.selectAnswer || ((answerId) => {
                console.log('Original selectAnswer not found');
            });
            
            // Override the original selectAnswer function with our enhanced version
            window.selectAnswer = (optionId) => {
                // Ignore if options are disabled
                if (document.querySelector('.answer-option.disabled')) {
                    return;
                }
                
                // Stop the timer
                clearInterval(gameState.questionTimerInterval);
                
                // Calculate response time in milliseconds
                const responseTime = new Date() - gameState.questionStartTime;
                
                // Highlight the selected option
                const options = document.querySelectorAll('.answer-option');
                options.forEach(option => {
                    const answer = option.getAttribute('data-answer');
                    if (answer === optionId) {
                        option.classList.add('selected');
                    } else {
                        option.classList.remove('selected');
                    }
                });
                
                // Disable further selections
                disableAnswerOptions();
                
                // Check if answer is correct
                const isCorrect = (optionId === gameState.correctAnswer);
                
                // Record the response
                recordResponse(optionId, false, responseTime, isCorrect);
                
                // Show feedback
                if (isCorrect) {
                    showToast('Correct!', '+4 points', 'success');
                } else {
                    showToast('Incorrect', '-1 point', 'error');
                }
                
                // Highlight correct answer
                highlightCorrectAnswer();
                
                // Enable next question button if it exists
                const nextBtn = document.getElementById('nextQuestionBtn');
                if (nextBtn) {
                    nextBtn.disabled = false;
                }
                
                // Send message to server that player has answered
                sendPlayerAnsweredMessage(optionId, isCorrect, responseTime);
                
                // Call the original function if it exists
                if (typeof originalSelectAnswer === 'function' && originalSelectAnswer !== window.selectAnswer) {
                    try {
                        originalSelectAnswer(optionId);
                    } catch (err) {
                        console.error('Error calling original selectAnswer:', err);
                    }
                }
            };
            
            // Show answer received confirmation
            const showAnswerReceived = () => {
                // Add a "Waiting for opponent" message
                const waitingMessage = document.createElement('div');
                waitingMessage.className = 'waiting-message';
                waitingMessage.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Waiting for opponent...';
                
                // Add to the question container if it exists
                const questionContainer = document.querySelector('.question-container');
                if (questionContainer) {
                    // Check if message already exists to avoid duplicates
                if (!questionContainer.querySelector('.waiting-message')) {
                    questionContainer.appendChild(waitingMessage);
                    }
                } else {
                    console.warn("Question container not found when showing answer received");
                }
            };
            
            // Show opponent has answered
            const showOpponentAnswered = () => {
                // Add an "Opponent has answered" message
                const opponentAnsweredMessage = document.createElement('div');
                opponentAnsweredMessage.className = 'opponent-answered-message';
                opponentAnsweredMessage.innerHTML = '<i class="fas fa-check"></i> Opponent has answered!';
                
                // Find the question container
                const questionContainer = document.querySelector('.question-container');
                if (!questionContainer) {
                    console.warn("Question container not found when showing opponent answered");
                    return;
                }
                
                // Replace waiting message if it exists
                const waitingMessage = questionContainer.querySelector('.waiting-message');
                if (waitingMessage) {
                    waitingMessage.parentNode.replaceChild(opponentAnsweredMessage, waitingMessage);
                } else {
                    // Add to the question container if waiting message doesn't exist
                    questionContainer.appendChild(opponentAnsweredMessage);
                }
                
                showToast('Opponent Answered', 'Your opponent has submitted their answer', 'info');
            };
            
            // Show answer results
            const showAnswerResults = (data) => {
                // Update scores
                gameState.playerScore = data.scores.you;
                gameState.opponentScore = data.scores.opponent;
                
                document.getElementById('playerScore').textContent = gameState.playerScore;
                document.getElementById('opponentScore').textContent = gameState.opponentScore;
                
                // Highlight correct and incorrect answers
                const options = document.querySelectorAll('.answer-option');
                options.forEach(option => {
                    const answer = option.getAttribute('data-answer');
                    
                    if (answer === data.correct_answer) {
                        option.classList.add('correct');
                    } else if (option.classList.contains('selected')) {
                        option.classList.add('incorrect');
                    }
                    
                    // Add indicators for player and opponent answers
                    if (answer === data.your_answer.answer) {
                        option.classList.add('player-selected');
                    }
                    
                    if (answer === data.opponent_answer.answer) {
                        option.classList.add('opponent-selected');
                    }
                });
                
                // Remove waiting messages
                const waitingMessage = document.querySelector('.waiting-message');
                if (waitingMessage) waitingMessage.remove();
                
                // Enable next button
                document.getElementById('nextQuestionBtn').disabled = false;
                
                // Update progress bars
                updateProgressBars(data.progress);
            };
            
            // Load next question
            const loadNextQuestion = (question, current, total) => {
                gameState.currentQuestion = current;
                loadQuestion(question);
                
                // Reset next button
                const nextBtn = document.getElementById('nextQuestionBtn');
                nextBtn.disabled = true;
                nextBtn.innerHTML = '<span>Next Question</span><i class="fas fa-arrow-right"></i>';
                
                // Add chat message
                addSystemChatMessage(`Moving to question ${current} of ${total}`);
            };
            
            // Show waiting for opponent for next question
            const showWaitingForOpponentNext = () => {
                const nextBtn = document.getElementById('nextQuestionBtn');
                nextBtn.disabled = true;
                nextBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Waiting for opponent...</span>';
            };
            
            // Show opponent is ready for next question
            const showOpponentReadyNext = () => {
                showToast('Opponent Ready', 'Your opponent is ready for the next question', 'info');
            };
            
            // End battle and show results
            const endBattle = (result, stats) => {
                // If battle already ended, ignore
                if (gameState.battleEnded) return;
                
                gameState.battleEnded = true;
                
                // Calculate final scores
                updatePlayerScore();
                updateOpponentScore();
                
                // Determine actual winner based on scores for Quick Battle
                let finalResult = result;
                if (result === 'end_battle') {
                    if (gameState.playerScore > gameState.opponentScore) {
                        finalResult = 'win';
                    } else if (gameState.playerScore < gameState.opponentScore) {
                        finalResult = 'lose';
                    } else {
                        finalResult = 'tie';
                    }
                }
                
                // Show the appropriate result modal
                showResultModal(finalResult);
                
                // Calculate correct answer counts
                const player1CorrectAnswers = gameState.playerId === gameState.player1Id ? 
                    gameState.playerAnswers.filter(a => a.isCorrect).length : 
                    gameState.opponentAnswers.filter(a => a.isCorrect).length;
                    
                const player2CorrectAnswers = gameState.playerId === gameState.player1Id ? 
                    gameState.opponentAnswers.filter(a => a.isCorrect).length : 
                    gameState.playerAnswers.filter(a => a.isCorrect).length;
                
                // Update battle record in database
                updateBattleEnd(
                    finalResult, 
                    battleType, 
                    gameState.player1Id === gameState.playerId ? gameState.playerScore : gameState.opponentScore, 
                    gameState.player2Id === gameState.playerId ? gameState.playerScore : gameState.opponentScore,
                    player1CorrectAnswers,
                    player2CorrectAnswers
                );
                
                // Stop all timers
                clearInterval(gameState.battleTimerInterval);
                clearInterval(questionTimer);
            };
            
            // Handle opponent disconnect
            const handleOpponentDisconnect = (result, reason) => {
                // Show a message that opponent disconnected
                showToast('Opponent Disconnected', 'Your opponent has disconnected from the battle', 'error');
                
                // Create a disconnect message element
                const disconnectMessage = document.createElement('div');
                disconnectMessage.className = 'waiting-message';
                disconnectMessage.style.color = '#dc3545';
                disconnectMessage.style.borderColor = '#f5c6cb';
                disconnectMessage.innerHTML = '<i class="fas fa-user-slash"></i> Opponent disconnected!';
                
                // Add to the question container
                const questionContainer = document.querySelector('.question-container');
                questionContainer.appendChild(disconnectMessage);
                
                // Add chat message
                addSystemChatMessage('Your opponent has disconnected from the battle.');
                
                // After a delay, show the result modal
                setTimeout(() => {
                    endBattle('win', {
                        your_score: gameState.playerScore,
                        opponent_score: gameState.opponentScore,
                        correct_answers: gameState.playerAnswers.filter(a => a.correct).length || 0,
                        time: ((new Date()) - gameState.startTime) / 1000
                    });
                }, 2000);
            };
            
            // Handle opponent reconnection
            const handlePlayerReconnected = (playerNum) => {
                showToast('Opponent Reconnected', 'Your opponent has reconnected to the battle', 'success');
                
                // Show a message that opponent reconnected
                const reconnectMessage = document.createElement('div');
                reconnectMessage.className = 'reconnect-message';
                reconnectMessage.innerHTML = '<i class="fas fa-user-check"></i> Opponent reconnected!';
                
                // Add to the question container
                const questionContainer = document.querySelector('.question-container');
                questionContainer.appendChild(reconnectMessage);
                
                // Add chat message
                addSystemChatMessage('Your opponent has reconnected to the battle.');
                
                // Remove the message after 5 seconds
                setTimeout(() => {
                    if (reconnectMessage.parentNode) {
                        reconnectMessage.remove();
                    }
                }, 5000);
            };
            
            // Handle answer result
            const handleAnswerResult = (data) => {
                // Update scores based on correctness
                if (data.isCorrect) {
                    gameState.playerScore += 10;
                }
                
                document.getElementById('playerScore').textContent = gameState.playerScore;
                
                // Update progress bars
                document.getElementById('playerProgress').style.width = `${data.player1Health}%`;
                document.getElementById('playerProgressText').textContent = `${Math.round(data.player1Health)}%`;
                document.getElementById('opponentProgress').style.width = `${data.player2Health}%`;
                document.getElementById('opponentProgressText').textContent = `${Math.round(data.player2Health)}%`;
                
                // Add visual indication for correct/incorrect answers
                const options = document.querySelectorAll('.answer-option');
                options.forEach(option => {
                    const answer = option.getAttribute('data-answer');
                    if (answer === data.correctAnswer) {
                        option.classList.add('correct');
                    } else if (option.classList.contains('selected')) {
                        option.classList.add('incorrect');
                    }
                });
                
                // Add chat message
                addSystemChatMessage(data.isCorrect ? 'You answered correctly!' : 'You answered incorrectly!');
            };
            
            // Handle opponent answer
            const handleOpponentAnswer = (data) => {
                // Update opponent progress
                document.getElementById('opponentProgress').style.width = `${data.health}%`;
                document.getElementById('opponentProgressText').textContent = `${Math.round(data.health)}%`;
                
                // Show opponent answered message
                showOpponentAnswered();
                
                // Add chat message
                addSystemChatMessage('Your opponent has submitted their answer.');
            };
            
            // Show toast notification
            const showToast = (title, message, type = 'success') => {
                // Remove any existing toasts
                const existingToasts = document.querySelectorAll('.toast-notification');
                existingToasts.forEach(toast => {
                    toast.remove();
                });
                
                // Create toast element
                const toast = document.createElement('div');
                toast.className = `toast-notification ${type}`;
                
                // Set icon based on type
                let icon = 'check-circle';
                if (type === 'error') icon = 'exclamation-circle';
                if (type === 'info') icon = 'info-circle';
                
                toast.innerHTML = `
                    <div class="toast-notification-icon">
                        <i class="fas fa-${icon}"></i>
                    </div>
                    <div class="toast-notification-content">
                        <h4 class="toast-notification-title">${title}</h4>
                        <p class="toast-notification-message">${message}</p>
                    </div>
                `;
                
                // Add to document
                document.body.appendChild(toast);
                
                // Remove after 5 seconds
                setTimeout(() => {
                    toast.remove();
                }, 5000);
            };
            
            // Chat functions
            
            // Send chat message
            const sendChatMessage = () => {
                const chatInput = document.getElementById('chatInput');
                const message = chatInput.value.trim();
                
                if (!message) return;
                
                // Get user info
                const userId = gameState.playerId;
                const username = document.getElementById('playerName').textContent;
                const avatar = document.getElementById('playerAvatar').src;
                
                // Send to server
                sendToServer({
                    action: 'chat_message',
                    message: message
                });
                
                // Display locally
                displayChatMessage(userId, username, avatar, message, true);
                
                // Clear input
                chatInput.value = '';
            };
            
            // Display chat message
            const displayChatMessage = (userId, username, avatar, message, isOutgoing = false) => {
                const chatMessages = document.getElementById('chatMessages');
                
                // Create message element
                const messageElement = document.createElement('div');
                messageElement.className = `chat-message ${isOutgoing ? 'outgoing' : ''}`;
                
                // Format time
                const now = new Date();
                const timeText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                
                messageElement.innerHTML = `
                    <div class="chat-avatar">
                        <img src="${avatar}" alt="${username}">
                    </div>
                    <div class="chat-bubble">
                        <p class="chat-message-text">${escapeHtml(message)}</p>
                        <div class="chat-message-time">${isOutgoing ? 'You' : username} • ${timeText}</div>
                    </div>
                `;
                
                // Add to chat
                chatMessages.appendChild(messageElement);
                
                // Scroll to bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Store message
                gameState.chatMessages.push({
                    userId,
                    username,
                    avatar,
                    message,
                    isOutgoing,
                    time: now
                });
            };
            
            // Add system message to chat
            const addSystemChatMessage = (message) => {
                const chatMessages = document.getElementById('chatMessages');
                
                // Create message element
                const messageElement = document.createElement('div');
                messageElement.className = 'chat-message';
                
                // Format time
                const now = new Date();
                const timeText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                
                messageElement.innerHTML = `
                    <div class="chat-avatar">
                        <img src="../assets/default.png" alt="System">
                    </div>
                    <div class="chat-bubble" style="background: #e9ecef;">
                        <p class="chat-message-text">${message}</p>
                        <div class="chat-message-time">System • ${timeText}</div>
                    </div>
                `;
                
                // Add to chat
                chatMessages.appendChild(messageElement);
                
                // Scroll to bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
            };
            
            // Escape HTML to prevent XSS
            const escapeHtml = (text) => {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            };
            
            // Confetti effect for winning
            const confettiColors = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff'];
            const confettiCount = 150;
            let confettiAnimationId = null;
            
            // Start confetti animation
            const startConfetti = () => {
                confettiCanvas.width = window.innerWidth;
                confettiCanvas.height = window.innerHeight;
                confettiCanvas.style.display = 'block';
                
                const ctx = confettiCanvas.getContext('2d');
                const confetti = [];
                
                // Create confetti particles
                for (let i = 0; i < confettiCount; i++) {
                    confetti.push({
                        x: Math.random() * confettiCanvas.width,
                        y: Math.random() * -confettiCanvas.height,
                        size: Math.random() * 10 + 5,
                        color: confettiColors[Math.floor(Math.random() * confettiColors.length)],
                        speed: Math.random() * 3 + 2,
                        angle: Math.random() * Math.PI * 2,
                        rotation: Math.random() * 0.2 - 0.1,
                        rotationSpeed: Math.random() * 0.01
                    });
                }
                
                // Animate confetti
                const animate = () => {
                    ctx.clearRect(0, 0, confettiCanvas.width, confettiCanvas.height);
                    
                    let stillFalling = false;
                    
                    confetti.forEach(particle => {
                        particle.y += particle.speed;
                        particle.x += Math.sin(particle.angle) * 2;
                        particle.rotation += particle.rotationSpeed;
                        
                        if (particle.y < confettiCanvas.height) {
                            stillFalling = true;
                        }
                        
                        ctx.save();
                        ctx.translate(particle.x, particle.y);
                        ctx.rotate(particle.rotation);
                        ctx.fillStyle = particle.color;
                        ctx.fillRect(-particle.size / 2, -particle.size / 2, particle.size, particle.size);
                        ctx.restore();
                    });
                    
                    if (stillFalling) {
                        confettiAnimationId = requestAnimationFrame(animate);
                    } else {
                        stopConfetti();
                    }
                };
                
                animate();
                
                // Stop after 5 seconds to conserve resources
                setTimeout(stopConfetti, 5000);
            };
            
            // Stop confetti animation
            const stopConfetti = () => {
                if (confettiAnimationId) {
                    cancelAnimationFrame(confettiAnimationId);
                    confettiAnimationId = null;
                }
                confettiCanvas.style.display = 'none';
            };
            
            // Event Listeners
            
            // Cancel matchmaking button
            document.getElementById('cancelMatchmakingBtn').addEventListener('click', cancelMatchmaking);
            
            // Chat send button
            document.getElementById('chatSendBtn').addEventListener('click', sendChatMessage);
            
            // Chat input enter key
            document.getElementById('chatInput').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    sendChatMessage();
                }
            });
            
            // Leave battle button
            document.getElementById('leaveBtn').addEventListener('click', () => {
                if (confirm('Are you sure you want to leave the battle? You will forfeit the match.')) {
                    // Make sure the socket is open before sending
                    if (socket && socket.readyState === WebSocket.OPEN) {
                        console.log('Sending quit_battle message to server');
                        sendToServer({
                            action: 'quit_battle'
                        });
                        
                        // Add a small delay to ensure the message is sent before redirecting
                        setTimeout(() => {
                            window.location.href = 'dashboard.php';
                        }, 500);
                    } else {
                        console.error('Socket is not open, cannot send quit message');
                        // Just redirect if socket isn't working
                        window.location.href = 'dashboard.php';
                    }
                }
            });
            
            // Ready for next round button
            document.getElementById('readyBtn').addEventListener('click', () => {
                document.getElementById('readyBtn').disabled = true;
                document.getElementById('readyBtn').innerHTML = '<i class="fas fa-check-circle"></i> Ready!';
                
                sendToServer({
                    action: 'ready_for_next_round'
                });
                
                showToast('Ready', 'You are ready for the next round', 'success');
            });
            
            // Rematch button
            document.getElementById('rematchBtn').addEventListener('click', () => {
                window.location.reload();
            });
            
            // Dashboard button
            document.getElementById('dashboardBtn').addEventListener('click', () => {
                window.location.href = 'dashboard.php';
            });
            
            // Handle window resize for confetti
            window.addEventListener('resize', () => {
                if (confettiAnimationId) {
                    confettiCanvas.width = window.innerWidth;
                    confettiCanvas.height = window.innerHeight;
                }
            });
            
            // Clean up on page unload
            window.addEventListener('beforeunload', () => {
                // Send a message to notify server we're leaving
                if (socket && socket.readyState === WebSocket.OPEN) {
                    sendToServer({
                        action: 'leaving_page'
                    });
                }
                
                // Stop heartbeat
                stopHeartbeat();
                
                // Close socket
                if (socket) {
                    socket.close();
                }
            });

            // Create record of battle in Supabase database when battle starts
            const recordBattleStart = async (battleId, player1, player2, questionCount, difficulty, subject) => {
                console.log('Recording battle start with data:', {
                    battleId, 
                    player1_id: player1.userId,
                    player1_name: player1.username,
                    player2_id: player2.userId,
                    player2_name: player2.username,
                    questionCount,
                    difficulty,
                    subject
                });
                
                try {
                    // Validate required parameters
                    if (!battleId) {
                        console.error('Missing battleId parameter');
                        return;
                    }
                    
                    if (!player1 || !player1.userId) {
                        console.error('Missing or invalid player1 parameter');
                        return;
                    }
                    
                    if (!player2 || !player2.userId) {
                        console.error('Missing or invalid player2 parameter');
                        return;
                    }
                    
                    // Store the original battle ID (string format) for reference
                    gameState.originalBattleId = battleId;
                    
                    // IMPORTANT: For consistency, only Player 1 should create the battle record
                    // Player 2 should check if the record exists and use it
                    const isPlayer1 = (gameState.playerId === player1.userId);
                    console.log(`Current player is ${isPlayer1 ? 'Player 1' : 'Player 2'} - checking for existing battle record...`);
                    
                    // Try to find an existing battle record first
                    let existingRecord = null;
                    
                    try {
                        // First, try to find by battle ID prefix
                        const formattedBattlePrefix = battleId.replace('battle_', '');
                        
                        const { data: existingRecords, error: searchError } = await supabaseClient
                            .from('battle_records')
                            .select('*')
                            .eq('player1_id', player1.userId)
                            .eq('player2_id', player2.userId)
                            .order('created_at', { ascending: false })
                            .limit(5);
                            
                        if (searchError) {
                            console.error('Error searching for existing battle records:', searchError);
                        } else if (existingRecords && existingRecords.length > 0) {
                            // Look for records that match our battle ID prefix
                            for (const record of existingRecords) {
                                // If created in the last 30 seconds, consider it a match
                                const recordAge = new Date() - new Date(record.created_at);
                                if (recordAge < 30000) { // 30 seconds
                                    console.log('Found recent matching battle record:', record);
                                    existingRecord = record;
                                    break;
                                }
                            }
                        }
                    } catch (searchErr) {
                        console.error('Exception searching for existing battle records:', searchErr);
                    }
                    
                    // If record exists, use it (for both players)
                    if (existingRecord) {
                        console.log('Using existing battle record:', existingRecord);
                        gameState.battleRecordId = existingRecord.id;
                        gameState.battleId = existingRecord.battle_id;
                        return;
                    }
                    
                    // For Player 2 - if no record exists yet, wait a brief moment and check again
                    // This is to prevent both players from creating separate records
                    if (!isPlayer1) {
                        console.log('Player 2 waiting for Player 1 to create battle record...');
                        
                        // Try up to 3 times with a delay between attempts
                        for (let attempt = 0; attempt < 3; attempt++) {
                            // Wait 1 second
                            await new Promise(resolve => setTimeout(resolve, 1000));
                            
                            try {
                                const { data: retryRecords, error: retryError } = await supabaseClient
                                    .from('battle_records')
                                    .select('*')
                                    .eq('player1_id', player1.userId)
                                    .eq('player2_id', player2.userId)
                                    .order('created_at', { ascending: false })
                                    .limit(3);
                                    
                                if (retryError) {
                                    console.error(`Retry ${attempt+1}: Error checking for battle record:`, retryError);
                                } else if (retryRecords && retryRecords.length > 0) {
                                    // Look for records created in the last 30 seconds
                                    for (const record of retryRecords) {
                                        const recordAge = new Date() - new Date(record.created_at);
                                        if (recordAge < 30000) { // 30 seconds
                                            console.log(`Retry ${attempt+1}: Found battle record:`, record);
                                            gameState.battleRecordId = record.id;
                                            gameState.battleId = record.battle_id;
                                            return;
                                        }
                                    }
                                }
                                
                                console.log(`Retry ${attempt+1}: No matching battle record found yet, waiting...`);
                            } catch (retryErr) {
                                console.error(`Retry ${attempt+1}: Exception checking for battle record:`, retryErr);
                            }
                        }
                        
                        console.log('Player 2 gave up waiting, will create own battle record');
                    }
                    
                    // Get player points before battle
                    let player1Points = 0;
                    let player2Points = 0;
                    
                    // Get player1 points from profiles
                    try {
                        const { data: player1Profile, error: player1Error } = await supabaseClient
                            .from('profiles')
                            .select('points')
                            .eq('user_id', player1.userId)
                            .single();
                        
                        if (player1Error) {
                            console.warn('Error fetching player1 profile:', player1Error);
                        } else if (player1Profile) {
                            player1Points = player1Profile.points || 0;
                            gameState.player1InitialPoints = player1Points;
                            console.log('Player 1 initial points:', player1Points);
                        }
                    } catch (err) {
                        console.error('Exception fetching player1 profile:', err);
                    }
                    
                    // Get player2 points from profiles
                    try {
                        const { data: player2Profile, error: player2Error } = await supabaseClient
                            .from('profiles')
                            .select('points')
                            .eq('user_id', player2.userId)
                            .single();
                        
                        if (player2Error) {
                            console.warn('Error fetching player2 profile:', player2Error);
                        } else if (player2Profile) {
                            player2Points = player2Profile.points || 0;
                            gameState.player2InitialPoints = player2Points;
                            console.log('Player 2 initial points:', player2Points);
                        }
                    } catch (err) {
                        console.error('Exception fetching player2 profile:', err);
                    }
                    
                    // Determine database battle ID: use existing UUID if valid, or generate new one
                    let databaseBattleId = gameState.battleId;
                    const uuidPattern = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
                    
                    if (!databaseBattleId || !uuidPattern.test(databaseBattleId)) {
                        databaseBattleId = generateUUID();
                        gameState.battleId = databaseBattleId;
                        console.log('Generated new UUID for battle:', databaseBattleId);
                    } else {
                        console.log('Using existing valid UUID:', databaseBattleId);
                    }
                    
                    // Prepare the battle record data
                    const battleRecord = {
                        battle_id: databaseBattleId, // Use the UUID format
                        player1_id: player1.userId,
                        player1_name: player1.username || 'Player 1',
                        player1_initial_points: player1Points,
                        player2_id: player2.userId,
                        player2_name: player2.username || 'Player 2',
                        player2_initial_points: player2Points,
                        questions_count: questionCount || 5,
                        difficulty: difficulty || 'medium',
                        subject: subject || 'general',
                        start_time: new Date().toISOString(),
                        battle_type: 'Success', // Valid value from battle_records_battle_type_check constraint
                        battle_mode: 'quick'    // Add the battle mode field to indicate this is a quick battle
                    };
                    
                    // Log the exact battle_type value for debugging
                    console.log(`DEBUG - battle_type value: "${battleRecord.battle_type}"`, 
                        { 
                            type: typeof battleRecord.battle_type,
                            caseMatters: 'quick' !== 'Quick', 
                            validValues: ['Success', 'Cancelled', 'Player1Left', 'Player2Left', 'Timeout', 'quick', 'arena']
                        }
                    );
                    
                    // Special error checking for battle_type before inserting
                    if (!['Success', 'Cancelled', 'Player1Left', 'Player2Left', 'Timeout', 'quick', 'arena'].includes(battleRecord.battle_type)) {
                        console.warn(`Invalid battle_type: "${battleRecord.battle_type}". Changing to "Success" to match constraint`);
                        battleRecord.battle_type = 'Success'; // Fallback to a known valid value
                    }
                    
                    console.log('Inserting battle record with UUID:', battleRecord);
                    
                    // Insert battle record
                    const { data, error } = await supabaseClient
                        .from('battle_records')
                        .insert(battleRecord)
                        .select()
                        .single();
                        
                    if (error) {
                        console.error('Error recording battle start:', error);
                        
                        // Log more diagnostic info
                        console.error('Failed record data:', battleRecord);
                        
                        // Special handling for different error types
                        if (error.code === '23514' && error.message.includes('battle_records_battle_type_check')) {
                            // Battle type constraint violation - try different valid values
                            console.error('Battle type constraint violation. Trying alternative values...');
                            
                            // Try each allowed value in the constraint
                            const validBattleTypes = ['Success', 'Cancelled', 'Player1Left', 'Player2Left', 'Timeout', 'quick', 'arena'];
                            
                            for (const validType of validBattleTypes) {
                                if (validType === battleRecord.battle_type) continue; // Skip the one we already tried
                                
                                console.log(`Attempting with battle_type = "${validType}"`);
                                
                                const retryRecord = {...battleRecord, battle_type: validType};
                                
                                try {
                                    const { data: retryData, error: retryError } = await supabaseClient
                                        .from('battle_records')
                                        .insert(retryRecord)
                                        .select()
                                        .single();
                                        
                                    if (retryError) {
                                        console.error(`Still failed with battle_type = "${validType}":`, retryError);
                                    } else if (retryData) {
                                        console.log(`Success with battle_type = "${validType}"!`, retryData);
                                        gameState.battleRecordId = retryData.id;
                                        gameState.battleId = retryData.battle_id;
                                        
                                        // We found a working value, no need to try others
                                        return;
                                    }
                                } catch (retryEx) {
                                    console.error(`Exception trying battle_type = "${validType}":`, retryEx);
                                }
                            }
                            
                            console.error('All battle_type values failed. Constraint issue may be more complex.');
                        }
                        else if (error.code === '23503' || error.message.includes('foreign key')) {
                            // Foreign key violation - check if player profiles exist
                            console.error('Foreign key violation. Verify that player IDs exist in the profiles table.');
                            
                            try {
                                const { data: player1Exists } = await supabaseClient
                                    .from('profiles')
                                    .select('user_id')
                                    .eq('user_id', player1.userId)
                                    .maybeSingle();
                                
                                const { data: player2Exists } = await supabaseClient
                                    .from('profiles')
                                    .select('user_id')
                                    .eq('user_id', player2.userId)
                                    .maybeSingle();
                                
                                console.log('Player profiles exist check:', {
                                    player1: !!player1Exists, 
                                    player2: !!player2Exists
                                });
                            } catch (e) {
                                console.error('Error checking player profiles:', e);
                            }
                        } else if (error.code === '22P02' && error.message.includes('invalid input syntax for type uuid')) {
                            // UUID format error - try alternative approach
                            console.error('UUID format error. Trying alternative approach...');
                            
                            try {
                                // Try inserting without specifying the battle_id so Supabase will generate one
                                const alternativeBattleRecord = {...battleRecord};
                                delete alternativeBattleRecord.battle_id; // Remove the battle_id to let Supabase generate one
                                
                                const { data: altData, error: altError } = await supabaseClient
                                    .from('battle_records')
                                    .insert(alternativeBattleRecord)
                                    .select()
                                    .single();
                                    
                                if (altError) {
                                    console.error('Alternative insert also failed:', altError);
                                    showToast('Database Error', 'Could not create battle record after multiple attempts', 'error');
                                } else {
                                    console.log('Alternative battle record created successfully:', altData);
                                    gameState.battleRecordId = altData.id;
                                    gameState.battleId = altData.battle_id; // Store the UUID generated by Supabase
                                    console.log('Battle record created with UUID:', altData.battle_id);
                                }
                            } catch (altException) {
                                console.error('Exception during alternative insert:', altException);
                            }
                        }
                        
                        // Show error message to user if appropriate
                        showToast('Database Error', 'Failed to record battle start. Results may not be saved.', 'error');
                    } else if (data) {
                        console.log('Battle start recorded successfully:', data);
                        // Store the battle record ID in gameState for future updates
                        gameState.battleRecordId = data.id;
                        gameState.battleId = data.battle_id; // Store the UUID for future reference
                        
                        // Show success in UI if appropriate
                        console.log('Battle record created with ID:', data.id);
                    } else {
                        console.error('No data or error returned from battle record insert');
                    }
                } catch (error) {
                    console.error('Unhandled error in recordBattleStart:', error);
                    showToast('Error', 'An unexpected error occurred while recording battle data.', 'error');
                }
            };

            // Find the battle record by battle ID, handling both UUID and string formats
            const findBattleRecordByBattleId = async (battleId) => {
                // First try to find by gameState.battleId which should be the UUID version
                if (battleId) {
                    try {
                        const { data, error } = await supabaseClient
                            .from('battle_records')
                            .select('id, battle_id')
                            .eq('battle_id', battleId)
                            .maybeSingle();
                            
                        if (data) {
                            console.log('Found battle record by UUID battle_id:', data);
                            return data;
                        } else if (error) {
                            console.error('Error finding battle record by battle_id UUID:', error);
                        }
                    } catch (e) {
                        console.error('Exception finding battle record by battle_id UUID:', e);
                    }
                }
                
                // If original (string) battleId is stored, try to find records created recently
                // This is a fallback if we couldn't find by UUID
                if (gameState.originalBattleId) {
                    try {
                        // Since we can't query by the string battle_id (it's not in the table),
                        // get recent records and filter by player IDs
                        const { data, error } = await supabaseClient
                            .from('battle_records')
                            .select('id, battle_id')
                            .eq('player1_id', gameState.player1Id)
                            .eq('player2_id', gameState.player2Id)
                            .order('created_at', { ascending: false })
                            .limit(5); // Get the most recent 5 battles
                            
                        if (data && data.length > 0) {
                            console.log('Found recent battle records:', data);
                            return data[0]; // Return the most recent one
                        } else if (error) {
                            console.error('Error finding battle record by player IDs:', error);
                        }
                    } catch (e) {
                        console.error('Exception finding battle record by player IDs:', e);
                    }
                }
                
                return null;
            };

            // Update battle record in Supabase when battle ends
            const updateBattleEnd = async (result, battleType, player1Score, player2Score, player1CorrectAnswers, player2CorrectAnswers) => {
                console.log('Updating battle end with data:', {
                    result, 
                    battleType, 
                    player1Score, 
                    player2Score, 
                    player1CorrectAnswers, 
                    player2CorrectAnswers,
                    battleRecordId: gameState.battleRecordId,
                    battleId: gameState.battleId,
                    originalBattleId: gameState.originalBattleId
                });
                
                try {
                    if (!gameState.battleRecordId) {
                        console.error('No battle record ID found, attempting to find by battle_id');
                        
                        // Try to find the battle record by battle_id if we have it
                        if (gameState.battleId) {
                            const existingRecord = await findBattleRecordByBattleId(gameState.battleId);
                            
                            if (existingRecord) {
                                console.log('Found battle record:', existingRecord);
                                gameState.battleRecordId = existingRecord.id;
                            } else {
                                console.error('No battle record found for battle_id:', gameState.battleId);
                                
                                // Try to create a new battle record as a fallback
                                if (gameState.player1Id && gameState.player2Id) {
                                    console.log('Attempting to create missing battle record as fallback');
                                    
                                    try {
                                        // Generate a UUID for the new record
                                        const generateUUID = () => {
                                            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                                                const r = Math.random() * 16 | 0, 
                                                      v = c === 'x' ? r : (r & 0x3 | 0x8);
                                                return v.toString(16);
                                            });
                                        };
                                        
                                        // Create minimal battle record
                                        const { data: newRecord, error: createError } = await supabaseClient
                                            .from('battle_records')
                                            .insert({
                                                battle_id: generateUUID(), // Generate proper UUID
                                                player1_id: gameState.player1Id,
                                                player1_name: 'Player 1',
                                                player2_id: gameState.player2Id,
                                                player2_name: 'Player 2',
                                                start_time: new Date(Date.now() - 600000).toISOString() // Assume battle started 10 min ago
                                            })
                                            .select()
                                            .single();
                                            
                                        if (createError) {
                                            console.error('Failed to create fallback battle record:', createError);
                                            showToast('Database Error', 'Failed to create battle record', 'error');
                                            return;
                                        } else {
                                            console.log('Created fallback battle record:', newRecord);
                                            gameState.battleRecordId = newRecord.id;
                                            gameState.battleId = newRecord.battle_id;
                                            showToast('Info', 'Created fallback battle record', 'info');
                                        }
                                    } catch (createErr) {
                                        console.error('Exception creating fallback battle record:', createErr);
                                        return;
                                    }
                                } else {
                                    console.error('Missing player IDs - cannot create fallback record');
                                    showToast('Data Error', 'Cannot update battle - missing player data', 'error');
                                    return;
                                }
                            }
                        } else {
                            console.error('No battle_id available to look up record');
                            showToast('Data Error', 'Cannot update battle - missing battle ID', 'error');
                            return;
                        }
                    }
                    
                    // Calculate final points based on game result
                    let player1FinalPoints = gameState.player1InitialPoints || 0;
                    let player2FinalPoints = gameState.player2InitialPoints || 0;
                    
                    // Convert server result format to database format
                    let battleResult = 'Incomplete';
                    if (result === 'win') {
                        battleResult = gameState.playerId === gameState.player1Id ? 'Player1Wins' : 'Player2Wins';
                    } else if (result === 'lose') {
                        battleResult = gameState.playerId === gameState.player1Id ? 'Player2Wins' : 'Player1Wins';
                    } else if (result === 'tie') {
                        battleResult = 'Draw';
                    }
                    
                    // Convert battle type if needed
                    if (battleType === 'opponent_quit') {
                        battleType = gameState.playerId === gameState.player1Id ? 'Player2Left' : 'Player1Left';
                    } else if (battleType === 'opponent_timeout') {
                        battleType = 'Timeout';
                    } else if (battleType === 'cancelled') {
                        battleType = 'Cancelled';
                    } else {
                        battleType = 'Success';
                    }
                    
                    // Ensure the battle_type is one of the valid values for the constraint
                    const validBattleTypes = ['Success', 'Cancelled', 'Player1Left', 'Player2Left', 'Timeout', 'quick', 'arena'];
                    if (!validBattleTypes.includes(battleType)) {
                        console.warn(`Invalid battle_type: "${battleType}". Defaulting to "Success".`);
                        battleType = 'Success';
                    }
                    
                    // Points calculation logic (adjust as needed)
                    const pointsForWin = 25;
                    const pointsForDraw = 10;
                    const pointsPerCorrectAnswer = 5;
                    
                    if (battleResult === 'Player1Wins') {
                        player1FinalPoints = (gameState.player1InitialPoints || 0) + pointsForWin + (player1CorrectAnswers * pointsPerCorrectAnswer);
                        player2FinalPoints = Math.max(0, (gameState.player2InitialPoints || 0) - 10);
                    } else if (battleResult === 'Player2Wins') {
                        player2FinalPoints = (gameState.player2InitialPoints || 0) + pointsForWin + (player2CorrectAnswers * pointsPerCorrectAnswer);
                        player1FinalPoints = Math.max(0, (gameState.player1InitialPoints || 0) - 10);
                    } else if (battleResult === 'Draw') {
                        player1FinalPoints = (gameState.player1InitialPoints || 0) + pointsForDraw + (player1CorrectAnswers * pointsPerCorrectAnswer);
                        player2FinalPoints = (gameState.player2InitialPoints || 0) + pointsForDraw + (player2CorrectAnswers * pointsPerCorrectAnswer);
                    }
                    
                    // Prepare update data
                    const updateData = {
                        end_time: new Date().toISOString(),
                        battle_result: battleResult,
                        battle_type: battleType, // Use the validated battle type
                        battle_mode: 'quick',    // Explicitly set the battle mode for the update
                        player1_final_points: player1FinalPoints,
                        player2_final_points: player2FinalPoints,
                        player1_correct_answers: player1CorrectAnswers,
                        player2_correct_answers: player2CorrectAnswers,
                        duration_seconds: Math.floor((new Date() - gameState.startTime) / 1000) || 300 // Default to 5 min if startTime missing
                    };
                    
                    console.log('Updating battle record with:', updateData);
                    
                    // Update battle record
                    const { data, error } = await supabaseClient
                        .from('battle_records')
                        .update(updateData)
                        .eq('id', gameState.battleRecordId)
                        .select();
                        
                    if (error) {
                        console.error('Error updating battle record:', error);
                        
                        // Handle battle_type constraint violation
                        if (error.code === '23514' && error.message.includes('battle_records_battle_type_check')) {
                            console.error('Battle type constraint violation in update. Trying alternative values...');
                            
                            // Try each valid battle type until one works
                            for (const validType of validBattleTypes) {
                                if (validType === updateData.battle_type) continue; // Skip the one we already tried
                                
                                console.log(`Attempting update with battle_type = "${validType}"`);
                                
                                const retryUpdateData = {...updateData, battle_type: validType};
                                
                                try {
                                    const { data: retryData, error: retryError } = await supabaseClient
                                        .from('battle_records')
                                        .update(retryUpdateData)
                                        .eq('id', gameState.battleRecordId)
                                        .select();
                                        
                                    if (retryError) {
                                        console.error(`Still failed with battle_type = "${validType}":`, retryError);
                                    } else {
                                        console.log(`Success with battle_type = "${validType}"!`, retryData);
                                        
                                        // Update player profiles with new points since this update succeeded
                                        try {
                                            if (gameState.playerId === gameState.player1Id) {
                                                await updatePlayerPoints(gameState.playerId, player1FinalPoints);
                                            } else if (gameState.playerId === gameState.player2Id) {
                                                await updatePlayerPoints(gameState.playerId, player2FinalPoints);
                                            }
                                        } catch (pointsError) {
                                            console.error('Error updating player points:', pointsError);
                                        }
                                        
                                        return; // Exit the function since we succeeded
                                    }
                                } catch (retryEx) {
                                    console.error(`Exception trying battle_type = "${validType}":`, retryEx);
                                }
                            }
                            
                            console.error('All battle_type values failed for update. Constraint issue may be more complex.');
                            showToast('Database Error', 'Failed to update battle record due to constraint issues', 'error');
                        } else {
                            showToast('Database Error', 'Failed to update battle record', 'error');
                        }
                    } else {
                        console.log('Battle record updated successfully:', data);
                        
                        // Update player profiles with new points
                        try {
                            if (gameState.playerId === gameState.player1Id) {
                                await updatePlayerPoints(gameState.playerId, player1FinalPoints);
                            } else if (gameState.playerId === gameState.player2Id) {
                                await updatePlayerPoints(gameState.playerId, player2FinalPoints);
                            }
                        } catch (pointsError) {
                            console.error('Error updating player points:', pointsError);
                        }
                    }
                } catch (error) {
                    console.error('Unhandled error in updateBattleEnd:', error);
                    showToast('Error', 'An unexpected error occurred while updating battle record', 'error');
                }
            };

            // Update player points in profile
            const updatePlayerPoints = async (playerId, newPoints) => {
                try {
                    const { error } = await supabaseClient
                        .from('profiles')
                        .update({ points: newPoints })
                        .eq('user_id', playerId);
                        
                    if (error) {
                        console.error('Error updating player points:', error);
                    } else {
                        console.log('Player points updated to:', newPoints);
                    }
                } catch (error) {
                    console.error('Error in updatePlayerPoints:', error);
                }
            };

            // Start question timer
            const startQuestionTimer = () => {
                // Reset timer
                clearInterval(questionTimer);
                questionTimeRemaining = questionTimeLimit;
                
                // Check if timer elements exist
                const timerFill = document.getElementById('questionTimerFill');
                const timerText = document.getElementById('questionTimer');
                
                if (!timerFill || !timerText) {
                    console.warn("Timer elements not found, cannot start question timer");
                    return; // Exit if elements don't exist
                }
                
                // Update timer display
                updateQuestionTimer();
                
                // Start timer countdown
                questionTimer = setInterval(() => {
                    questionTimeRemaining--;
                    
                    // Check if timer elements still exist before updating
                    if (!document.getElementById('questionTimerFill') || !document.getElementById('questionTimer')) {
                        console.warn("Timer elements no longer exist, stopping timer");
                        clearInterval(questionTimer);
                        return;
                    }
                    
                    updateQuestionTimer();
                    
                    // Auto-submit if time runs out
                    if (questionTimeRemaining <= 0) {
                        clearInterval(questionTimer);
                        // If no answer was selected, auto-select (could also just time out)
                        if (!gameState.waitingForOpponent) {
                            const answers = document.querySelectorAll('.answer-option');
                            if (!Array.from(answers).some(el => el.classList.contains('selected'))) {
                                // Choose random answer if none selected
                                const randomAnswer = String.fromCharCode(97 + Math.floor(Math.random() * 4)); // a, b, c, or d
                                selectAnswer(randomAnswer);
                            }
                        }
                    }
                }, 1000);
            };

            // Add event listeners for nav buttons
            document.addEventListener('DOMContentLoaded', function() {
                // Add event listener for question navigation buttons
                const questionNavBtns = document.querySelectorAll('.nav-btn');
                questionNavBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        if (this.classList.contains('right')) {
                            // This would be the next question button
                            if (!document.getElementById('nextQuestionBtn').disabled) {
                                document.getElementById('nextQuestionBtn').click();
                            }
                        } else {
                            // This would be the previous question button - usually disabled in a battle
                            showToast('Navigation', 'Cannot go back to previous questions in battle mode', 'info');
                        }
                    });
                });
            });

            // Update progress bars
            const updateProgressBars = (progress) => {
                // If progress is not provided, calculate it based on current question
                if (!progress) {
                    const totalQuestions = parseInt(document.getElementById('totalQuestions')?.textContent || 5);
                    progress = {
                        player: (gameState.currentQuestion / totalQuestions) * 100,
                        opponent: (gameState.currentQuestion / totalQuestions) * 100
                    };
                }
                
                // Update player progress
                const playerProgress = Math.min(Math.round(progress.player || 0), 100);
                document.getElementById('playerProgress').style.width = playerProgress + '%';
                document.getElementById('playerProgressText').textContent = playerProgress + '%';
                
                // Update opponent progress
                const opponentProgress = Math.min(Math.round(progress.opponent || 0), 100);
                document.getElementById('opponentProgress').style.width = opponentProgress + '%';
                document.getElementById('opponentProgressText').textContent = opponentProgress + '%';
            };

            // Extract and format battle ID from URL parameters
            const extractBattleIdFromURL = () => {
                const params = new URLSearchParams(window.location.search);
                const matchId = params.get('matchId');
                
                if (matchId) {
                    console.log('Extracted matchId from URL:', matchId);
                    
                    // Store the original string version
                    gameState.originalBattleId = matchId;
                    
                    // Check if it looks like a UUID already (xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx)
                    const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
                    if (uuidRegex.test(matchId)) {
                        console.log('URL matchId is already in UUID format');
                        return matchId; // It's already a UUID
                    }
                    
                    // If it's a string like "battle_67f962a9bbc73", extract the hex part
                    const hexPart = matchId.replace(/^battle_/, '');
                    
                    // Check if we have enough hex digits to create a UUID
                    if (hexPart && hexPart.length >= 12 && /^[0-9a-f]+$/i.test(hexPart)) {
                        try {
                            // Create a UUID format from whatever hex digits we have
                            // If we don't have enough, we'll pad with random digits
                            const paddedHex = hexPart.padEnd(32, '0123456789abcdef'[Math.floor(Math.random() * 16)]);
                            
                            // Format as UUID: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
                            // where y is 8, 9, a, or b
                            const formattedUUID = 
                                paddedHex.substring(0, 8) + '-' + 
                                paddedHex.substring(8, 12) + '-' + 
                                '4' + paddedHex.substring(12, 15) + '-' + 
                                '8' + paddedHex.substring(15, 18) + '-' + 
                                paddedHex.substring(18, 30);
                                
                            console.log('Formatted UUID from URL matchId:', formattedUUID);
                            return formattedUUID;
                        } catch (e) {
                            console.error('Error formatting UUID from match ID:', e);
                        }
                    }
                    
                    // If we can't extract a UUID, generate a new one
                    console.log('Could not extract valid UUID from URL matchId, generating a new one');
                    return generateUUID();
                }
                
                return null;
            };

            // Generate a proper UUID
            const generateUUID = () => {
                return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                    const r = Math.random() * 16 | 0, 
                          v = c === 'x' ? r : (r & 0x3 | 0x8);
                    return v.toString(16);
                });
            };

            // Call this function early to set up the battle ID
            const urlBattleId = extractBattleIdFromURL();
            if (urlBattleId) {
                gameState.battleId = urlBattleId;
            }

            // Update player score
            const updatePlayerScore = () => {
                if (!gameState.playerAnswers || !gameState.playerAnswers.length) return;
                
                let score = 0;
                
                // Calculate score based on correct answers
                // For Quick Battle: +4 for correct answers, -1 for wrong answers
                gameState.playerAnswers.forEach(answer => {
                    if (answer.isCorrect) {
                        score += 4; // +4 points for correct answers
                    } else if (answer.answer) {
                        score -= 1; // -1 point for wrong answers (but answered)
                    }
                    // No points for unanswered questions
                });
                
                gameState.playerScore = score;
                
                // Update score display
                document.getElementById('playerScore').textContent = score;
            };
            
            // Update opponent score
            const updateOpponentScore = () => {
                if (!gameState.opponentAnswers || !gameState.opponentAnswers.length) return;
                
                let score = 0;
                
                // Calculate score based on correct answers
                // For Quick Battle: +4 for correct answers, -1 for wrong answers
                gameState.opponentAnswers.forEach(answer => {
                    if (answer.isCorrect) {
                        score += 4; // +4 points for correct answers
                    } else if (answer.answer) {
                        score -= 1; // -1 point for wrong answers (but answered)
                    }
                    // No points for unanswered questions
                });
                
                gameState.opponentScore = score;
                
                // Update score display
                document.getElementById('opponentScore').textContent = score;
            };

            // Show the result modal
            const showResultModal = (result) => {
                // Update result modal
                const resultIcon = document.getElementById('resultIcon');
                const resultTitle = document.getElementById('resultTitle');
                const resultMessage = document.getElementById('resultMessage');
                
                // Update scores in result modal
                document.getElementById('resultPlayerScore').textContent = gameState.playerScore;
                document.getElementById('resultOpponentScore').textContent = gameState.opponentScore;
                document.getElementById('correctAnswers').textContent = gameState.playerAnswers.filter(a => a.isCorrect).length;
                document.getElementById('pointsEarned').textContent = gameState.playerScore;
                
                // Calculate elapsed time
                const elapsed = Math.floor((new Date() - gameState.startTime) / 1000);
                const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
                const seconds = (elapsed % 60).toString().padStart(2, '0');
                document.getElementById('timeUsed').textContent = `${minutes}:${seconds}`;
                
                if (result === 'tie') {
                    resultIcon.className = 'result-icon tie';
                    resultIcon.innerHTML = '<i class="fas fa-handshake"></i>';
                    resultTitle.textContent = "It's a Tie!";
                    resultMessage.textContent = "Great effort! You and your opponent finished with the same score.";
                    
                    showToast('Battle Ended', "It's a tie! Both players had the same score.", 'info');
                } else if (result === 'win') {
                    resultIcon.className = 'result-icon win';
                    resultIcon.innerHTML = '<i class="fas fa-trophy"></i>';
                    resultTitle.textContent = "You Won!";
                    resultMessage.textContent = "Congratulations! You've won the battle.";
                    
                    // Show confetti animation for win
                    startConfetti();
                    
                    showToast('Victory!', 'Congratulations! You won the battle!', 'success');
                } else {
                    resultIcon.className = 'result-icon lose';
                    resultIcon.innerHTML = '<i class="fas fa-thumbs-down"></i>';
                    resultTitle.textContent = "You Lost";
                    resultMessage.textContent = "Better luck next time! Keep practicing to improve.";
                    
                    showToast('Defeat', 'You lost the battle. Better luck next time!', 'error');
                }
                
                // Show modal
                battleResultModal.classList.add('active');
                
                // Add chat message
                addSystemChatMessage(`Battle ended! ${result === 'win' ? 'You won!' : result === 'tie' ? "It's a tie!" : 'You lost!'}`);
            };

            // Handle answer submission for a question
            const submitAnswer = () => {
                if (gameState.waitingForOpponent) {
                    showToast('Warning', 'Already submitted. Waiting for opponent...', 'warning');
                    return;
                }
                
                // Get selected answer
                const selectedAnswer = document.querySelector('.answer-option.selected');
                if (!selectedAnswer) {
                    showToast('Warning', 'Please select an answer', 'warning');
                    return;
                }
                
                // Clear question timer
                clearInterval(questionTimer);
                
                // Disable answer selection
                document.querySelectorAll('.answer-option').forEach(element => {
                    element.classList.add('disabled');
                });
                
                // Get current question and selected answer
                const questionIndex = gameState.currentQuestion - 1;
                const currentQuestion = gameState.questions[questionIndex];
                const selectedOption = selectedAnswer.dataset.option;
                const isCorrect = selectedOption === currentQuestion.correct_answer;
                
                // Calculate response time
                const responseTime = Math.floor((new Date() - gameState.questionStartTime) / 1000);
                
                // Record answer
                gameState.playerAnswers[questionIndex] = {
                    questionId: currentQuestion.id,
                    answer: selectedOption,
                    isCorrect: isCorrect,
                    responseTime: responseTime
                };
                
                // Check if answer is correct
                if (isCorrect) {
                    // Mark as correct
                    selectedAnswer.classList.add('correct');
                    showToast('Correct!', '+4 points', 'success');
                } else {
                    // Mark as incorrect
                    selectedAnswer.classList.add('incorrect');
                    
                    // Find and mark the correct answer
                    document.querySelector(`.answer-option[data-option="${currentQuestion.correct_answer}"]`)
                        .classList.add('correct');
                        
                    showToast('Incorrect', '-1 point', 'error');
                }
                
                // Update score based on Quick Battle rules
                updatePlayerScore();
                
                // Send answer to server
                sendToServer({
                    action: 'submit_answer',
                    questionIndex: questionIndex,
                    answer: selectedOption,
                    isCorrect: isCorrect,
                    responseTime: responseTime
                });
                
                // Show waiting for opponent
                document.getElementById('waitingForOpponent').style.display = 'flex';
                gameState.waitingForOpponent = true;
                
                // Next question button (for opponent timeout or both answered)
                document.getElementById('nextBtn').style.display = 'none';
                
                // Check if this is the last question
                if (gameState.currentQuestion >= gameState.questions.length) {
                    // Wait for opponent or timeout
                    setTimeout(() => {
                        if (gameState.waitingForOpponent) {
                            // If still waiting, auto-proceed to results
                            endBattle('end_battle', 'timeout');
                        }
                    }, 10000); // 10 second timeout for last question
                }
            };

            // Fetch battle questions from database
            const fetchBattleQuestions = async (battleId) => {
                if (!battleId) {
                    console.error('No battle ID provided for fetching questions');
                    return null;
                }
                
                console.log('Fetching questions for battle original ID:', battleId);
                
                try {
                    // Wait briefly to ensure recordBattleStart has completed
                    if (!gameState.battleId) {
                        console.log('Waiting for gameState.battleId to be set...');
                        await new Promise(resolve => setTimeout(resolve, 500));
                    }
                    
                    // Use the UUID from gameState that was set during recordBattleStart
                    const databaseBattleId = gameState.battleId;
                    
                    if (!databaseBattleId) {
                        console.error('No valid battle ID found in gameState');
                        showToast('Error', 'Battle ID not found', 'error');
                        return null;
                    }
                    
                    console.log('Using database battle ID:', databaseBattleId);
                    
                    // Call the Supabase function to fetch questions
                    const { data, error } = await supabaseClient.rpc('fetch_quick_battle_questions', {
                        p_battle_id: databaseBattleId
                    });
                    
                    if (error) {
                        console.error('Error fetching questions:', error);
                        showToast('Error', 'Failed to load questions. Please try again.', 'error');
                        return null;
                    }
                    
                    if (!data || data.length === 0) {
                        console.error('No questions returned from database');
                        showToast('Error', 'No questions available for this battle.', 'error');
                        return null;
                    }
                    
                    console.log('Successfully fetched', data.length, 'questions for battle');
                    
                    // Store questions in gameState for later use
                    gameState.questions = data.sort((a, b) => a.question_number - b.question_number);
                    
                    return gameState.questions;
                } catch (err) {
                    console.error('Exception in fetchBattleQuestions:', err);
                    showToast('Error', 'An unexpected error occurred while loading questions.', 'error');
                    return null;
                }
            };
            
            // Display the question with the given index (0-based)
            const displayQuestion = (questionIndex) => {
                if (!gameState.questions || !gameState.questions[questionIndex]) {
                    console.error('No question available at index', questionIndex);
                    return false;
                }
                
                const question = gameState.questions[questionIndex];
                console.log('Displaying question', questionIndex + 1, ':', question);
                
                // Show loading state immediately
                showLoadingState();
                
                // Update question number display
                const currentQuestionEl = document.getElementById('currentQuestion');
                if (currentQuestionEl) {
                    currentQuestionEl.textContent = questionIndex + 1;
                }
                
                const totalQuestionsEl = document.getElementById('totalQuestions');
                if (totalQuestionsEl) {
                    totalQuestionsEl.textContent = gameState.questions.length;
                }
                
                // Function to show loading state
                function showLoadingState() {
                    // Clear any existing sample question text
                    const questionText = document.getElementById('questionText');
                    if (questionText) {
                        // Replace text with a loader
                        questionText.innerHTML = '<div class="question-loader"><i class="fas fa-spinner fa-spin"></i> Loading question...</div>';
                        questionText.classList.add('loading-question');
                    }
                    
                    // Update image container with loader
                    const imageContainer = document.querySelector('.question-image-container');
                    if (imageContainer) {
                        imageContainer.innerHTML = '<div class="question-loader"><i class="fas fa-spinner fa-spin"></i> Loading image...</div>';
                    } else {
                        // If no image container exists, create one
                        const newImageContainer = document.createElement('div');
                        newImageContainer.className = 'question-image-container';
                        newImageContainer.innerHTML = '<div class="question-loader"><i class="fas fa-spinner fa-spin"></i> Loading image...</div>';
                        
                        // Insert after question text
                        if (questionText && questionText.parentNode) {
                            questionText.parentNode.insertBefore(newImageContainer, questionText.nextSibling);
                        }
                    }
                    
                    // Also clear the answers container and show a loader there
                    const answersContainer = document.getElementById('answersContainer');
                    if (answersContainer) {
                        answersContainer.innerHTML = '<div class="question-loader"><i class="fas fa-spinner fa-spin"></i> Loading answer options...</div>';
                    }
                }
                
                // Add actual image with a slight delay to ensure loading state is visible
                setTimeout(() => {
                    if (question.image_url) {
                        // Create image with loading and error handling
                        const img = document.createElement('img');
                        img.src = question.image_url;
                        img.alt = `Question ${questionIndex + 1}`;
                        img.className = 'question-image loading';
                        
                        // When image loads successfully, remove loader and placeholder text
                        img.onload = () => {
                            img.classList.remove('loading');
                            // Clear the placeholder text when image is loaded
                            const questionText = document.getElementById('questionText');
                            if (questionText) {
                                questionText.innerHTML = '';
                                questionText.classList.remove('loading-question');
                            }
                        };
                        
                        // Handle image loading errors
                        img.onerror = () => {
                            img.src = 'https://placehold.co/600x400?text=Image+Not+Available';
                            img.classList.remove('loading');
                            // Show an error message in the question text area
                            const questionText = document.getElementById('questionText');
                            if (questionText) {
                                questionText.innerHTML = '<div class="error-message">Image could not be loaded. Please view the placeholder image below.</div>';
                                questionText.classList.remove('loading-question');
                            }
                        };
                        
                        // Replace loader with actual image
                        const imageContainer = document.querySelector('.question-image-container');
                        if (imageContainer) {
                            imageContainer.innerHTML = '';
                            imageContainer.appendChild(img);
                        }
                    } else {
                        // No image available
                        const imageContainer = document.querySelector('.question-image-container');
                        if (imageContainer) {
                            imageContainer.innerHTML = '<div class="no-image-message">No image available for this question.</div>';
                        }
                        
                        // Update the question text
                        const questionText = document.getElementById('questionText');
                        if (questionText) {
                            questionText.innerHTML = '<div class="error-message">No image available for this question.</div>';
                            questionText.classList.remove('loading-question');
                        }
                    }
                    
                    // Prepare answer options (A, B, C, D)
                    const options = ['Option A', 'Option B', 'Option C', 'Option D'];
                    
                    // Clear previous answers
                    const answersContainer = document.getElementById('answersContainer');
                    if (answersContainer) {
                        answersContainer.innerHTML = '';
                        
                        // Create options
                        for (let i = 0; i < options.length; i++) {
                            const option = options[i];
                            const optionId = String.fromCharCode(97 + i); // a, b, c, d
                            const optionNumber = i + 1; // 1, 2, 3, 4
                            
                            const optionElement = document.createElement('div');
                            optionElement.className = 'answer-option';
                            optionElement.setAttribute('data-answer', optionId);
                            optionElement.setAttribute('data-question-id', question.question_id);
                            optionElement.innerHTML = `
                                <div class="answer-number">${optionNumber}</div>
                                <div class="answer-text-container">
                                    <div class="answer-text">${option}</div>
                                    <div class="answer-radio"></div>
                                </div>
                            `;
                            
                            optionElement.addEventListener('click', () => selectAnswer(optionId));
                            answersContainer.appendChild(optionElement);
                        }
                    }
                }, 300); // Small delay to ensure loading state is visible
                
                // Store current question in game state
                gameState.currentQuestion = questionIndex;
                gameState.currentQuestionId = question.question_id;
                gameState.correctAnswer = question.correct_answer.toLowerCase();
                
                // Start question timer
                startQuestionTimer();
                
                return true;
            };
            
            // Handle battle start message by loading questions and displaying the first one
            socket.addEventListener('message', async (event) => {
                // Parse the message
                const data = JSON.parse(event.data);
                
                // Handle battle start event
                if (data.type === 'battleStart') {
                    console.log('Battle starting, received battle ID:', data.battleId);
                    
                    // Store battle ID from the websocket message
                    // This is the original format, like "battle_67fe998dab1f1"
                    gameState.originalBattleId = data.battleId;
                    
                    // Add a longer delay to ensure recordBattleStart has completed and saved the proper UUID in gameState.battleId
                    setTimeout(async () => {
                        try {
                            console.log('Delayed fetch - Status check:',
                                { 
                                    originalBattleId: gameState.originalBattleId,
                                    databaseUUID: gameState.battleId,
                                    recordId: gameState.battleRecordId,
                                    player1: gameState.player1Id,
                                    player2: gameState.player2Id
                                }
                            );
                            
                            // Extra validation - if we somehow still don't have a valid battle ID
                            if (!gameState.battleId) {
                                console.error('No valid battle ID available in gameState after delay');
                                
                                // Try to find the battle record one last time
                                try {
                                    const { data: lastAttemptRecords, error: lastError } = await supabaseClient
                                        .from('battle_records')
                                        .select('*')
                                        .eq('player1_id', gameState.player1Id)
                                        .eq('player2_id', gameState.player2Id)
                                        .order('created_at', { ascending: false })
                                        .limit(1);
                                        
                                    if (!lastError && lastAttemptRecords && lastAttemptRecords.length > 0) {
                                        console.log('Last attempt found battle record:', lastAttemptRecords[0]);
                                        gameState.battleRecordId = lastAttemptRecords[0].id;
                                        gameState.battleId = lastAttemptRecords[0].battle_id;
                                    } else {
                                        console.error('Unable to find a valid battle record');
                                        showToast('Error', 'Battle record not found', 'error');
                                        return;
                                    }
                                } catch (finalErr) {
                                    console.error('Final error finding battle record:', finalErr);
                                    showToast('Error', 'Failed to find battle record', 'error');
                                    return;
                                }
                            }
                            
                            // Fetch questions for this battle using the proper battle ID
                            console.log('Attempting to fetch questions with battle ID:', gameState.battleId);
                            const questions = await fetchBattleQuestions(data.battleId);
                            
                            if (questions && questions.length > 0) {
                                // Log the questions for debugging
                                const questionSummary = questions.map(q => ({
                                    number: q.question_number,
                                    id: q.question_id,
                                    subject: q.subject,
                                    difficulty: q.difficulty
                                }));
                                console.log('Successfully loaded questions:', questionSummary);
                                
                                // Display the first question
                                displayQuestion(0);
                                
                                // Set battle as started
                                battleStarted = true;
                                gameState.startTime = new Date();
                                
                                // Start battle timer
                                updateBattleTimer();
                                if (!gameState.battleTimerInterval) {
                                    gameState.battleTimerInterval = setInterval(updateBattleTimer, 1000);
                                }
                            } else {
                                console.error('Failed to load questions for battle');
                                showToast('Error', 'Failed to load questions. Please try again.', 'error');
                            }
                        } catch (err) {
                            console.error('Error starting battle with questions:', err);
                        }
                    }, 3000); // Wait 3 seconds for database to be updated
                }
                
                // Handle next question
                if (data.type === 'next_question') {
                    // Get the next question index (0-based)
                    const nextIndex = (gameState.currentQuestion !== undefined) ? 
                        gameState.currentQuestion + 1 : 0;
                    
                    // Check if we have more questions
                    if (gameState.questions && nextIndex < gameState.questions.length) {
                        // Display the next question
                        displayQuestion(nextIndex);
                    } else {
                        console.error('No more questions available');
                    }
                }
            });
            
            // When moving to next question, use our custom display function
            const displayNextQuestion = (question, current, total) => {
                // If we have fetched questions from database, use them
                if (gameState.questions && gameState.questions.length > 0) {
                    // Convert from 1-based to 0-based index
                    const nextIndex = current - 1;
                    
                    if (nextIndex >= 0 && nextIndex < gameState.questions.length) {
                        displayQuestion(nextIndex);
                    } else {
                        console.error('Invalid question index:', nextIndex);
                    }
                } else {
                    // Fall back to original implementation if no questions from database
                    gameState.currentQuestion = current;
                    loadQuestion(question);
                }
                
                // Reset next button
                const nextBtn = document.getElementById('nextQuestionBtn');
                nextBtn.disabled = true;
                nextBtn.innerHTML = '<span>Next Question</span><i class="fas fa-arrow-right"></i>';
                
                // Add chat message
                addSystemChatMessage(`Moving to question ${current} of ${total}`);
            };

            // Global constants for the quick battle
            const QUESTION_TIME_LIMIT = 90; // 1 minute 30 seconds
            
            // Start a countdown timer for the current question
            const startQuestionCountdown = () => {
                // Clear any existing timer
                if (gameState.questionTimerInterval) {
                    clearInterval(gameState.questionTimerInterval);
                }
                
                // Initialize the time
                gameState.questionTimeRemaining = QUESTION_TIME_LIMIT;
                gameState.questionStartTime = new Date();
                
                // Update the timer display immediately
                updateQuestionTimerDisplay();
                
                // Enable answer options
                enableAnswerOptions();
                
                // Set interval to update timer every second
                gameState.questionTimerInterval = setInterval(() => {
                    // Decrement time remaining
                    gameState.questionTimeRemaining -= 1;
                    
                    // Update the display
                    updateQuestionTimerDisplay();
                    
                    // Check if time is up
                    if (gameState.questionTimeRemaining <= 0) {
                        // Time's up
                        handleTimeUp();
                    }
                }, 1000);
            };
            
            // Update the question timer display
            const updateQuestionTimerDisplay = () => {
                const timerElement = document.getElementById('questionTimer');
                if (!timerElement) {
                    console.error('Question timer element not found');
                    return;
                }
                
                const minutes = Math.floor(gameState.questionTimeRemaining / 60);
                const seconds = gameState.questionTimeRemaining % 60;
                
                // Format time as MM:SS
                timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                // Add visual indication as time runs low
                if (gameState.questionTimeRemaining <= 10) {
                    timerElement.classList.add('timer-critical');
                } else if (gameState.questionTimeRemaining <= 30) {
                    timerElement.classList.add('timer-warning');
                    timerElement.classList.remove('timer-critical');
                } else {
                    timerElement.classList.remove('timer-warning', 'timer-critical');
                }
            };
            
            // Handle when time is up for a question
            const handleTimeUp = () => {
                // Stop the timer
                clearInterval(gameState.questionTimerInterval);
                
                // Disable answer options
                disableAnswerOptions();
                
                // Record a timeout response
                recordResponse(null, true);
                
                // Show timeout message
                showToast('Time\'s up!', 'You ran out of time for this question', 'warning');
                
                // Update UI to show the correct answer
                highlightCorrectAnswer();
            };
            
            // Enable answer options for selection
            const enableAnswerOptions = () => {
                const options = document.querySelectorAll('.answer-option');
                options.forEach(option => {
                    option.classList.remove('disabled');
                });
                
                // Enable the skip button
                const skipButton = document.getElementById('skipQuestionBtn');
                if (skipButton) {
                    skipButton.disabled = false;
                }
            };
            
            // Disable answer options after selection or timeout
            const disableAnswerOptions = () => {
                const options = document.querySelectorAll('.answer-option');
                options.forEach(option => {
                    option.classList.add('disabled');
                });
                
                // Disable the skip button
                const skipButton = document.getElementById('skipQuestionBtn');
                if (skipButton) {
                    skipButton.disabled = true;
                }
            };
            
            // Handle answer selection
            const selectAnswer = (optionId) => {
                // Ignore if options are disabled
                if (document.querySelector('.answer-option.disabled')) {
                    return;
                }
                
                // Stop the timer
                clearInterval(gameState.questionTimerInterval);
                
                // Calculate response time in milliseconds
                const responseTime = new Date() - gameState.questionStartTime;
                
                // Highlight the selected option
                const options = document.querySelectorAll('.answer-option');
                options.forEach(option => {
                    const answer = option.getAttribute('data-answer');
                    if (answer === optionId) {
                        option.classList.add('selected');
                    } else {
                        option.classList.remove('selected');
                    }
                });
                
                // Disable further selections
                disableAnswerOptions();
                
                // Check if answer is correct
                const isCorrect = (optionId === gameState.correctAnswer);
                
                // Record the response
                recordResponse(optionId, false, responseTime, isCorrect);
                
                // Show feedback
                if (isCorrect) {
                    showToast('Correct!', '+4 points', 'success');
                } else {
                    showToast('Incorrect', '-1 point', 'error');
                }
                
                // Highlight correct answer
                highlightCorrectAnswer();
                
                // Enable next question button if it exists
                const nextBtn = document.getElementById('nextQuestionBtn');
                if (nextBtn) {
                    nextBtn.disabled = false;
                }
                
                // Send message to server that player has answered
                sendPlayerAnsweredMessage(optionId, isCorrect, responseTime);
            };
            
            // Handle skipping a question
            const skipQuestion = () => {
                // Stop the timer
                clearInterval(gameState.questionTimerInterval);
                
                // Calculate response time in milliseconds
                const responseTime = new Date() - gameState.questionStartTime;
                
                // Disable further selections
                disableAnswerOptions();
                
                // Record the response as skipped
                recordResponse(null, true, responseTime, false);
                
                // Show feedback
                showToast('Skipped', 'No points awarded', 'info');
                
                // Highlight correct answer
                highlightCorrectAnswer();
                
                // Enable next question button if it exists
                const nextBtn = document.getElementById('nextQuestionBtn');
                if (nextBtn) {
                    nextBtn.disabled = false;
                }
                
                // Send message to server that player has skipped
                sendPlayerSkippedMessage(responseTime);
            };
            
            // Highlight the correct answer after player responds
            const highlightCorrectAnswer = () => {
                if (!gameState.correctAnswer) return;
                
                const options = document.querySelectorAll('.answer-option');
                options.forEach(option => {
                    const answer = option.getAttribute('data-answer');
                    if (answer === gameState.correctAnswer) {
                        option.classList.add('correct');
                    } else if (option.classList.contains('selected')) {
                        option.classList.add('incorrect');
                    }
                });
            };
            
            // Record player's response
            const recordResponse = async (answerGiven, isSkipped, timeTaken = null, isCorrect = false) => {
                if (!gameState.battleId || !gameState.currentQuestionId) {
                    console.error('Missing battle ID or question ID');
                    return;
                }
                
                // Store the answer in the player's answers array
                if (!gameState.playerAnswers) {
                    gameState.playerAnswers = [];
                }
                
                const answerRecord = {
                    questionId: gameState.currentQuestionId,
                    answer: answerGiven,
                    isSkipped: isSkipped,
                    isCorrect: isCorrect,
                    timeTaken: timeTaken,
                    timestamp: new Date()
                };
                
                // Find if we already answered this question
                const existingIndex = gameState.playerAnswers.findIndex(a => a.questionId === gameState.currentQuestionId);
                if (existingIndex >= 0) {
                    // Update existing answer
                    gameState.playerAnswers[existingIndex] = answerRecord;
                } else {
                    // Add new answer
                    gameState.playerAnswers.push(answerRecord);
                }
                
                // Update the player's score display
                updatePlayerScore();
                
                try {
                    // Record the response in the database
                    const { data, error } = await supabaseClient.rpc('record_quick_battle_response', {
                        p_battle_id: gameState.battleId,
                        p_player_id: gameState.playerId,
                        p_question_id: gameState.currentQuestionId,
                        p_answer_given: answerGiven ? answerGiven.toUpperCase() : null,
                        p_is_skipped: isSkipped,
                        p_time_taken_ms: timeTaken
                    });
                    
                    if (error) {
                        console.error('Error recording response:', error);
                    } else {
                        console.log('Response recorded, points awarded:', data);
                    }
                } catch (err) {
                    console.error('Exception recording response:', err);
                }
            };
            
            // Send message to server that player has answered
            const sendPlayerAnsweredMessage = (answerGiven, isCorrect, responseTime) => {
                if (!socket || socket.readyState !== WebSocket.OPEN) {
                    console.error('WebSocket not connected');
                    return;
                }
                
                const message = {
                    type: 'player_answered',
                    battleId: gameState.originalBattleId,
                    questionId: gameState.currentQuestionId,
                    answerGiven: answerGiven,
                    isCorrect: isCorrect,
                    responseTime: responseTime,
                    questionNumber: gameState.currentQuestion + 1
                };
                
                socket.send(JSON.stringify(message));
                console.log('Sent player answered message:', message);
            };
            
            // Send message to server that player has skipped
            const sendPlayerSkippedMessage = (responseTime) => {
                if (!socket || socket.readyState !== WebSocket.OPEN) {
                    console.error('WebSocket not connected');
                    return;
                }
                
                const message = {
                    type: 'player_skipped',
                    battleId: gameState.originalBattleId,
                    questionId: gameState.currentQuestionId,
                    responseTime: responseTime,
                    questionNumber: gameState.currentQuestion + 1
                };
                
                socket.send(JSON.stringify(message));
                console.log('Sent player skipped message:', message);
            };
            
            // Check if both players have answered and move to next question
            const checkBothPlayersAnswered = async () => {
                try {
                    // Call the database function to check if both players answered
                    const { data, error } = await supabaseClient.rpc('both_players_answered', {
                        p_battle_id: gameState.battleId,
                        p_question_id: gameState.currentQuestionId
                    });
                    
                    if (error) {
                        console.error('Error checking if both players answered:', error);
                        return false;
                    }
                    
                    return data === true;
                } catch (err) {
                    console.error('Exception checking if both players answered:', err);
                    return false;
                }
            };
            
            // Get battle scores and determine winner
            const getBattleScores = async () => {
                try {
                    // Call the database function to get scores
                    const { data, error } = await supabaseClient.rpc('get_quick_battle_scores', {
                        p_battle_id: gameState.battleId
                    });
                    
                    if (error) {
                        console.error('Error getting battle scores:', error);
                        return null;
                    }
                    
                    console.log('Battle scores:', data);
                    return data;
                } catch (err) {
                    console.error('Exception getting battle scores:', err);
                    return null;
                }
            };
            
            // Complete the battle and show results
            const completeBattle = async (forced = false, quitBy = null) => {
                try {
                    // Call the database function to complete the battle
                    const { data, error } = await supabaseClient.rpc('complete_quick_battle', {
                        p_battle_id: gameState.battleId,
                        p_forced_end: forced,
                        p_quit_by: quitBy
                    });
                    
                    if (error) {
                        console.error('Error completing battle:', error);
                        return;
                    }
                    
                    console.log('Battle completed:', data);
                    
                    // Get the final scores
                    const scores = await getBattleScores();
                    
                    if (!scores) {
                        console.error('Could not get final scores');
                        return;
                    }
                    
                    // Determine the winner
                    let result = 'tie';
                    
                    // Find the current player's score
                    const playerScore = scores.find(s => s.player_id === gameState.playerId);
                    const opponentScore = scores.find(s => s.player_id !== gameState.playerId);
                    
                    if (playerScore && opponentScore) {
                        if (playerScore.total_score > opponentScore.total_score) {
                            result = 'win';
                        } else if (playerScore.total_score < opponentScore.total_score) {
                            result = 'lose';
                        } else {
                            // Scores are tied, check response time
                            if (playerScore.avg_response_time_ms < opponentScore.avg_response_time_ms) {
                                result = 'win'; // Player was faster
                            } else if (playerScore.avg_response_time_ms > opponentScore.avg_response_time_ms) {
                                result = 'lose'; // Opponent was faster
                            } else {
                                result = 'tie'; // True tie (same score and time)
                            }
                        }
                    }
                    
                    // Calculate correct answers count
                    const player1CorrectAnswers = scores.find(s => s.player_id === gameState.player1Id)?.correct_answers || 0;
                    const player2CorrectAnswers = scores.find(s => s.player_id === gameState.player2Id)?.correct_answers || 0;
                    
                    // Update the battle end
                    await updateBattleEnd(
                        result,
                        forced ? 'cancelled' : 'Success',
                        playerScore?.total_score || 0,
                        opponentScore?.total_score || 0,
                        playerScore.player_id === gameState.player1Id ? player1CorrectAnswers : player2CorrectAnswers,
                        opponentScore.player_id === gameState.player1Id ? player1CorrectAnswers : player2CorrectAnswers
                    );
                    
                    // Show the result modal
                    showResultModal(result);
                } catch (err) {
                    console.error('Exception completing battle:', err);
                }
            };
            
            // Add a skip button to the UI
            const addSkipButton = () => {
                // Check if the button already exists
                if (document.getElementById('skipQuestionBtn')) {
                    return; // Already added
                }
                
                console.log('Adding skip button to UI');
                
                // Find the question navigation section
                const navigationSection = document.querySelector('.question-navigation');
                if (!navigationSection) {
                    console.error('Could not find question navigation section, creating it');
                    
                    // Try to find the answers container as a fallback
                    const answersContainer = document.getElementById('answersContainer');
                    if (answersContainer) {
                        // Create the navigation section if it doesn't exist
                        const newNavSection = document.createElement('div');
                        newNavSection.className = 'question-navigation';
                        answersContainer.after(newNavSection);
                        
                        // Create the skip button
                        const skipButton = document.createElement('button');
                        skipButton.id = 'skipQuestionBtn';
                        skipButton.className = 'skip-btn';
                        skipButton.innerHTML = '<i class="fas fa-forward"></i> Skip Question';
                        skipButton.addEventListener('click', skipQuestion);
                        
                        // Add it to the new navigation section
                        newNavSection.appendChild(skipButton);
                        console.log('Created skip button and added to new navigation section');
                    } else {
                        console.error('Could not find answers container to add navigation section');
                    }
                    return;
                }
                
                // Create the skip button
                const skipButton = document.createElement('button');
                skipButton.id = 'skipQuestionBtn';
                skipButton.className = 'skip-btn';
                skipButton.innerHTML = '<i class="fas fa-forward"></i> Skip Question';
                skipButton.addEventListener('click', skipQuestion);
                
                // Add it to the navigation section
                navigationSection.appendChild(skipButton);
                console.log('Added skip button to existing navigation section');
            };
            
            // Update setupQuickBattle to be called earlier and more reliably
            const setupQuickBattle = () => {
                console.log('Setting up Quick Battle UI elements');
                
                // Add timer element if it doesn't exist
                if (!document.getElementById('questionTimer')) {
                    addTimerElement();
                }
                
                // Add skip button if it doesn't exist
                if (!document.getElementById('skipQuestionBtn')) {
                    addSkipButton();
                }
                
                // Initialize player answers array
                gameState.playerAnswers = [];
                gameState.opponentAnswers = [];
                
                console.log('Quick Battle setup complete');
            };
            
            // Call setupQuickBattle immediately and also on DOM content loaded
            setupQuickBattle();
            
            document.addEventListener('DOMContentLoaded', () => {
                console.log('DOM loaded, setting up quick battle if needed');
                setupQuickBattle();
                
                // Check URL parameters to verify it's a quick battle
                const params = new URLSearchParams(window.location.search);
                const battleType = params.get('battleType') || '';
                
                // Add a special CSS class to make the skip button more visible
                document.body.classList.toggle('quick-battle-mode', battleType.toLowerCase() === 'quick');
                
                if (battleType.toLowerCase() === 'quick') {
                    console.log('Detected quick battle in URL parameters');
                } else {
                    console.log('Not a quick battle, but still setting up UI elements');
                }
            });

            // Add a timer element to the UI
            const addTimerElement = () => {
                // Check if timer already exists
                if (document.getElementById('questionTimer')) {
                    return; // Already added
                }
                
                console.log('Adding timer element to UI');
                
                // Find the question header
                const questionHeader = document.querySelector('.question-header');
                if (!questionHeader) {
                    console.error('Could not find question header');
                    return;
                }
                
                // Create the timer element
                const timerElement = document.createElement('div');
                timerElement.id = 'questionTimer';
                timerElement.className = 'question-timer';
                timerElement.textContent = '01:30';
                
                // Add it to the question header
                questionHeader.appendChild(timerElement);
                console.log('Timer element added successfully');
            };
            
            // Extended displayQuestion function to start the timer
            const originalDisplayQuestion = displayQuestion;
            displayQuestion = (questionIndex) => {
                console.log('Extended displayQuestion called for index', questionIndex);
                
                // Call the original function first
                const result = originalDisplayQuestion(questionIndex);
                
                // If successful, start the countdown
                if (result) {
                    console.log('Original displayQuestion successful, adding UI elements');
                    
                    // Ensure we have the skip button and timer
                    addSkipButton();
                    addTimerElement();
                    
                    // Add a highly visible skip button above the next question button
                    addVisibleSkipButton();
                    
                    // Start the countdown when the image is loaded
                    const img = document.querySelector('.question-image');
                    if (img) {
                        if (img.complete) {
                            // Image is already loaded
                            console.log('Image already loaded, starting countdown');
                            startQuestionCountdown();
                        } else {
                            // Wait for image to load
                            console.log('Waiting for image to load before starting countdown');
                            img.onload = () => {
                                console.log('Image loaded, starting countdown');
                                startQuestionCountdown();
                            };
                            
                            // Or if it fails to load, still start the timer
                            img.onerror = () => {
                                console.log('Image failed to load, starting countdown anyway');
                                startQuestionCountdown();
                            };
                        }
                    } else {
                        // No image, start timer right away
                        console.log('No image found, starting countdown immediately');
                        startQuestionCountdown();
                    }
                }
                
                return result;
            };
            
            // Add a more visible skip button directly to the UI
            const addVisibleSkipButton = () => {
                console.log('Adding highly visible skip button');
                
                // Remove any existing prominent skip button
                const existingButton = document.getElementById('prominentSkipBtn');
                if (existingButton) {
                    existingButton.remove();
                }
                
                // Find the battle footer or question content
                const battleFooter = document.querySelector('.battle-footer');
                if (!battleFooter) {
                    console.error('Could not find battle footer to add prominent skip button');
                    return;
                }
                
                // Create a prominent skip button
                const skipButton = document.createElement('button');
                skipButton.id = 'prominentSkipBtn';
                skipButton.className = 'prominent-skip-btn';
                skipButton.innerHTML = '<i class="fas fa-forward"></i> SKIP QUESTION';
                skipButton.addEventListener('click', skipQuestion);
                
                // Insert before the battle footer
                battleFooter.parentNode.insertBefore(skipButton, battleFooter);
                
                console.log('Prominent skip button added successfully');
            };
        });
    </script>
    
    <!-- Immediate execution script to ensure quick battle elements are added -->
    <script>
        // Add a class to the body to enable quick battle mode
        document.body.classList.add('quick-battle-mode');
        
        // Wait for page to be interactive then add the skip button
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, adding skip button immediately');
            
            // Try to add the skip button right away
            setTimeout(function() {
                // Check if we have the addVisibleSkipButton function
                if (typeof addVisibleSkipButton === 'function') {
                    addVisibleSkipButton();
                    console.log('Skip button added on page load');
                } else {
                    console.log('addVisibleSkipButton function not available yet');
                }
                
                // Force the quick-battle-mode class again
                document.body.classList.add('quick-battle-mode');
            }, 1000); // Short delay to ensure other scripts have loaded
        });
        
        // Also try immediately
        setTimeout(function() {
            // Add a prominent skip button directly to the DOM
            const battleContent = document.querySelector('.battle-content');
            if (battleContent) {
                const skipBtn = document.createElement('button');
                skipBtn.id = 'directSkipBtn';
                skipBtn.className = 'prominent-skip-btn';
                skipBtn.innerHTML = '<i class="fas fa-forward"></i> SKIP THIS QUESTION';
                skipBtn.onclick = function() {
                    console.log('Direct skip button clicked');
                    // Try to call skipQuestion if available
                    if (typeof skipQuestion === 'function') {
                        skipQuestion();
                    } else {
                        console.log('skipQuestion function not available, showing notification');
                        alert('Skip feature not yet loaded. Please wait a moment and try again.');
                    }
                };
                
                // Insert at the beginning of battle content
                battleContent.insertBefore(skipBtn, battleContent.firstChild);
                console.log('Direct skip button added to battle content');
            } else {
                console.log('Battle content not found for direct skip button');
            }
        }, 500);
    </script>
</body>
</html> 
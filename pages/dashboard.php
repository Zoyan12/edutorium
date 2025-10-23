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
    <title>Dashboard - Edutorium</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <link rel="stylesheet" href="../css/components/avatar.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 60px;
            --primary-color: #4CAF50;
            --secondary-color: #45a049;
            --text-color: #333;
            --bg-color: #f5f5f5;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--bg-color);
            font-family: 'Arial', sans-serif;
        }

        /* Animation Keyframes */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
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

        @keyframes scaleIn {
            from {
                transform: scale(0.95);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Global Transition Styles */
        * {
            transition: background-color 0.3s ease, color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background-color: white;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                box-shadow 0.3s ease;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }

        /* Add this class to prevent body scroll on mobile */
        @media (max-width: 768px) {
            body.sidebar-open {
                overflow: hidden;
                position: fixed;
                width: 100%;
                height: 100%;
            }
        }

        .sidebar-header {
            padding: 20px 0px;
            border-bottom: 1px solid #eee;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .logo {
            padding-bottom: 10px;
            margin: -10px;
            color: white;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .logo i {
            font-size: 28px;
        }

        .sidebar-nav {
            padding: 10px 0;
            flex-grow: 1;
        }

        .nav-item {
            padding: 15px 25px;
            display: flex;
            align-items: center;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .nav-item:after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            background: var(--primary-color);
            opacity: 0.1;
            transition: width 0.3s ease;
        }

        .nav-item:hover:after {
            width: 100%;
        }

        .nav-item.active {
            background-color: #e8f5e9;
            color: var(--primary-color);
            border-left: 4px solid var(--primary-color);
        }

        .nav-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* User Profile Styles */
        .user-profile {
            border-top: 1px solid #eee;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: auto;
            transition: background-color 0.3s ease;
        }

        .user-profile:hover {
            background-color: #f8f8f8;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e8f5e9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 20px;
        }

        .user-info {
            flex-grow: 1;
            overflow: hidden;
        }

        .user-name {
            font-weight: 600;
            color: var(--text-color);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-field {
            color: #666;
            font-size: 0.9em;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .logout-arrow {
            padding: 8px;
            cursor: pointer;
            color: #666;
            transition: all 0.3s ease;
        }

        .logout-arrow:hover {
            transform: translateX(2px);
            color: #f44336;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            display: flex;
            flex-direction: row;
            gap: 25px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            animation: scaleIn 0.5s ease forwards;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .goals-section,
        .friend-battle-section,
        .quick-battle-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            animation: scaleIn 0.5s ease forwards;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        /* quick battle css */
        .quick-battle-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: linear-gradient(135deg, #c7daf7 0%, #c3cfe2 100%);
            border-radius: 12px;
            margin-top: 15px;
            gap: 15px;
        }

        .battle-stats {
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 100%;
        }

        .stat {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4a5568;
            justify-content: center;
        }

        .stat i {
            color: var(--primary-color);
        }

        .quick-battle-btn {
            background: linear-gradient(135deg, var(--primary-color) 0%, #4a90e2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(74, 144, 226, 0.3);
            width: 100%;
            justify-content: center;
        }

        .quick-battle-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(74, 144, 226, 0.4);
        }

        .quick-battle-btn:active {
            transform: scale(0.98);
        }

        .btn-content {
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            z-index: 1;
        }

        .btn-glow {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .quick-battle-btn:hover .btn-glow {
            opacity: 1;
        }

        .quick-battle-btn i {
            font-size: 1.2em;
        }

        @media (min-width: 768px) {
            .quick-battle-card {
                flex-direction: row;
            }

            .battle-stats {
                flex-direction: row;
                width: auto;
            }

            .stat {
                justify-content: flex-start;
            }

            .quick-battle-btn {
                width: auto;
            }
        }

        .goal-item {
            margin-top: 20px;
        }

        .goals-section:hover,
        .friend-battle-section:hover,
        .quick-battle-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .progress-bar {
            height: 8px;
            background: #eee;
            border-radius: 4px;
            margin-top: 10px;
        }

        .progress-bar-fill {
            height: 100%;
            background: var(--primary-color);
            border-radius: 4px;
            width: 0%;
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Custom progress bar styles */
        .progress-bar-battles, 
        .progress-bar-points, 
        .progress-bar-wins {
            height: 100%;
            border-radius: 4px;
            width: 0%;
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .progress-bar-battles {
            background: #4a90e2;
        }
        
        .progress-bar-points {
            background: #f5b041;
        }
        
        .progress-bar-wins {
            background: #2ecc71;
        }

        .battle-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 15px;
        }

        .battle-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            position: relative;
        }

        .battle-btn:after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .battle-btn:hover:after {
            width: 300%;
            height: 300%;
        }

        .battle-btn:active {
            transform: scale(0.95);
        }

        .battle-btn i {
            margin-right: 8px;
        }

        .hamburger {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 10px;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .welcome-text {
                font-size: 25px;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .hamburger {
                display: block;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .battle-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            transition: all 0.4s cubic-bezier(0.19, 1, 0.22, 1);
        }

        .modal-overlay.active {
            display: flex;
            animation: fadeIn 0.3s cubic-bezier(0.19, 1, 0.22, 1) forwards;
        }

        /* Add this class to prevent body scroll */
        body.modal-open {
            overflow: hidden;
            position: fixed;
            width: 100%;
            height: 100%;
        }

        .modal {
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            animation: scaleModal 0.4s cubic-bezier(0.19, 1, 0.22, 1) forwards;
            transform: scale(0.9);
            position: relative;
            overflow: hidden;
        }

        @keyframes scaleModal {
            0% {
                transform: scale(0.9);
                opacity: 0;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .modal::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }

        .modal h3 {
            margin: 0 0 20px 0;
            color: var(--text-color);
            font-size: 1.8rem;
            position: relative;
            display: inline-block;
        }

        .modal h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -8px;
            height: 3px;
            width: 50px;
            background: var(--primary-color);
            left: 50%;
            transform: translateX(-50%);
            border-radius: 2px;
        }

        .modal p {
            margin: 0 0 30px 0;
            color: #666;
            font-size: 1.1rem;
        }

        .modal-warning-icon {
            background: #ffebee;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .modal-warning-icon i {
            color: #f44336;
            font-size: 32px;
        }

        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .modal-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.19, 1, 0.22, 1);
            position: relative;
            overflow: hidden;
            min-width: 120px;
        }

        .modal-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .modal-btn:hover::after {
            width: 300%;
            height: 300%;
        }

        .modal-btn:active {
            transform: scale(0.95);
        }

        .modal-btn.confirm {
            background: #f44336;
            color: white;
            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);
        }

        .modal-btn.confirm:hover {
            background: #d32f2f;
            box-shadow: 0 6px 20px rgba(244, 67, 54, 0.4);
        }

        .modal-btn.cancel {
            background: white;
            color: #333;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }

        .modal-btn.cancel:hover {
            background: #f5f5f5;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .modal-btn i {
            margin-right: 8px;
        }

        /* Additional Section Styles */
        .dashboard-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .battle-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .top-players-section,
        .achievements-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            animation: scaleIn 0.5s ease forwards;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }

        .top-players-section:hover,
        .achievements-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .section-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--text-color);
        }

        .player-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .player-item:last-child {
            border-bottom: none;
        }

        .player-rank {
            width: 30px;
            height: 30px;
            background: #f5f5f5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }

        .player-rank.rank-1 {
            background: gold;
            color: #333;
        }

        .player-rank.rank-2 {
            background: silver;
            color: #333;
        }

        .player-rank.rank-3 {
            background: #cd7f32;
            /* bronze */
            color: white;
        }

        .player-info {
            flex-grow: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .player-name {
            font-weight: 500;
        }

        .player-score {
            font-weight: bold;
            color: var(--primary-color);
        }

        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 10px;
        }

        .achievement-item {
            background: #f9f9f9;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .achievement-item:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
        }

        .achievement-icon {
            color: var(--primary-color);
            font-size: 24px;
            margin-bottom: 10px;
        }

        .achievement-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        .achievement-desc {
            font-size: 0.9em;
            color: #666;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .dashboard-row {
                grid-template-columns: 1fr;
            }
        }

        /* Add friends drawer/overlay styles */
        .friends-drawer {
            position: fixed;
            right: -400px;
            top: 0;
            width: 400px;
            height: 100%;
            background: white;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1500;
            transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .friends-drawer.active {
            right: 0;
        }

        .friends-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .friends-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .friends-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            transition: background-color 0.3s ease;
        }

        .friends-close:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .friends-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .friends-search {
            position: relative;
        }

        .friends-search input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid #ddd;
            border-radius: 50px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .friends-search input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        }

        .friends-search button {
            position: absolute;
            right: 5px;
            top: 5px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .friends-search button:hover {
            background-color: var(--secondary-color);
        }

        .friends-tabs {
            display: flex;
            border-bottom: 1px solid #eee;
        }

        .friends-tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            color: #666;
            position: relative;
        }

        .friends-tab.active {
            color: var(--primary-color);
        }

        .friends-tab.active:after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--primary-color);
        }

        .friends-tab-content {
            display: none;
        }

        .friends-tab-content.active {
            display: block;
        }

        .friend-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 10px;
            background: #f9f9f9;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .friend-item:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .friend-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 15px;
            position: relative;
        }

        .friend-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .friend-status {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid white;
        }

        .status-online {
            background-color: #4CAF50;
        }

        .status-offline {
            background-color: #9e9e9e;
        }

        .friend-info {
            flex: 1;
        }

        .friend-name {
            font-weight: 600;
            margin: 0 0 5px 0;
            color: #333;
        }

        .friend-field {
            font-size: 0.8rem;
            color: #666;
            margin: 0;
        }

        .friend-actions {
            display: flex;
            gap: 10px;
        }

        .friend-action-btn {
            background: none;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #666;
        }

        .friend-action-btn:hover {
            background-color: rgba(0, 0, 0, 0.1);
            color: var(--primary-color);
        }

        .friend-action-btn.battle {
            background: linear-gradient(135deg, var(--primary-color) 0%, #4a90e2 100%);
            color: white;
        }

        .friend-action-btn.battle:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .friend-action-btn.accept {
            background-color: var(--primary-color);
            color: white;
        }

        .friend-action-btn.reject, .friend-action-btn.remove {
            background-color: #f44336;
            color: white;
        }

        .friend-action-btn.remove:hover {
            background-color: #d32f2f;
            transform: scale(1.05);
        }

        .no-friends-message {
            text-align: center;
            padding: 30px;
            color: #666;
        }

        .no-friends-message i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 15px;
            display: block;
        }

        .search-results {
            margin-top: 20px;
        }

        .search-result-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 10px;
            background: #f9f9f9;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .search-result-item:hover {
            background: #f0f0f0;
        }

        .add-friend-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 8px 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .add-friend-btn:hover {
            background: var(--secondary-color);
        }

        .add-friend-btn.sent {
            background: #9e9e9e;
        }

        /* Mobile styles for friends */
        @media (max-width: 768px) {
            .friends-drawer {
                width: 100%;
                right: -100%;
            }

            body.friends-open {
                overflow: hidden;
                position: fixed;
                width: 100%;
                height: 100%;
            }
        }

        .friend-action-status {
            background: #f5f5f5;
            border-radius: 20px;
            padding: 8px 15px;
            font-size: 0.9rem;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
            animation: fadeIn 0.3s ease forwards;
        }

        .friend-action-status i {
            font-size: 1.1rem;
        }

        .friend-action-status.success {
            background: #e8f5e9;
            color: var(--primary-color);
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

        /* Success notification for friend actions */
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #4CAF50;
            color: white;
            padding: 12px 20px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 3000;
            animation: slideInRight 0.3s ease forwards, fadeOut 0.3s ease forwards 3s;
            max-width: 300px;
        }

        .toast-notification i {
            font-size: 1.2rem;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                transform: translateY(10px);
            }
        }
        
        /* Battle Modal Styles */
        .battle-modal {
            max-width: 600px;
            padding: 0;
            overflow: hidden;
            position: relative;
        }
        
        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.1);
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s;
        }
        
        .modal-close:hover {
            background: rgba(0, 0, 0, 0.2);
        }
        
        .battle-modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
        }
        
        .battle-modal-header h3 {
            margin: 0 0 15px 0;
            font-size: 1.6rem;
        }
        
        .battle-modal-steps {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }
        
        .battle-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            position: relative;
            width: 33%;
            opacity: 0.6;
            transition: all 0.3s;
        }
        
        .battle-step::before {
            content: '';
            position: absolute;
            top: 15px;
            left: -50%;
            width: 100%;
            height: 2px;
            background-color: rgba(255, 255, 255, 0.5);
        }
        
        .battle-step:first-child::before {
            display: none;
        }
        
        .battle-step.active {
            opacity: 1;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: white;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .step-text {
            font-size: 0.9rem;
        }
        
        .battle-step-content {
            padding: 25px;
            display: none;
        }
        
        .battle-step-content.active {
            display: block;
            animation: fadeIn 0.3s;
        }
        
        .battle-step-content h4 {
            margin: 0 0 10px 0;
            font-size: 1.3rem;
            color: var(--text-color);
        }
        
        .battle-step-content p {
            margin: 0 0 20px 0;
            color: #666;
        }
        
        .subject-selection, .difficulty-selection {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .questions-selection {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .subject-option, .difficulty-option {
            background-color: #f5f5f5;
            border-radius: 10px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .subject-option:hover, .difficulty-option:hover {
            background-color: #e8f5e9;
            transform: translateY(-2px);
        }
        
        .subject-option.selected, .difficulty-option.selected {
            background-color: #d4edda;
            border: 2px solid var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .subject-icon, .difficulty-icon {
            font-size: 24px;
            color: var(--primary-color);
            transition: all 0.3s;
        }
        
        .subject-option.selected .subject-icon,
        .difficulty-option.selected .difficulty-icon {
            color: white;
        }
        
        .subject-label, .difficulty-label {
            font-weight: 500;
        }
        
        .question-option {
            background-color: #f5f5f5;
            border-radius: 10px;
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .question-option:hover {
            background-color: #e8f5e9;
            transform: translateY(-2px);
        }
        
        .question-option.selected {
            background-color: #d4edda;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .battle-modal-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .battle-modal-footer:first-child {
            justify-content: flex-end;
        }
        
        .modal-btn {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .modal-btn.next, .modal-btn.confirm {
            background-color: var(--primary-color);
        }
        
        .modal-btn.next:disabled, .modal-btn.confirm:disabled {
            background-color: #9e9e9e;
            cursor: not-allowed;
        }
        
        /* Matchmaking modal styles */
        .matchmaking-content {
            padding: 30px;
            text-align: center;
        }
        
        .searching-animation {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
        }
        
        .pulse-ring {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 3px solid var(--primary-color);
            opacity: 0;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(0.5);
                opacity: 0;
            }
            50% {
                opacity: 0.5;
            }
            100% {
                transform: scale(1.5);
                opacity: 0;
            }
        }
        
        .searching-animation i {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 40px;
            color: var(--primary-color);
        }
        
        .countdown {
            margin: 20px 0;
            font-size: 1.2rem;
            font-weight: 500;
        }
        
        #searchTimer {
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .matchmaking-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        .opponent-found-animation i {
            font-size: 60px;
            color: var(--primary-color);
            margin-bottom: 20px;
            animation: bounceIn 0.6s;
        }
        
        @keyframes bounceIn {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .opponent-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 20px 0;
        }
        
        .opponent-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--primary-color);
            margin-bottom: 10px;
        }
        
        .opponent-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .opponent-name {
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .ready-countdown {
            display: none;
            width: 60px;
            height: 60px;
            background: var(--primary-color);
            border-radius: 50%;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            margin: 20px auto;
            line-height: 60px;
            text-align: center;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.7);
            }
            70% {
                transform: scale(1.1);
                box-shadow: 0 0 0 15px rgba(0, 123, 255, 0);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(0, 123, 255, 0);
            }
        }
        
        .modal-btn.ready {
            background: #4CAF50;
            color: white;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }
        
        .modal-btn.ready:hover {
            background: #43a047;
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
        }
        
        .no-opponent-animation i {
            font-size: 60px;
            color: #f44336;
            margin-bottom: 20px;
        }
        
        @media (max-width: 600px) {
            .subject-selection, .difficulty-selection {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .questions-selection {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .opponent-ready-indicator {
            background-color: rgba(40, 167, 69, 0.1);
            border: 1px solid #28a745;
            color: #28a745;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            animation: fadeIn 0.5s;
        }

        /* Modal warning icon */
        .modal-warning-icon {
            width: 60px;
            height: 60px;
            background-color: #fff3cd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }

        .modal-warning-icon i {
            font-size: 2rem;
            color: #f44336;
        }

        .modal-subtitle {
            color: #777;
            font-size: 0.9rem;
            margin-top: 5px;
            margin-bottom: 20px;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2 class="logo">
                <i class="fas fa-graduation-cap"></i>
                Edutorium
            </h2>
    </div>
        <nav class="sidebar-nav">
            <a href="#" class="nav-item active">
                <i class="fas fa-home"></i>
                Home
            </a>
            <a href="#" class="nav-item" id="friendsNavItem">
                <i class="fas fa-users"></i>
                Friends
            </a>
            <a href="#" class="nav-item" id="battleNavItem">
                <i class="fas fa-gamepad"></i>
                Battle Arena
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-trophy"></i>
                Leaderboard
            </a>
            <a href="../admin/" class="nav-item" id="adminNavItem" style="display: none;">
                <i class="fas fa-cog"></i>
                Admin Panel
            </a>
        </nav>
        <div class="user-profile">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-info">
                <p class="user-name" id="userName">Loading...</p>
                <p class="user-field" id="userField">Loading...</p>
            </div>
            <div class="logout-arrow" id="logoutBtn">
                <i class="fas fa-sign-out-alt"></i>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <button class="hamburger" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="welcome-text">Welcome Back! 👋</h1>
            <div class="notifications">
                <i class="fas fa-bell"></i>
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <i style="margin-top: 5px;" class="fas fa-trophy"></i>
                <p style="margin-top: 1px;">Points</p>
                <h3 id="userPoints">0</h3>
            </div>
            <div class="stat-card">
                <i style="margin-top: 5px;" class="fas fa-award"></i>
                <p style="margin-top: 1px;">Wins</p>
                <h3 id="userWins">0</h3>
            </div>
            <div class="stat-card">
                <i style="margin-top: 5px;" class="fas fa-fire"></i>
                <p style="margin-top: 1px;">Day Streak</p>
                <h3 id="userStreak">0</h3>
            </div>
        </div>

        <div class="goals-section">
            <div class="section-header">
                <h2>Daily Goals</h2>
                <span>0/3 Completed</span>
            </div>
            <div class="goals-list">
                <div class="goal-item">
                    <div class="goal-info">
                        <i class="fas fa-gamepad"></i>
                        <span>Play 5 Battles</span>
                    </div>
                    <div class="progress-bar">
                        <div id="progressBarBattles" class="progress-bar-battles" style="width: 0%"></div>
                    </div>
                </div>
                <div class="goal-item">
                    <div class="goal-info">
                        <i class="fas fa-star"></i>
                        <span>Earn 100 Points</span>
                    </div>
                    <div class="progress-bar">
                        <div id="progressBarPoints" class="progress-bar-points" style="width: 0%"></div>
                    </div>
                </div>
                <div class="goal-item">
                    <div class="goal-info">
                        <i class="fas fa-trophy"></i>
                        <span>Win 3 Battles</span>
                    </div>
                    <div class="progress-bar">
                        <div id="progressBarWins" class="progress-bar-wins" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="quick-battle-section">
            <div class="section-header">
                <h2>Quick Battle</h2>
                <span class="subtitle">Challenge a random opponent!</span>
            </div>
            <div class="quick-battle-card">
                <div class="battle-stats">
                    <div class="stat">
                        <i class="fas fa-users"></i>
                        <span id="onlinePlayersCount">124 players online</span>
                    </div>
                    <div class="stat">
                        <i class="fas fa-clock"></i>
                        <span>~2 min match</span>
                    </div>
                </div>
                <button class="quick-battle-btn">
                    <div class="btn-content">
                        <i class="fas fa-bolt"></i>
                        <span>Quick Battle</span>
                    </div>
                    <div class="btn-glow"></div>
                </button>
            </div>
        </div>
        <!-- TODO: Add friend battle section -->
        <!-- <div class="friend-battle-section">
                <div class="section-header">
                    <h2>Battle with Friends</h2>
                    <span class="subtitle">Challenge your friends directly</span>
                </div>
            </div> -->

        <div class="dashboard-row">
            <div class="top-players-section">
                <div class="section-header">
                    <h2>Top Players</h2>
                </div>
                <div id="topPlayersList">
                    <div class="player-item">
                        <div class="player-rank rank-1">1</div>
                        <div class="player-info">
                            <span class="player-name">Loading...</span>
                            <span class="player-score">-</span>
                        </div>
                    </div>
                    <div class="player-item">
                        <div class="player-rank rank-2">2</div>
                        <div class="player-info">
                            <span class="player-name">Loading...</span>
                            <span class="player-score">-</span>
                        </div>
                    </div>
                    <div class="player-item">
                        <div class="player-rank rank-3">3</div>
                        <div class="player-info">
                            <span class="player-name">Loading...</span>
                            <span class="player-score">-</span>
                        </div>
                    </div>
                    <div class="player-item">
                        <div class="player-rank">4</div>
                        <div class="player-info">
                            <span class="player-name">Loading...</span>
                            <span class="player-score">-</span>
                        </div>
                    </div>
                    <div class="player-item">
                        <div class="player-rank">5</div>
                        <div class="player-info">
                            <span class="player-name">Loading...</span>
                            <span class="player-score">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="achievements-section">
                <div class="section-header">
                    <h2>Achievements</h2>
                    <span>0/4</span>
                </div>
                <div class="achievements-grid">
                    <div class="achievement-item" style="opacity: 0.5;">
                        <div class="achievement-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="achievement-title">First Battle</div>
                        <div class="achievement-desc">Win your first battle</div>
                    </div>
                    <div class="achievement-item" style="opacity: 0.5;">
                        <div class="achievement-icon">
                            <i class="fas fa-fire"></i>
                        </div>
                        <div class="achievement-title">Hot Streak</div>
                        <div class="achievement-desc">Win 5 battles in a row</div>
                    </div>
                    <div class="achievement-item" style="opacity: 0.5;">
                        <div class="achievement-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="achievement-title">Point Collector</div>
                        <div class="achievement-desc">Earn 1000 points</div>
                    </div>
                    <div class="achievement-item" style="opacity: 0.5;">
                        <div class="achievement-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="achievement-title">Social Butterfly</div>
                        <div class="achievement-desc">Add 10 friends</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal">
            <div class="modal-warning-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h3>Logout Confirmation</h3>
            <p>Are you sure you want to logout from your account?</p>
            <div class="modal-buttons">
                <button class="modal-btn cancel" id="cancelLogout">
                    <i class="fas fa-times"></i>Cancel
                </button>
                <button class="modal-btn confirm" id="confirmLogout">
                    <i class="fas fa-check"></i>Logout
                </button>
            </div>
        </div>
    </div>

    <!-- Remove Friend Confirmation Modal -->
    <div class="modal-overlay" id="removeFriendModal">
        <div class="modal">
            <div class="modal-warning-icon">
                <i class="fas fa-user-minus"></i>
            </div>
            <h3>Remove Friend</h3>
            <p>Are you sure you want to remove <span id="friendToRemoveName">this friend</span> from your friends list?</p>
            <p class="modal-subtitle">This action cannot be undone. You will need to send a new friend request if you want to reconnect later.</p>
            <div class="modal-buttons">
                <button class="modal-btn cancel" id="cancelRemoveFriend">
                    <i class="fas fa-times"></i>Cancel
                </button>
                <button class="modal-btn danger" id="confirmRemoveFriend">
                    <i class="fas fa-check"></i>Remove
                </button>
            </div>
        </div>
    </div>

    <!-- Friends Drawer/Overlay -->
    <div class="friends-drawer" id="friendsDrawer">
        <div class="friends-header">
            <h2>Friends</h2>
            <span>122 players online</span>
            <button class="friends-close" id="friendsClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="friends-content">
            <div class="friends-search">
                <input type="text" id="friendSearch" placeholder="Search for friends...">
                <button id="searchBtn"><i class="fas fa-search"></i></button>
            </div>
            
            <div class="friends-tabs">
                <div class="friends-tab active" data-tab="friendsList">My Friends</div>
                <div class="friends-tab" data-tab="friendRequests">Requests <span id="requestCount">(0)</span></div>
                <div class="friends-tab" data-tab="findFriends">Find Friends</div>
            </div>
            
            <div class="friends-tab-content active" id="friendsList">
                <div id="friendsListContainer">
                    <div class="no-friends-message">
                        <i class="fas fa-user-friends"></i>
                        <p>You haven't added any friends yet.</p>
                        <p>Search for users to add them as friends!</p>
                    </div>
                </div>
            </div>
            
            <div class="friends-tab-content" id="friendRequests">
                <div id="requestsContainer">
                    <div class="no-friends-message">
                        <i class="fas fa-user-clock"></i>
                        <p>No pending friend requests.</p>
                    </div>
                </div>
            </div>
            
            <div class="friends-tab-content" id="findFriends">
                <p>Search for users by username or name to add them as friends.</p>
                <div id="searchResultsContainer" class="search-results"></div>
            </div>
        </div>
    </div>

    <!-- Battle Selection Modal -->
    <div class="modal-overlay" id="battleSelectionModal">
        <div class="modal battle-modal">
            <div class="modal-close" id="closeBattleModal">
                <i class="fas fa-times"></i>
            </div>
            <div class="battle-modal-header">
                <h3>Start a Battle</h3>
                <div class="battle-modal-steps">
                    <div class="battle-step active" data-step="1">
                        <span class="step-number">1</span>
                        <span class="step-text">Subjects</span>
                    </div>
                    <div class="battle-step" data-step="2">
                        <span class="step-number">2</span>
                        <span class="step-text">Questions</span>
                    </div>
                    <div class="battle-step" data-step="3">
                        <span class="step-number">3</span>
                        <span class="step-text">Difficulty</span>
                    </div>
                </div>
            </div>
            
            <!-- Step 1: Subject Selection -->
            <div class="battle-step-content active" id="battleStep1">
                <h4>Select Subjects</h4>
                <p>Choose at least one subject to continue</p>
                
                <div class="subject-selection">
                    <div class="subject-option" data-subject="physics">
                        <div class="subject-icon">
                            <i class="fas fa-atom"></i>
                        </div>
                        <div class="subject-label">Physics</div>
                    </div>
                    <div class="subject-option" data-subject="chemistry">
                        <div class="subject-icon">
                            <i class="fas fa-flask"></i>
                        </div>
                        <div class="subject-label">Chemistry</div>
                    </div>
                    <div class="subject-option" data-subject="math">
                        <div class="subject-icon">
                            <i class="fas fa-square-root-alt"></i>
                        </div>
                        <div class="subject-label">Math</div>
                    </div>
                    <div class="subject-option" data-subject="botany">
                        <div class="subject-icon">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <div class="subject-label">Botany</div>
                    </div>
                    <div class="subject-option" data-subject="zoology">
                        <div class="subject-icon">
                            <i class="fas fa-paw"></i>
                        </div>
                        <div class="subject-label">Zoology</div>
                    </div>
                </div>
                
                <div class="battle-modal-footer">
                    <button class="modal-btn next" id="nextStep1" disabled>
                        <span>Next</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
            
            <!-- Step 2: Number of Questions -->
            <div class="battle-step-content" id="battleStep2">
                <h4>Number of Questions</h4>
                <p>Select how many questions you want</p>
                
                <div class="questions-selection">
                    <div class="question-option" data-questions="1">1</div>
                    <div class="question-option" data-questions="2">2</div>
                    <div class="question-option" data-questions="3">3</div>
                    <div class="question-option" data-questions="4">4</div>
                    <div class="question-option" data-questions="5">5</div>
                    <div class="question-option" data-questions="10">10</div>
                    <div class="question-option" data-questions="15">15</div>
                </div>
                
                <div class="battle-modal-footer">
                    <button class="modal-btn back" id="backStep2">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back</span>
                    </button>
                    <button class="modal-btn next" id="nextStep2" disabled>
                        <span>Next</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
            
            <!-- Step 3: Difficulty Level -->
            <div class="battle-step-content" id="battleStep3">
                <h4>Difficulty Level</h4>
                <p>Select the difficulty level</p>
                
                <div class="difficulty-selection">
                    <div class="difficulty-option" data-difficulty="easy">
                        <div class="difficulty-icon">
                            <i class="fas fa-smile"></i>
                        </div>
                        <div class="difficulty-label">Easy</div>
                    </div>
                    <div class="difficulty-option" data-difficulty="medium">
                        <div class="difficulty-icon">
                            <i class="fas fa-meh"></i>
                        </div>
                        <div class="difficulty-label">Medium</div>
                    </div>
                    <div class="difficulty-option" data-difficulty="hard">
                        <div class="difficulty-icon">
                            <i class="fas fa-dizzy"></i>
                        </div>
                        <div class="difficulty-label">Hard</div>
                    </div>
                </div>
                
                <div class="battle-modal-footer">
                    <button class="modal-btn back" id="backStep3">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back</span>
                    </button>
                    <button class="modal-btn confirm" id="startBattle" disabled>
                        <i class="fas fa-play"></i>
                        <span>Start Battle</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Battle Matchmaking Modal -->
    <div class="modal-overlay" id="matchmakingModal">
        <div class="modal battle-modal">
            <div class="modal-close" id="closeMatchmakingModal">
                <i class="fas fa-times"></i>
            </div>
            <div class="matchmaking-content">
                <div class="searching-state">
                    <div class="searching-animation">
                        <div class="pulse-ring"></div>
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Searching for an opponent...</h3>
                    <p><span id="activeSearchers">10</span> players looking for a match</p>
                    <div class="countdown">
                        <span id="searchTimer">30</span> seconds remaining
                    </div>
                    <div class="matchmaking-buttons">
                        <button class="modal-btn cancel" id="cancelMatchmaking">
                            <i class="fas fa-times"></i>
                            <span>Cancel</span>
                        </button>
                    </div>
                </div>
                
                <div class="opponent-found-state" style="display: none;">
                    <div class="opponent-found-animation">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Opponent Found!</h3>
                    <div class="opponent-info">
                        <div class="opponent-avatar">
                            <img src="../assets/default.png" alt="Opponent" id="opponentAvatar">
                        </div>
                        <div class="opponent-name" id="opponentName">John Doe</div>
                    </div>
                    <p>Are you ready for the battle?</p>
                    <div class="ready-countdown" id="readyCountdown">
                        <span>3</span>
                    </div>
                    <div class="matchmaking-buttons">
                        <button class="modal-btn ready" id="readyButton">
                            <i class="fas fa-check"></i>
                            <span>Ready</span>
                        </button>
                    </div>
                </div>
                
                <div class="no-opponent-state" style="display: none;">
                    <div class="no-opponent-animation">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <h3>No Opponent Found</h3>
                    <p>We couldn't find an opponent for you at this time.</p>
                    <div class="matchmaking-buttons">
                        <button class="modal-btn retry" id="retryMatchmaking">
                            <i class="fas fa-redo"></i>
                            <span>Try Again</span>
                        </button>
                        <button class="modal-btn cancel" id="cancelNoOpponent">
                            <i class="fas fa-times"></i>
                            <span>Cancel</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden WebSocket URL element -->
    <span id="websocket-url" style="display:none;"><?php echo $websocketUrl; ?></span>

    <script type="module">
        import { AuthManager } from '../js/auth/authManager.js';
        import { supabase } from '../js/config/supabase.js';
        import { FriendsUI } from '../js/components/FriendsUI.js';

        // Global variables
        let onlineUsers = new Set();

        // Make functions globally accessible
        window.fetchFriendsList = async function() {
            try {
                const { data: { user } } = await supabase.auth.getUser();
                if (!user) return;

                console.log('Fetching friends list for user:', user.id);

                // First, get all accepted friendships where the current user is either user_id or friend_id
                const { data: friendships, error: friendshipError } = await supabase
                    .from('friend_relationships')
                    .select('*')
                    .eq('status', 'accepted')
                    .or(`user_id.eq.${user.id},friend_id.eq.${user.id}`);

                if (friendshipError) {
                    console.error('Error fetching friendships:', friendshipError);
                    showToast('Error loading friends list');
                    return;
                }

                console.log('Fetched friendships:', friendships);

                if (!friendships || friendships.length === 0) {
                    const friendsListContainer = document.getElementById('friendsListContainer');
                    friendsListContainer.innerHTML = `
                        <div class="no-friends-message">
                            <i class="fas fa-user-friends"></i>
                            <p>No friends yet. Start adding friends!</p>
                        </div>
                    `;
                    return;
                }

                // Get the IDs of all friends (either user_id or friend_id, whichever isn't the current user)
                const friendIds = friendships.map(friendship => 
                    friendship.user_id === user.id ? friendship.friend_id : friendship.user_id
                );

                // Fetch profiles for all friends
                const { data: friendProfiles, error: profileError } = await supabase
                    .from('profiles')
                    .select('*')
                    .in('user_id', friendIds);

                if (profileError) {
                    console.error('Error fetching friend profiles:', profileError);
                    showToast('Error loading friend profiles');
                    return;
                }

                console.log('Fetched friend profiles:', friendProfiles);

                const friendsListContainer = document.getElementById('friendsListContainer');
                friendsListContainer.innerHTML = '';

                // Combine friendships with profiles and display
                friendProfiles.forEach(profile => {
                    const friendship = friendships.find(f => 
                        f.user_id === profile.user_id || f.friend_id === profile.user_id
                    );

                    if (!friendship) return;

                    const avatar = profile.avatar_url || '../assets/default.png';
                    const isOnline = onlineUsers.has(profile.user_id);

                    friendsListContainer.innerHTML += `
                        <div class="friend-item">
                            <div class="friend-avatar">
                                <img src="${avatar}" alt="${profile.username}" onerror="this.src='../assets/default.png'">
                                <span class="online-status ${isOnline ? 'online' : 'offline'}"></span>
                            </div>
                            <div class="friend-info">
                                <p class="friend-name">${profile.full_name || profile.username}</p>
                                <p class="friend-field">${profile.field || 'Student'}</p>
                            </div>
                            <div class="friend-actions">
                                <button class="friend-action-btn remove" title="Remove friend" onclick="removeFriend('${profile.user_id}', '${(profile.full_name || profile.username).replace(/'/g, "\\'")}')">
                                    <i class="fas fa-user-minus"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });
            } catch (error) {
                console.error('Error in fetchFriendsList:', error);
                showToast('Error loading friends list');
            }
        };

        window.fetchFriendRequests = async function() {
            try {
                const { data: { user } } = await supabase.auth.getUser();
                if (!user) return;

                console.log('Fetching friend requests for user:', user.id);

                // First get the pending requests
                const { data: requests, error: requestError } = await supabase
                    .from('friend_relationships')
                    .select('*')
                    .eq('friend_id', user.id)
                    .eq('status', 'pending');

                if (requestError) {
                    console.error('Error fetching friend requests:', requestError);
                    showToast('Error loading friend requests');
                    return;
                }

                console.log('Friend requests:', requests);

                // Update request count
                const requestCount = requests ? requests.length : 0;
                document.getElementById('requestCount').textContent = `(${requestCount})`;

                const requestsContainer = document.getElementById('requestsContainer');
                
                if (requests && requests.length > 0) {
                    // Get all sender profiles in one query
                    const senderIds = requests.map(req => req.user_id);
                    const { data: senderProfiles, error: profileError } = await supabase
                        .from('profiles')
                        .select('*')
                        .in('user_id', senderIds);

                    if (profileError) {
                        console.error('Error fetching sender profiles:', profileError);
                        showToast('Error loading friend requests');
                        return;
                    }

                    console.log('Sender profiles:', senderProfiles);

                    requestsContainer.innerHTML = '';
                    
                    requests.forEach(request => {
                        const senderProfile = senderProfiles.find(profile => profile.user_id === request.user_id);
                        if (!senderProfile) {
                            console.error('No sender profile found for request:', request);
                            return;
                        }

                        const avatar = senderProfile.avatar_url || '../assets/default.png';

                        requestsContainer.innerHTML += `
                            <div class="friend-item" data-request-id="${request.id}" data-sender-id="${senderProfile.user_id}">
                                <div class="friend-avatar">
                                    <img src="${avatar}" alt="${senderProfile.username}" onerror="this.src='../assets/default.png'">
                                </div>
                                <div class="friend-info">
                                    <p class="friend-name">${senderProfile.full_name || senderProfile.username}</p>
                                    <p class="friend-field">${senderProfile.field || 'Student'}</p>
                                </div>
                                <div class="friend-actions">
                                    <button class="friend-action-btn accept" title="Accept request" onclick="handleFriendRequest('${senderProfile.user_id}', 'accept')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="friend-action-btn reject" title="Reject request" onclick="handleFriendRequest('${senderProfile.user_id}', 'reject')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    requestsContainer.innerHTML = `
                        <div class="no-friends-message">
                            <i class="fas fa-user-clock"></i>
                            <p>No pending friend requests.</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error in friend requests:', error);
                showToast('Error loading friend requests');
            }
        };

        window.handleFriendRequest = async function(senderId, action) {
            try {
                const { data: { user } } = await supabase.auth.getUser();
                if (!user) return;

                console.log('Handling friend request:', { senderId, action });
                
                // Find and update the button states to show loading
                const requestItem = document.querySelector(`.friend-item[data-sender-id="${senderId}"]`);
                if (requestItem) {
                    const buttons = requestItem.querySelectorAll('.friend-action-btn');
                    buttons.forEach(btn => {
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    });
                }

                if (action === 'accept') {
                    // Update the existing request to accepted
                    const { error: updateError } = await supabase
                        .from('friend_relationships')
                        .update({ status: 'accepted' })
                        .eq('user_id', senderId)
                        .eq('friend_id', user.id)
                        .eq('status', 'pending');

                    if (updateError) {
                        console.error('Error accepting friend request:', updateError);
                        showToast('Failed to accept friend request');
                        return;
                    }

                    showToast('Friend request accepted!');
                } else {
                    // Reject by deleting the pending request
                    const { error } = await supabase
                        .from('friend_relationships')
                        .delete()
                        .eq('user_id', senderId)
                        .eq('friend_id', user.id)
                        .eq('status', 'pending');

                    if (error) {
                        console.error('Error rejecting friend request:', error);
                        showToast('Failed to reject friend request');
                        return;
                    }

                    showToast('Friend request rejected');
                }

                // Refresh the requests list
                await fetchFriendRequests();
                await window.fetchFriendsList();
            } catch (error) {
                console.error('Error in handleFriendRequest:', error);
                showToast('Failed to process friend request');
            }
        };

        window.showToast = function(message) {
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(toast);

            // Remove toast after animation
            setTimeout(() => {
                toast.remove();
            }, 3000);
        };

        // Add sendFriendRequest to global scope
        window.sendFriendRequest = async function(friendId, button) {
            try {
                const { data: { user } } = await supabase.auth.getUser();
                if (!user) {
                    showToast('Please log in to send friend requests');
                    return;
                }

                // Check if a request already exists using two separate queries to avoid ambiguity
                const { data: existingRequestsAsUser, error: checkError1 } = await supabase
                    .from('friend_relationships')
                    .select('*')
                    .eq('user_id', user.id)
                    .eq('friend_id', friendId);

                const { data: existingRequestsAsFriend, error: checkError2 } = await supabase
                    .from('friend_relationships')
                    .select('*')
                    .eq('user_id', friendId)
                    .eq('friend_id', user.id);

                if (checkError1 || checkError2) {
                    console.error('Error checking existing request:', checkError1 || checkError2);
                    showToast('Error checking friend request status');
                    return;
                }

                const hasExistingRequest = 
                    (existingRequestsAsUser && existingRequestsAsUser.length > 0) || 
                    (existingRequestsAsFriend && existingRequestsAsFriend.length > 0);

                if (hasExistingRequest) {
                    showToast('A friend request already exists');
                    return;
                }

                // Send the friend request
                const { error: insertError } = await supabase
                    .from('friend_relationships')
                    .insert([{
                        user_id: user.id,
                        friend_id: friendId,
                        status: 'pending'
                    }]);

                if (insertError) {
                    console.error('Error sending friend request:', insertError);
                    showToast('Error sending friend request');
                    return;
                }

                // Update button appearance
                button.innerHTML = '<i class="fas fa-clock"></i> Request Sent';
                button.disabled = true;
                button.classList.add('sent');
                showToast('Friend request sent successfully');

            } catch (error) {
                console.error('Error in sendFriendRequest:', error);
                showToast('Error sending friend request');
            }
        };

        // Add removeFriend to global scope
        window.removeFriend = async function(friendId, friendName) {
            // Store the friendId to use it when confirmed
            window.friendToRemove = friendId;
            
            // Set the friend's name in the modal
            const friendNameElement = document.getElementById('friendToRemoveName');
            friendNameElement.textContent = friendName || 'this friend';
            
            // Show the confirmation modal
            const removeFriendModal = document.getElementById('removeFriendModal');
            removeFriendModal.classList.add('active');
            document.body.classList.add('modal-open');
        };

        window.confirmRemoveFriend = async function() {
            try {
                const friendId = window.friendToRemove;
                if (!friendId) return;
                
                const { data: { user } } = await supabase.auth.getUser();
                if (!user) return;

                // Show loading state in the modal
                const confirmButton = document.getElementById('confirmRemoveFriend');
                confirmButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';
                confirmButton.disabled = true;

                // Delete the specific friendship
                const { error } = await supabase
                    .from('friend_relationships')
                    .delete()
                    .or(`and(user_id.eq.${user.id},friend_id.eq.${friendId}),and(user_id.eq.${friendId},friend_id.eq.${user.id})`)
                    .eq('status', 'accepted');

                // Close the modal
                const removeFriendModal = document.getElementById('removeFriendModal');
                removeFriendModal.classList.remove('active');
                document.body.classList.remove('modal-open');
                
                // Reset the button state
                confirmButton.innerHTML = '<i class="fas fa-check"></i>Remove';
                confirmButton.disabled = false;

                if (error) {
                    console.error('Error removing friend:', error);
                    showToast('Failed to remove friend');
                    return;
                }

                // Find the specific friend item to remove
                const friendItem = document.querySelector(`.friend-item button[onclick*="'${friendId}'"]`)?.closest('.friend-item');
                
                if (friendItem) {
                    // Apply animation to the specific friend item
                    friendItem.style.height = friendItem.offsetHeight + 'px';
                    friendItem.style.transition = 'all 0.3s ease';
                    
                    // Trigger reflow to ensure transition works
                    friendItem.offsetHeight;
                    
                    // Apply fade out and collapse effect
                    friendItem.style.height = '0';
                    friendItem.style.opacity = '0';
                    friendItem.style.margin = '0';
                    friendItem.style.padding = '0';
                    friendItem.style.overflow = 'hidden';
                    
                    // Remove only this specific element after animation completes
                    setTimeout(() => {
                        friendItem.remove();
                        
                        // Check if there are no more friends
                        const friendsListContainer = document.getElementById('friendsListContainer');
                        if (friendsListContainer.children.length === 0) {
                            friendsListContainer.innerHTML = `
                                <div class="no-friends-message">
                                    <i class="fas fa-user-friends"></i>
                                    <p>No friends yet. Start adding friends!</p>
                                </div>
                            `;
                        }
                    }, 300);
                } else {
                    // If we can't find the element, refresh the entire list
                    await window.fetchFriendsList();
                }

                showToast('Friend removed successfully');
                window.friendToRemove = null;
            } catch (error) {
                console.error('Error in confirmRemoveFriend:', error);
                showToast('Failed to remove friend');
                
                // Reset the confirmation modal
                const removeFriendModal = document.getElementById('removeFriendModal');
                removeFriendModal.classList.remove('active');
                document.body.classList.remove('modal-open');
                
                const confirmButton = document.getElementById('confirmRemoveFriend');
                confirmButton.innerHTML = '<i class="fas fa-check"></i>Remove';
                confirmButton.disabled = false;
            }
        };

        window.searchFriends = async function() {
            const searchTerm = document.getElementById('friendSearch').value.trim();
            
            if (!searchTerm) {
                showToast('Please enter a search term');
                return;
            }
            
            try {
                const { data: { user } } = await supabase.auth.getUser();
                if (!user) return;
                
                // Search for profiles that match the search term
                const { data: results, error } = await supabase
                    .from('profiles')
                    .select('user_id, username, full_name, field, avatar_url')
                    .neq('user_id', user.id)
                    .or(`username.ilike.%${searchTerm}%,full_name.ilike.%${searchTerm}%`)
                    .limit(5);
                
                if (error) {
                    console.error('Error searching profiles:', error);
                    showToast('Error searching for users');
                    return;
                }
                
                // Get existing friend relationships
                const { data: relationships, error: relError } = await supabase
                    .from('friend_relationships')
                    .select('*')
                    .or(`user_id.eq.${user.id},friend_id.eq.${user.id}`);
                
                if (relError) {
                    console.error('Error fetching relationships:', relError);
                    showToast('Error checking friend status');
                    return;
                }
                
                const searchResultsContainer = document.getElementById('searchResultsContainer');
                
                if (results && results.length > 0) {
                    searchResultsContainer.innerHTML = '';
                    
                    results.forEach(result => {
                        const avatar = result.avatar_url || '../assets/default.png';
                        const existingRelationship = relationships?.find(r => 
                            (r.user_id === user.id && r.friend_id === result.user_id) ||
                            (r.user_id === result.user_id && r.friend_id === user.id)
                        );
                        
                        let buttonHtml = '';
                        if (existingRelationship?.status === 'accepted') {
                            buttonHtml = `<button class="add-friend-btn sent" disabled>
                                <i class="fas fa-check"></i> Friends
                            </button>`;
                        } else if (existingRelationship?.status === 'pending') {
                            buttonHtml = `<button class="add-friend-btn sent" disabled>
                                <i class="fas fa-clock"></i> Request Sent
                            </button>`;
                        } else {
                            buttonHtml = `<button class="add-friend-btn" onclick="sendFriendRequest('${result.user_id}', this)">
                                <i class="fas fa-user-plus"></i> Add Friend
                            </button>`;
                        }
                        
                        searchResultsContainer.innerHTML += `
                            <div class="search-result-item">
                                <div class="friend-avatar">
                                    <img src="${avatar}" alt="${result.username}" onerror="this.src='../assets/default.png'">
                                </div>
                                <div class="friend-info">
                                    <p class="friend-name">${result.full_name || result.username}</p>
                                    <p class="friend-field">${result.field || 'Student'}</p>
                                </div>
                                ${buttonHtml}
                            </div>
                        `;
                    });
                } else {
                    searchResultsContainer.innerHTML = `
                        <div class="no-friends-message">
                            <i class="fas fa-search"></i>
                            <p>No users found matching "${searchTerm}"</p>
                            <p>Try a different search term</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error searching friends:', error);
                showToast('Error searching for users');
            }
        };

        document.addEventListener('DOMContentLoaded', async () => {
            // Initialize auth manager
            new AuthManager();

            // Initialize FriendsUI for online user count updates
            const friendsUI = new FriendsUI();
            
            // Update online player count in both places
            const updateOnlinePlayerCount = async () => {
                try {
                    // Use FriendService directly to get the player count
                    const { data, error } = await supabase.rpc('count_online_users');
                    
                    if (error) {
                        throw new Error('Failed to fetch online user count');
                    }
                    
                    const count = data || 124;  // Use a fixed fallback value of 124
                    const countText = `${count} players online`;
                    
                    // Update count in quick battle section
                    document.getElementById('onlinePlayersCount').textContent = countText;
                    
                    // Update count in friends header
                    const friendsHeader = document.querySelector('.friends-header span');
                    if (friendsHeader) {
                        friendsHeader.textContent = countText;
                    }
                    
                    return count;
                } catch (error) {
                    console.error('Error updating online player count:', error);
                    
                    // Use a static number instead of a random one
                    const staticCount = 124;
                    const countText = `${staticCount} players online`;
                    
                    // Update with the static count
                    document.getElementById('onlinePlayersCount').textContent = countText;
                    const friendsHeader = document.querySelector('.friends-header span');
                    if (friendsHeader) {
                        friendsHeader.textContent = countText;
                    }
                    
                    return staticCount;
                }
            };
            
            // Update player count initially and every minute
            updateOnlinePlayerCount();
            setInterval(updateOnlinePlayerCount, 60000); // Update every minute
            
            // Load and display user profile data
            const loadUserProfile = async () => {
                try {
                    const { data: { user } } = await supabase.auth.getUser();
                    if (!user) return;
                    
                    const { data: profile, error } = await supabase
                        .from('profiles')
                        .select('*')
                        .eq('user_id', user.id)
                        .single();
                    
                    if (error) {
                        console.error('Error fetching user profile:', error);
                        return;
                    }
                    
                    // Update user avatar
                    const userAvatar = document.querySelector('.user-avatar');
                    if (profile.avatar_url) {
                        userAvatar.innerHTML = `<img src="${profile.avatar_url}" alt="${profile.username}" 
                            style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;" 
                            onerror="this.src='../assets/default.png'">`;
                    } else {
                        userAvatar.innerHTML = `<i class="fas fa-user"></i>`;
                    }
                    
                    // Update user name and field
                    document.getElementById('userName').textContent = profile.full_name || profile.username || user.email;
                    document.getElementById('userField').textContent = profile.field || 'Student';
                    
                    // Update user stats
                    if (profile.points !== undefined) document.getElementById('userPoints').textContent = profile.points || 0;
                    if (profile.wins !== undefined) document.getElementById('userWins').textContent = profile.wins || 0;
                    if (profile.streak !== undefined) document.getElementById('userStreak').textContent = profile.streak || 0;

                    // Update progress bars
                    const battles = profile.battles || 0;
                    const points = profile.points || 0;
                    const wins = profile.wins || 0;
                    
                    // Calculate percentages (cap at 100%)
                    const battlesPercentage = Math.min(battles / 5 * 100, 100);
                    const pointsPercentage = Math.min(points / 100 * 100, 100);
                    const winsPercentage = Math.min(wins / 3 * 100, 100);
                    
                    // Update progress bar widths with error handling
                    const progressBattles = document.getElementById('progressBarBattles');
                    const progressPoints = document.getElementById('progressBarPoints');
                    const progressWins = document.getElementById('progressBarWins');
                    
                    if (progressBattles) progressBattles.style.width = battlesPercentage + '%';
                    if (progressPoints) progressPoints.style.width = pointsPercentage + '%';
                    if (progressWins) progressWins.style.width = winsPercentage + '%';
                    
                    // Update goals completion count
                    const completedGoals = [
                        battles >= 5,
                        points >= 100,
                        wins >= 3
                    ].filter(Boolean).length;
                    
                    const goalsCounter = document.querySelector('.goals-section .section-header span');
                    if (goalsCounter) {
                        goalsCounter.textContent = `${completedGoals}/3 Completed`;
                    }

                    // Show admin nav item if user is admin
                    const adminNavItem = document.getElementById('adminNavItem');
                    if (adminNavItem && profile.is_admin === true) {
                        adminNavItem.style.display = 'flex';
                    }
                } catch (error) {
                    console.error('Error loading user profile data:', error);
                }
            };
            
            // Load user profile data
            loadUserProfile();

            // Handle friends tabs
            const friendsTabs = document.querySelectorAll('.friends-tab');
            const friendsTabContents = document.querySelectorAll('.friends-tab-content');

            friendsTabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Remove active class from all tabs and contents
                    friendsTabs.forEach(t => t.classList.remove('active'));
                    friendsTabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    tab.classList.add('active');
                    const tabContentId = tab.getAttribute('data-tab');
                    document.getElementById(tabContentId).classList.add('active');

                    // Refresh content based on tab
                    if (tabContentId === 'friendRequests') {
                        console.log('Friend requests tab clicked, refreshing requests...');
                        window.fetchFriendRequests();
                    } else if (tabContentId === 'friendsList') {
                        console.log('My Friends tab clicked, refreshing friends list...');
                        window.fetchFriendsList();
                    }
                });
            });

            // Open/close friends drawer
            const friendsNavItem = document.getElementById('friendsNavItem');
            const friendsDrawer = document.getElementById('friendsDrawer');
            const friendsClose = document.getElementById('friendsClose');

            friendsNavItem.addEventListener('click', async (e) => {
                e.preventDefault();
                friendsDrawer.classList.add('active');
                if (window.innerWidth <= 768) {
                    document.body.classList.add('friends-open');
                }
                // Fetch initial data
                await window.fetchFriendRequests();
                await window.fetchFriendsList();
            });
            
            friendsClose.addEventListener('click', () => {
                friendsDrawer.classList.remove('active');
                document.body.classList.remove('friends-open');
            });

            // Set up real-time subscription for friend requests
            const setupFriendRequestSubscription = async () => {
                try {
                    const { data: { user } } = await supabase.auth.getUser();
                    if (!user) return;

                    const channel = supabase.channel('friend-requests');

                    channel
                        .on('postgres_changes', {
                            event: '*',
                            schema: 'public',
                            table: 'friend_relationships',
                            filter: `friend_id=eq.${user.id}`
                        }, async (payload) => {
                            console.log('Friend request change received:', payload);
                            await window.fetchFriendRequests();
                            await window.fetchFriendsList();
                        })
                        .subscribe((status) => {
                            console.log('Subscription status:', status);
                        });

                    console.log('Friend request subscription set up for user:', user.id);
                } catch (error) {
                    console.error('Error setting up friend request subscription:', error);
                }
            };

            // Set up the subscription
            await setupFriendRequestSubscription();

            // Initial data fetch
            await window.fetchFriendRequests();

            // Sidebar toggle functionality
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const logoutBtn = document.getElementById('logoutBtn');
            const logoutModal = document.getElementById('logoutModal');
            const cancelLogout = document.getElementById('cancelLogout');
            const confirmLogout = document.getElementById('confirmLogout');

            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                // Toggle body scroll lock class for mobile
                if (window.innerWidth <= 768) {
                    if (sidebar.classList.contains('active')) {
                        document.body.classList.add('sidebar-open');
                    } else {
                        document.body.classList.remove('sidebar-open');
                    }
                }
            });

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 768) {
                    // Don't close sidebar when interacting with modal elements
                    const isModalInteraction = e.target.closest('.modal') ||
                        e.target.closest('.modal-overlay') ||
                        e.target.classList.contains('modal-btn') ||
                        e.target.closest('.modal-btn');

                    if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target) && !isModalInteraction) {
                        sidebar.classList.remove('active');
                        document.body.classList.remove('sidebar-open');
                    }
                }
            });

            // Handle window resize
            window.addEventListener('resize', () => {
                if (window.innerWidth > 768) {
                    // Remove sidebar-open class on larger screens
                    document.body.classList.remove('sidebar-open');
                } else if (sidebar.classList.contains('active')) {
                    // Add sidebar-open class on smaller screens if sidebar is active
                    document.body.classList.add('sidebar-open');
                }
            });

            // Show logout confirmation modal
            logoutBtn.addEventListener('click', () => {
                logoutModal.classList.add('active');
                document.body.classList.add('modal-open');
            });

            // Handle cancel logout
            cancelLogout.addEventListener('click', () => {
                logoutModal.classList.remove('active');
                document.body.classList.remove('modal-open');
            });

            // Handle confirm logout
            confirmLogout.addEventListener('click', async () => {
                try {
                    await supabase.auth.signOut();
                    logoutModal.classList.remove('active');
                    document.body.classList.remove('modal-open');
                } catch (error) {
                    console.error('Error signing out:', error);
                }
            });

            // Close modal with Escape key only
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && logoutModal.classList.contains('active')) {
                    logoutModal.classList.remove('active');
                    document.body.classList.remove('modal-open');
                }
                if (e.key === 'Escape' && removeFriendModal.classList.contains('active')) {
                    removeFriendModal.classList.remove('active');
                    document.body.classList.remove('modal-open');
                    window.friendToRemove = null;
                }
            });

            // Remove friend modal
            const cancelRemoveFriend = document.getElementById('cancelRemoveFriend');
            const confirmRemoveFriend = document.getElementById('confirmRemoveFriend');
            const removeFriendModal = document.getElementById('removeFriendModal');
            
            cancelRemoveFriend.addEventListener('click', () => {
                removeFriendModal.classList.remove('active');
                document.body.classList.remove('modal-open');
                window.friendToRemove = null;
            });
            
            confirmRemoveFriend.addEventListener('click', async () => {
                await window.confirmRemoveFriend();
            });

            // Search functionality
            const searchBtn = document.getElementById('searchBtn');
            const friendSearch = document.getElementById('friendSearch');
            
            searchBtn.addEventListener('click', () => window.searchFriends());
            friendSearch.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    window.searchFriends();
                }
            });

            // Fetch top players
            try {
                const { data: topPlayers, error: topPlayersError } = await supabase
                    .from('profiles')
                    .select('username, full_name, points, field, avatar_url')
                    .order('points', { ascending: false }) // Order by points in descending order
                    .limit(4);

                if (topPlayersError) {
                    console.error('Error fetching top players:', topPlayersError);

                    if (topPlayersError.code === '42703') { // If "points" column doesn't exist
                        console.log('Points column not found, fetching players without points sorting');
                        const { data: altPlayers, error: altPlayersError } = await supabase
                            .from('profiles')
                            .select('username, full_name, field, avatar_url')
                            .is('is_complete', true)
                            .limit(4);

                        if (altPlayersError) {
                            console.error('Error fetching alternative players:', altPlayersError);
                            return;
                        }

                        renderPlayers(altPlayers);
                    }
                } else if (topPlayers && topPlayers.length > 0) {
                    renderPlayers(topPlayers);
                }
            } catch (error) {
                console.error('Error fetching top players:', error);
            }

            function renderPlayers(players) {
                const topPlayersList = document.getElementById('topPlayersList');
                topPlayersList.innerHTML = '';

                players.forEach((player, index) => {
                    const rankClass = index < 3 ? `rank-${index + 1}` : '';
                    const playerName = player.full_name || player.username || 'Anonymous';
                    const playerScore = player.points || 0;
                    let playerAvatar = player.avatar_url || '../assets/default.png';

                    // Ensure avatar URL is absolute if using Supabase Storage
                    if (player.avatar_url && !player.avatar_url.startsWith('http')) {
                        playerAvatar = `https://your-supabase-url.storage/v1/object/public/avatars/${player.avatar_url}`;
                    }

                    topPlayersList.innerHTML += `
            <div class="player-item">
                <div class="player-avatar">
                    <img src="${playerAvatar}" class="avatar-img" onerror="this.src='../assets/default.png'">
                </div>
                <div class="player-info">
                    <span class="player-name">${playerName}</span>
                    <span class="player-score">${playerScore}</span>
                </div>
            </div>
        `;
                });
            }
        });

        // Battle System Implementation
        const battleNavItem = document.getElementById('battleNavItem');
        const quickBattleBtn = document.querySelector('.quick-battle-btn');
        const battleSelectionModal = document.getElementById('battleSelectionModal');
        const closeBattleModal = document.getElementById('closeBattleModal');
        
        // Step buttons
        const nextStep1 = document.getElementById('nextStep1');
        const nextStep2 = document.getElementById('nextStep2');
        const backStep2 = document.getElementById('backStep2');
        const backStep3 = document.getElementById('backStep3');
        const startBattle = document.getElementById('startBattle');
        
        // Selection elements
        const subjectOptions = document.querySelectorAll('.subject-option');
        const questionOptions = document.querySelectorAll('.question-option');
        const difficultyOptions = document.querySelectorAll('.difficulty-option');
        
        // Step content elements
        const battleStep1 = document.getElementById('battleStep1');
        const battleStep2 = document.getElementById('battleStep2');
        const battleStep3 = document.getElementById('battleStep3');
        
        // Battle steps
        const battleSteps = document.querySelectorAll('.battle-step');
        
        // Matchmaking elements
        const matchmakingModal = document.getElementById('matchmakingModal');
        const closeMatchmakingModal = document.getElementById('closeMatchmakingModal');
        const cancelMatchmaking = document.getElementById('cancelMatchmaking');
        const retryMatchmaking = document.getElementById('retryMatchmaking');
        const cancelNoOpponent = document.querySelector('#cancelNoOpponent');
        const readyButton = document.getElementById('readyButton');
        const searchTimer = document.getElementById('searchTimer');
        const readyCountdown = document.getElementById('readyCountdown');
        
        // States for selections
        const battleConfig = {
            subjects: [],
            questions: null,
            difficulty: null
        };
        
        // Show battle modal
        function showBattleModal() {
            // Reset to first step
            goToStep(1);
            
            // Reset selections
            battleConfig.subjects = [];
            battleConfig.questions = null;
            battleConfig.difficulty = null;
            
            // Reset UI states
            subjectOptions.forEach(option => option.classList.remove('selected'));
            questionOptions.forEach(option => option.classList.remove('selected'));
            difficultyOptions.forEach(option => option.classList.remove('selected'));
            
            // Disable next buttons
            nextStep1.disabled = true;
            nextStep2.disabled = true;
            startBattle.disabled = true;
            
            // Show the modal
            battleSelectionModal.classList.add('active');
            document.body.classList.add('modal-open');
        }
        
        // Add event listeners for opening battle modal
        if (battleNavItem) {
            battleNavItem.addEventListener('click', function(e) {
                e.preventDefault();
                showBattleModal();
            });
        }
        
        // Add informational message for the quick battle button
        if (quickBattleBtn) {
            quickBattleBtn.addEventListener('click', function() {
                // Start a quick battle
                startQuickBattle();
            });
        }
        
        // Function to start a quick battle
        function startQuickBattle() {
            // Reset matchmaking UI
            const searchingState = matchmakingModal.querySelector('.searching-state');
            const opponentFoundState = matchmakingModal.querySelector('.opponent-found-state');
            const noOpponentState = matchmakingModal.querySelector('.no-opponent-state');
            
            searchingState.style.display = 'block';
            opponentFoundState.style.display = 'none';
            noOpponentState.style.display = 'none';
            
            // Show matchmaking modal
            matchmakingModal.classList.add('active');
            document.body.classList.add('modal-open');
            
            // Set the battle type to 'quick'
            battleConfig.battleType = 'quick';
            
            // Initialize WebSocket connection
            initBattleSocket();
        }
        
        // Close battle modal
        if (closeBattleModal) {
            closeBattleModal.addEventListener('click', function() {
                battleSelectionModal.classList.remove('active');
                document.body.classList.remove('modal-open');
            });
        }
        
        // Handle subject selection
        subjectOptions.forEach(option => {
            option.addEventListener('click', function() {
                const subject = this.getAttribute('data-subject');
                
                // Toggle selection
                if (this.classList.contains('selected')) {
                    this.classList.remove('selected');
                    battleConfig.subjects = battleConfig.subjects.filter(s => s !== subject);
                } else {
                    this.classList.add('selected');
                    battleConfig.subjects.push(subject);
                }
                
                // Enable/disable next button based on selection
                nextStep1.disabled = battleConfig.subjects.length === 0;
            });
        });
        
        // Handle question count selection
        questionOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                questionOptions.forEach(opt => opt.classList.remove('selected'));
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Store selection
                battleConfig.questions = parseInt(this.getAttribute('data-questions'));
                
                // Enable next button
                nextStep2.disabled = false;
            });
        });
        
        // Handle difficulty selection
        difficultyOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                difficultyOptions.forEach(opt => opt.classList.remove('selected'));
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Store selection
                battleConfig.difficulty = this.getAttribute('data-difficulty');
                
                // Enable start battle button
                startBattle.disabled = false;
            });
        });
        
        // Navigation between steps
        function goToStep(stepNumber) {
            // Update step indicators
            battleSteps.forEach(step => {
                const dataStep = parseInt(step.getAttribute('data-step'));
                if (dataStep === stepNumber) {
                    step.classList.add('active');
                } else {
                    step.classList.remove('active');
                }
            });
            
            // Hide all step content
            battleStep1.classList.remove('active');
            battleStep2.classList.remove('active');
            battleStep3.classList.remove('active');
            
            // Show current step content
            if (stepNumber === 1) {
                battleStep1.classList.add('active');
            } else if (stepNumber === 2) {
                battleStep2.classList.add('active');
            } else if (stepNumber === 3) {
                battleStep3.classList.add('active');
            }
        }
        
        // Step navigation handlers
        nextStep1.addEventListener('click', () => goToStep(2));
        nextStep2.addEventListener('click', () => goToStep(3));
        backStep2.addEventListener('click', () => goToStep(1));
        backStep3.addEventListener('click', () => goToStep(2));
        
        // Start battle handler
        startBattle.addEventListener('click', function() {
            // Hide battle selection modal
            battleSelectionModal.classList.remove('active');
            
            // Show matchmaking confirmation
            showMatchmakingConfirmation();
        });
        
        // Matchmaking confirmation
        function showMatchmakingConfirmation() {
            // Create and show matchmaking confirmation modal
            const confirmationModalHtml = `
                <div class="modal-overlay active" id="confirmMatchmakingModal">
                    <div class="modal">
                        <div class="modal-icon">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <h3>Find a Match</h3>
                        <p>There are <strong id="activePlayerCount">0</strong> players currently looking for a match.</p>
                        <p>Would you like to start matchmaking?</p>
                        <div class="modal-buttons">
                            <button class="modal-btn cancel" id="cancelConfirmMatchmaking">
                                <i class="fas fa-times"></i>No
                            </button>
                            <button class="modal-btn confirm" id="confirmMatchmaking">
                                <i class="fas fa-check"></i>Yes
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Append modal to body
            document.body.insertAdjacentHTML('beforeend', confirmationModalHtml);
            document.body.classList.add('modal-open');
            
            // Get active player count
            fetchActivePlayerCount()
                .then(count => {
                    document.getElementById('activePlayerCount').textContent = count;
                })
                .catch(error => {
                    console.error('Error fetching active player count:', error);
                    document.getElementById('activePlayerCount').textContent = '0';
                });
            
            // Add event listeners for confirmation
            document.getElementById('cancelConfirmMatchmaking').addEventListener('click', function() {
                document.getElementById('confirmMatchmakingModal').remove();
                document.body.classList.remove('modal-open');
            });
            
            document.getElementById('confirmMatchmaking').addEventListener('click', function() {
                document.getElementById('confirmMatchmakingModal').remove();
                startMatchmaking();
            });
        }
        
        // Fetch active player count (stub - replace with actual Supabase query)
        async function fetchActivePlayerCount() {
            // Replace with actual Supabase query to get active players looking for a match
            return new Promise(resolve => {
                setTimeout(() => {
                    resolve(Math.floor(Math.random() * 20) + 5); // Random number between 5-25
                }, 500);
            });
        }
        
        // Start matchmaking
        function startMatchmaking() {
            // Reset matchmaking UI
            const searchingState = matchmakingModal.querySelector('.searching-state');
            const opponentFoundState = matchmakingModal.querySelector('.opponent-found-state');
            const noOpponentState = matchmakingModal.querySelector('.no-opponent-state');
            
            searchingState.style.display = 'block';
            opponentFoundState.style.display = 'none';
            noOpponentState.style.display = 'none';
            
            // Show matchmaking modal
            matchmakingModal.classList.add('active');
            document.body.classList.add('modal-open');
            
            // Initialize WebSocket connection
            initBattleSocket();
        }
        
        // WebSocket variables
        let battleSocket = null;
        let matchId = null; // Initialize matchId variable
        
        // Initialize WebSocket connection
        function initBattleSocket() {
            try {
                console.log('Initializing WebSocket connection...');
                
                // Close existing connection if any
                if (battleSocket) {
                    console.log('Closing existing WebSocket connection');
                    battleSocket.close();
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
                battleSocket = new WebSocket(websocketUrl);
                console.log('WebSocket created:', battleSocket);
                
                // Connection opened
                battleSocket.addEventListener('open', function(event) {
                    console.log('Connected to battle server');
                    
                    // Update connection status
                    if (document.getElementById('connectionStatusIndicator')) {
                        document.getElementById('connectionStatusIndicator').classList.remove('offline');
                        document.getElementById('connectionStatusIndicator').classList.add('online');
                        document.getElementById('connectionStatusText').textContent = 'Connected';
                    }
                    
                    // Login with current user info
                    loginToBattleServer();
                });
                
                // Connection closed
                battleSocket.addEventListener('close', function(event) {
                    console.log('Disconnected from battle server');
                    showToast('Disconnected from battle server', 'You have been disconnected from the battle server', 'error');
                    
                    // Show no opponent found if disconnected during search
                    const searchingState = matchmakingModal.querySelector('.searching-state');
                    if (searchingState && searchingState.style.display === 'block') {
                        showNoOpponentFound();
                    }
                });
                
                // Connection error
                battleSocket.addEventListener('error', function(event) {
                    console.error('WebSocket error:', event);
                    showNoOpponentFound();
                });
                
                // Listen for messages
                battleSocket.addEventListener('message', function(event) {
                    try {
                        const data = JSON.parse(event.data);
                        handleBattleServerMessage(data);
                    } catch (error) {
                        console.error('Error parsing server message:', error);
                    }
                });
            } catch (error) {
                console.error('Error initializing WebSocket:', error);
                showNoOpponentFound();
            }
        }
        
        // Login to battle server
        async function loginToBattleServer() {
            try {
                const { data: { user }, error: userError } = await supabase.auth.getUser();
                if (userError || !user) {
                    console.error('User not authenticated:', userError);
                    return;
                }
                
                // Get the current session to access the access token
                const { data: { session }, error: sessionError } = await supabase.auth.getSession();
                if (sessionError || !session) {
                    console.error('No active session:', sessionError);
                    return;
                }
                
                // Get user profile
                const { data: profile } = await supabase
                    .from('profiles')
                    .select('username, full_name, avatar_url')
                    .eq('user_id', user.id)
                    .single();
                
                if (!profile) {
                    console.error('User profile not found');
                    return;
                }
                
                // Send login info to server with authentication token
                sendToServer({
                    action: 'login',
                    token: session.access_token, // Include the Supabase access token
                    userId: user.id,
                    username: profile.full_name || profile.username || 'Player',
                    avatar: profile.avatar_url || '../assets/default.png',
                    battleType: battleConfig.battleType || 'arena' // Include battle type in login
                });
                
            } catch (error) {
                console.error('Error logging in to battle server:', error);
            }
        }
        
        // Send message to server
        function sendToServer(message) {
            if (battleSocket && battleSocket.readyState === WebSocket.OPEN) {
                console.log('Sending message to server:', message);
                battleSocket.send(JSON.stringify(message));
            } else {
                console.error('WebSocket is not connected, readyState:', battleSocket ? battleSocket.readyState : 'null');
            }
        }
        
        // Handle messages from server
        function handleBattleServerMessage(data) {
            console.log('Received from server:', data);
            
            switch (data.action) {
                case 'login_success':
                    // After successful login, find a match
                    findMatch();
                    break;
                    
                case 'matchmaking_started':
                    console.log('Matchmaking started:', data.message);
                    break;
                    
                case 'matchmaking_cancelled':
                    console.log('Matchmaking cancelled:', data.message);
                    break;
                    
                case 'ping':
                    // Respond to server ping to keep connection alive
                    console.log('Received ping from server, sending pong response');
                    sendToServer({ action: 'pong' });
                    break;
                    
                case 'pong':
                    // Handle ping response
                    break;
                    
                case 'opponent_disconnected':
                    console.log('Opponent disconnected:', data.message);
                    showToast('warning', 'Opponent Disconnected', data.message);
                    break;
                    
                case 'match_found':
                    // Show opponent found state with real opponent data
                    matchId = data.battle_id;
                    
                    // Update match found message based on battle type
                    const matchFoundMsg = document.querySelector('.opponent-found-state h3');
                    if (matchFoundMsg) {
                        const battleType = data.battleType || battleConfig.battleType || 'arena';
                        matchFoundMsg.textContent = battleType === 'quick' 
                            ? 'Quick Battle Opponent Found!' 
                            : 'Battle Arena Opponent Found!';
                    }
                    
                    showOpponentFound(data.opponent);
                    break;
                    
                case 'opponent_ready':
                    // Show that opponent is ready
                    showOpponentReady();
                    break;
                    
                case 'both_ready':
                    // Both players are ready, start countdown to battle
                    startBattleCountdown();
                    break;
                    
                case 'battle_start':
                    // Redirect to battle page
                    redirectToBattle();
                    break;
                    
                case 'error':
                    console.error('Server error:', data.message);
                    // Show relevant error message to user
                    showToast('error', 'Server error', data.message);
                    break;
                    
                default:
                    // Handle messages without explicit action field (battle data, results, etc.)
                    console.log('Received message without action field:', data);
                    break;
            }
        }
        
        // Find a match
        function findMatch() {
            console.log('findMatch() called, battleConfig:', battleConfig);
            
            // Set default values for quick battle if not specified
            if (battleConfig.battleType === 'quick') {
                // For quick battles, default to 3 questions and medium difficulty
                if (!battleConfig.questions) battleConfig.questions = 3;
                if (!battleConfig.difficulty) battleConfig.difficulty = 'medium';
                if (!battleConfig.subjects || !battleConfig.subjects.length) {
                    // Default to general subject if not specified
                    battleConfig.subjects = ['general'];
                }
            }
            
            console.log('Sending find_match request with config:', battleConfig);
            sendToServer({
                action: 'find_match',
                config: battleConfig,
                battleType: battleConfig.battleType || 'arena' // Send battle type to server
            });
            
            // Start countdown
            let timeLeft = 60; // Longer timeout for real server
            const searchTimer = document.getElementById('searchTimer');
            searchTimer.textContent = timeLeft;
            
            const countdownInterval = setInterval(() => {
                timeLeft--;
                searchTimer.textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    
                    // Cancel matchmaking on server
                    sendToServer({
                        action: 'cancel_matchmaking'
                    });
                    
                    showNoOpponentFound();
                }
            }, 1000);
            
            // Store interval ID to clear on cancel
            matchmakingModal.dataset.countdownInterval = countdownInterval;
        }
        
        // Cancel matchmaking
        if (cancelMatchmaking) {
            cancelMatchmaking.addEventListener('click', function() {
                clearInterval(parseInt(matchmakingModal.dataset.countdownInterval));
                
                // Tell server to cancel matchmaking
                if (battleSocket && battleSocket.readyState === WebSocket.OPEN) {
                    sendToServer({
                        action: 'cancel_matchmaking'
                    });
                }
                
                matchmakingModal.classList.remove('active');
                document.body.classList.remove('modal-open');
            });
        }
        
        // Show opponent found state with real opponent data
        function showOpponentFound(opponent) {
            const searchingState = matchmakingModal.querySelector('.searching-state');
            const opponentFoundState = matchmakingModal.querySelector('.opponent-found-state');
            
            // Update opponent info with real data
            document.getElementById('opponentName').textContent = opponent.username;
            document.getElementById('opponentAvatar').src = opponent.avatar || '../assets/default.png';
            
            // Show opponent found state
            searchingState.style.display = 'none';
            opponentFoundState.style.display = 'block';
            
            // Reset ready button
            readyButton.disabled = false;
            readyButton.classList.remove('ready');
            readyButton.innerHTML = '<i class="fas fa-check"></i><span>Ready</span>';
        }
        
        // Ready button handler
        if (readyButton) {
            readyButton.addEventListener('click', function() {
                // Mark as ready
                this.classList.add('ready');
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-check-circle"></i><span>Ready!</span>';
                
                // Tell server player is ready
                sendToServer({
                    action: 'confirm_match',
                    matchId: matchId
                });
            });
        }
        
        // Start battle countdown
        function startBattleCountdown() {
            const readyCountdownElement = document.getElementById('readyCountdown');
            readyCountdownElement.style.display = 'block';
            
            let count = 3;
            readyCountdownElement.querySelector('span').textContent = count;
            
            const countInterval = setInterval(() => {
                count--;
                readyCountdownElement.querySelector('span').textContent = count;
                
                if (count <= 0) {
                    clearInterval(countInterval);
                    redirectToBattle();
                }
            }, 1000);
        }
        
        // Redirect to battle page
        function redirectToBattle() {
            // Build query params for battle configuration
            const queryParams = new URLSearchParams({
                matchId: matchId,
                subjects: battleConfig.subjects.join(','),
                questions: battleConfig.questions || (battleConfig.battleType === 'quick' ? 3 : 5),
                difficulty: battleConfig.difficulty || 'medium'
            });
            
            // Redirect to battle page based on battle type
            if (battleConfig.battleType === 'quick') {
                // Redirect to quick battle page
                window.location.href = `quick_battle_mode.php?${queryParams.toString()}`;
            } else {
                // Redirect to regular battle page
                window.location.href = `battle.php?${queryParams.toString()}`;
            }
        }

        // Close matchmaking modal
        if (closeMatchmakingModal) {
            closeMatchmakingModal.addEventListener('click', function() {
                clearInterval(parseInt(matchmakingModal.dataset.countdownInterval));
                
                // Tell server to cancel matchmaking
                if (battleSocket && battleSocket.readyState === WebSocket.OPEN) {
                    sendToServer({
                        action: 'cancel_matchmaking'
                    });
                }
                
                matchmakingModal.classList.remove('active');
                document.body.classList.remove('modal-open');
            });
        }

        // Show no opponent found state
        function showNoOpponentFound() {
            const searchingState = matchmakingModal.querySelector('.searching-state');
            const noOpponentState = matchmakingModal.querySelector('.no-opponent-state');
            
            searchingState.style.display = 'none';
            noOpponentState.style.display = 'block';
        }

        // Retry matchmaking
        if (retryMatchmaking) {
            retryMatchmaking.addEventListener('click', function() {
                startMatchmaking();
            });
        }

        // Cancel no opponent
        if (cancelNoOpponent) {
            cancelNoOpponent.addEventListener('click', function() {
                matchmakingModal.classList.remove('active');
                document.body.classList.remove('modal-open');
            });
        }

        // Show opponent is ready message
        function showOpponentReady() {
            // Create opponent ready indicator if it doesn't exist
            if (!document.querySelector('.opponent-ready-indicator')) {
                const opponentReadyIndicator = document.createElement('div');
                opponentReadyIndicator.className = 'opponent-ready-indicator';
                opponentReadyIndicator.innerHTML = '<i class="fas fa-check-circle"></i> Opponent is ready!';
                
                // Get the opponent-found-state container
                const opponentFoundState = matchmakingModal.querySelector('.opponent-found-state');
                opponentFoundState.appendChild(opponentReadyIndicator);
            }
        }

        // Toast notification function
        function showToast(title, message, type = 'info') {
            // Create toast container if it doesn't exist
            let toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.style.position = 'fixed';
                toastContainer.style.top = '20px';
                toastContainer.style.right = '20px';
                toastContainer.style.zIndex = '9999';
                document.body.appendChild(toastContainer);
            }
            
            // Create toast element
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.style.backgroundColor = type === 'error' ? '#f44336' : 
                                         type === 'success' ? '#4CAF50' : 
                                         type === 'warning' ? '#ff9800' : '#2196F3';
            toast.style.color = 'white';
            toast.style.padding = '16px';
            toast.style.borderRadius = '4px';
            toast.style.marginBottom = '10px';
            toast.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
            toast.style.display = 'flex';
            toast.style.alignItems = 'flex-start';
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s ease-in-out';
            
            // Create icon based on type
            const icon = document.createElement('i');
            icon.className = type === 'error' ? 'fas fa-exclamation-circle' : 
                            type === 'success' ? 'fas fa-check-circle' : 
                            type === 'warning' ? 'fas fa-exclamation-triangle' : 'fas fa-info-circle';
            icon.style.marginRight = '12px';
            icon.style.fontSize = '24px';
            
            // Create content div
            const content = document.createElement('div');
            content.style.flex = '1';
            
            // Create title
            const titleEl = document.createElement('div');
            titleEl.textContent = title;
            titleEl.style.fontWeight = 'bold';
            titleEl.style.marginBottom = '5px';
            
            // Create message
            const messageEl = document.createElement('div');
            messageEl.textContent = message;
            messageEl.style.fontSize = '14px';
            
            // Create close button
            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '&times;';
            closeBtn.style.backgroundColor = 'transparent';
            closeBtn.style.border = 'none';
            closeBtn.style.color = 'white';
            closeBtn.style.fontSize = '20px';
            closeBtn.style.marginLeft = '10px';
            closeBtn.style.cursor = 'pointer';
            closeBtn.style.padding = '0 5px';
            closeBtn.onclick = function() {
                toast.style.opacity = '0';
                setTimeout(() => {
                    toastContainer.removeChild(toast);
                }, 300);
            };
            
            // Assemble toast
            content.appendChild(titleEl);
            content.appendChild(messageEl);
            toast.appendChild(icon);
            toast.appendChild(content);
            toast.appendChild(closeBtn);
            
            // Add to container
            toastContainer.appendChild(toast);
            
            // Fade in
            setTimeout(() => {
                toast.style.opacity = '1';
            }, 10);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => {
                    if (toastContainer.contains(toast)) {
                        toastContainer.removeChild(toast);
                    }
                }, 300);
            }, 5000);
        }

        // Check for error parameters
        const params = new URLSearchParams(window.location.search);
        if (params.has('error')) {
            const errorType = params.get('error');
            if (errorType === 'direct_access_denied') {
                showToast('Access Denied', 'You cannot access the battle page directly. Please start a new battle or join an existing one.', 'error');
                
                // Remove the error parameter from URL without reloading the page
                const newUrl = window.location.pathname + window.location.hash;
                window.history.replaceState({}, document.title, newUrl);
            }
        }
    </script>
</body>

</html> 
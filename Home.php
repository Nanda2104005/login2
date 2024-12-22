<?php

session_start();

// database_config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Default XAMPP username
define('DB_PASS', '');              // Default XAMPP has no password
define('DB_NAME', 'user_database'); // Your database name

require_once 'sessioncheck.php';
// Connection function
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Update user's online status
function updateUserStatus($userId, $status) {
    $conn = connectDB();
    $status = $status ? 1 : 0;
    
    $sql = "UPDATE users SET 
            is_online = ?, 
            last_activity = CURRENT_TIMESTAMP,
            status_updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $status, $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

function cekAkses($roleYangDiizinkan) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $roleYangDiizinkan) {
        header("Location: akses_ditolak.php");
        exit();
    }
}

// Get all online users
function getOnlineUsers() {
    $conn = connectDB();
    // Consider users offline if no activity for 5 minutes
    $sql = "SELECT id, full_name, class, is_online 
            FROM users 
            WHERE last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
            OR is_online = 1 
            ORDER BY is_online DESC, full_name ASC";
            
    $result = $conn->query($sql);
    $users = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
    $conn->close();
    return $users;
}

// Auto-update status based on activity
function autoUpdateStatus() {
    $conn = connectDB();
    // Set users as offline if inactive for more than 5 minutes
    $sql = "UPDATE users 
            SET is_online = 0 
            WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
            AND is_online = 1";
    
    $conn->query($sql);
    $conn->close();
}

if ($_SERVER['PHP_SELF'] === '/manajemen_kesehatan.php' || 
    $_SERVER['PHP_SELF'] === '/monitoringkesehatan.php' || 
    $_SERVER['PHP_SELF'] === '/rekamkesehatan.php' || 
    $_SERVER['PHP_SELF'] === '/analisiskesehatan.php') {
    cekAkses('admin');
}

// Database connection for slider
$conn = connectDB();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M3 Care - Modern Healthcare System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
         @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
        
        :root {
            --primary-color: #1ca883;
            --primary-dark: #158a6d;
            --secondary-color: #f0f9f6;
            --accent-color: #ff6b6b;
            --text-color: #2c3e50;
            --card-hover: #e8f5f1;
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.18);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--secondary-color);
            color: var(--text-color);
            line-height: 1.6;
            overflow-x: hidden;
            margin-right: 300px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* Animation styles */
        .bg-animation {
            position: fixed;
            width: 100vw;
            height: 100vh;
            top: 0;
            left: 0;
            z-index: -1;
            background: linear-gradient(45deg, rgba(28, 168, 131, 0.1), rgba(255, 107, 107, 0.1));
        }

        .bg-animation::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, var(--primary-color) 0%, transparent 70%);
            top: -300px;
            left: -300px;
            opacity: 0.1;
            animation: float 15s infinite alternate;
        }

        .bg-animation::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, var(--accent-color) 0%, transparent 70%);
            bottom: -250px;
            right: -250px;
            opacity: 0.1;
            animation: float 20s infinite alternate-reverse;
        }

        /* Navigation styles */
        .navbar {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 2rem;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
            padding-right: calc(300px + 2rem);
        }

        .navbar-content {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
        .navbar-brand {
    color: var(--primary-color);
    font-size: 1.8rem;
    font-weight: 700;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

        .navbar-links {
    display: flex;
    align-items: center;
    gap: 2rem;
}

        .nav-link {
    color: var(--text-color);
    text-decoration: none;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 12px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nav-link:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
}

        .btn-logout {
    background: linear-gradient(135deg, var(--accent-color), #ff8f8f);
    color: white;
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    position: absolute;
    top: 1rem;
    right: 2rem;
}

.btn-logout:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
}

        /* Hero Section */
        .hero {
            margin-top: 5rem;
            padding: 8rem 2rem;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, var(--primary-color), #159f7f);
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            color: white;
        }

        .hero-text h1 {
            font-size: 3.5rem;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }

        .hero-text p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .hero-image {
            position: relative;
            animation: float-slow 6s infinite ease-in-out;
        }

        .hero-image img {
            width: 100%;
            height: auto;
        }

        @keyframes float-slow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        /* Feature Cards */
        .features {
            padding: 4rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            margin-top: 2rem;
        }

        .feature-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: translateX(-100%);
            transition: 0.5s;
        }

        .feature-card:hover::before {
            transform: translateX(100%);
        }

        .feature-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(28, 168, 131, 0.15);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(-10deg);
            color: var(--accent-color);
        }

        .feature-card h3 {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .feature-card p {
            color: var(--text-color);
            opacity: 0.8;
            font-size: 1.1rem;
        }

        /* Stats Section */
        .stats {
            padding: 4rem 2rem;
            background: linear-gradient(135deg, var(--primary-color), #159f7f);
            clip-path: polygon(0 15%, 100% 0, 100% 85%, 0 100%);
            color: white;
            text-align: center;
        }

        .stats-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 3rem;
            padding: 2rem 0;
        }

        .stat-item h3 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .stat-item p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

       /* Footer */
footer {
    background: var(--primary-dark);
    color: white;
    padding: 2rem 0;
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.social-links {
    display: flex;
    gap: 1rem;
}

.social-link {
    color: white;
    text-decoration: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}

.social-link:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

/* Responsive Design */
@media (max-width: 768px) {
    .download-container {
        text-align: center;
        justify-content: center;
    }

    .download-buttons {
        justify-content: center;
    }

    .footer-content {
        flex-direction: column;
        text-align: center;
    }

    .social-links {
        margin-top: 1rem;
    }
}

        .status-sidebar {
            position: fixed;
            right: -300px; /* Start hidden */
            top: 0;
            width: 300px;
            height: 100vh;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-left: 1px solid var(--glass-border);
            padding: 6rem 1rem 1rem 1rem;
            z-index: 999;
            overflow-y: auto;
            transition: right 0.3s ease-in-out;
        }

        .status-sidebar.open{

            right: 0;
        }

        .status-toggle {
            position: fixed;
            right: 20px;
            top: 85px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.8rem;
            cursor: pointer;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(28, 168, 131, 0.2);
        }

        .status-toggle:hover {
            transform: translateY(-2px);
            background: var(--primary-dark);
            box-shadow: 0 6px 20px rgba(28, 168, 131, 0.3);
        }

        .status-toggle i {
            transition: transform 0.3s ease;
        }

        .status-toggle.open i {
            transform: rotate(180deg);
        }

        .status-header {
            color: var(--primary-color);
            padding: 1rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--glass-border);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .student-status {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .student-status:hover {
            transform: translateX(-5px);
            background: rgba(255, 255, 255, 0.8);
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 1rem;
        }

        .online {
            background-color: var(--primary-color);
            box-shadow: 0 0 5px var(--primary-color);
        }

        .offline {
            background-color: var(--accent-color);
            opacity: 0.5;
        }

        .student-info {
            flex: 1;
        }

        .student-name {
            font-weight: 500;
            color: var(--text-color);
        }

        .student-class {
            font-size: 0.8rem;
            color: var(--text-color);
            opacity: 0.7;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .hero-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .status-sidebar {
                width: 280px;
                right: -280px;
            }
            body.sidebar-open {
                margin-right: 0; /* Don't shift content on mobile */
            }
            .navbar.sidebar-open {
                padding-right: 2rem; /* Don't adjust navbar on mobile */
            }
        
            }

            body {
                margin-right: 0;
                transition: margin-right 0.3s ease-in-out;
            }

            .navbar {
                padding-right: calc(250px + 2rem);
            }

            body.sidebar-open {
            margin-right: 300px;
        }
           
            

            .hero-image {
                order: -1;
                margin: 0 auto;
                max-width: 500px;
            }

            .hero-text h1 {
                font-size: 2.8rem;
            }
        

            @media (max-width: 768px) {
            body {
                margin-right: 0 !important; /* Override margin untuk mobile */
            }
            
            .navbar {
                padding-right: 1rem !important; /* Reset padding navbar */
            }
            
            .navbar-content {
                position: relative;
            }
            
            .navbar-links {
                display: none; /* Sembunyikan menu secara default */
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--glass-bg);
                padding: 1rem;
                flex-direction: column;
                gap: 0.5rem;
                border-radius: 0 0 12px 12px;
            }
            
            .navbar-links.show {
                display: flex;
            }
            
            .menu-toggle {
                display: block;
                background: none;
                border: none;
                color: var(--primary-color);
                font-size: 1.5rem;
                cursor: pointer;
                padding: 0.5rem;
                position: absolute;
                right: 1rem;
                top: 50%;
                transform: translateY(-50%);
            }
            
            .btn-logout {
                position: static;
                width: 100%;
                justify-content: center;
                margin-top: 0.5rem;
            }
            
            .hero-content {
                grid-template-columns: 1fr;
                padding: 2rem 1rem;
                text-align: center;
            }
            
            .hero-image {
                order: -1;
                margin: 0 auto 2rem;
            }
            
            .hero-image img {
                max-width: 200px;
                margin: 0 auto;
            }
            
            .features-grid {
                padding: 0 1rem;
            }
            
            .feature-card {
                flex: 0 0 280px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
                padding: 1rem;
            }
            
            .status-sidebar {
                width: 100%;
                right: -100%;
            }
            
            .status-sidebar.open {
                right: 0;
            }
        }

        /* Tambahan untuk tablet */
        @media (min-width: 769px) and (max-width: 1024px) {
            body {
                margin-right: 0;
            }
            
            .hero-content {
                gap: 2rem;
                padding: 4rem 2rem;
            }
            
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .btn-start {
    background: linear-gradient(135deg, var(--accent-color), #ff8f8f);
    color: white;
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    text-decoration: none;
}

       .btn-start:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
}

/* Responsive Design untuk Feature Cards */
@media (max-width: 1200px) {
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .features-grid {
        grid-template-columns: 1fr;
        padding: 0;
    }
    
    .feature-card {
        margin: 0 auto;
        max-width: 100%;
    }
}

.navbar-brand img {
            width: 40px; /* Adjust size as needed */
            height: 40px;
            margin-right: 0.5rem;
            vertical-align: middle;
        }

        .navbar-brand {
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

    
        
        .slider-container {
    position: relative;
    width: 100%;
    max-width: 1000px; /* Reduced max-width for single slide */
    margin: 4rem auto;
    overflow: hidden;
    padding: 0;
    height: 500px; /* Increased height */
}

.slider-wrapper {
    display: flex;
    transition: transform 0.5s ease-in-out;
    height: 100%;
}

.slide {
    flex: 0 0 100%; /* Changed to 100% for full width */
    width: 100%;
    position: relative;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    transition: transform 0.3s ease;
    height: 100%;
}

.slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.slide:hover img {
    transform: scale(1.02);
}

.slide-caption {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
    color: white;
    padding: 1rem;
    font-size: 1rem;
    text-align: center;
}

.slider-button {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.9);
    border: none;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    cursor: pointer;
    z-index: 10;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: var(--primary-color);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.slider-button:hover {
    background: white;
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
}

.prev { left: 20px; }
.next { right: 20px; }

@media (max-width: 768px) {
    .slider-container {
        height: 300px;
        margin: 2rem auto;
    }
    
    .slider-button {
        width: 35px;
        height: 35px;
        font-size: 1rem;
    }

    .modal-content.zoomed {
        transform: scale(1.2);
    }
}

.prev { left: 10px; }
.next { right: 10px; }

.slider-dots {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 8px;
    z-index: 10;
}

.dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.3s ease;
}

.dot.active {
    background: var(--primary-color);
    transform: scale(1.2);
}

.image-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.95);
    backdrop-filter: blur(8px);
}

.modal-content {
    margin: auto;
    display: block;
    max-width: 90%;
    max-height: 90vh;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    animation: zoomIn 0.4s ease-out;
    cursor: zoom-in;
}

.modal-content.zoomed {
    transform: scale(1.5);
    cursor: zoom-out;
    transition: transform 0.3s ease;
}

@keyframes zoomIn {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}


.close-modal {
    position: absolute;
    right: 25px;
    top: 15px;
    color: #fff;
    font-size: 35px;
    font-weight: bold;
    cursor: pointer;
    opacity: 0.8;
    transition: all 0.3s ease;
    background: rgba(0, 0, 0, 0.5);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-modal:hover {
    opacity: 1;
    transform: scale(1.1);
}

#modalCaption {
    margin: 20px auto;
    width: 80%;
    text-align: center;
    color: white;
    padding: 10px;
    font-size: 1.2em;
    background: rgba(0, 0, 0, 0.5);
    border-radius: 8px;
    font-weight: 500;
}

@keyframes zoom {
    from { transform: scale(0); }
    to { transform: scale(1); }
}

@media (max-width: 768px) {
    .slider-container {
        height: 300px;
    }
    
    .slide {
        flex: 0 0 100%;
        height: 300px;
    }
    
    .slider-button {
        width: 32px;
        height: 32px;
        font-size: 1rem;
    }
}

/* Download Section */
.download-section {
    background: linear-gradient(135deg, var(--primary-color), #159f7f);
    padding: 3rem 0;
    margin-top: 2rem;
}

.download-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 2rem;
}

.download-text {
    flex: 1;
    min-width: 300px;
    color: white;
}

.download-text h3 {
    font-size: 2rem;
    margin-bottom: 1rem;
    font-weight: 700;
}

.download-text p {
    font-size: 1.1rem;
    opacity: 0.9;
}

.download-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.download-btn {
    background: white;
    color: var(--primary-color);
    padding: 0.8rem 1.5rem;
    border-radius: 12px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.download-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.download-btn i {
    font-size: 1.5rem;
}
        
    </style>
</head>
<body>

<div class="bg-animation"></div>

<nav class="navbar">
    <div class="navbar-content">
        <a href="Home.php" class="navbar-brand">
            <img src="images/m3-logo.png" alt="M3 Care Logo">
            M3 Care
        </a>
        <div class="navbar-links">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="about.php" class="nav-link">
                <i class="fas fa-info-circle"></i>
                Tentang Kami
            </a>
            <a href="logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>
</nav>

<section class="hero">
    <div class="hero-content">
        <div class="hero-text">
            <h1>Selamat Datang di M3 Care</h1>
            <p>Sistem Informasi Kesehatan Modern untuk SMA Muhammadiyah 3 Jember. Monitoring kesehatan siswa dengan teknologi terkini untuk masa depan yang lebih sehat.</p>
            <a href="dashboard.php" class="btn-start">
                <i class="fas fa-arrow-right"></i>
                Mulai Sekarang
            </a>
        </div>
        <div class="hero-image">
            <img src="images/logo MU.png" alt="Healthcare Illustration" style="max-width: 300px; height: auto; float: right;">
        </div>
    </div>
</section>

<!-- Add Image Slider Section -->
<div class="slider-container">
        <div class="slider-wrapper">
            <?php
            // Get images from edukasi_kesehatan
            $sql = "SELECT gambar, judul FROM edukasi_kesehatan WHERE gambar IS NOT NULL";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo '<div class="slide" onclick="openModal(this)">';
                    echo '<img src="' . htmlspecialchars($row['gambar']) . '" 
                          alt="' . htmlspecialchars($row['judul']) . '" 
                          data-full-img="' . htmlspecialchars($row['gambar']) . '" 
                          data-caption="' . htmlspecialchars($row['judul']) . '">';
                    echo '<div class="slide-caption">' . htmlspecialchars($row['judul']) . '</div>';
                    echo '</div>';
                }
            }
            ?>
    </div>
        <button class="slider-button prev">&#10094;</button>
        <button class="slider-button next">&#10095;</button>
        <div class="slider-dots"></div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="image-modal">
        <span class="close-modal">&times;</span>
        <img id="modalImage" class="modal-content">
        <div id="modalCaption"></div>
    </div>

<section class="features">
    <div class="features-grid">
        <div class="feature-card">
            <i class="fas fa-user-md feature-icon"></i>
            <h3>Manajemen Kesehatan Digital</h3>
            <p>Kelola data kesehatan siswa dengan sistem digital yang terintegrasi dan mudah diakses.</p>
        </div>

        <div class="feature-card">
            <i class="fas fa-heartbeat feature-icon"></i>
            <h3>Monitoring Real-time</h3>
            <p>Pantau kondisi kesehatan siswa secara real-time dengan teknologi modern.</p>
        </div>

        <div class="feature-card">
            <i class="fas fa-notes-medical feature-icon"></i>
            <h3>Rekam Kesehatan Siswa Terpadu</h3>
            <p>Akses riwayat kesehatan lengkap dengan sistem penyimpanan yang aman.</p>
        </div>

        <div class="feature-card">
            <i class="fas fa-chart-line feature-icon"></i>
            <h3>Laporan Digital</h3>
            <p>Dapatkan insight kesehatan melalui analisis data dan laporan komprehensif.</p>
        </div>
    </div>
</section>

<section class="stats">
    <div class="stats-grid">
        <div class="stat-item">
            <h3>350+</h3>
            <p>Siswa Terpantau</p>
        </div>
        <div class="stat-item">
            <h3>24/7</h3>
            <p>Monitoring Aktif</p>
        </div>
        <div class="stat-item">
            <h3>99,9%</h3>
            <p>Data Transparan</p>
        </div>
        <div class="stat-item">
            <h3>30</h3>
            <p>Tahun Pengalaman</p>
        </div>
    </div>
</section>

<!-- Pre-footer section -->
<section class="download-section">
    <div class="download-container">
        <div class="download-text">
            <h3>Download Sekarang di Sini</h3>
            <p>Unduh aplikasi M3 Care untuk akses lebih mudah ke layanan kesehatan sekolah</p>
        </div>
        <div class="download-buttons">
            <a href="#" class="download-btn">
                <i class="fab fa-google-play"></i>
                Play Store
            </a>
        </div>
    </div>
</section>

<footer>
    <div class="footer-content">
        <div class="footer-left">
            <p>&copy; 2024 M3 Care - Sistem Informasi Kesehatan Sekolah SMA Muhammadiyah 3 Jember</p>
        </div>
        <div class="footer-right">
            <div class="social-links">
                <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
            </div>
        </div>
    </div>
</footer>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize slider elements
    const sliderContainer = document.querySelector('.slider-container');
    const sliderWrapper = document.querySelector('.slider-wrapper');
    const slides = document.querySelectorAll('.slide');
    const prevButton = document.querySelector('.prev');
    const nextButton = document.querySelector('.next');
    const dotsContainer = document.querySelector('.slider-dots');

    // Check for single slide
    const isSingleSlide = slides.length === 1;

    // Configure single slide
    if (isSingleSlide) {
        sliderWrapper.classList.add('single-slide');
        slides[0].classList.add('single');
        
        // Hide navigation
        if (prevButton) prevButton.style.display = 'none';
        if (nextButton) nextButton.style.display = 'none';
        if (dotsContainer) dotsContainer.style.display = 'none';
        
        // Center the single slide
        slides[0].style.margin = '0 auto';
        return; // Exit early as no need for slider functionality
    }

    // Slider variables
    let currentIndex = 0;
    let isTransitioning = false;
    let autoSlideInterval;

    // Create dots for navigation
    slides.forEach((_, index) => {
        const dot = document.createElement('div');
        dot.classList.add('dot');
        if (index === 0) dot.classList.add('active');
        dot.addEventListener('click', () => goToSlide(index));
        dotsContainer.appendChild(dot);
    });

    // Update active dot
    function updateDots() {
        document.querySelectorAll('.dot').forEach((dot, index) => {
            dot.classList.toggle('active', index === currentIndex);
        });
    }

    // Go to specific slide
    function goToSlide(index) {
        if (isTransitioning) return;
        isTransitioning = true;
        currentIndex = index;
        const offset = -index * 100 + '%';
        sliderWrapper.style.transform = `translateX(${offset})`;
        updateDots();
        
        setTimeout(() => {
            isTransitioning = false;
        }, 500);
    }

    // Next slide
    function nextSlide() {
        goToSlide(currentIndex === slides.length - 1 ? 0 : currentIndex + 1);
    }

    // Previous slide
    function prevSlide() {
        goToSlide(currentIndex === 0 ? slides.length - 1 : currentIndex - 1);
    }

    // Auto slide functionality
    function startAutoSlide() {
        stopAutoSlide();
        autoSlideInterval = setInterval(nextSlide, 5000);
    }

    function stopAutoSlide() {
        if (autoSlideInterval) {
            clearInterval(autoSlideInterval);
        }
    }

    // Touch handling
    let touchStartX = 0;
    let touchEndX = 0;

    sliderWrapper.addEventListener('touchstart', (e) => {
        touchStartX = e.touches[0].clientX;
        stopAutoSlide();
    }, { passive: true });

    sliderWrapper.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].clientX;
        const swipeDistance = touchStartX - touchEndX;
        
        if (Math.abs(swipeDistance) > 50) {
            if (swipeDistance > 0) {
                nextSlide();
            } else {
                prevSlide();
            }
        }
        
        startAutoSlide();
    }, { passive: true });

    // Modal functionality
    function openModal(slideElement) {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');
        const modalCaption = document.getElementById('modalCaption');
        const img = slideElement.querySelector('img');

        modal.style.display = 'block';
        modalImg.src = img.dataset.fullImg;
        modalCaption.textContent = img.dataset.caption;
        stopAutoSlide();
    }

    function closeModal() {
        const modal = document.getElementById('imageModal');
        modal.style.display = 'none';
        if (!isSingleSlide) startAutoSlide();
    }

    // Set up navigation
    if (prevButton) prevButton.addEventListener('click', () => {
        prevSlide();
        stopAutoSlide();
        startAutoSlide();
    });

    if (nextButton) nextButton.addEventListener('click', () => {
        nextSlide();
        stopAutoSlide();
        startAutoSlide();
    });

    // Modal events
    document.querySelector('.close-modal').addEventListener('click', closeModal);
    window.addEventListener('click', (e) => {
        if (e.target === document.getElementById('imageModal')) {
            closeModal();
        }
    });

    // Add click events to slides
    slides.forEach(slide => {
        slide.addEventListener('click', () => openModal(slide));
    });

    // Handle hover events
    if (!isSingleSlide) {
        sliderWrapper.addEventListener('mouseenter', stopAutoSlide);
        sliderWrapper.addEventListener('mouseleave', startAutoSlide);
    }

    // Handle visibility change
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopAutoSlide();
        } else if (!isSingleSlide) {
            startAutoSlide();
        }
    });

    // Start auto-sliding if multiple slides
    if (!isSingleSlide) {
        startAutoSlide();
    }
});
</script>

</body>
</html>

<?php
// Close database connection
if(isset($conn)) {
    $conn->close();
}
?>
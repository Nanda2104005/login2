<?php
session_start();

// Jika sudah login, redirect ke home
if (isset($_SESSION['username'])) {
    header("Location: home.php");
    exit();
}

// Konfigurasi Koneksi Database
$server = 'localhost';
$username = 'root';
$password = '';
$database = 'user_databasee';

// Membuat koneksi
$conn = mysqli_connect($server, $username, $password, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Proses Login
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);

    // Query untuk cek user di database
    $sql = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $sql);

    // Cek apakah user ditemukan
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];
        $_SESSION['user_id'] = $row['id']; // Tambahkan user_id jika ada
        
        // Set cookie jika opsi "Remember me" diaktifkan
        if (isset($_POST['remember'])) {
            setcookie('username', $username, time() + (86400 * 30), "/");
        }

        // Arahkan ke halaman home
        header("Location: home.php");
        exit();
    } else {
        $error = "Username atau password salah!";
    }
}

// Proses Pendaftaran
if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = md5($_POST['password']);
    $role = 'siswa';  // Default role for new registrations

    // Cek apakah username sudah ada
    $checkUser = "SELECT * FROM users WHERE username='$username'";
    $result = mysqli_query($conn, $checkUser);
    
    if (mysqli_num_rows($result) > 0) {
        $error = "Username sudah terdaftar!";
    } else {
        $sql = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$password', '$role')";
        
        if (mysqli_query($conn, $sql)) {
            $success = "Pendaftaran berhasil! Silakan login.";
        } else {
            $error = "Terjadi kesalahan, coba lagi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>M3 Care - Your Health Companion</title>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

:root {
    --primary-color: #05b385;
    --primary-dark: #104537;
    --text-color: #333;
    --bg-gradient-light: linear-gradient(135deg, #bdffed 0%, #05b385 100%);
    --bg-gradient-dark: linear-gradient(135deg, #388a73 0%, #104537 100%);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    -webkit-tap-highlight-color: transparent;
}

@keyframes gradientBG {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes scaleIn {
    from {
        transform: scale(0.9);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

@keyframes slideInFromRight {
    from {
        transform: translateX(100px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

body {
    font-family: 'Poppins', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background: var(--bg-gradient-light);
    background-size: 200% 200%;
    animation: gradientBG 15s ease infinite;
    transition: background 0.5s ease;
    position: relative;
    overflow-x: hidden;
    padding: 20px;
    color: var(--text-color);
}

body.dark-mode {
    background: var(--bg-gradient-dark);
    color: #ffffff;
}

.container {
    background-color: rgba(255, 255, 255, 0.95);
    padding: clamp(20px, 5vw, 40px);
    border-radius: 20px;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
    animation: scaleIn 0.6s ease-out;
    margin: auto;
    transform-origin: center center;
}

.dark-mode .container {
    background-color: rgba(25, 25, 25, 0.95);
}

.logo {
    margin-bottom: 20px;
    animation: fadeInUp 0.8s ease-out;
    text-align: center;
}

.logo img {
    max-width: min(100px, 30vw);
    height: auto;
    transform-origin: center;
    animation: scaleIn 1s ease-out;
}

h2 {
    color: var(--primary-color);
    font-size: clamp(24px, 5vw, 28px);
    margin-bottom: 20px;
    font-weight: 700;
    animation: fadeInUp 0.8s ease-out 0.2s backwards;
    text-align: center;
}

.dark-mode h2 {
    color: #ffffff;
}

/* Form Styling */
form {
    display: flex;
    flex-direction: column;
    gap: 15px;
    text-align: left;
}

.animate-form > * {
    opacity: 0;
    animation: fadeInUp 0.8s ease-out forwards;
}

.animate-form > *:nth-child(1) { animation-delay: 0.1s; }
.animate-form > *:nth-child(2) { animation-delay: 0.2s; }
.animate-form > *:nth-child(3) { animation-delay: 0.3s; }
.animate-form > *:nth-child(4) { animation-delay: 0.4s; }
.animate-form > *:nth-child(5) { animation-delay: 0.5s; }
.animate-form > *:nth-child(6) { animation-delay: 0.6s; }

.input-group {
    position: relative;
    margin-bottom: 15px;
    opacity: 0;
    animation: fadeInUp 0.8s ease-out forwards;
}

label {
    display: block;
    margin-bottom: 5px;
    text-align: left;
    font-weight: 500;
}

.dark-mode label {
    color: #ffffff;
}

input[type="text"],
input[type="email"],
input[type="password"] {
    width: 100%;
    padding: 12px 15px;
    border: 1.5px solid var(--primary-color);
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s ease;
    appearance: none;
    -webkit-appearance: none;
    background-color: white;
    color: var(--text-color);
}

.dark-mode input[type="text"],
.dark-mode input[type="email"],
.dark-mode input[type="password"] {
    background-color: rgba(255, 255, 255, 0.1);
    color: white;
    border-color: rgba(255, 255, 255, 0.3);
}

input[type="text"]:focus,
input[type="email"]:focus,
input[type="password"]:focus {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(5, 179, 133, 0.2);
    outline: none;
}

/* Password Field Styling */
.password-group {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    padding: 5px;
    z-index: 2;
    color: var(--text-color);
    transition: all 0.3s ease;
}

.dark-mode .password-toggle {
    color: #ffffff;
}

.password-toggle:hover {
    color: var(--primary-color);
}

/* Checkbox Styling */
input[type="checkbox"] {
    margin-right: 8px;
}

label[for="remember"] {
    display: flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
}

/* Button Styling */
button {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 15px;
    border-radius: 8px;
    font-size: clamp(16px, 4vw, 18px);
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

button:hover {
    background-color: var(--primary-dark);
    transform: translateY(-2px);
}

button:active {
    transform: translateY(0);
}

.switch {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 20px;
    font-size: 14px;
    z-index: 1000;
    border-radius: 30px;
    animation: slideInFromRight 0.8s ease-out;
}

/* Message Styling */
.message {
    animation: fadeInUp 0.6s ease-out;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 15px;
    text-align: left;
}

.error {
    background-color: rgba(255, 82, 82, 0.1);
    color: #ff5252;
}

.success {
    background-color: rgba(5, 179, 133, 0.1);
    color: var(--primary-color);
}

/* Auth Switch Link Styling */
.auth-switch {
    text-align: center;
    margin-top: 10px;
    color: var(--text-color);
}

.dark-mode .auth-switch {
    color: #ffffff;
}

.auth-switch a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.auth-switch a:hover {
    text-decoration: underline;
}

.dark-mode .auth-switch a {
    color: #05b385;
}

/* Responsive Design */
@media (max-width: 480px) {
    body {
        padding: 15px;
    }

    .container {
        padding: 20px;
    }

    .switch {
        top: 10px;
        right: 10px;
        padding: 8px 16px;
        font-size: 12px;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"] {
        font-size: 14px;
    }
}

/* Accessibility */
 @media (prefers-reduced-motion: reduce) {
    * {
        animation: none !important;
        transition: none !important;
    }
}

/* Prevent pull-to-refresh but keep smooth scrolling */
html {
    overscroll-behavior-y: contain;
    scroll-behavior: smooth;
}
    </style>
</head>
<body>
    <button class="switch" id="themeSwitch">Switch Theme</button>
    
    <div class="container">
        <div class="logo">
            <img src="logo MU.png" alt="M3 Care Logo">
        </div>
        
        <?php if (isset($error)): ?>
            <div class="message error"><?= $error ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="message success"><?= $success ?></div>
        <?php endif; ?>

        <?php if (!isset($_GET['register'])): ?>
            <h2>Welcome to M3 Care</h2>
            <form method="post" action="">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php if(isset($_COOKIE['username'])) { echo $_COOKIE['username']; } ?>" required>
                
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                
                <label for="remember">
                    <input type="checkbox" id="remember" name="remember" <?php if(isset($_COOKIE['username'])) { echo "checked"; } ?>>
                    Remember me
                </label>

                <button type="submit" name="login">Login</button>
                <p class="auth-switch">New to M3 Care? <a href="?register=1">Sign Up</a></p>
            </form>
        <?php else: ?>
            <h2>Join M3 Care</h2>
    <form method="post" action="" class="animate-form">
        <div class="input-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
        </div>
        
        <div class="input-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="input-group password-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <span class="password-toggle" id="passwordToggle">
                <i class="far fa-eye"></i>
            </span>
        </div>
        
        <button type="submit" name="register">Sign Up</button>
        <p class="auth-switch">Already have an account? <a href="login.php">Login</a></p>
    </form>
        <?php endif; ?>
    </div>

    <script>
        const switchButton = document.getElementById('themeSwitch');
        let darkMode = false;

        if (localStorage.getItem('darkMode') === 'true') {
            darkMode = true;
            document.body.classList.add('dark-mode');
            switchButton.textContent = 'Light Theme';
        }

        switchButton.onclick = () => {
            darkMode = !darkMode;
            document.body.classList.toggle('dark-mode');
            switchButton.textContent = darkMode ? 'Light Theme' : 'Dark Theme';
            localStorage.setItem('darkMode', darkMode);
        };

         // Add password visibility toggle functionality
    const passwordToggle = document.getElementById('passwordToggle');
    const passwordInput = document.getElementById('password');

    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle icon
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }
    </script>
</body>
</html>
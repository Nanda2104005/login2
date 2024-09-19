<?php
session_start();

// Koneksi ke database
$host = "localhost";
$username = "root";
$password = "";
$dbname = "user_database";

$conn = new mysqli($host, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$errorMessage = "";

// Menangani login dan registrasi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['login-username'])) {
        // Proses login
        $loginUsername = $_POST['login-username'];
        $loginPassword = $_POST['login-password'];

        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $loginUsername);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($loginPassword, $row['password'])) {
                $_SESSION['username'] = $loginUsername;
                header("Location: Dashboard.php");
                exit();
            } else {
                $errorMessage = "Password salah!";
            }
        } else {
            $errorMessage = "Username tidak ditemukan!";
        }
        $stmt->close();
    } elseif (isset($_POST['register-username'])) {
        // Proses registrasi
        $registerUsername = $_POST['register-username'];
        $registerEmail = $_POST['register-email'];
        $registerPassword = password_hash($_POST['register-password'], PASSWORD_BCRYPT);

        // Cek apakah username sudah ada
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $registerUsername, $registerEmail);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['username'] === $registerUsername) {
                $errorMessage = "Username sudah digunakan.";
            } elseif ($row['email'] === $registerEmail) {
                $errorMessage = "Email sudah terdaftar.";
            }
        } else {
            // Insert data jika username dan email belum terdaftar
            $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $registerUsername, $registerEmail, $registerPassword);

            if ($stmt->execute()) {
                echo "<script>
                        alert('Registrasi berhasil! Silakan login.');
                        window.location.href = 'RegisterBerhasil.php';
                      </script>";
                exit();
            } else {
                $errorMessage = "Gagal mendaftar, coba lagi.";
            }
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f2f2f2;
        }
        .container {
            width: 300px;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        .btn {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 10px;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .clear-btn {
            background-color: #f44336;
            color: white;
        }
        .clear-btn:hover {
            background-color: #e53935;
        }
        .toggle-btn {
            margin-top: 10px;
            text-align: center;
            cursor: pointer;
            color: #007BFF;
        }
        .error {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 id="form-title">Login</h2>

    <div class="error"><?php echo $errorMessage; ?></div>

    <!-- Form Login -->
    <form id="login-form" method="POST" action="Login.php" style="display: block;">
        <div class="form-group">
            <label for="login-username">Username</label>
            <input type="text" id="login-username" name="login-username" required>
        </div>
        <div class="form-group">
            <label for="login-password">Password</label>
            <input type="password" id="login-password" name="login-password" required>
        </div>
        <button type="submit" class="btn">Login</button>
        <button type="button" class="btn clear-btn" id="clear-login">Clear</button>
    </form>

    <!-- Form Register -->
    <form id="register-form" action="Login.php" method="POST" style="display: none;">
        <div class="form-group">
            <label for="register-username">Username</label>
            <input type="text" id="register-username" name="register-username" required>
        </div>
        <div class="form-group">
            <label for="register-email">Email</label>
            <input type="email" id="register-email" name="register-email" required>
        </div>
        <div class="form-group">
            <label for="register-password">Password</label>
            <input type="password" id="register-password" name="register-password" required>
        </div>
        <button type="submit" class="btn">Register</button>
        <button type="button" class="btn clear-btn" id="clear-register">Clear</button>
    </form>

    <div class="toggle-btn" id="toggle-form">Belum punya akun? Register di sini</div>
</div>

<script>
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const toggleBtn = document.getElementById('toggle-form');
    const formTitle = document.getElementById('form-title');

    const clearLoginBtn = document.getElementById('clear-login');
    const clearRegisterBtn = document.getElementById('clear-register');

    // Menampilkan form login/register
    toggleBtn.addEventListener('click', () => {
        if (loginForm.style.display === 'none') {
            loginForm.style.display = 'block';
            registerForm.style.display = 'none';
            formTitle.innerText = 'Login';
            toggleBtn.innerText = 'Belum punya akun? Register di sini';
        } else {
            loginForm.style.display = 'none';
            registerForm.style.display = 'block';
            formTitle.innerText = 'Register';
            toggleBtn.innerText = 'Sudah punya akun? Login di sini';
        }
    });

    // Fungsi untuk mengosongkan form login
    clearLoginBtn.addEventListener('click', () => {
        document.getElementById('login-username').value = '';
        document.getElementById('login-password').value = '';
    });

    // Fungsi untuk mengosongkan form register
    clearRegisterBtn.addEventListener('click', () => {
        document.getElementById('register-username').value = '';
        document.getElementById('register-email').value = '';
        document.getElementById('register-password').value = '';
    });
</script>

</body>
</html>

<?php
session_start();

// Cek login status
function checkLoginStatus() {
    if (!isset($_SESSION['username'])) {
        header("Location: login.php?message=Silakan login untuk mengakses halaman ini");
        exit();
    }
}

checkLoginStatus();

// Koneksi database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_database";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Cek role pengguna
$current_username = $_SESSION['username'];
$role_query = "SELECT role FROM users WHERE username = ?";
$stmt = $conn->prepare($role_query);
$stmt->bind_param("s", $current_username);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$user_role = $user_data['role'];

// Fungsi untuk mengupdate password
function updatePassword($conn, $username, $new_password) {
    // Menggunakan MD5 untuk kompatibilitas dengan sistem yang ada
    $hashed_password = md5($new_password);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ? AND role = 'siswa'");
    $stmt->bind_param("ss", $hashed_password, $username);
    return $stmt->execute();
}

// Handle form submission untuk reset password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $username = $_POST['selected_user'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verifikasi username ada di database dan merupakan siswa
    $check_user = $conn->prepare("SELECT username FROM users WHERE username = ? AND role = 'siswa'");
    $check_user->bind_param("s", $username);
    $check_user->execute();
    $user_result = $check_user->get_result();

    if ($user_result->num_rows > 0) {
        if ($new_password === $confirm_password) {
            if (updatePassword($conn, $username, $new_password)) {
                $success_message = "Password berhasil diperbarui!";
            } else {
                $error_message = "Gagal memperbarui password. Silakan coba lagi.";
            }
        } else {
            $error_message = "Password baru dan konfirmasi password tidak cocok.";
        }
    } else {
        $error_message = "Username tidak terdaftar atau bukan merupakan akun siswa.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pengguna</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1ca883;
            --primary-dark: #158f6e;
            --secondary-color: #f0f9f6;
            --accent-color: #ff6b6b;
            --text-color: #2c3e50;
            --background-color: #ecf0f1;
            --card-hover: #e8f5f1;
            --shadow-color: rgba(28, 168, 131, 0.1);
            --border-radius: 12px;
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.18);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 2rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: var(--border-radius);
            color: white;
            box-shadow: 0 4px 6px var(--shadow-color);
        }

        header h1 {
            font-size: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .user-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 6px var(--shadow-color);
            border: 1px solid var(--glass-border);
        }

        .table-responsive {
            overflow-x: unset;
            width: 100%;
            margin-bottom: 1rem;
        }

        table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin: 1.5rem 0;
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 1.2rem;
            text-align: left;
            border-bottom: 1px solid var(--secondary-color);
            white-space: normal;
            word-wrap: break-word;
        }

        th {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.9rem;
    letter-spacing: 0.5px;
    text-align: center; /* Center align all headers */
    white-space: nowrap; /* Prevent text wrapping */
}

th:first-child,td:first-child {
{
    width: 5%;
    white-space: nowrap; /* Prevent "No" column from wrapping */
    text-align: center; /* Center align the "No" column */
}
        }

        /* Mengatur lebar kolom */
        th:nth-child(1), td:nth-child(1) { width: 5%; }  /* No */
        th:nth-child(2), td:nth-child(2) { width: 15%; } /* Username */
        th:nth-child(3), td:nth-child(3) { width: 15%; } /* NIS */
        th:nth-child(4), td:nth-child(4) { width: 25%; } /* Nama Lengkap */
        th:nth-child(5), td:nth-child(5) { width: 25%; } /* Email */
        th:nth-child(6), td:nth-child(6) { width: 15%; } /* Role (jika admin) */

        tr:hover {
            background-color: var(--card-hover);
            transition: all 0.3s ease;
        }

        .role-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            text-align: center;
            display: inline-block;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .role-admin {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .role-siswa {
            background-color: #e3fcef;
            color: #0f766e;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            border: none;
            font-size: 0.95rem;
            min-width: 180px;
            justify-content: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-back {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }

        .btn-forgot-password {
            background: linear-gradient(135deg, var(--accent-color), #ff8f8f);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--glass-bg);
            padding: 2.5rem;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--glass-border);
            animation: slideIn 0.3s ease-out;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--secondary-color);
        }

        .modal-header h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .close {
            font-size: 1.8rem;
            cursor: pointer;
            color: var(--text-color);
            transition: color 0.3s ease;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close:hover {
            color: var(--accent-color);
            background-color: rgba(255, 107, 107, 0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.8rem;
            color: var(--text-color);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .password-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--secondary-color);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(28, 168, 131, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 1rem;
            cursor: pointer;
            color: var(--text-color);
            opacity: 0.7;
            transition: all 0.3s ease;
        }

        .toggle-password:hover {
            opacity: 1;
            color: var(--primary-color);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }

        .message {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-weight: 500;
        }

        .success {
            background-color: #e3fcef;
            color: #0f766e;
            border-left: 4px solid #0f766e;
        }

        .error {
            background-color: #fee2e2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }

        .btn-reset {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(28, 168, 131, 0.2);
            margin-top: 1rem;
        }

        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(28, 168, 131, 0.3);
        }

        .password-strength {
            height: 4px;
            margin-top: 0.5rem;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        .weak {
            background: var(--accent-color);
            width: 33.33%;
        }

        .medium {
            background: #fbbf24;
            width: 66.66%;
        }

        .strong {
            background: var(--primary-color);
            width: 100%;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            header h1 {
                font-size: 2rem;
            }
            
            .table-responsive {
                overflow-x: auto;
            }
            
            table {
                min-width: 800px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            .modal-content {
                width: 95%;
                padding: 1.5rem;
            }
            
            .form-group input {
                padding: 0.8rem;
            }
        }

        @keyframes slideIn {
            from {
                transform: translate(-50%, -70%);
                opacity: 0;
            }
            to {
                transform: translate(-50%, -50%);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translate(-50%, -50%);
                opacity: 1;
            }
            to {
                transform: translate(-50%, -70%);
                opacity: 0;
            }
        }

        .modal-content.closing {
            animation: slideOut 0.3s ease-in forwards;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-users"></i> Daftar Pengguna System</h1>
        </header>

        <?php if (isset($success_message)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="user-container">
            <?php
            // Query untuk menampilkan data dengan NIS
            if ($user_role === 'admin') {
                $query = "SELECT id, username, role, nama_lengkap, nis, email FROM users ORDER BY role DESC, username ASC";
            } else {
                $query = "SELECT id, username, nama_lengkap, nis, email FROM users WHERE role = 'siswa' ORDER BY username ASC";
            }

            $result = $conn->query($query);

            if ($result->num_rows > 0) {
                echo "<div class='table-responsive'>";
                echo "<table>";
                echo "<tr>";
                echo "<th>No</th>";
                echo "<th>Username</th>";
                echo "<th>NIS</th>";
                echo "<th>Nama Lengkap</th>";
                echo "<th>Email</th>";
                if ($user_role === 'admin') {
                    echo "<th>Role</th>";
                }
                echo "</tr>";

                $no = 1;
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $no++ . "</td>";
                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['nis'] ?? '-') . "</td>";
                    echo "<td>" . htmlspecialchars($row['nama_lengkap'] ?? '-') . "</td>";
                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                    if ($user_role === 'admin') {
                        echo "<td><span class='role-badge role-" . $row['role'] . "'>" . 
                             ucfirst($row['role']) . "</span></td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
                echo "</div>";
            } else {
                echo "<p>Tidak ada data pengguna.</p>";
            }
            ?>

            <div class="action-buttons">
                <a href="dashboard.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
                <?php if ($user_role === 'admin'): ?>
                    <button onclick="showResetPasswordModal()" class="btn btn-forgot-password">
                        <i class="fas fa-key"></i> Reset Password Siswa
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Reset Password -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-key"></i> Reset Password Siswa</h2>
                <span class="close" onclick="closeResetPasswordModal()">&times;</span>
            </div>
            <form method="POST" action="" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="selected_user">Pilih Siswa:</label>
                    <select id="selected_user" name="selected_user" class="form-control" required>
                        <option value="">Pilih username siswa...</option>
                        <?php
                        $user_query = "SELECT username, nama_lengkap, nis FROM users WHERE role = 'siswa' ORDER BY username";
                        $user_result = $conn->query($user_query);
                        while ($row = $user_result->fetch_assoc()) {
                            $display_text = $row['username'];
                            if (!empty($row['nama_lengkap'])) {
                                $display_text .= ' - ' . $row['nama_lengkap'];
                            }
                            if (!empty($row['nis'])) {
                                $display_text .= ' (' . $row['nis'] . ')';
                            }
                            echo "<option value='" . htmlspecialchars($row['username']) . "'>" . 
                                 htmlspecialchars($display_text) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="new_password">Password Baru:</label>
                    <div class="password-input-container">
                        <input type="password" id="new_password" name="new_password" class="form-control" required 
                               placeholder="Masukkan password baru"
                               minlength="8">
                        <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('new_password', this)"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password:</label>
                    <div class="password-input-container">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required 
                               placeholder="Konfirmasi password baru"
                               minlength="8">
                        <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('confirm_password', this)"></i>
                    </div>
                    
                </div>
                <button type="submit" name="reset_password" class="btn btn-reset">
                    <i class="fas fa-save"></i> Update Password
                </button>
            </form>

        <div class="password-strength" id="password-strength"></div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            }
        }

        function showResetPasswordModal() {
            document.getElementById('resetPasswordModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeResetPasswordModal() {
            const modalContent = document.querySelector('.modal-content');
            modalContent.classList.add('closing');
            setTimeout(() => {
                document.getElementById('resetPasswordModal').style.display = 'none';
                document.body.style.overflow = 'auto';
                modalContent.classList.remove('closing');
            }, 300);
        }

        function validateForm() {
            const selectedUser = document.getElementById('selected_user').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (!selectedUser) {
                alert('Mohon pilih username siswa!');
                return false;
            }

            if (newPassword !== confirmPassword) {
                alert('Password baru dan konfirmasi password tidak cocok!');
                return false;
            }

            return true;
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('resetPasswordModal');
            if (event.target == modal) {
                closeResetPasswordModal();
            }
        }

        // Auto hide messages after 5 seconds
        setTimeout(function() {
            const messages = document.getElementsByClassName('message');
            for (let i = 0; i < messages.length; i++) {
                messages[i].style.display = 'none';
            }
        }, 5000);

        function checkPasswordStrength(password) {
    let strength = 0;
    
    // Check length
    if (password.length >= 8) strength += 1;
    if (password.length >= 12) strength += 1;
    
    // Check for numbers
    if (/\d/.test(password)) strength += 1;
    
    // Check for lowercase letters
    if (/[a-z]/.test(password)) strength += 1;
    
    // Check for uppercase letters
    if (/[A-Z]/.test(password)) strength += 1;
    
    // Check for special characters
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength += 1;

    // Calculate strength level
    if (strength <= 2) return 'weak';
    if (strength <= 4) return 'medium';
    return 'strong';
}

function updatePasswordStrength() {
    const password = document.getElementById('new_password').value;
    const strengthIndicator = document.getElementById('password-strength');
    const strength = checkPasswordStrength(password);
    
    // Remove previous classes
    strengthIndicator.classList.remove('weak', 'medium', 'strong');
    
    // Add new class
    if (password.length > 0) {
        strengthIndicator.classList.add(strength);
        strengthIndicator.style.display = 'block';
    } else {
        strengthIndicator.style.display = 'none';
    }
}
    </script>
</body>
</html>

<?php $conn->close(); ?>
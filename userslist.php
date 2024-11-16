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
function updatePassword($conn, $email, $new_password) {
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ? AND role = 'siswa'");
    $stmt->bind_param("ss", $hashed_password, $email);
    return $stmt->execute();
}

// Handle form submission untuk reset password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $email = $_POST['email'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verifikasi email ada di database dan merupakan siswa
    $check_email = $conn->prepare("SELECT email FROM users WHERE email = ? AND role = 'siswa'");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $email_result = $check_email->get_result();

    if ($email_result->num_rows > 0) {
        if ($new_password === $confirm_password) {
            if (updatePassword($conn, $email, $new_password)) {
                $success_message = "Password berhasil diperbarui!";
            } else {
                $error_message = "Gagal memperbarui password. Silakan coba lagi.";
            }
        } else {
            $error_message = "Password baru dan konfirmasi password tidak cocok.";
        }
    } else {
        $error_message = "Email tidak terdaftar atau bukan merupakan akun siswa.";
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

        table {
            width: 100%;
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
        }

        th {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

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

        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--secondary-color);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.9);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(28, 168, 131, 0.1);
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

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            header h1 {
                font-size: 2rem;
            }
            
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
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
            if ($user_role === 'admin') {
                $query = "SELECT id, username, role, nama_lengkap, email FROM users ORDER BY role DESC, username ASC";
            } else {
                $query = "SELECT id, username, nama_lengkap, email FROM users WHERE role = 'siswa' ORDER BY username ASC";
            }

            $result = $conn->query($query);

            if ($result->num_rows > 0) {
                echo "<table>";
                echo "<tr>";
                echo "<th>No</th>";
                echo "<th>Username</th>";
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
                    echo "<td>" . htmlspecialchars($row['nama_lengkap'] ?? '-') . "</td>";
                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                    if ($user_role === 'admin') {
                        echo "<td><span class='role-badge role-" . $row['role'] . "'>" . 
                             ucfirst($row['role']) . "</span></td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>Tidak ada data pengguna.</p>";
            }
            ?>

            <div class="action-buttons">
                <a href="dashboard.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
                <?php if ($user_role === 'siswa'): ?>
                    <button onclick="showResetPasswordModal()" class="btn btn-forgot-password">
                        <i class="fas fa-key"></i> Lupa Password
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Reset Password -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-key"></i> Reset Password</h2>
                <span class="close" onclick="closeResetPasswordModal()">&times;</span>
            </div>
            <form method="POST" action="" onsubmit="return validateForm()
             <div class="form-group">
                   <label for="email">Email:</label>
                   <input type="email" id="email" name="email" required 
                          placeholder="Masukkan email terdaftar">
               </div>
               <div class="form-group">
                   <label for="new_password">Password Baru:</label>
                   <input type="password" id="new_password" name="new_password" required 
                          placeholder="Masukkan password baru"
                          minlength="8">
               </div>
               <div class="form-group">
                   <label for="confirm_password">Konfirmasi Password:</label>  
                   <input type="password" id="confirm_password" name="confirm_password" required 
                          placeholder="Konfirmasi password baru"
                          minlength="8">
               </div>
               <button type="submit" name="reset_password" class="btn btn-reset">
                   <i class="fas fa-save"></i> Update Password
               </button>
           </form>
       </div>
   </div>

   <script>
       function showResetPasswordModal() {
           document.getElementById('resetPasswordModal').style.display = 'block';
           document.body.style.overflow = 'hidden'; // Prevent scrolling
       }

       function closeResetPasswordModal() {
           document.getElementById('resetPasswordModal').style.display = 'none';
           document.body.style.overflow = 'auto'; // Enable scrolling
       }

       function validateForm() {
           var email = document.getElementById('email').value;
           var newPassword = document.getElementById('new_password').value;
           var confirmPassword = document.getElementById('confirm_password').value;

           // Email validation
           var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
           if (!emailPattern.test(email)) {
               alert('Mohon masukkan email yang valid!');
               return false;
           }

           // Password strength validation
           var passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/;
           if (!passwordPattern.test(newPassword)) {
               alert('Password harus minimal 8 karakter dan mengandung huruf besar, huruf kecil, dan angka!');
               return false;
           }

           // Password match validation
           if (newPassword !== confirmPassword) {
               alert('Password baru dan konfirmasi password tidak cocok!');
               return false;
           }

           return true;
       }

       // Close modal when clicking outside
       window.onclick = function(event) {
           var modal = document.getElementById('resetPasswordModal');
           if (event.target == modal) {
               closeResetPasswordModal();
           }
       }

       // Auto hide messages after 5 seconds
       setTimeout(function() {
           var messages = document.getElementsByClassName('message');
           for (var i = 0; i < messages.length; i++) {
               messages[i].style.display = 'none';
           }
       }, 5000);

       // Tambahkan animasi untuk modal
       document.querySelector('.modal-content').addEventListener('animationend', function(e) {
           if (e.animationName === 'slideOut') {
               document.getElementById('resetPasswordModal').style.display = 'none';
           }
       });
   </script>

   <style>
       /* Additional Styles for Animations */
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

       .modal-content {
           animation: slideIn 0.3s ease-out;
       }

       .modal-content.closing {
           animation: slideOut 0.3s ease-in forwards;
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

       .form-group input:focus {
           outline: none;
           border-color: var(--primary-color);
           box-shadow: 0 0 0 4px rgba(28, 168, 131, 0.1);
       }

       /* Password strength indicator */
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
           background: var(--warning);
           width: 66.66%;
       }

       .strong {
           background: var(--primary-color);
           width: 100%;
       }
   </style>
</body>
</html>

<?php $conn->close(); ?>
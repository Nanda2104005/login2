<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "user_database";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

$username = $_SESSION['username'];
$query = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = $_POST['nama_lengkap'];
    $new_username = $_POST['username'];
    $email = $_POST['email'];
    $telepon = $_POST['telepon'];
    $alamat = $_POST['alamat'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $filename = $_FILES['foto']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            
             if (in_array(strtolower($filetype), $allowed)) {
                if (!file_exists('uploads')) {
                    mkdir('uploads', 0777, true);
                }
                
                $new_filename = "avatar_" . $new_username . "." . $filetype;
                move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $new_filename);
                
                $query = "UPDATE users SET foto = ?, nama_lengkap = ?, username = ?, email = ?, telepon = ?, alamat = ?, tanggal_lahir = ? WHERE username = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssssssss", $new_filename, $nama_lengkap, $new_username, $email, $telepon, $alamat, $tanggal_lahir, $username);
            }
        } else {
            $query = "UPDATE users SET nama_lengkap = ?, username = ?, email = ?, telepon = ?, alamat = ?, tanggal_lahir = ? WHERE username = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssssss", $nama_lengkap, $new_username, $email, $telepon, $alamat, $tanggal_lahir, $username);
        }
        
        if ($stmt->execute()) {
            $_SESSION['username'] = $new_username;
            $conn->commit();
            $success_message = "Profil berhasil diperbarui!";
            
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->bind_param("s", $new_username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            throw new Exception("Gagal memperbarui profil");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Gagal memperbarui profil: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - SIKS SMA Muhammadiyah 3 Jember</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1ca883;
            --primary-dark: #159f7f;
            --primary-light: #e8f5f1;
            --secondary-color: #f0f9f6;
            --accent-color: #ff6b6b;
            --text-color: #2c3e50;
            --text-light: #6c757d;
            --white: #ffffff;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--secondary-color);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            position: relative;
            padding-bottom: 80px;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--white);
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-link {
            color: var(--white);
            text-decoration: none;
            padding: 0.7rem 1.2rem;
            border-radius: 25px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .nav-link i {
            font-size: 1.1rem;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .profile-section {
            background-color: var(--white);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: var(--shadow-lg);
            position: relative;
        }

        .section-title {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 3rem;
            padding: 2rem;
            background-color: var(--primary-light);
            border-radius: 15px;
        }

        .profile-avatar-container {
            position: relative;
            width: 180px;
            height: 180px;
        }

        .profile-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--white);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }

        .avatar-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: var(--primary-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .avatar-upload:hover {
            transform: scale(1.1);
        }

        .avatar-upload i {
            color: var(--white);
        }

        .profile-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 500;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group input {
            padding: 1rem;
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            font-size: 1rem;
            transition: var(--transition);
            background-color: var(--white);
        }

        .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(28, 168, 131, 0.1);
        }

        .form-group input[type="file"] {
            display: none;
        }

        .btn-update {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: var(--transition);
            margin-top: 1rem;
        }

        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        footer {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            text-align: center;
            padding: 1.5rem 0;
            position: absolute;
            bottom: 0;
            width: 100%;
            box-shadow: var(--shadow-md);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .profile-form {
                grid-template-columns: 1fr;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .container {
                padding: 0 1rem;
            }

            .navbar {
                padding: 1rem;
            }

            .nav-links {
                display: none;
            }
        }

        /* Hover Effects */
        .form-group input:hover {
            border-color: var(--primary-color);
        }

        .profile-avatar:hover {
            transform: scale(1.02);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: var(--secondary-color);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="Home.php" class="navbar-brand">Sistem Informasi Kesehatan Sekolah (SIKS)</a>
        <div class="nav-links">
            <a href="Home.php" class="nav-link">
                <i class="fas fa-home"></i>
                Home
            </a>
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="about.php" class="nav-link">
                <i class="fas fa-info-circle"></i>
                About Us
            </a>
            <a href="logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="profile-section">
            <h2 class="section-title">
                <i class="fas fa-user-circle"></i>
                Profile Settings
            </h2>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="profile-form">
                <div class="profile-header">
                    <div class="profile-avatar-container">
                        <img src="<?php echo isset($user['foto']) ? 'uploads/' . $user['foto'] : 'assets/default-avatar.png'; ?>" 
                             alt="Profile Picture" 
                             class="profile-avatar">
                        <label for="foto" class="avatar-upload">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" name="foto" id="foto" accept="image/*">
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user"></i> Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user-tag"></i> Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Nomor Telepon</label>
                    <input type="tel" name="telepon" value="<?php echo htmlspecialchars($user['telepon']); ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Alamat</label>
                    <input type="text" name="alamat" value="<?php echo htmlspecialchars($user['alamat']); ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" value="<?php echo htmlspecialchars($user['tanggal_lahir']); ?>" required>
                </div>

                <button type="submit" class="btn-update">
                    <i class="fas fa-save"></i>
                    Update Profile
                </button>
            </form>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 Sistem Informasi Kesehatan Sekolah (SIKS) SMA Muhammadiyah 3 Jember. All Rights Reserved.</p>
    </footer>
</body>
</html>
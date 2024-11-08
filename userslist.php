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
            --secondary-color: #f0f9f6;
            --accent-color: #ff6b6b;
            --text-color: #2c3e50;
            --background-color: #ecf0f1;
            --card-hover: #e8f5f1;
            --shadow-color: rgba(28, 168, 131, 0.1);
            --border-radius: 12px;
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
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 2rem;
        }

        header h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .user-container {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 6px var(--shadow-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--secondary-color);
        }

        th {
            background-color: var(--primary-color);
            color: white;
        }

        tr:hover {
            background-color: var(--secondary-color);
        }

        .role-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            text-align: center;
        }

        .role-admin {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .role-siswa {
            background-color: #e3fcef;
            color: #0f766e;
        }

        .btn-back {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            margin-top: 1rem;
            transition: background-color 0.3s;
        }

        .btn-back:hover {
            background-color: #158f6e;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-users"></i> Daftar Pengguna System</h1>
        </header>

        <div class="user-container">
            <?php
            if ($user_role === 'admin') {
                // Admin dapat melihat semua pengguna
                $query = "SELECT id, username, role, nama_lengkap, email FROM users ORDER BY role DESC, username ASC";
            } else {
                // Siswa hanya dapat melihat sesama siswa
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

            <a href="dashboard.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
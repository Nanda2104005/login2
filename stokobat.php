<?php
require_once 'config.php'; // Include the config file

// Bagian delete tetap seperti sebelumnya
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete']) && isset($_POST['id_stok'])) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Pastikan id_stok adalah angka valid
        $id_stok = filter_var($_POST['id_stok'], FILTER_VALIDATE_INT);
        
        if ($id_stok === false) {
            throw new Exception("ID stok tidak valid!");
        }
        
        // Cek apakah data dengan id tersebut ada
        $check = $pdo->prepare("SELECT * FROM stok_obat WHERE id_stok = :id_stok LIMIT 1");
        $check->bindParam(':id_stok', $id_stok, PDO::PARAM_INT);
        $check->execute();
        
        if ($check->rowCount() == 0) {
            throw new Exception("Data tidak ditemukan!");
        }
        
        // Lakukan penghapusan dengan WHERE clause yang spesifik
        $stmt = $pdo->prepare("DELETE FROM stok_obat WHERE id_stok = :id_stok LIMIT 1");
        $stmt->bindParam(':id_stok', $id_stok, PDO::PARAM_INT);
        $stmt->execute();
        
        $success_message = "Data berhasil dihapus!";
        
    } catch(Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
} elseif (isset($_POST['action'])) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Begin transaction
        $pdo->beginTransaction();

        if ($_POST['action'] == 'edit') {
            $sql = "UPDATE stok_obat SET 
                    nama = :nama,
                    jumlah = :jumlah,
                    dosis = :dosis,
                    diperbarui = :diperbarui,
                    tanggal_kadaluarsa = :tanggal_kadaluarsa
                    WHERE id_stok = :id_stok";
                    
            $stmt = $pdo->prepare($sql);
            
            $stmt->bindParam(':id_stok', $_POST['id_stok'], PDO::PARAM_INT);
            $stmt->bindParam(':nama', $_POST['nama'], PDO::PARAM_STR);
            $stmt->bindParam(':jumlah', $_POST['jumlah'], PDO::PARAM_INT);
            $stmt->bindParam(':dosis', $_POST['dosis'], PDO::PARAM_STR);
            $stmt->bindParam(':diperbarui', $_POST['diperbarui']);
            $stmt->bindParam(':tanggal_kadaluarsa', $_POST['tanggal_kadaluarsa']);

            $stmt->execute();
            $pdo->commit();
            $success_message = "Data berhasil diperbarui!";
        } else {
            // Add new record with auto ID
            if (empty($_POST['nama']) || empty($_POST['jumlah']) || empty($_POST['dosis']) || 
                empty($_POST['diperbarui']) || empty($_POST['tanggal_kadaluarsa'])) {
                throw new Exception("Semua field harus diisi!");
            }

            // Cek apakah obat dengan nama yang sama sudah ada
            $check_obat = $pdo->prepare("SELECT s.id_stok, s.id_pengingat, s.jumlah FROM stok_obat s 
                                        WHERE LOWER(nama) = LOWER(:nama) LIMIT 1");
            $check_obat->bindParam(':nama', $_POST['nama'], PDO::PARAM_STR);
            $check_obat->execute();
            $existing_obat = $check_obat->fetch(PDO::FETCH_ASSOC);

            if ($existing_obat) {
                // Update jumlah stok yang sudah ada
                $new_jumlah = $existing_obat['jumlah'] + $_POST['jumlah'];
                $update_sql = "UPDATE stok_obat SET 
                            jumlah = :jumlah,
                            diperbarui = :diperbarui,
                            tanggal_kadaluarsa = :tanggal_kadaluarsa
                            WHERE id_stok = :id_stok";
                
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->bindParam(':jumlah', $new_jumlah, PDO::PARAM_INT);
                $update_stmt->bindParam(':diperbarui', $_POST['diperbarui']);
                $update_stmt->bindParam(':tanggal_kadaluarsa', $_POST['tanggal_kadaluarsa']);
                $update_stmt->bindParam(':id_stok', $existing_obat['id_stok'], PDO::PARAM_INT);
                $update_stmt->execute();
                
                $pdo->commit();
                $success_message = "Data obat ditambahkan ke stok yang sudah ada!";
            } else {
                // Insert ke pengingatobat dulu untuk mendapatkan ID
                $sql_pengingat = "INSERT INTO pengingatobat (patient_id, condition_name, severity, nama_obat, waktu_pengingat) 
                                VALUES ('AUTO', 'AUTO', 'Normal', :nama_obat, CURRENT_TIME())";
                $stmt_pengingat = $pdo->prepare($sql_pengingat);
                $stmt_pengingat->bindParam(':nama_obat', $_POST['nama'], PDO::PARAM_STR);
                $stmt_pengingat->execute();
                
                // Dapatkan ID yang baru dibuat
                $id_pengingat = $pdo->lastInsertId();

                // Insert ke stok_obat
                $sql = "INSERT INTO stok_obat (nama, jumlah, dosis, diperbarui, tanggal_kadaluarsa, id_pengingat) 
                        VALUES (:nama, :jumlah, :dosis, :diperbarui, :tanggal_kadaluarsa, :id_pengingat)";
                
                $stmt = $pdo->prepare($sql);
                
                $stmt->bindParam(':nama', $_POST['nama'], PDO::PARAM_STR);
                $stmt->bindParam(':jumlah', $_POST['jumlah'], PDO::PARAM_INT);
                $stmt->bindParam(':dosis', $_POST['dosis'], PDO::PARAM_STR);
                $stmt->bindParam(':diperbarui', $_POST['diperbarui']);
                $stmt->bindParam(':tanggal_kadaluarsa', $_POST['tanggal_kadaluarsa']);
                $stmt->bindParam(':id_pengingat', $id_pengingat, PDO::PARAM_INT);

                $stmt->execute();
                $pdo->commit();
                $success_message = "Data berhasil disimpan!";
            }
        }
    } catch(Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        $error_message = "Error: " . $e->getMessage();
    }
}

// Kode untuk menampilkan data
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query untuk data stok obat
    $stmt = $pdo->prepare("SELECT * FROM stok_obat ORDER BY diperbarui DESC");
    $stmt->execute();
    $data_stok = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}
    ?>

    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Stok Obat UKS</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <style>
            :root {
                --primary-color: #1ca883;
                --secondary-color: #f0f9f6;
                --accent-color: #ff6b6b;
                --text-color: #2c3e50;
                --background-color: #ecf0f1;
                --card-hover: #e8f5f1;
                --danger-color: #e74c3c;
            }

            body {
                font-family: 'Poppins', sans-serif;
                margin: 0;
                padding: 0;
                background: linear-gradient(135deg, var(--secondary-color), #e8f5f1);
                color: var(--text-color);
                min-height: 100vh;
            }

            .container {
                display: flex;
                gap: 20px;
                padding: 20px;
                margin-top: 40px;
                flex-wrap: wrap;
            }

            form {
                flex: 0 0 350px;
                background: white;
                padding: 20px;
                border-radius: 15px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
                height: fit-content;
            }

            .dashboard-header {
                background: linear-gradient(135deg, var(--primary-color), #159f7f);
                color: white;
                padding: 1.5rem;
                border-radius: 15px;
                text-align: center;
                margin-bottom: 20px;
                width: 100%;
            }

            .form-group {
                margin-bottom: 15px;
            }

            label {
                display: block;
                margin-bottom: 8px;
                font-weight: 500;
                color: var(--text-color);
            }

            input[type="text"],
            input[type="number"],
            input[type="date"],
            textarea {
                width: 100%;
                padding: 10px;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                font-size: 0.9rem;
                box-sizing: border-box;
                margin-bottom: 5px;
            }

            .table-container {
                flex: 1;
                background: white;
                padding: 20px;
                border-radius: 15px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
                min-width: 0;
                overflow-x: auto;
            }

            table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                font-size: 0.9rem;
            }

            th {
                background: var(--primary-color);
                color: white;
                padding: 12px;
                text-align: left;
                font-weight: 500;
                white-space: nowrap;
            }

            td {
                padding: 12px;
                border-bottom: 1px solid #e2e8f0;
            }

            .btn {
                background: var(--primary-color);
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-size: 0.9rem;
                transition: all 0.3s ease;
            }

            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 6px rgba(28, 168, 131, 0.2);
            }

            .action-buttons {
                display: flex;
                gap: 8px;
                align-items: center;
            }

            .btn-edit, 
            .btn-delete {
                background-color: #3498db;
                color: white;
                border: none;
                width: 32px;
                height: 32px;
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.2s ease;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
            }

            .btn-edit:hover, 
            .btn-delete:hover {
                transform: translateY(-2px);
            }

            .btn-delete {
                background-color: #ff5252;
            }

            .expired-row {
                background-color: #ffe4e4 !important;
            }

            .expired-label {
                color: #e74c3c;
                font-weight: 600;
                font-size: 0.8rem;
                padding: 2px 6px;
                border-radius: 4px;
                background: #ffd5d5;
                margin-left: 8px;
                display: inline-block;
            }

            .btn-back {
                position: fixed;
                top: 20px;
                left: 20px;
                background: var(--primary-color);
                color: white;
                padding: 8px 16px;
                border-radius: 20px;
                text-decoration: none;
                display: flex;
                align-items: center;
                gap: 8px;
                z-index: 1000;
                transition: all 0.3s ease;
            }

            .alert {
                padding: 12px;
                border-radius: 8px;
                margin-bottom: 15px;
                text-align: center;
                width: 100%;
            }

            .alert.success {
                background-color: #def7ec;
                color: #0e9f6e;
            }

            .alert.error {
                background-color: #fde2e8;
                color: #e02424;
            }

            .modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1000;
            }

            .modal-content {
                background: white;
                width: 90%;
                max-width: 500px;
                margin: 20px auto;
                padding: 20px;
                border-radius: 15px;
                position: relative;
            }

            .close {
                position: absolute;
                right: 20px;
                top: 15px;
                font-size: 24px;
                cursor: pointer;
                color: var(--text-color);
                transition: color 0.3s ease;
            }

            .close:hover {
                color: var(--accent-color);
            }

            @media (max-width: 768px) {
                .container {
                    padding: 10px;
                }
                
                form, .table-container {
                    flex: 1 1 100%;
                }
                
                .table-container {
                    overflow-x: auto;
                }
                
                th, td {
                    padding: 8px;
                }
                
                .btn-back {
                    position: static;
                    margin: 10px;
                }
                
                .modal-content {
                    width: 95%;
                    margin: 10px;
                }
            }
        </style>
    </head>
    <body>
        <a href="dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
        
        <div class="container">
            <?php if (isset($success_message)): ?>
                <div class="alert success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="dashboard-header">
                <h1>Stok Obat UKS</h1>
            </div>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="nama">Nama Obat:</label>
                    <input type="text" id="nama" name="nama" required>
                </div>

                <div class="form-group">
                    <label for="jumlah">Jumlah:</label>
                    <input type="number" id="jumlah" name="jumlah" min="0" required>
                </div>

                <div class="form-group">
                    <label for="dosis">Dosis:</label>
                    <input type="text" id="dosis" name="dosis" required>
                </div>

                <div class="form-group">
                    <label for="diperbarui">Tanggal Diperbarui:</label>
                    <input type="date" id="diperbarui" name="diperbarui" required>
                </div>

                <div class="form-group">
                    <label for="tanggal_kadaluarsa">Tanggal Kadaluarsa:</label>
                    <input type="date" id="tanggal_kadaluarsa" name="tanggal_kadaluarsa" required>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Simpan Data
                </button>
            </form>

            <!-- Tabel Data -->
            <?php if (!empty($data_stok)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Nama Obat</th>
                                <th>Jumlah</th>
                                <th>Dosis</th>
                                <th>Tanggal Diperbarui</th>
                                <th>Tanggal Kadaluarsa</th>
                                <th>ID Obat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $no = 1;
                        $today = new DateTime();
                        foreach ($data_stok as $item): 
                            $expiry_date = new DateTime($item['tanggal_kadaluarsa']);
                            $is_expired = $expiry_date < $today;
                        ?>
                            <tr class="<?php echo $is_expired ? 'expired-row' : ''; ?>">
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($item['nama']); ?>
                                    <?php if ($is_expired): ?>
                                        <span class="expired-label">Obat sudah expired</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['jumlah']); ?></td>
                                <td><?php echo htmlspecialchars($item['dosis']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($item['diperbarui'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($item['tanggal_kadaluarsa'])); ?></td>
                                <td><?php echo htmlspecialchars($item['id_pengingat']); ?></td>
                                <td class="action-buttons">
                                    <button onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)" 
                                            class="btn-edit" title="Edit">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button onclick="deleteItem(<?php echo $item['id_stok']; ?>)"
                                            class="btn-delete" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>Tidak ada data stok obat saat ini.</p>
            <?php endif; ?>

            <!-- Modal Edit -->
            <div id="editModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <h2>Edit Data Stok Obat</h2>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id_stok" id="edit_id_stok">
                        
                        <div class="form-group">
                            <label for="edit_nama">Nama Obat:</label>
                            <input type="text" id="edit_nama" name="nama" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_jumlah">Jumlah:</label>
                            <input type="number" id="edit_jumlah" name="jumlah" min="0" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_dosis">Dosis:</label>
                            <input type="text" id="edit_dosis" name="dosis" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_diperbarui">Tanggal Diperbarui:</label>
                            <input type="date" id="edit_diperbarui" name="diperbarui" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_tanggal_kadaluarsa">Tanggal Kadaluarsa:</label>
                            <input type="date" id="edit_tanggal_kadaluarsa" name="tanggal_kadaluarsa" required>
                        </div>

                        <input type="hidden" id="edit_id_pengingat" name="id_pengingat">

                        <button type="submit" class="btn">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function editItem(item) {
                document.getElementById('editModal').style.display = 'block';
                document.getElementById('edit_id_stok').value = item.id_stok;
                document.getElementById('edit_nama').value = item.nama;
                document.getElementById('edit_jumlah').value = item.jumlah;
                document.getElementById('edit_dosis').value = item.dosis;
                document.getElementById('edit_diperbarui').value = item.diperbarui;
                document.getElementById('edit_tanggal_kadaluarsa').value = item.tanggal_kadaluarsa;
                document.getElementById('edit_id_pengingat').value = item.id_pengingat;
            }

            function deleteItem(id_stok) {
                if (confirm('Apakah Anda yakin ingin menghapus item ini?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';
                    
                    const deleteInput = document.createElement('input');
                    deleteInput.type = 'hidden';
                    deleteInput.name = 'delete';
                    deleteInput.value = '1';
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id_stok';
                    idInput.value = id_stok;
                    
                    form.appendChild(deleteInput);
                    form.appendChild(idInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            }

            function closeModal() {
                document.getElementById('editModal').style.display = 'none';
            }

            window.onclick = function(event) {
                if (event.target == document.getElementById('editModal')) {
                    closeModal();
                }
            }
        </script>
    </body>
    </html>
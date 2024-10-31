<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_database";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Penanganan khusus untuk penghapusan
        if (isset($_POST['delete'])) {
            $stmt = $conn->prepare("DELETE FROM inventaris_uks WHERE id_barang = :id_barang");
            $stmt->bindParam(':id_barang', $_POST['id_barang'], PDO::PARAM_INT);
            $stmt->execute();
            $success_message = "Data berhasil dihapus!";
        } 
        // Jika bukan penghapusan, cek action untuk add atau edit
        elseif (isset($_POST['action'])) {
            if ($_POST['action'] == 'edit') {
                $sql = "UPDATE inventaris_uks SET 
                        nama_barang = :nama_barang,
                        kategori = :kategori,
                        jumlah = :jumlah,
                        satuan = :satuan,
                        tanggal_masuk = :tanggal_masuk,
                        tanggal_kadaluarsa = :tanggal_kadaluarsa,
                        kondisi = :kondisi,
                        lokasi = :lokasi,
                        keterangan = :keterangan
                        WHERE id_barang = :id_barang";
                        
                $stmt = $conn->prepare($sql);
                
                $stmt->bindParam(':id_barang', $_POST['id_barang'], PDO::PARAM_INT);
                $stmt->bindParam(':nama_barang', $_POST['nama_barang'], PDO::PARAM_STR);
                $stmt->bindParam(':kategori', $_POST['kategori'], PDO::PARAM_STR);
                $stmt->bindParam(':jumlah', $_POST['jumlah'], PDO::PARAM_INT);
                $stmt->bindParam(':satuan', $_POST['satuan'], PDO::PARAM_STR);
                $stmt->bindParam(':tanggal_masuk', $_POST['tanggal_masuk']);
                $stmt->bindParam(':tanggal_kadaluarsa', $_POST['tanggal_kadaluarsa']);
                $stmt->bindParam(':kondisi', $_POST['kondisi'], PDO::PARAM_STR);
                $stmt->bindParam(':lokasi', $_POST['lokasi'], PDO::PARAM_STR);
                $stmt->bindParam(':keterangan', $_POST['keterangan'], PDO::PARAM_STR);

                $stmt->execute();
                $success_message = "Data berhasil diperbarui!";
            } else {
                // Validasi hanya untuk penambahan data baru
                if (empty($_POST['nama_barang']) || empty($_POST['kategori']) || empty($_POST['jumlah']) || 
                    empty($_POST['satuan']) || empty($_POST['tanggal_masuk']) || empty($_POST['kondisi']) || 
                    empty($_POST['lokasi'])) {
                    throw new Exception("Semua field harus diisi!");
                }

                $sql = "INSERT INTO inventaris_uks (nama_barang, kategori, jumlah, satuan, tanggal_masuk, 
                        tanggal_kadaluarsa, kondisi, lokasi, keterangan) 
                        VALUES (:nama_barang, :kategori, :jumlah, :satuan, :tanggal_masuk, 
                        :tanggal_kadaluarsa, :kondisi, :lokasi, :keterangan)";
                
                $stmt = $conn->prepare($sql);
                
                $stmt->bindParam(':nama_barang', $_POST['nama_barang'], PDO::PARAM_STR);
                $stmt->bindParam(':kategori', $_POST['kategori'], PDO::PARAM_STR);
                $stmt->bindParam(':jumlah', $_POST['jumlah'], PDO::PARAM_INT);
                $stmt->bindParam(':satuan', $_POST['satuan'], PDO::PARAM_STR);
                $stmt->bindParam(':tanggal_masuk', $_POST['tanggal_masuk']);
                $stmt->bindParam(':tanggal_kadaluarsa', $_POST['tanggal_kadaluarsa']);
                $stmt->bindParam(':kondisi', $_POST['kondisi'], PDO::PARAM_STR);
                $stmt->bindParam(':lokasi', $_POST['lokasi'], PDO::PARAM_STR);
                $stmt->bindParam(':keterangan', $_POST['keterangan'], PDO::PARAM_STR);

                $stmt->execute();
                $success_message = "Data berhasil disimpan!";
            }
        }
    } catch(Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Fetch inventory data
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->prepare("SELECT * FROM inventaris_uks ORDER BY tanggal_masuk DESC");
    $stmt->execute();
    $data_inventaris = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Inventaris UKS</title>
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
            background-color: var(--secondary-color);
            color: var(--text-color);
            min-height: 100vh;
            padding-bottom: 60px;
        }

        .container {
            max-width: 1300px;
            margin: 2rem auto;
            padding: 0 2rem;
            position: relative;
        }

        .dashboard-header {
            position: relative;
            color: white;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, var(--primary-color), #159f7f);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        form {
            background-color: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid var(--secondary-color);
            border-radius: 8px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn,
        .btn-back {
            padding: 0.6rem 1.2rem;
            background: linear-gradient(135deg, var(--primary-color), #159f7f);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn:hover,
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(28, 168, 131, 0.3);
        }

        .btn-back {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
            animation: fadeOut 3s forwards;
            animation-delay: 2s;
        }

        .alert.success {
            background-color: #def7ec;
            color: #0e9f6e;
        }

        .alert.error {
            background-color: #fde2e8;
            color: #e02424;
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; display: none; }
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 15px;
            text-align: left;
            border: none;
            white-space: nowrap;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #edf2f7;
            color: #2d3748;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f7fafc;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: flex-start;
            align-items: center;
        }

        .btn-edit {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.3s ease;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #2980b9, #2471a3);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(52, 152, 219, 0.2);
        }

        .btn-delete {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #c0392b, #a93226);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(231, 76, 60, 0.2);
        }

        /* Status Badges */
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            text-align: center;
            display: inline-block;
        }

        .status-baik {
            background-color: #def7ec;
            color: #0e9f6e;
        }

        .status-rusak {
            background-color: #fde8e8;
            color: #e02424;
        }

        .status-habis {
            background-color: #fdf6b2;
            color: #c27803;
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
            overflow-y: auto;
        }

        .modal-content {
            position: relative;
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            width: 70%;
            border-radius: 10px;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #666;
        }

        .close:hover {
            color: #333;
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 4px;
            }

            .btn-edit,
            .btn-delete {
                width: 100%;
                justify-content: center;
            }

            .modal-content {
                width: 90%;
                margin: 10% auto;
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
            <h1>Form Inventaris UKS</h1>
        </div>

        <!-- Form Tambah Data -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="nama_barang">Nama Barang:</label>
                <input type="text" id="nama_barang" name="nama_barang" required>
            </div>

            <div class="form-group">
                <label for="kategori">Kategori:</label>
                <select id="kategori" name="kategori" required>
                    <option value="">Pilih Kategori</option>
                    <option value="Obat">Obat</option>
                    <option value="Peralatan">Peralatan</option>
                    <option value="Perlengkapan">Perlengkapan</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>

            <div class="form-group">
                <label for="jumlah">Jumlah:</label>
                <input type="number" id="jumlah" name="jumlah" min="0" required>
            </div>

            <div class="form-group">
                <label for="satuan">Satuan:</label>
                <select id="satuan" name="satuan" required>
                    <option value="">Pilih Satuan</option>
                    <option value="Pcs">Pcs</option>
                    <option value="Box">Box</option>
                    <option value="Botol">Botol</option>
                    <option value="Pack">Pack</option>
                    <option value="Strip">Strip</option>
                    <option value="Unit">Unit</option>
                </select>
            </div>

            <div class="form-group">
                <label for="tanggal_masuk">Tanggal Masuk:</label>
                <input type="date" id="tanggal_masuk" name="tanggal_masuk" required>
            </div>

            <div class="form-group">
                <label for="tanggal_kadaluarsa">Tanggal Kadaluarsa:</label>
                <input type="date" id="tanggal_kadaluarsa" name="tanggal_kadaluarsa">
            </div>

            <div class="form-group">
                <label for="kondisi">Kondisi:</label>
                <select id="kondisi" name="kondisi" required>
                    <option value="">Pilih Kondisi</option>
                    <option value="Baik">Baik</option>
                    <option value="Rusak">Rusak</option>
                    <option value="Habis">Habis</option>
                </select>
            </div>

            <div class="form-group">
                <label for="lokasi">Lokasi:</label>
                <select id="lokasi" name="lokasi" required>
                    <option value="">Pilih Lokasi</option>
                    <option value="Lemari 1">Lemari 1</option>
                    <option value="Lemari 2">Lemari 2</option>
                    <option value="Lemari 3">Lemari 3</option>
                    <option value="Rak 1">Rak 1</option>
                    <option value="Rak 2">Rak 2</option>
                    <option value="Rak 3">Rak 3</option>
                </select>
            </div>

            <div class="form-group">
                <label for="keterangan">Keterangan:</label>
                <textarea id="keterangan" name="keterangan" rows="4"></textarea>
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-save"></i> Simpan Data
            </button>
        </form>

        <!-- Tabel Data -->
        <?php if (!empty($data_inventaris)): ?>
            <h2>Data Inventaris UKS</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Jumlah</th>
                            <th>Satuan</th>
                            <th>Tanggal Masuk</th>
                            <th>Tanggal Kadaluarsa</th>
                            <th>Kondisi</th>
                            <th>Lokasi</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($data_inventaris as $item): 
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                                <td><?php echo htmlspecialchars($item['kategori']); ?></td>
                                <td><?php echo htmlspecialchars($item['jumlah']); ?></td>
                                <td><?php echo htmlspecialchars($item['satuan']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($item['tanggal_masuk'])); ?></td>
                                <td>
                                    <?php 
                                    echo !empty($item['tanggal_kadaluarsa']) 
                                        ? date('d/m/Y', strtotime($item['tanggal_kadaluarsa']))
                                        : '-';
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($item['kondisi']); ?>">
                                        <?php echo htmlspecialchars($item['kondisi']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($item['lokasi']); ?></td>
                                <td><?php echo htmlspecialchars($item['keterangan'] ?: '-'); ?></td>
                                <td class="action-buttons">
                                    <button onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)" 
                                            class="btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus item ini?');">
                                        <input type="hidden" name="delete" value="1">
                                        <input type="hidden" name="id_barang" value="<?php echo $item['id_barang']; ?>">
                                        <button type="submit" class="btn-delete">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Tidak ada data inventaris saat ini.</p>
        <?php endif; ?>

        <!-- Modal Edit -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>Edit Data Inventaris</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id_barang" id="edit_id_barang">
                    
                    <div class="form-group">
                        <label for="edit_nama_barang">Nama Barang:</label>
                        <input type="text" id="edit_nama_barang" name="nama_barang" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_kategori">Kategori:</label>
                        <select id="edit_kategori" name="kategori" required>
                            <option value="Obat">Obat</option>
                            <option value="Peralatan">Peralatan</option>
                            <option value="Perlengkapan">Perlengkapan</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_jumlah">Jumlah:</label>
                        <input type="number" id="edit_jumlah" name="jumlah" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_satuan">Satuan:</label>
                        <select id="edit_satuan" name="satuan" required>
                            <option value="Pcs">Pcs</option>
                            <option value="Box">Box</option>
                            <option value="Botol">Botol</option>
                            <option value="Pack">Pack</option>
                            <option value="Strip">Strip</option>
                            <option value="Unit">Unit</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_tanggal_masuk">Tanggal Masuk:</label>
                        <input type="date" id="edit_tanggal_masuk" name="tanggal_masuk" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_tanggal_kadaluarsa">Tanggal Kadaluarsa:</label>
                        <input type="date" id="edit_tanggal_kadaluarsa" name="tanggal_kadaluarsa">
                    </div>

                    <div class="form-group">
                        <label for="edit_kondisi">Kondisi:</label>
                        <select id="edit_kondisi" name="kondisi" required>
                            <option value="Baik">Baik</option>
                            <option value="Rusak">Rusak</option>
                            <option value="Habis">Habis</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_lokasi">Lokasi:</label>
                        <select id="edit_lokasi" name="lokasi" required>
                            <option value="Lemari 1">Lemari 1</option>
                            <option value="Lemari 2">Lemari 2</option>
                            <option value="Lemari 3">Lemari 3</option>
                            <option value="Rak 1">Rak 1</option>
                            <option value="Rak 2">Rak 2</option>
                            <option value="Rak 3">Rak 3</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_keterangan">Keterangan:</label>
                        <textarea id="edit_keterangan" name="keterangan" rows="4"></textarea>
                    </div>

                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
    function editItem(item) {
        // Tampilkan modal
        document.getElementById('editModal').style.display = 'block';
        
        // Isi form dengan data yang ada
        document.getElementById('edit_id_barang').value = item.id_barang;
        document.getElementById('edit_nama_barang').value = item.nama_barang;
        document.getElementById('edit_kategori').value = item.kategori;
        document.getElementById('edit_jumlah').value = item.jumlah;
        document.getElementById('edit_satuan').value = item.satuan;
        document.getElementById('edit_tanggal_masuk').value = item.tanggal_masuk;
        document.getElementById('edit_tanggal_kadaluarsa').value = item.tanggal_kadaluarsa || '';
        document.getElementById('edit_kondisi').value = item.kondisi;
        document.getElementById('edit_lokasi').value = item.lokasi;
        document.getElementById('edit_keterangan').value = item.keterangan || '';
    }

    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    // Tutup modal jika user mengklik di luar modal
    window.onclick = function(event) {
        if (event.target == document.getElementById('editModal')) {
            closeModal();
        }
    }
    </script>
</body>
</html>
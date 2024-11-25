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
                $stmt->bindParam(':kondisi', $_POST['kondisi'], PDO::PARAM_STR);
                $stmt->bindParam(':lokasi', $_POST['lokasi'], PDO::PARAM_STR);
                $stmt->bindParam(':keterangan', $_POST['keterangan'], PDO::PARAM_STR);
            
                $stmt->execute();
                $success_message = "Data berhasil diperbarui!";
            } else {
                $sql = "INSERT INTO inventaris_uks (nama_barang, kategori, jumlah, satuan, tanggal_masuk, 
                        kondisi, lokasi, keterangan) 
                        VALUES (:nama_barang, :kategori, :jumlah, :satuan, :tanggal_masuk,
                        :kondisi, :lokasi, :keterangan)";
                
                $stmt = $conn->prepare($sql);
                
                $stmt->bindParam(':nama_barang', $_POST['nama_barang'], PDO::PARAM_STR);
                $stmt->bindParam(':kategori', $_POST['kategori'], PDO::PARAM_STR);
                $stmt->bindParam(':jumlah', $_POST['jumlah'], PDO::PARAM_INT);
                $stmt->bindParam(':satuan', $_POST['satuan'], PDO::PARAM_STR);
                $stmt->bindParam(':tanggal_masuk', $_POST['tanggal_masuk']);
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

/* Form Styles */
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
select,
textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9rem;
    box-sizing: border-box;
    margin-bottom: 5px;
}

/* Table Styles */
.table-container {
    flex: 1;
    background: white;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    min-width: 0; /* Prevents table from overflowing */
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

/* Button Styles */
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

<style>
.action-buttons {
    display: flex;
    gap: 10px;
    align-items: center;
}

.btn-edit {
    background: #3498db;
    color: white;
    border: none;
    width: 35px;
    height: 35px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-delete {
    background: linear-gradient(45deg, #ff4d4d, #ff6b6b);
    color: white;
    border: none;
    width: 35px;
    height: 35px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.btn-delete::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, #ff3333, #ff4d4d);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.btn-delete:hover::before {
    opacity: 1;
}

.btn-delete i {
    color: white;
    font-size: 1rem;
    position: relative;
    z-index: 2;
}

.btn-edit:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
    background: linear-gradient(45deg, #3498db, #2980b9);
}

.btn-delete:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(255, 77, 77, 0.3);
}

/* Optional: Animasi ketika diklik */
.btn-delete:active {
    transform: scale(0.95);
}

/* Optional: Tambahkan efek pulse saat hover */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(255, 77, 77, 0.4);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(255, 77, 77, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(255, 77, 77, 0);
    }
}

.btn-delete:hover {
    animation: pulse 1.5s infinite;
}

/* Optional: Style untuk modal konfirmasi hapus */
.delete-confirm-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.delete-confirm-content {
    background: white;
    padding: 25px;
    border-radius: 12px;
    text-align: center;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.delete-confirm-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 20px;
}

.delete-confirm-yes {
    background: #ff4d4d;
    color: white;
    padding: 8px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.delete-confirm-no {
    background: #6c757d;
    color: white;
    padding: 8px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}


/* Back Button */
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
    font-size: 0.9rem;
}

.btn-back:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(28, 168, 131, 0.2);
}

.btn-back i {
    font-size: 1rem;
}

/* Status Badges */
.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-baik {
    background-color: #def7ec;
    color: #0e9f6e;
}

.status-rusak {
    background-color: #fde2e8;
    color: #e02424;
}

.status-habis {
    background-color: #fef3c7;
    color: #d97706;
}

/* Alerts */
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

/* Modal Styles */
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
    max-height: 90vh;
    overflow-y: auto;
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
    overflow-y: auto;
    padding: 20px 0;
}

/* Custom scrollbar for modal */
.modal-content::-webkit-scrollbar {
    width: 8px;
}

.modal-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.modal-content::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 4px;
}

.modal-content::-webkit-scrollbar-thumb:hover {
    background: #159f7f;
}

/* Ensure form elements don't overflow */
.modal-content form {
    width: 100%;
    box-sizing: border-box;
}

.modal-content .form-group {
    margin-bottom: 15px;
    width: 100%;
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
            <!-- Hapus kolom Tanggal Kadaluarsa -->
            <th>Kondisi</th>
            <th>Lokasi</th>
            <th>Keterangan</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
                    <tbody>
                        <?php 
                        $no = 1;
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
                                <!-- Hapus kolom Tanggal Kadaluarsa -->
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($item['kondisi']); ?>">
                                        <?php echo htmlspecialchars($item['kondisi']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($item['lokasi']); ?></td>
                                <td><?php echo htmlspecialchars($item['keterangan'] ?: '-'); ?></td>
                                <td class="action-buttons">
        <button onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)" 
                class="btn-edit" title="Edit">
            <i class="fas fa-pen"></i>
        </button>
        <form method="POST" style="display: inline;" 
              onsubmit="return confirm('Apakah Anda yakin ingin menghapus item ini?');">
            <input type="hidden" name="delete" value="1">
            <input type="hidden" name="id_barang" value="<?php echo $item['id_barang']; ?>">
            <button class="btn-delete" title="Hapus">
    <i class="fas fa-trash"></i>
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

                    <!-- Di form edit, ubah bagian kategori menjadi: -->
<div class="form-group">
    <label for="edit_kategori">Kategori:</label>
    <select id="edit_kategori" name="kategori" required>
        <option value="Peralatan">Peralatan</option>
        <option value="Perlengkapan">Perlengkapan</option>
        <option value="Lainnya">Lainnya</option>
    </select>
</div>

<!-- Hapus form group tanggal kadaluarsa -->
<!-- <div class="form-group">
    <label for="edit_tanggal_kadaluarsa">Tanggal Kadaluarsa:</label>
    <input type="date" id="edit_tanggal_kadaluarsa" name="tanggal_kadaluarsa">
</div> -->

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
// Fungsi untuk menangani perubahan kategori pada form tambah dan edit
function aturPerubahanKategori(idPilihanKategori, idInputTanggal) {
    const pilihanKategori = document.getElementById(idPilihanKategori);
    const inputTanggal = document.getElementById(idInputTanggal);
    const labelTanggal = inputTanggal.previousElementSibling;

    pilihanKategori.addEventListener('change', function() {
        const kategoriTerpilih = this.value;
        const tanggalWajib = !['Peralatan', 'Perlengkapan'].includes(kategoriTerpilih);

        if (tanggalWajib && kategoriTerpilih === 'Obat') {
            inputTanggal.required = true;
            labelTanggal.textContent = 'Tanggal Kadaluarsa: *';
            inputTanggal.parentElement.style.display = 'block';
        } else {
            inputTanggal.required = false;
            labelTanggal.textContent = 'Tanggal Kadaluarsa:';
            if (['Peralatan', 'Perlengkapan'].includes(kategoriTerpilih)) {
                inputTanggal.value = '';
                inputTanggal.parentElement.style.display = 'none';
            } else {
                inputTanggal.parentElement.style.display = 'block';
            }
        }
    });

    pilihanKategori.dispatchEvent(new Event('change'));
}

// Inisialisasi untuk kedua form saat dokumen dimuat
document.addEventListener('DOMContentLoaded', function() {
    // Pengaturan untuk form tambah
    aturPerubahanKategori('kategori', 'tanggal_kadaluarsa');
    
    // Pengaturan untuk form edit
    aturPerubahanKategori('edit_kategori', 'edit_tanggal_kadaluarsa');
});

// Fungsi untuk menangani proses edit data
// Hapus semua fungsi yang berhubungan dengan tanggal kadaluarsa
<script>
function editItem(item) {
    // Buka modal
    document.getElementById('editModal').style.display = 'block';
    
    // Isi form dengan data yang ada
    document.getElementById('edit_id_barang').value = item.id_barang;
    document.getElementById('edit_nama_barang').value = item.nama_barang;
    document.getElementById('edit_kategori').value = item.kategori;
    document.getElementById('edit_jumlah').value = item.jumlah;
    document.getElementById('edit_satuan').value = item.satuan;
    document.getElementById('edit_tanggal_masuk').value = item.tanggal_masuk;
    document.getElementById('edit_kondisi').value = item.kondisi;
    document.getElementById('edit_lokasi').value = item.lokasi;
    document.getElementById('edit_keterangan').value = item.keterangan || '';
}

// Fungsi untuk menutup modal
function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Tutup modal saat mengklik di luar area modal
window.onclick = function(event) {
    if (event.target == document.getElementById('editModal')) {
        closeModal();
    }
}
</script>
</body>
</html>
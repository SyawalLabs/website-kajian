<?php
include '../includes/auth.php';
if (!isLoggedIn() || !isPembina()) {
    redirect('../login.php');
}

$success_message = '';
$error_message = '';

// Proses tambah kajian
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $pemateri = mysqli_real_escape_string($conn, $_POST['pemateri']);
    $tanggal = $_POST['tanggal'];
    $waktu = $_POST['waktu'];
    $tempat = mysqli_real_escape_string($conn, $_POST['tempat']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $link_video = mysqli_real_escape_string($conn, $_POST['link_video']);
    $created_by = $_SESSION['user_id'];
    
    $query = "INSERT INTO kajian (judul, pemateri, tanggal, waktu, tempat, deskripsi, link_video, created_by) 
              VALUES ('$judul', '$pemateri', '$tanggal', '$waktu', '$tempat', '$deskripsi', '$link_video', '$created_by')";
    
    if (mysqli_query($conn, $query)) {
        $success_message = "Kajian berhasil ditambahkan!";
    } else {
        $error_message = "Gagal menambahkan kajian: " . mysqli_error($conn);
    }
}

// Proses edit kajian
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $id = $_POST['id'];
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $pemateri = mysqli_real_escape_string($conn, $_POST['pemateri']);
    $tanggal = $_POST['tanggal'];
    $waktu = $_POST['waktu'];
    $tempat = mysqli_real_escape_string($conn, $_POST['tempat']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $link_video = mysqli_real_escape_string($conn, $_POST['link_video']);
    $created_by = $_SESSION['user_id'];
    
    // Pastikan hanya bisa mengedit milik sendiri
    $query = "UPDATE kajian SET 
              judul = '$judul', 
              pemateri = '$pemateri', 
              tanggal = '$tanggal', 
              waktu = '$waktu', 
              tempat = '$tempat', 
              deskripsi = '$deskripsi', 
              link_video = '$link_video' 
              WHERE id = $id AND created_by = $created_by";
    
    if (mysqli_query($conn, $query)) {
        $success_message = "Kajian berhasil diperbarui!";
    } else {
        $error_message = "Gagal memperbarui kajian: " . mysqli_error($conn);
    }
}

// Proses hapus kajian
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $created_by = $_SESSION['user_id'];
    
    // Pastikan hanya bisa menghapus milik sendiri
    $query = "DELETE FROM kajian WHERE id = $id AND created_by = $created_by";
    
    if (mysqli_query($conn, $query)) {
        $success_message = "Kajian berhasil dihapus!";
    } else {
        $error_message = "Gagal menghapus kajian: " . mysqli_error($conn);
    }
}

// Ambil data kajian untuk diedit
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $created_by = $_SESSION['user_id'];
    $query = "SELECT * FROM kajian WHERE id = $id AND created_by = $created_by";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
        $edit_data = mysqli_fetch_assoc($result);
    } else {
        $error_message = "Data kajian tidak ditemukan atau Anda tidak memiliki akses!";
    }
}

// Ambil semua kajian milik pembina yang login
$created_by = $_SESSION['user_id'];
$query = "SELECT * FROM kajian WHERE created_by = $created_by ORDER BY tanggal DESC, waktu DESC";
$result = mysqli_query($conn, $query);
$total_kajian = mysqli_num_rows($result);

// Ambil nama pembina dari session
$nama_pembina = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pembina';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kajian - Pembina</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
        }

        .navbar {
            background: #343a40;
            color: white;
            padding: 15px 0;
        }
        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
        }
        .navbar ul li {
            margin-left: 20px;
        }
        .navbar ul li a {
            color: white;
            text-decoration: none;
        }
        .navbar ul li a:hover {
            color: #007bff;
        }
        .main-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-header h2 {
            font-size: 2rem;
            color: #1e3c72;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header h2 i {
            color: #2a5298;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
            font-weight: 500;
        }

        .btn-primary {
            background: #1e3c72;
            color: white;
        }

        .btn-primary:hover {
            background: #2a5298;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30,60,114,0.3);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: #ffc107;
            color: #1e3c72;
        }

        .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.85rem;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: inline-block;
            min-width: 250px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .stats-card h3 {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .stats-card .number {
            font-size: 2.5rem;
            font-weight: bold;
        }

        .kajian-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .kajian-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border: 1px solid #e0e0e0;
        }

        .kajian-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }

        .kajian-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 15px 20px;
        }

        .kajian-header h3 {
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .kajian-header .pemateri {
            font-size: 0.9rem;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .kajian-body {
            padding: 20px;
        }

        .kajian-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #555;
            font-size: 0.95rem;
        }

        .info-item i {
            width: 20px;
            color: #1e3c72;
        }

        .kajian-deskripsi {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 0.95rem;
            color: #666;
            max-height: 100px;
            overflow-y: auto;
        }

        .kajian-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            grid-column: 1/-1;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: #1e3c72;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 20px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            background: white;
            margin: 50px auto;
            max-width: 600px;
            width: 90%;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .modal-header h3 {
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-header .close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: white;
            opacity: 0.8;
        }

        .modal-header .close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #1e3c72;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1e3c72;
            box-shadow: 0 0 0 3px rgba(30,60,114,0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #e9ecef;
            color: #495057;
        }

        .link-video {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #1e3c72;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .link-video:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
   <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <div class="navbar-logo">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <h1>MAKN ENDE <span>Panel Pembina</span></h1>
                </div>
            </div>
            <div class="navbar-menu">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="kelola_kajian.php"><i class="fas fa-calendar-alt"></i> Kelola Kajian</a>
                <a href="kelola_santri.php" class="active"><i class="fas fa-users-cog"></i> Kelola Santri</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>


    <div class="main-container">
        <div class="page-header">
            <h2>
                <i class="fas fa-calendar-alt"></i>
                Kelola Kajian
            </h2>
            <button class="btn btn-primary" onclick="openTambahModal()">
                <i class="fas fa-plus"></i> Tambah Kajian Baru
            </button>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Stats Card -->
        <div class="stats-card">
            <h3><i class="fas fa-calendar-check"></i> Total Kajian Saya</h3>
            <div class="number"><?php echo $total_kajian; ?></div>
            <div style="margin-top: 10px; font-size: 0.9rem; opacity: 0.9;">
                <i class="fas fa-user"></i> <?php echo htmlspecialchars($nama_pembina); ?>
            </div>
        </div>

        <!-- Grid Kajian -->
        <div class="kajian-grid">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="kajian-card">
                        <div class="kajian-header">
                            <h3><?php echo htmlspecialchars($row['judul']); ?></h3>
                            <div class="pemateri">
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($row['pemateri']); ?>
                            </div>
                        </div>
                        <div class="kajian-body">
                            <div class="kajian-info">
                                <div class="info-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo date('d F Y', strtotime($row['tanggal'])); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo date('H:i', strtotime($row['waktu'])); ?> WIB</span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($row['tempat']); ?></span>
                                </div>
                                <?php if (!empty($row['link_video'])): ?>
                                <div class="info-item">
                                    <i class="fas fa-video"></i>
                                    <a href="<?php echo htmlspecialchars($row['link_video']); ?>" target="_blank" class="link-video">
                                        Link Video <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($row['deskripsi'])): ?>
                                <div class="kajian-deskripsi">
                                    <i class="fas fa-align-left" style="margin-right: 5px; color: #1e3c72;"></i>
                                    <?php echo nl2br(htmlspecialchars($row['deskripsi'])); ?>
                                </div>
                            <?php endif; ?>

                            <div style="margin-top: 10px;">
                                <span class="badge">
                                    <i class="fas fa-tag"></i> ID: <?php echo $row['id']; ?>
                                </span>
                                <span class="badge">
                                    <i class="fas fa-clock"></i> Dibuat: <?php echo date('d/m/Y', strtotime($row['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="kajian-footer">
                            <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="?hapus=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" 
                               onclick="return confirm('Yakin ingin menghapus kajian ini?')">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>Belum Ada Kajian</h3>
                    <p>Anda belum membuat kajian. Klik tombol "Tambah Kajian Baru" untuk memulai.</p>
                    <button class="btn btn-primary" onclick="openTambahModal()">
                        <i class="fas fa-plus"></i> Buat Kajian Sekarang
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Tambah Kajian -->
    <div id="tambahModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Tambah Kajian Baru</h3>
                <button class="close" onclick="closeModal('tambahModal')">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="judul">Judul Kajian *</label>
                        <input type="text" id="judul" name="judul" required 
                               placeholder="Contoh: Kajian Fiqh Bab Thaharah">
                    </div>
                    
                    <div class="form-group">
                        <label for="pemateri">Pemateri *</label>
                        <input type="text" id="pemateri" name="pemateri" required 
                               placeholder="Contoh: Ustadz Rifai">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tanggal">Tanggal *</label>
                            <input type="date" id="tanggal" name="tanggal" required 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="waktu">Waktu *</label>
                            <input type="time" id="waktu" name="waktu" required 
                                   value="<?php echo date('H:i'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="tempat">Tempat *</label>
                        <input type="text" id="tempat" name="tempat" required 
                               placeholder="Contoh: Ruang Kelas 2 / Aula">
                    </div>
                    
                    <div class="form-group">
                        <label for="deskripsi">Deskripsi Kajian</label>
                        <textarea id="deskripsi" name="deskripsi" rows="4" 
                                  placeholder="Masukkan deskripsi kajian, materi yang akan dibahas, atau catatan penting..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="link_video">Link Video (Opsional)</label>
                        <input type="url" id="link_video" name="link_video" 
                               placeholder="Contoh: https://youtube.com/watch?v=...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="closeModal('tambahModal')">Batal</button>
                    <button type="submit" name="simpan" class="btn btn-success">
                        <i class="fas fa-save"></i> Simpan Kajian
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Kajian -->
    <?php if ($edit_data): ?>
    <div id="editModal" class="modal" style="display: block;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Kajian</h3>
                <a href="kelola_kajian.php" class="close" style="text-decoration: none; color: white;">&times;</a>
            </div>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_judul">Judul Kajian *</label>
                        <input type="text" id="edit_judul" name="judul" required 
                               value="<?php echo htmlspecialchars($edit_data['judul']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_pemateri">Pemateri *</label>
                        <input type="text" id="edit_pemateri" name="pemateri" required 
                               value="<?php echo htmlspecialchars($edit_data['pemateri']); ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_tanggal">Tanggal *</label>
                            <input type="date" id="edit_tanggal" name="tanggal" required 
                                   value="<?php echo $edit_data['tanggal']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_waktu">Waktu *</label>
                            <input type="time" id="edit_waktu" name="waktu" required 
                                   value="<?php echo $edit_data['waktu']; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_tempat">Tempat *</label>
                        <input type="text" id="edit_tempat" name="tempat" required 
                               value="<?php echo htmlspecialchars($edit_data['tempat']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_deskripsi">Deskripsi Kajian</label>
                        <textarea id="edit_deskripsi" name="deskripsi" rows="4"><?php echo htmlspecialchars($edit_data['deskripsi']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_link_video">Link Video (Opsional)</label>
                        <input type="url" id="edit_link_video" name="link_video" 
                               value="<?php echo htmlspecialchars($edit_data['link_video'] ?? ''); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="kelola_kajian.php" class="btn btn-danger">Batal</a>
                    <button type="submit" name="update" class="btn btn-success">
                        <i class="fas fa-save"></i> Update Kajian
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function openTambahModal() {
            document.getElementById('tambahModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Tutup modal jika klik di luar modal
        window.onclick = function(event) {
            const tambahModal = document.getElementById('tambahModal');
            const editModal = document.getElementById('editModal');
            
            if (event.target == tambahModal) {
                tambahModal.style.display = 'none';
            }
            if (event.target == editModal) {
                window.location.href = 'kelola_kajian.php';
            }
        }
    </script>
</body>
</html>
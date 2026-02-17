<?php
include '../includes/auth.php';
if (!isLoggedIn() || !isPembina()) {
    redirect('../login.php');
}

// Proses form tambah/update kehadiran
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['simpan_kehadiran'])) {
        $kajian_id = $_POST['kajian_id'];
        $santri_id = $_POST['santri_id'];
        $status = $_POST['status'];
        $keterangan = $_POST['keterangan'];
        
        // Cek apakah sudah ada data kehadiran
        $check = mysqli_prepare($conn, "SELECT id FROM kehadiran_santri WHERE kajian_id = ? AND santri_id = ?");
        mysqli_stmt_bind_param($check, "ii", $kajian_id, $santri_id);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);
        
        if (mysqli_num_rows($result) > 0) {
            // Update
            $stmt = mysqli_prepare($conn, "UPDATE kehadiran_santri SET status = ?, keterangan = ? WHERE kajian_id = ? AND santri_id = ?");
            mysqli_stmt_bind_param($stmt, "ssii", $status, $keterangan, $kajian_id, $santri_id);
        } else {
            // Insert
            $stmt = mysqli_prepare($conn, "INSERT INTO kehadiran_santri (kajian_id, santri_id, status, keterangan) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iiss", $kajian_id, $santri_id, $status, $keterangan);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Data kehadiran berhasil disimpan";
        } else {
            $_SESSION['error'] = "Gagal menyimpan data kehadiran";
        }
        
        header("Location: kelola_santri.php?kajian_id=" . $kajian_id);
        exit();
    }
}

// Hapus data kehadiran
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = mysqli_prepare($conn, "DELETE FROM kehadiran_santri WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Data kehadiran berhasil dihapus";
    }
    header("Location: kelola_santri.php" . (isset($_GET['kajian_id']) ? "?kajian_id=" . $_GET['kajian_id'] : ""));
    exit();
}

// Ambil daftar kajian yang dibuat pembina
$kajian_stmt = mysqli_prepare($conn, "SELECT * FROM kajian WHERE created_by = ? ORDER BY tanggal DESC");
mysqli_stmt_bind_param($kajian_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($kajian_stmt);
$daftar_kajian = mysqli_stmt_get_result($kajian_stmt);

// Ambil data kajian yang dipilih
$selected_kajian = null;
$data_kehadiran = [];
$daftar_santri = [];

if (isset($_GET['kajian_id'])) {
    $kajian_id = $_GET['kajian_id'];
    
    // Ambil detail kajian
    $kajian_detail = mysqli_prepare($conn, "SELECT * FROM kajian WHERE id = ? AND created_by = ?");
    mysqli_stmt_bind_param($kajian_detail, "ii", $kajian_id, $_SESSION['user_id']);
    mysqli_stmt_execute($kajian_detail);
    $selected_kajian = mysqli_stmt_get_result($kajian_detail)->fetch_assoc();
    
    if ($selected_kajian) {
        // Ambil semua santri
        $santri_query = "SELECT u.id, u.nama_lengkap, s.nis, s.kelas 
                         FROM users u 
                         LEFT JOIN santri s ON u.id = s.user_id 
                         WHERE u.role = 'santri' 
                         ORDER BY u.nama_lengkap";
        $daftar_santri = mysqli_query($conn, $santri_query);
        
        // Ambil data kehadiran untuk kajian ini
        $kehadiran_stmt = mysqli_prepare($conn, 
            "SELECT ks.*, u.nama_lengkap, s.nis, s.kelas 
             FROM kehadiran_santri ks 
             JOIN users u ON ks.santri_id = u.id 
             LEFT JOIN santri s ON u.id = s.user_id 
             WHERE ks.kajian_id = ? 
             ORDER BY ks.status, u.nama_lengkap");
        mysqli_stmt_bind_param($kehadiran_stmt, "i", $kajian_id);
        mysqli_stmt_execute($kehadiran_stmt);
        $data_kehadiran = mysqli_stmt_get_result($kehadiran_stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Santri Tidak Hadir - Pembina</title>
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
            margin-bottom: 30px;
        }

        .page-header h2 {
            font-size: 2rem;
            color: #1e3c72;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
            font-size: 1rem;
        }

        .kajian-selector {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .kajian-selector h3 {
            margin-bottom: 20px;
            color: #1e3c72;
            font-size: 1.3rem;
        }

        .kajian-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }

        .kajian-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .kajian-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-color: #1e3c72;
        }

        .kajian-card.active {
            border-color: #1e3c72;
            background: #e8f0fe;
        }

        .kajian-card .judul {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 8px;
            color: #1e3c72;
        }

        .kajian-card .info {
            font-size: 0.9rem;
            color: #666;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .kajian-card .info i {
            margin-right: 5px;
            color: #1e3c72;
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

        .content-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .kajian-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .kajian-info h3 {
            font-size: 1.8rem;
            margin-bottom: 15px;
        }

        .kajian-info-detail {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .kajian-info-detail p {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
        }

        .kajian-info-detail i {
            opacity: 0.9;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #1e3c72;
            border-bottom: 2px solid #dee2e6;
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }

        .badge-hadir {
            background: #d4edda;
            color: #155724;
        }

        .badge-tidak-hadir {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-izin {
            background: #fff3cd;
            color: #856404;
        }

        .badge-sakit {
            background: #cce5ff;
            color: #004085;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-primary {
            background: #1e3c72;
            color: white;
        }

        .btn-primary:hover {
            background: #2a5298;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
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

        .btn-warning {
            background: #ffc107;
            color: #1e3c72;
        }

        .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.85rem;
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
            max-width: 500px;
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
        }

        .modal-header h3 {
            color: #1e3c72;
            font-size: 1.3rem;
        }

        .modal-header .close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
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

        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 1rem;
        }

        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1e3c72;
            box-shadow: 0 0 0 3px rgba(30,60,114,0.1);
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-state h4 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #1e3c72;
        }

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-item .label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }

        .stat-item .value {
            font-size: 2rem;
            font-weight: bold;
            color: #1e3c72;
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
            <h2><i class="fas fa-users-cog"></i> Kelola Kehadiran Santri</h2>
            <p>Catat dan pantau kehadiran santri dalam setiap kegiatan kajian</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Pilih Kajian -->
        <div class="kajian-selector">
            <h3><i class="fas fa-calendar-check"></i> Pilih Kajian</h3>
            <div class="kajian-grid">
                <?php if (mysqli_num_rows($daftar_kajian) > 0): ?>
                    <?php while ($kajian = mysqli_fetch_assoc($daftar_kajian)): ?>
                        <a href="?kajian_id=<?php echo $kajian['id']; ?>" 
                           class="kajian-card <?php echo (isset($_GET['kajian_id']) && $_GET['kajian_id'] == $kajian['id']) ? 'active' : ''; ?>">
                            <div class="judul"><?php echo htmlspecialchars($kajian['judul']); ?></div>
                            <div class="info">
                                <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($kajian['tanggal'])); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($kajian['waktu']); ?></span>
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($kajian['tempat']); ?></span>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="grid-column: 1/-1; text-align: center; color: #666; padding: 20px;">
                        <i class="fas fa-info-circle"></i> Anda belum membuat kajian. 
                        <a href="kelola_kajian.php" style="color: #1e3c72; text-decoration: underline;">Buat kajian sekarang</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($selected_kajian): ?>
            <!-- Detail Kajian -->
            <div class="content-section">
                <div class="kajian-info">
                    <h3><?php echo htmlspecialchars($selected_kajian['judul']); ?></h3>
                    <div class="kajian-info-detail">
                        <p><i class="fas fa-user"></i> Pemateri: <?php echo htmlspecialchars($selected_kajian['pemateri']); ?></p>
                        <p><i class="fas fa-calendar"></i> Tanggal: <?php echo date('d/m/Y', strtotime($selected_kajian['tanggal'])); ?></p>
                        <p><i class="fas fa-clock"></i> Waktu: <?php echo htmlspecialchars($selected_kajian['waktu']); ?></p>
                        <p><i class="fas fa-map-marker-alt"></i> Tempat: <?php echo htmlspecialchars($selected_kajian['tempat']); ?></p>
                    </div>
                </div>

                <!-- Tombol Tambah Kehadiran -->
                <div style="margin-bottom: 20px;">
                    <button class="btn btn-primary" onclick="openModal('tambah')">
                        <i class="fas fa-plus"></i> Tambah Data Kehadiran
                    </button>
                </div>

                <?php 
                // Hitung statistik
                $total_hadir = 0;
                $total_tidak_hadir = 0;
                $total_izin = 0;
                $total_sakit = 0;
                
                if (mysqli_num_rows($data_kehadiran) > 0) {
                    mysqli_data_seek($data_kehadiran, 0);
                    while ($kehadiran = mysqli_fetch_assoc($data_kehadiran)) {
                        switch ($kehadiran['status']) {
                            case 'hadir': $total_hadir++; break;
                            case 'tidak_hadir': $total_tidak_hadir++; break;
                            case 'izin': $total_izin++; break;
                            case 'sakit': $total_sakit++; break;
                        }
                    }
                    mysqli_data_seek($data_kehadiran, 0);
                }
                ?>

                <!-- Statistik Kehadiran -->
                <div class="stats-summary">
                    <div class="stat-item">
                        <div class="label">Total Santri</div>
                        <div class="value"><?php echo mysqli_num_rows($daftar_santri); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="label">Hadir</div>
                        <div class="value" style="color: #28a745;"><?php echo $total_hadir; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="label">Tidak Hadir</div>
                        <div class="value" style="color: #dc3545;"><?php echo $total_tidak_hadir; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="label">Izin</div>
                        <div class="value" style="color: #ffc107;"><?php echo $total_izin; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="label">Sakit</div>
                        <div class="value" style="color: #17a2b8;"><?php echo $total_sakit; ?></div>
                    </div>
                </div>

                <!-- Tabel Kehadiran -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Santri</th>
                                <th>NIS</th>
                                <th>Kelas</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($data_kehadiran) > 0): ?>
                                <?php 
                                $no = 1;
                                while ($kehadiran = mysqli_fetch_assoc($data_kehadiran)): 
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($kehadiran['nama_lengkap']); ?></td>
                                    <td><?php echo htmlspecialchars($kehadiran['nis'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($kehadiran['kelas'] ?? '-'); ?></td>
                                    <td>
                                        <?php
                                        $badge_class = '';
                                        $status_text = '';
                                        switch ($kehadiran['status']) {
                                            case 'hadir':
                                                $badge_class = 'badge-hadir';
                                                $status_text = 'Hadir';
                                                break;
                                            case 'tidak_hadir':
                                                $badge_class = 'badge-tidak-hadir';
                                                $status_text = 'Tidak Hadir';
                                                break;
                                            case 'izin':
                                                $badge_class = 'badge-izin';
                                                $status_text = 'Izin';
                                                break;
                                            case 'sakit':
                                                $badge_class = 'badge-sakit';
                                                $status_text = 'Sakit';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($kehadiran['keterangan'] ?? '-'); ?></td>
                                    <td class="action-buttons">
                                        <button class="btn btn-warning btn-sm" onclick="editKehadiran(<?php echo $kehadiran['id']; ?>, '<?php echo $kehadiran['status']; ?>', '<?php echo htmlspecialchars(addslashes($kehadiran['keterangan'])); ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?kajian_id=<?php echo $kajian_id; ?>&hapus=<?php echo $kehadiran['id']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Yakin ingin menghapus data kehadiran ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fas fa-users"></i>
                                            <h4>Belum Ada Data Kehadiran</h4>
                                            <p>Klik tombol "Tambah Data Kehadiran" untuk mencatat kehadiran santri</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-hand-pointer"></i>
                <h4>Pilih Kajian</h4>
                <p>Silakan pilih kajian dari daftar di atas untuk mengelola kehadiran santri</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Tambah/Edit Kehadiran -->
    <div id="kehadiranModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Tambah Data Kehadiran</h3>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="kajian_id" value="<?php echo $_GET['kajian_id'] ?? ''; ?>">
                    <input type="hidden" name="santri_id" id="santri_id">
                    
                    <div class="form-group">
                        <label for="santri_select">Pilih Santri</label>
                        <select name="santri_id" id="santri_select" required>
                            <option value="">-- Pilih Santri --</option>
                            <?php 
                            if (isset($daftar_santri) && mysqli_num_rows($daftar_santri) > 0) {
                                mysqli_data_seek($daftar_santri, 0);
                                while ($santri = mysqli_fetch_assoc($daftar_santri)): 
                                ?>
                                <option value="<?php echo $santri['id']; ?>">
                                    <?php echo htmlspecialchars($santri['nama_lengkap']); ?> 
                                    (<?php echo htmlspecialchars($santri['nis'] ?? 'NIS: -'); ?>)
                                </option>
                                <?php 
                                endwhile;
                            } 
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status Kehadiran</label>
                        <select name="status" id="status" required>
                            <option value="">-- Pilih Status --</option>
                            <option value="hadir">Hadir</option>
                            <option value="tidak_hadir">Tidak Hadir</option>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="keterangan">Keterangan (Opsional)</label>
                        <textarea name="keterangan" id="keterangan" rows="3" placeholder="Masukkan keterangan tambahan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="closeModal()">Batal</button>
                    <button type="submit" name="simpan_kehadiran" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('kehadiranModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Tambah Data Kehadiran';
            document.getElementById('santri_id').value = '';
            document.getElementById('santri_select').value = '';
            document.getElementById('status').value = '';
            document.getElementById('keterangan').value = '';
        }

        function editKehadiran(id, status, keterangan) {
            document.getElementById('kehadiranModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Edit Data Kehadiran';
            document.getElementById('santri_id').value = id;
            document.getElementById('santri_select').value = id;
            document.getElementById('status').value = status;
            document.getElementById('keterangan').value = keterangan;
        }

        function closeModal() {
            document.getElementById('kehadiranModal').style.display = 'none';
        }

        // Tutup modal jika klik di luar modal
        window.onclick = function(event) {
            const modal = document.getElementById('kehadiranModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
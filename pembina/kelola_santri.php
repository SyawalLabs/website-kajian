<?php
include '../includes/auth.php';
if (!isLoggedIn() || !isPembina()) {
    redirect('../login.php');
}

// Proses form tambah/update kehadiran
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['simpan_kehadiran'])) {
        $kajian_id = $_POST['kajian_id'];
        $nama_santri = $_POST['nama_santri'];
        $status = $_POST['status'];
        $waktu_hadir = ($status == 'hadir' && isset($_POST['waktu_hadir'])) ? $_POST['waktu_hadir'] : null;
        
        // Insert data kehadiran dengan nama santri
        $stmt = mysqli_prepare($conn, "INSERT INTO kehadiran_santri (kajian_id, nama_santri, status, waktu_hadir) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isss", $kajian_id, $nama_santri, $status, $waktu_hadir);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Data kehadiran berhasil disimpan";
        } else {
            $_SESSION['error'] = "Gagal menyimpan data kehadiran";
        }
        
        header("Location: kelola_santri.php?kajian_id=" . $kajian_id);
        exit();
    }

    // Proses khusus untuk form tidak hadir
    if (isset($_POST['simpan_tidak_hadir'])) {
        $kajian_id = $_POST['kajian_id'];
        $nama_santri = $_POST['nama_santri'];
        $waktu_tidak_hadir = $_POST['waktu_tidak_hadir'];
        $status = 'tidak_hadir';
        
        $stmt = mysqli_prepare($conn, "INSERT INTO kehadiran_santri (kajian_id, nama_santri, status, keterangan) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isss", $kajian_id, $nama_santri, $status, $waktu_tidak_hadir);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Data santri tidak hadir berhasil disimpan";
        } else {
            $_SESSION['error'] = "Gagal menyimpan data santri tidak hadir";
        }
        
        header("Location: kelola_santri.php?kajian_id=" . $kajian_id);
        exit();
    }
}

// Hapus data kehadiran
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $kajian_id = isset($_GET['kajian_id']) ? $_GET['kajian_id'] : '';
    
    $stmt = mysqli_prepare($conn, "DELETE FROM kehadiran_santri WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Data kehadiran berhasil dihapus";
    }
    header("Location: kelola_santri.php" . ($kajian_id ? "?kajian_id=" . $kajian_id : ""));
    exit();
}

// Ambil SEMUA kajian
$kajian_stmt = mysqli_prepare($conn, "SELECT k.*, u.nama_lengkap as pembina_nama 
                                      FROM kajian k 
                                      JOIN users u ON k.created_by = u.id 
                                      ORDER BY k.tanggal DESC, k.waktu DESC");
mysqli_stmt_execute($kajian_stmt);
$daftar_kajian = mysqli_stmt_get_result($kajian_stmt);
$total_kajian = mysqli_num_rows($daftar_kajian);

// Ambil data kajian yang dipilih
$selected_kajian = null;
$data_kehadiran = [];

if (isset($_GET['kajian_id'])) {
    $kajian_id = $_GET['kajian_id'];
    
    $kajian_detail = mysqli_prepare($conn, "SELECT k.*, u.nama_lengkap as pembina_nama 
                                           FROM kajian k 
                                           JOIN users u ON k.created_by = u.id 
                                           WHERE k.id = ?");
    mysqli_stmt_bind_param($kajian_detail, "i", $kajian_id);
    mysqli_stmt_execute($kajian_detail);
    $selected_kajian = mysqli_stmt_get_result($kajian_detail)->fetch_assoc();
    
    if ($selected_kajian) {
        $kehadiran_stmt = mysqli_prepare($conn, 
            "SELECT ks.* 
             FROM kehadiran_santri ks 
             WHERE ks.kajian_id = ?");
        mysqli_stmt_bind_param($kehadiran_stmt, "i", $kajian_id);
        mysqli_stmt_execute($kehadiran_stmt);
        $data_kehadiran = mysqli_stmt_get_result($kehadiran_stmt);
    } else {
        $_SESSION['error'] = "Kajian tidak ditemukan";
        header("Location: kelola_santri.php");
        exit();
    }
}

$nama_pembina = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pembina';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Santri - Pembina | MAKN ENDE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        /* Custom styles for kelola santri */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-header h2 {
            font-size: 2rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header p {
            color: #666;
            font-size: 1rem;
            margin-top: 5px;
        }

        .search-box {
            display: flex;
            gap: 10px;
            align-items: center;
            background: white;
            padding: 5px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .search-box input {
            border: none;
            padding: 10px 15px;
            width: 250px;
            font-size: 0.95rem;
            border-radius: var(--border-radius);
        }

        .search-box input:focus {
            outline: none;
        }

        .search-box button {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s;
        }

        .search-box button:hover {
            background: var(--secondary-color);
        }

        .stats-grid-small {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card-small {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s;
        }

        .stat-card-small:hover {
            transform: translateY(-5px);
        }

        .stat-icon-small {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .stat-content-small h4 {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-bottom: 5px;
        }

        .stat-content-small .number {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .kajian-selector {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
        }

        .kajian-selector h3 {
            margin-bottom: 20px;
            color: var(--primary-color);
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .kajian-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 15px;
            max-height: 500px;
            overflow-y: auto;
            padding: 5px;
        }

        .kajian-card {
            background: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 15px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s;
            border: 2px solid transparent;
            position: relative;
        }

        .kajian-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-color: var(--primary-color);
        }

        .kajian-card.active {
            border-color: var(--primary-color);
            background: #e8f0fe;
        }

        .kajian-card .judul {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 8px;
            color: var(--primary-color);
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
            color: var(--primary-color);
        }

        .kajian-card .pembina-info {
            margin-top: 10px;
            font-size: 0.85rem;
            color: #28a745;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .badge-info {
            background: var(--info-color);
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            display: inline-block;
        }

        .kajian-detail-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 25px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
        }

        .kajian-detail-card h3 {
            font-size: 1.8rem;
            margin-bottom: 15px;
        }

        .kajian-detail-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .kajian-detail-meta span {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .form-tidak-hadir {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 30px;
        }

        .form-tidak-hadir h4 {
            color: #856404;
            margin-bottom: 20px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-tidak-hadir h4 i {
            color: #dc3545;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-group label i {
            margin-right: 5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(30,60,114,0.1);
        }

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: var(--border-radius);
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }

        .stat-item .label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 8px;
        }

        .stat-item .value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .table-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
            overflow-x: auto;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filter-select {
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            min-width: 200px;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .badge {
            padding: 6px 12px;
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

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn-icon {
            padding: 6px 12px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-delete {
            background: var(--danger-color);
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .empty-state i {
            font-size: 5rem;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-state h4 {
            font-size: 1.5rem;
            color: var(--primary-color);
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
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            margin: 30px auto;
            max-width: 500px;
            width: 90%;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
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
            font-size: 2rem;
            cursor: pointer;
            color: white;
            opacity: 0.8;
            transition: opacity 0.3s;
        }

        .modal-header .close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background: #f8f9fa;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }

        .info-text {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-box {
                width: 100%;
            }
            
            .search-box input {
                width: 100%;
            }
            
            .kajian-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-summary {
                grid-template-columns: 1fr 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <div class="navbar-logo">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div>
                    <h1>MAKN ENDE <span>Panel Pembina</span></h1>
                </div>
            </div>
            <div class="navbar-menu">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="kelola_kajian.php"><i class="fas fa-calendar-alt"></i> Kelola Kajian</a>
                <a href="kelola_santri.php" class="active"><i class="fas fa-users"></i> Kelola Santri</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span>Kelola Santri</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h2><i class="fas fa-users"></i> Kelola Kehadiran Santri</h2>
                <p>Catat dan pantau kehadiran santri dalam setiap kegiatan kajian</p>
            </div>
            <div class="search-box">
                <input type="text" id="searchKajian" placeholder="Cari kajian...">
                <button onclick="searchKajian()"><i class="fas fa-search"></i> Cari</button>
            </div>
        </div>

        <!-- Alert Messages -->
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

        <!-- Statistics Cards -->
        <div class="stats-grid-small">
            <div class="stat-card-small">
                <div class="stat-icon-small">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content-small">
                    <h4>Total Kajian</h4>
                    <div class="number"><?php echo $total_kajian; ?></div>
                </div>
            </div>
            
            <div class="stat-card-small">
                <div class="stat-icon-small">
                    <i class="fas fa-user"></i>
                </div>
                <div class="stat-content-small">
                    <h4>Pembina</h4>
                    <div class="number" style="font-size: 1.2rem;"><?php echo htmlspecialchars($nama_pembina); ?></div>
                </div>
            </div>
        </div>

        <!-- Pilih Kajian -->
        <div class="kajian-selector">
            <h3>
                <i class="fas fa-calendar-alt"></i> 
                Pilih Kajian
                <?php if ($total_kajian > 0): ?>
                    <span class="badge-info"><?php echo $total_kajian; ?> Kajian</span>
                <?php endif; ?>
            </h3>
            
            <?php if ($total_kajian > 0): ?>
                <div class="kajian-grid" id="kajianGrid">
                    <?php 
                    mysqli_data_seek($daftar_kajian, 0);
                    while ($kajian = mysqli_fetch_assoc($daftar_kajian)): 
                    ?>
                        <a href="?kajian_id=<?php echo $kajian['id']; ?>" 
                           class="kajian-card <?php echo (isset($_GET['kajian_id']) && $_GET['kajian_id'] == $kajian['id']) ? 'active' : ''; ?>">
                            <div class="judul"><?php echo htmlspecialchars($kajian['judul']); ?></div>
                            
                            <div class="info">
                                <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($kajian['tanggal'])); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($kajian['waktu']); ?></span>
                            </div>
                            <div class="info">
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($kajian['tempat']); ?></span>
                            </div>
                            <div class="pembina-info">
                                <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($kajian['pembina_nama']); ?>
                                <?php if ($kajian['created_by'] == $_SESSION['user_id']): ?>
                                    <span class="badge badge-hadir" style="margin-left: 5px;">Saya</span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding: 30px;">
                    <i class="fas fa-calendar-times"></i>
                    <h4>Belum Ada Kajian</h4>
                    <p>Silakan buat kajian terlebih dahulu di menu Kelola Kajian.</p>
                    <a href="kelola_kajian.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Buat Kajian Baru
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($selected_kajian): ?>
            <!-- Detail Kajian -->
            <div class="kajian-detail-card">
                <h3><?php echo htmlspecialchars($selected_kajian['judul']); ?></h3>
                <div class="pembina-info" style="background: rgba(255,255,255,0.2); display: inline-block; padding: 5px 15px; border-radius: 20px; margin-bottom: 15px;">
                    <i class="fas fa-user-tie"></i> 
                    Pembina: <?php echo htmlspecialchars($selected_kajian['pembina_nama']); ?>
                    <?php if ($selected_kajian['created_by'] == $_SESSION['user_id']): ?>
                        <span class="badge badge-hadir" style="margin-left: 10px;">Kajian Saya</span>
                    <?php endif; ?>
                </div>
                <div class="kajian-detail-meta">
                    <span><i class="fas fa-microphone-alt"></i> <?php echo htmlspecialchars($selected_kajian['pemateri']); ?></span>
                    <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($selected_kajian['tanggal'])); ?></span>
                    <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($selected_kajian['waktu']); ?></span>
                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($selected_kajian['tempat']); ?></span>
                </div>
            </div>

            <!-- Form Khusus Santri Tidak Hadir -->
            <div class="form-tidak-hadir">
                <h4>
                    <i class="fas fa-user-times"></i>
                    Form Input Santri Tidak Hadir
                </h4>
                
                <form method="POST" onsubmit="return validasiFormTidakHadir()">
                    <input type="hidden" name="kajian_id" value="<?php echo $kajian_id; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Nama Santri</label>
                            <input type="text" name="nama_santri" id="nama_santri" 
                                   placeholder="Masukkan nama santri..." required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Waktu Tidak Hadir</label>
                            <select name="waktu_tidak_hadir" id="waktu_tidak_hadir" required>
                                <option value="">-- Pilih Waktu --</option>
                                <option value="Awal Kajian">Awal Kajian (Sebelum dimulai)</option>
                                <option value="Tengah Kajian">Tengah Kajian (Saat berlangsung)</option>
                                <option value="Akhir Kajian">Akhir Kajian (Setelah selesai)</option>
                                <option value="Tidak Hadir Full">Tidak Hadir Full (Tidak datang)</option>
                            </select>
                            <div class="info-text">Pilih waktu ketika santri tidak hadir</div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="reset" class="btn btn-danger">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                        <button type="submit" name="simpan_tidak_hadir" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Data Tidak Hadir
                        </button>
                    </div>
                </form>
            </div>

            <?php 
            // Hitung statistik
            $total_hadir = $total_tidak_hadir = $total_izin = $total_sakit = 0;
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
                    <div class="value"><?php echo mysqli_num_rows($data_kehadiran); ?></div>
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
            <div class="table-container">
                <div class="table-header">
                    <h4><i class="fas fa-list"></i> Daftar Kehadiran Santri</h4>
                    <div style="display: flex; gap: 10px;">
                        <select id="filterStatus" class="filter-select" onchange="filterByStatus(this.value)">
                            <option value="">Semua Status</option>
                            <option value="hadir">Hadir</option>
                            <option value="tidak_hadir">Tidak Hadir</option>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                        </select>
                        
                        <button class="btn btn-success" onclick="openModal()">
                            <i class="fas fa-plus"></i> Tambah
                        </button>
                    </div>
                </div>

                <table class="table" id="kehadiranTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Santri</th>
                            <th>Status</th>
                            <th>Waktu Hadir / Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($data_kehadiran) > 0): ?>
                            <?php 
                            $no = 1;
                            while ($kehadiran = mysqli_fetch_assoc($data_kehadiran)): 
                            ?>
                            <tr data-status="<?php echo $kehadiran['status']; ?>">
                                <td><?php echo $no++; ?></td>
                                <td><strong><?php echo htmlspecialchars($kehadiran['nama_santri']); ?></strong></td>
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
                                        <i class="fas <?php 
                                            echo $kehadiran['status'] == 'hadir' ? 'fa-check-circle' : 
                                                ($kehadiran['status'] == 'tidak_hadir' ? 'fa-times-circle' : 
                                                ($kehadiran['status'] == 'izin' ? 'fa-clock' : 'fa-thermometer-half')); 
                                        ?>"></i>
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($kehadiran['status'] == 'hadir' && !empty($kehadiran['waktu_hadir'])) {
                                        echo '<i class="fas fa-clock" style="color: #28a745;"></i> ' . htmlspecialchars($kehadiran['waktu_hadir']);
                                    } elseif (!empty($kehadiran['keterangan'])) {
                                        echo '<i class="fas fa-info-circle"></i> ' . htmlspecialchars($kehadiran['keterangan']);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?kajian_id=<?php echo $kajian_id; ?>&hapus=<?php echo $kehadiran['id']; ?>" 
                                           class="btn-icon btn-delete" 
                                           onclick="return confirm('Yakin ingin menghapus data kehadiran ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="fas fa-users"></i>
                                        <h4>Belum Ada Data Kehadiran</h4>
                                        <p>Gunakan form di atas untuk mencatat santri yang tidak hadir atau tombol Tambah untuk mencatat kehadiran lainnya</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($total_kajian > 0): ?>
            <div class="empty-state">
                <i class="fas fa-hand-pointer"></i>
                <h4>Pilih Kajian</h4>
                <p>Silakan pilih kajian dari daftar di atas untuk mengelola kehadiran santri</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Tambah Kehadiran -->
    <div id="kehadiranModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Tambah Data Kehadiran</h3>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="kajian_id" value="<?php echo $_GET['kajian_id'] ?? ''; ?>">
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nama Santri</label>
                        <input type="text" name="nama_santri" id="nama_santri_modal" 
                               placeholder="Masukkan nama santri..." required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Status Kehadiran</label>
                        <select name="status" id="status_modal" required onchange="toggleWaktuHadir()">
                            <option value="">-- Pilih Status --</option>
                            <option value="hadir">Hadir</option>
                            <option value="tidak_hadir">Tidak Hadir</option>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="waktu_hadir_group" style="display: none;">
                        <label><i class="fas fa-clock"></i> Waktu Hadir</label>
                        <input type="time" name="waktu_hadir" id="waktu_hadir">
                        <div class="info-text">Masukkan jam kehadiran santri (format 24 jam)</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="closeModal()">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" name="simpan_kehadiran" class="btn btn-success">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('kehadiranModal').style.display = 'block';
            document.getElementById('nama_santri_modal').value = '';
            document.getElementById('status_modal').value = '';
            document.getElementById('waktu_hadir').value = '';
            document.getElementById('waktu_hadir_group').style.display = 'none';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('kehadiranModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('kehadiranModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('kehadiranModal');
                if (modal.style.display === 'block') {
                    closeModal();
                }
            }
        });

        function toggleWaktuHadir() {
            const status = document.getElementById('status_modal').value;
            const waktuHadirGroup = document.getElementById('waktu_hadir_group');
            
            if (status === 'hadir') {
                waktuHadirGroup.style.display = 'block';
                document.getElementById('waktu_hadir').required = true;
            } else {
                waktuHadirGroup.style.display = 'none';
                document.getElementById('waktu_hadir').required = false;
                document.getElementById('waktu_hadir').value = '';
            }
        }

        function validasiFormTidakHadir() {
            const nama_santri = document.getElementById('nama_santri').value;
            const waktu = document.getElementById('waktu_tidak_hadir').value;
            
            if (!nama_santri || !waktu) {
                alert('Harap lengkapi semua field yang diperlukan!');
                return false;
            }
            return true;
        }

        document.querySelector('#kehadiranModal form').onsubmit = function() {
            const nama_santri = document.getElementById('nama_santri_modal').value;
            const status = document.getElementById('status_modal').value;
            
            if (!nama_santri || !status) {
                alert('Harap lengkapi nama santri dan status kehadiran!');
                return false;
            }
            
            if (status === 'hadir') {
                const waktuHadir = document.getElementById('waktu_hadir').value;
                if (!waktuHadir) {
                    alert('Harap isi waktu hadir untuk status Hadir!');
                    return false;
                }
            }
            return true;
        };

        function searchKajian() {
            const searchText = document.getElementById('searchKajian').value.toLowerCase();
            const cards = document.querySelectorAll('.kajian-card');
            
            cards.forEach(card => {
                const judul = card.querySelector('.judul').textContent.toLowerCase();
                const pembina = card.querySelector('.pembina-info').textContent.toLowerCase();
                const tempat = card.querySelectorAll('.info')[1]?.textContent.toLowerCase() || '';
                
                if (judul.includes(searchText) || pembina.includes(searchText) || tempat.includes(searchText)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function filterByStatus(status) {
            const rows = document.querySelectorAll('#kehadiranTable tbody tr');
            
            rows.forEach(row => {
                if (row.cells.length > 1 && row.querySelector('td')?.colSpan !== 5) {
                    const rowStatus = row.getAttribute('data-status');
                    if (!status || rowStatus === status) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        }

        document.getElementById('searchKajian').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchKajian();
            }
        });
    </script>
</body>
</html>
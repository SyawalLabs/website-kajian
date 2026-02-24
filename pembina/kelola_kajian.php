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

// Proses edit kajian - SEMUA BISA DIEDIT
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $id = $_POST['id'];
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $pemateri = mysqli_real_escape_string($conn, $_POST['pemateri']);
    $tanggal = $_POST['tanggal'];
    $waktu = $_POST['waktu'];
    $tempat = mysqli_real_escape_string($conn, $_POST['tempat']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $link_video = mysqli_real_escape_string($conn, $_POST['link_video']);
    
    $query = "UPDATE kajian SET 
              judul = '$judul', 
              pemateri = '$pemateri', 
              tanggal = '$tanggal', 
              waktu = '$waktu', 
              tempat = '$tempat', 
              deskripsi = '$deskripsi', 
              link_video = '$link_video' 
              WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        $success_message = "Kajian berhasil diperbarui!";
    } else {
        $error_message = "Gagal memperbarui kajian: " . mysqli_error($conn);
    }
}

// Proses hapus kajian - SEMUA BISA DIHAPUS
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    $query = "DELETE FROM kajian WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        $success_message = "Kajian berhasil dihapus!";
    } else {
        $error_message = "Gagal menghapus kajian: " . mysqli_error($conn);
    }
}

// Ambil data kajian untuk diedit - SEMUA BISA DIAMBIL
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $query = "SELECT * FROM kajian WHERE id = $id";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
        $edit_data = mysqli_fetch_assoc($result);
    } else {
        $error_message = "Data kajian tidak ditemukan!";
    }
}

// AMBIL SEMUA KAJIAN
$query = "SELECT k.*, u.nama_lengkap as pembuat 
          FROM kajian k 
          LEFT JOIN users u ON k.created_by = u.id 
          ORDER BY k.tanggal DESC, k.waktu DESC";
$result = mysqli_query($conn, $query);
$total_kajian = mysqli_num_rows($result);

// Hitung statistik tambahan
$tanggal_sekarang = date('Y-m-d');
$akan_datang = 0;
$sudah_lewat = 0;

// Reset pointer untuk menghitung
mysqli_data_seek($result, 0);
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['tanggal'] >= $tanggal_sekarang) {
        $akan_datang++;
    } else {
        $sudah_lewat++;
    }
}
// Reset pointer lagi untuk ditampilkan
mysqli_data_seek($result, 0);

$nama_pembina = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pembina';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kajian - Pembina | MAKN ENDE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        /* Custom styles for kelola kajian */
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
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
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

        .kajian-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .kajian-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: all 0.3s;
            border: 1px solid #e0e0e0;
            position: relative;
        }

        .kajian-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .kajian-status {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 1;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-badge.akan-datang {
            background: #f39c12;
            color: white;
        }

        .status-badge.selesai {
            background: #95a5a6;
            color: white;
        }

        .kajian-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 20px;
            position: relative;
        }

        .kajian-header h3 {
            font-size: 1.3rem;
            margin-bottom: 8px;
            padding-right: 100px;
        }

        .kajian-header .pemateri {
            font-size: 0.95rem;
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
            gap: 12px;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #555;
            font-size: 0.95rem;
        }

        .info-item i {
            width: 20px;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .kajian-deskripsi {
            background: #f8f9fa;
            padding: 15px;
            border-radius: var(--border-radius);
            margin: 15px 0;
            font-size: 0.95rem;
            color: #666;
            max-height: 100px;
            overflow-y: auto;
            border-left: 3px solid var(--primary-color);
        }

        .kajian-meta {
            margin-top: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .meta-tag {
            background: #e9ecef;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            color: #495057;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .meta-tag i {
            font-size: 0.8rem;
        }

        .meta-tag.saya {
            background: #d4edda;
            color: #155724;
        }

        .kajian-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-icon {
            padding: 8px 15px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
            font-weight: 500;
        }

        .btn-edit {
            background: var(--warning-color);
            color: var(--primary-color);
        }

        .btn-edit:hover {
            background: #e0a800;
            transform: translateY(-2px);
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
            padding: 80px 20px;
            background: white;
            border-radius: var(--border-radius);
            grid-column: 1/-1;
            box-shadow: var(--box-shadow);
        }

        .empty-state i {
            font-size: 5rem;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.8rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 25px;
            font-size: 1.1rem;
        }

        .link-video {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            padding: 5px 10px;
            background: #e3f2fd;
            border-radius: 15px;
        }

        .link-video:hover {
            background: #bbdefb;
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
            max-width: 600px;
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
            font-size: 1.4rem;
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-group label i {
            margin-right: 5px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(30,60,114,0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .required-field::after {
            content: " *";
            color: var(--danger-color);
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .kajian-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid-small {
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
                <a href="kelola_kajian.php" class="active"><i class="fas fa-calendar-alt"></i> Kelola Kajian</a>
                <a href="kelola_santri.php"><i class="fas fa-users"></i> Kelola Santri</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span>Kelola Kajian</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h2>
                <i class="fas fa-calendar-alt"></i>
                Kelola Kajian
            </h2>
            <button class="btn btn-primary" onclick="openTambahModal()">
                <i class="fas fa-plus"></i> Tambah Kajian Baru
            </button>
        </div>

        <!-- Alert Messages -->
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
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content-small">
                    <h4>Akan Datang</h4>
                    <div class="number"><?php echo $akan_datang; ?></div>
                </div>
            </div>
            
            <div class="stat-card-small">
                <div class="stat-icon-small">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content-small">
                    <h4>Sudah Selesai</h4>
                    <div class="number"><?php echo $sudah_lewat; ?></div>
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

        <!-- Grid Kajian -->
        <div class="kajian-grid">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): 
                    $status = ($row['tanggal'] >= $tanggal_sekarang) ? 'akan-datang' : 'selesai';
                    $status_text = ($status == 'akan-datang') ? 'Akan Datang' : 'Selesai';
                    $status_icon = ($status == 'akan-datang') ? 'fa-clock' : 'fa-check-circle';
                ?>
                    <div class="kajian-card">
                        <div class="kajian-status">
                            <span class="status-badge <?php echo $status; ?>">
                                <i class="fas <?php echo $status_icon; ?>"></i>
                                <?php echo $status_text; ?>
                            </span>
                        </div>
                        
                        <div class="kajian-header">
                            <h3><?php echo htmlspecialchars($row['judul']); ?></h3>
                            <div class="pemateri">
                                <i class="fas fa-microphone-alt"></i>
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
                                        <i class="fas fa-play"></i> Tonton Rekaman
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($row['deskripsi'])): ?>
                                <div class="kajian-deskripsi">
                                    <i class="fas fa-align-left" style="margin-right: 8px; color: var(--primary-color);"></i>
                                    <?php echo nl2br(htmlspecialchars($row['deskripsi'])); ?>
                                </div>
                            <?php endif; ?>

                            <div class="kajian-meta">
                                <span class="meta-tag">
                                    <i class="fas fa-hashtag"></i> ID: <?php echo $row['id']; ?>
                                </span>
                                <span class="meta-tag">
                                    <i class="fas fa-calendar-plus"></i> <?php echo date('d/m/Y', strtotime($row['created_at'])); ?>
                                </span>
                                <span class="meta-tag <?php echo ($row['created_by'] == $_SESSION['user_id']) ? 'saya' : ''; ?>">
                                    <i class="fas fa-user"></i> 
                                    <?php 
                                    if ($row['created_by'] == $_SESSION['user_id']) {
                                        echo 'Saya';
                                    } else {
                                        echo htmlspecialchars($row['pembuat'] ?? 'Admin');
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="kajian-footer">
                            <a href="?edit=<?php echo $row['id']; ?>" class="btn-icon btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="?hapus=<?php echo $row['id']; ?>" class="btn-icon btn-delete" 
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
                    <p>Belum ada kajian yang tersedia. Mulai dengan membuat kajian baru.</p>
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
                        <label><i class="fas fa-heading"></i> <span class="required-field">Judul Kajian</span></label>
                        <input type="text" name="judul" required 
                               placeholder="Contoh: Kajian Fiqh Bab Thaharah">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-microphone-alt"></i> <span class="required-field">Pemateri</span></label>
                        <input type="text" name="pemateri" required 
                               placeholder="Contoh: Ustadz Rifai"
                               value="<?php echo htmlspecialchars($nama_pembina); ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> <span class="required-field">Tanggal</span></label>
                            <input type="date" name="tanggal" required 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> <span class="required-field">Waktu</span></label>
                            <input type="time" name="waktu" required 
                                   value="<?php echo date('H:i'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> <span class="required-field">Tempat</span></label>
                        <input type="text" name="tempat" required 
                               placeholder="Contoh: Ruang Kelas 2 / Aula">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Deskripsi Kajian</label>
                        <textarea name="deskripsi" rows="4" 
                                  placeholder="Masukkan deskripsi kajian, materi yang akan dibahas, atau catatan penting..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-video"></i> Link Video (Opsional)</label>
                        <input type="url" name="link_video" 
                               placeholder="Contoh: https://youtube.com/watch?v=...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="closeModal('tambahModal')">
                        <i class="fas fa-times"></i> Batal
                    </button>
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
                        <label><i class="fas fa-heading"></i> <span class="required-field">Judul Kajian</span></label>
                        <input type="text" name="judul" required 
                               value="<?php echo htmlspecialchars($edit_data['judul']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-microphone-alt"></i> <span class="required-field">Pemateri</span></label>
                        <input type="text" name="pemateri" required 
                               value="<?php echo htmlspecialchars($edit_data['pemateri']); ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> <span class="required-field">Tanggal</span></label>
                            <input type="date" name="tanggal" required 
                                   value="<?php echo $edit_data['tanggal']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> <span class="required-field">Waktu</span></label>
                            <input type="time" name="waktu" required 
                                   value="<?php echo $edit_data['waktu']; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> <span class="required-field">Tempat</span></label>
                        <input type="text" name="tempat" required 
                               value="<?php echo htmlspecialchars($edit_data['tempat']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Deskripsi Kajian</label>
                        <textarea name="deskripsi" rows="4"><?php echo htmlspecialchars($edit_data['deskripsi']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-video"></i> Link Video (Opsional)</label>
                        <input type="url" name="link_video" 
                               value="<?php echo htmlspecialchars($edit_data['link_video'] ?? ''); ?>"
                               placeholder="Contoh: https://youtube.com/watch?v=...">
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="kelola_kajian.php" class="btn btn-danger">
                        <i class="fas fa-times"></i> Batal
                    </a>
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
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Tutup modal jika klik di luar modal
        window.onclick = function(event) {
            const tambahModal = document.getElementById('tambahModal');
            const editModal = document.getElementById('editModal');
            
            if (event.target == tambahModal) {
                closeModal('tambahModal');
            }
            if (event.target == editModal) {
                window.location.href = 'kelola_kajian.php';
            }
        }

        // Close modal with ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const tambahModal = document.getElementById('tambahModal');
                const editModal = document.getElementById('editModal');
                
                if (tambahModal.style.display === 'block') {
                    closeModal('tambahModal');
                }
                if (editModal && editModal.style.display === 'block') {
                    window.location.href = 'kelola_kajian.php';
                }
            }
        });
    </script>
</body>
</html>
<?php
include '../includes/auth.php';
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Handle Hapus
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus']);
    mysqli_query($conn, "DELETE FROM kajian WHERE id='$id'");
    $_SESSION['success_message'] = "Data kajian berhasil dihapus!";
    redirect('kelola_kajian.php');
}

// FUNGSI VALIDASI BARU - Lebih ketat

/**
 * Cek apakah waktu bentrok dengan jadwal lain di TEMPAT MANAPUN
 * Ini akan mencegah jadwal di waktu yang sama di semua tempat
 */
function cekBentrokWaktuGlobal($conn, $tanggal, $waktu_mulai, $id = null, $durasi = 2) {
    $waktu = strtotime($waktu_mulai);
    $waktu_mulai_menit = date('H', $waktu) * 60 + date('i', $waktu);
    $waktu_selesai_menit = $waktu_mulai_menit + ($durasi * 60);
    
    // Ambil semua jadwal di tanggal yang sama
    $query = "SELECT id, waktu, tempat, judul, pemateri FROM kajian WHERE tanggal = '$tanggal'";
    if ($id) {
        $query .= " AND id != '$id'";
    }
    
    $result = mysqli_query($conn, $query);
    $bentrok_list = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $waktu_existing = strtotime($row['waktu']);
        $waktu_existing_menit = date('H', $waktu_existing) * 60 + date('i', $waktu_existing);
        $waktu_existing_selesai = $waktu_existing_menit + ($durasi * 60);
        
        // Cek apakah waktu bentrok (interval 2 jam)
        if (($waktu_mulai_menit >= $waktu_existing_menit && $waktu_mulai_menit < $waktu_existing_selesai) ||
            ($waktu_selesai_menit > $waktu_existing_menit && $waktu_selesai_menit <= $waktu_existing_selesai) ||
            ($waktu_mulai_menit <= $waktu_existing_menit && $waktu_selesai_menit >= $waktu_existing_selesai)) {
            
            $bentrok_list[] = [
                'judul' => $row['judul'],
                'tempat' => $row['tempat'],
                'waktu' => $row['waktu'],
                'pemateri' => $row['pemateri']
            ];
        }
    }
    
    return $bentrok_list;
}

/**
 * Cek apakah pemateri bentrok (mengajar di dua tempat berbeda di waktu yang sama)
 */
function cekBentrokPemateri($conn, $tanggal, $waktu_mulai, $pemateri, $id = null, $durasi = 2) {
    $waktu = strtotime($waktu_mulai);
    $waktu_mulai_menit = date('H', $waktu) * 60 + date('i', $waktu);
    $waktu_selesai_menit = $waktu_mulai_menit + ($durasi * 60);
    
    // Cek jadwal pemateri yang sama
    $query = "SELECT id, waktu, tempat, judul FROM kajian 
              WHERE tanggal = '$tanggal' AND pemateri = '$pemateri'";
    if ($id) {
        $query .= " AND id != '$id'";
    }
    
    $result = mysqli_query($conn, $query);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $waktu_existing = strtotime($row['waktu']);
        $waktu_existing_menit = date('H', $waktu_existing) * 60 + date('i', $waktu_existing);
        $waktu_existing_selesai = $waktu_existing_menit + ($durasi * 60);
        
        // Cek bentrok waktu
        if (($waktu_mulai_menit >= $waktu_existing_menit && $waktu_mulai_menit < $waktu_existing_selesai) ||
            ($waktu_selesai_menit > $waktu_existing_menit && $waktu_selesai_menit <= $waktu_existing_selesai) ||
            ($waktu_mulai_menit <= $waktu_existing_menit && $waktu_selesai_menit >= $waktu_existing_selesai)) {
            return $row;
        }
    }
    
    return false;
}

/**
 * Cek apakah tempat sudah digunakan di waktu yang berdekatan
 */
function cekBentrokTempat($conn, $tanggal, $waktu_mulai, $tempat, $id = null, $durasi = 2) {
    $waktu = strtotime($waktu_mulai);
    $waktu_mulai_menit = date('H', $waktu) * 60 + date('i', $waktu);
    $waktu_selesai_menit = $waktu_mulai_menit + ($durasi * 60);
    
    // Cek jadwal di tempat yang sama
    $query = "SELECT id, waktu, judul, pemateri FROM kajian 
              WHERE tanggal = '$tanggal' AND tempat = '$tempat'";
    if ($id) {
        $query .= " AND id != '$id'";
    }
    
    $result = mysqli_query($conn, $query);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $waktu_existing = strtotime($row['waktu']);
        $waktu_existing_menit = date('H', $waktu_existing) * 60 + date('i', $waktu_existing);
        $waktu_existing_selesai = $waktu_existing_menit + ($durasi * 60);
        
        // Cek bentrok waktu
        if (($waktu_mulai_menit >= $waktu_existing_menit && $waktu_mulai_menit < $waktu_existing_selesai) ||
            ($waktu_selesai_menit > $waktu_existing_menit && $waktu_selesai_menit <= $waktu_existing_selesai) ||
            ($waktu_mulai_menit <= $waktu_existing_menit && $waktu_selesai_menit >= $waktu_existing_selesai)) {
            return $row;
        }
    }
    
    return false;
}

// Handle Tambah/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $pemateri = mysqli_real_escape_string($conn, $_POST['pemateri']);
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $waktu = mysqli_real_escape_string($conn, $_POST['waktu']);
    $tempat = mysqli_real_escape_string($conn, $_POST['tempat']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    
    $errors = [];
    
    // Validasi dasar
    if (strtotime($tanggal) < strtotime(date('Y-m-d'))) {
        $errors[] = "Tanggal kajian tidak boleh kurang dari hari ini!";
    }
    
    if (empty($judul) || empty($pemateri) || empty($tanggal) || empty($waktu) || empty($tempat)) {
        $errors[] = "Semua field wajib diisi!";
    }
    
    if (strlen($judul) < 3) {
        $errors[] = "Judul kajian minimal 3 karakter!";
    }
    
    if (strlen($judul) > 200) {
        $errors[] = "Judul kajian maksimal 200 karakter!";
    }
    
    if (strlen($tempat) > 200) {
        $errors[] = "Tempat kajian maksimal 200 karakter!";
    }
    
    $id = $_POST['id'] ?? null;
    
    // VALIDASI BENTROK WAKTU GLOBAL (semua tempat)
    $bentrok_global = cekBentrokWaktuGlobal($conn, $tanggal, $waktu, $id, 2); // Durasi 2 jam
    
    if (!empty($bentrok_global)) {
        $bentrok_info = [];
        foreach ($bentrok_global as $b) {
            $bentrok_info[] = "'{$b['judul']}' di {$b['tempat']} pukul " . date('H:i', strtotime($b['waktu']));
        }
        $errors[] = "Jadwal bentrok dengan kajian lain: " . implode(", ", $bentrok_info) . ". Minimal jarak antar kajian 2 jam!";
    }
    
    // VALIDASI BENTROK PEMATERI
    $bentrok_pemateri = cekBentrokPemateri($conn, $tanggal, $waktu, $pemateri, $id, 2);
    if ($bentrok_pemateri) {
        $errors[] = "Ustadz {$pemateri} sudah mengajar di '{$bentrok_pemateri['judul']}' di {$bentrok_pemateri['tempat']} pada pukul " . date('H:i', strtotime($bentrok_pemateri['waktu'])) . " (jarak kurang dari 2 jam)!";
    }
    
    // VALIDASI BENTROK TEMPAT
    $bentrok_tempat = cekBentrokTempat($conn, $tanggal, $waktu, $tempat, $id, 2);
    if ($bentrok_tempat) {
        $errors[] = "Tempat {$tempat} sudah digunakan untuk '{$bentrok_tempat['judul']}' pada pukul " . date('H:i', strtotime($bentrok_tempat['waktu'])) . " (jarak kurang dari 2 jam)!";
    }
    
    // Eksekusi jika tidak ada error
    if (empty($errors)) {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Mode Edit
            $id = $_POST['id'];
            $query = "UPDATE kajian SET 
                      judul='$judul', 
                      pemateri='$pemateri',
                      tanggal='$tanggal',
                      waktu='$waktu',
                      tempat='$tempat',
                      deskripsi='$deskripsi'
                      WHERE id='$id'";
            
            if (mysqli_query($conn, $query)) {
                $_SESSION['success_message'] = "Data kajian berhasil diupdate!";
                redirect('kelola_kajian.php');
            } else {
                $errors[] = "Error: " . mysqli_error($conn);
            }
        } else {
            // Mode Tambah
            $query = "INSERT INTO kajian (judul, pemateri, tanggal, waktu, tempat, deskripsi, created_by) 
                      VALUES ('$judul', '$pemateri', '$tanggal', '$waktu', '$tempat', '$deskripsi', '{$_SESSION['user_id']}')";
            
            if (mysqli_query($conn, $query)) {
                $_SESSION['success_message'] = "Data kajian berhasil ditambahkan!";
                redirect('kelola_kajian.php');
            } else {
                $errors[] = "Error: " . mysqli_error($conn);
            }
        }
    }
}

// Ambil data untuk edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM kajian WHERE id='$id'");
    $edit_data = mysqli_fetch_assoc($result);
}

// Tampilkan pesan sukses
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kajian - Admin MAKN ENDE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .validation-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 25px;
            margin-bottom: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .validation-info ul {
            margin-top: 10px;
            margin-left: 20px;
            color: #fff;
        }
        
        .validation-info li {
            margin-bottom: 5px;
        }
        
        .error-list {
            background-color: #f8d7da;
            border-left: 5px solid #dc3545;
            padding: 20px 25px;
            margin-bottom: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .error-list ul {
            margin: 15px 0 0 25px;
            color: #721c24;
        }
        
        .error-list li {
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .jadwal-tersedia {
            background-color: #d4edda;
            border-left: 5px solid #28a745;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 8px;
            color: #155724;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .jadwal-tidak-tersedia {
            background-color: #f8d7da;
            border-left: 5px solid #dc3545;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 8px;
            color: #721c24;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .jadwal-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
            font-size: 1rem;
        }
        
        .jadwal-info i {
            font-size: 1.5rem;
        }
        
        .badge-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.95rem;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .conflict-row {
            background-color: #fff3cd !important;
            border-left: 4px solid #ffc107;
        }
        
        .warning-badge {
            background-color: #dc3545;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 8px;
            display: inline-block;
        }
        
        .time-gap-warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 0.95rem;
        }
        
        .btn-disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .stat-card-conflict {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <div class="navbar-logo">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div>
                    <h1>MAKN ENDE <span>Panel Admin - Kelola Kajian</span></h1>
                </div>
            </div>
            <div class="navbar-menu">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="kelola_kajian.php" class="active"><i class="fas fa-calendar-alt"></i> Kelola Kajian</a>
                <a href="kelola_user.php"><i class="fas fa-users-cog"></i> Kelola User</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <a href="kelola_kajian.php"><i class="fas fa-calendar-alt"></i> Kelola Kajian</a>
            <?php if ($edit_data): ?>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span>Edit Kajian</span>
            <?php endif; ?>
        </div>

        <!-- Info Panel dengan aturan yang jelas -->
        <div class="validation-info">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <i class="fas fa-shield-alt" style="font-size: 2rem;"></i>
                <h3 style="margin: 0; color: white;">Sistem Proteksi Jadwal MAKN ENDE</h3>
            </div>
            <p style="margin-bottom: 10px;"><strong>Aturan yang diterapkan:</strong></p>
            <ul>
                <li><i class="fas fa-clock"></i> <strong>JARAK MINIMUM 2 JAM</strong> antar kajian di hari yang sama (di seluruh tempat)</li>
                <li><i class="fas fa-chalkboard-teacher"></i> <strong>Seorang ustadz TIDAK BOLEH</strong> mengajar di dua tempat berbeda dalam rentang 2 jam</li>
                <li><i class="fas fa-mosque"></i> <strong>Satu tempat TIDAK BOLEH</strong> digunakan untuk dua kajian dalam rentang 2 jam</li>
                <li><i class="fas fa-exclamation-triangle"></i> Jika ada jadwal yang bentrok, sistem akan menolak penyimpanan</li>
            </ul>
            <div style="margin-top: 15px; background: rgba(255,255,255,0.2); padding: 10px; border-radius: 5px;">
                <i class="fas fa-info-circle"></i> Contoh: Jika ada kajian jam 08:00, maka kajian berikutnya minimal jam 10:00
            </div>
        </div>

        <!-- Form Title -->
        <h2 class="dashboard-title">
            <i class="fas fa-<?php echo $edit_data ? 'edit' : 'plus-circle'; ?>"></i> 
            <?php echo $edit_data ? 'Edit Kajian' : 'Tambah Kajian Baru'; ?>
        </h2>
        
        <!-- Success Message -->
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Error Messages -->
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="error-list">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 1.5rem; color: #dc3545;"></i>
                    <strong style="font-size: 1.1rem;">Validasi Gagal:</strong>
                </div>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><i class="fas fa-times-circle" style="color: #dc3545; margin-right: 8px;"></i><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Form -->
        <form method="POST" class="form-container" id="formKajian">
            <?php if ($edit_data): ?>
                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label><i class="fas fa-heading"></i> Judul Kajian</label>
                <input type="text" name="judul" id="judul" value="<?php echo $edit_data['judul'] ?? ''; ?>" placeholder="Contoh: Kajian Fiqih Wanita" required maxlength="200">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-user-tie"></i> Pemateri / Ustadz</label>
                <input type="text" name="pemateri" id="pemateri" value="<?php echo $edit_data['pemateri'] ?? ''; ?>" placeholder="Contoh: Ustadz Ahmad" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-calendar-day"></i> Tanggal</label>
                    <input type="date" name="tanggal" id="tanggal" value="<?php echo $edit_data['tanggal'] ?? ''; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Waktu</label>
                    <input type="time" name="waktu" id="waktu" value="<?php echo $edit_data['waktu'] ?? ''; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-map-marker-alt"></i> Tempat / Lokasi</label>
                <input type="text" name="tempat" id="tempat" value="<?php echo $edit_data['tempat'] ?? ''; ?>" placeholder="Contoh: Masjid Al-Ikhlas / Ruang Kelas 2" required maxlength="200">
            </div>
            
            <!-- Real-time validation info -->
            <div id="jadwalInfo" class="jadwal-info" style="display: none;"></div>
            
            <!-- Time gap warning -->
            <div id="timeGapWarning" class="time-gap-warning" style="display: none;">
                <i class="fas fa-hourglass-half" style="color: #856404;"></i>
                <span id="timeGapMessage"></span>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Deskripsi (Opsional)</label>
                <textarea name="deskripsi" rows="4" placeholder="Masukkan deskripsi kajian, materi yang akan dibahas, dll."><?php echo $edit_data['deskripsi'] ?? ''; ?></textarea>
            </div>
            
            <div style="display: flex; gap: 15px; align-items: center;">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-save"></i> Simpan Kajian
                </button>
                <?php if ($edit_data): ?>
                    <a href="kelola_kajian.php" class="btn">
                        <i class="fas fa-times"></i> Batal
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Daftar Kajian dengan Deteksi Konflik -->
        <div style="margin-top: 50px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <h3 style="font-size: 1.8rem; color: var(--primary-color);">
                    <i class="fas fa-list"></i> 
                    Daftar Kajian
                </h3>
                <div style="display: flex; gap: 10px;">
                    <span class="badge-info">
                        <i class="fas fa-shield-alt"></i> Proteksi 2 Jam
                    </span>
                    <?php
                    // Hitung konflik yang ada
                    $conflict_count = 0;
                    $jadwal_check = [];
                    $konflik_detected = [];
                    
                    $query_conflict = "SELECT * FROM kajian ORDER BY tanggal, waktu";
                    $result_conflict = mysqli_query($conn, $query_conflict);
                    $all_jadwal = [];
                    
                    while ($row = mysqli_fetch_assoc($result_conflict)) {
                        $all_jadwal[] = $row;
                    }
                    
                    for ($i = 0; $i < count($all_jadwal); $i++) {
                        for ($j = $i + 1; $j < count($all_jadwal); $j++) {
                            if ($all_jadwal[$i]['tanggal'] == $all_jadwal[$j]['tanggal']) {
                                $waktu1 = strtotime($all_jadwal[$i]['waktu']);
                                $waktu2 = strtotime($all_jadwal[$j]['waktu']);
                                $selisih = abs($waktu2 - $waktu1) / 60; // dalam menit
                                
                                if ($selisih < 120) { // kurang dari 2 jam
                                    $conflict_count++;
                                    $konflik_detected[] = $all_jadwal[$i]['id'];
                                    $konflik_detected[] = $all_jadwal[$j]['id'];
                                }
                            }
                        }
                    }
                    ?>
                    <?php if ($conflict_count > 0): ?>
                    <span class="warning-badge">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $conflict_count; ?> Konflik Terdeteksi
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Filter dan Search -->
            <div style="margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px;">
                    <input type="text" id="searchTable" placeholder="Cari kajian..." style="width: 100%; padding: 12px 20px; border: 2px solid #e0e0e0; border-radius: 50px;">
                </div>
                <select id="filterTempat" style="padding: 12px 25px; border: 2px solid #e0e0e0; border-radius: 50px; background: white;">
                    <option value="">Semua Tempat</option>
                    <?php
                    $tempat_query = "SELECT DISTINCT tempat FROM kajian ORDER BY tempat";
                    $tempat_result = mysqli_query($conn, $tempat_query);
                    while ($tempat_row = mysqli_fetch_assoc($tempat_result)) {
                        echo "<option value='" . $tempat_row['tempat'] . "'>" . $tempat_row['tempat'] . "</option>";
                    }
                    ?>
                </select>
                <button onclick="deteksiKonflik()" class="btn btn-primary" style="padding: 12px 25px;">
                    <i class="fas fa-exclamation-triangle"></i> Scan Konflik
                </button>
            </div>
            
            <!-- Statistik Konflik -->
            <?php if ($conflict_count > 0): ?>
            <div class="stat-card-conflict" style="margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i>
                <strong>PERHATIAN!</strong> Ditemukan <?php echo $conflict_count; ?> jadwal dengan jarak kurang dari 2 jam. 
                Segera perbaiki untuk menghindari bentrok.
            </div>
            <?php endif; ?>
            
            <div class="table-container">
                <table class="table" id="kajianTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Judul</th>
                            <th>Pemateri</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Tempat</th>
                            <th>Dibuat Oleh</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Query dengan JOIN untuk menampilkan pembuat
                        $query = "SELECT k.*, u.nama_lengkap as pembuat 
                                 FROM kajian k 
                                 LEFT JOIN users u ON k.created_by = u.id 
                                 ORDER BY k.tanggal DESC, k.waktu ASC";
                        $result = mysqli_query($conn, $query);
                        $no = 1;
                        
                        $jadwal_list = [];
                        $konflik_set = array_flip($konflik_detected);
                        
                        while ($row = mysqli_fetch_assoc($result)) {
                            $jadwal_list[] = $row;
                            
                            // Deteksi konflik
                            $status = '<span style="color: green;"><i class="fas fa-check-circle"></i> OK</span>';
                            $row_class = '';
                            $warning_text = '';
                            
                            if (isset($konflik_set[$row['id']])) {
                                $status = '<span style="color: red;"><i class="fas fa-exclamation-triangle"></i> Jarak < 2 Jam</span>';
                                $row_class = 'conflict-row';
                                $warning_text = '<span class="warning-badge" style="margin-left: 5px;">Konflik</span>';
                            }
                            
                            echo "<tr data-tempat='" . htmlspecialchars($row['tempat']) . "' 
                                      data-judul='" . htmlspecialchars($row['judul']) . "'
                                      data-tanggal='" . $row['tanggal'] . "'
                                      data-waktu='" . $row['waktu'] . "'
                                      data-pemateri='" . htmlspecialchars($row['pemateri']) . "'
                                      class='$row_class'>";
                            echo "<td>" . $no++ . "</td>";
                            echo "<td><strong>" . $row['judul'] . "</strong> $warning_text</td>";
                            echo "<td>" . $row['pemateri'] . "</td>";
                            echo "<td>" . date('d/m/Y', strtotime($row['tanggal'])) . "</td>";
                            echo "<td>" . $row['waktu'] . " WIB</td>";
                            echo "<td>" . $row['tempat'] . "</td>";
                            echo "<td>" . ($row['pembuat'] ?? '<em>Tidak diketahui</em>') . "</td>";
                            echo "<td>" . $status . "</td>";
                            echo "<td>
                                    <a href='?edit=" . $row['id'] . "' class='btn-edit' title='Edit Data'><i class='fas fa-edit'></i> Edit</a>
                                    <a href='?hapus=" . $row['id'] . "' class='btn-hapus' onclick='return confirm(\"Yakin ingin menghapus kajian \\\"" . $row['judul'] . "\\\"?\")' title='Hapus Data'><i class='fas fa-trash'></i> Hapus</a>
                                  </td>";
                            echo "</tr>";
                        }
                        
                        if (empty($jadwal_list)) {
                            echo "<tr><td colspan='9' style='text-align: center; padding: 40px;'><i class='fas fa-folder-open' style='font-size: 48px; color: #ccc; margin-bottom: 10px; display: block;'></i>Belum ada data kajian</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Info Box dengan penjelasan -->
            <div class="alert alert-info" style="margin-top: 20px; background: #e3f2fd; border-left: 5px solid #2196F3;">
                <div style="display: flex; align-items: start; gap: 15px;">
                    <i class="fas fa-info-circle" style="font-size: 2rem; color: #2196F3;"></i>
                    <div>
                        <strong style="font-size: 1.1rem;">Sistem Proteksi Jadwal MAKN ENDE</strong>
                        <p style="margin-top: 8px;">✅ Setiap kajian wajib memiliki jarak minimal <strong>2 jam</strong> dari kajian lain di hari yang sama.<br>
                        ✅ Seorang ustadz tidak boleh mengajar di dua tempat berbeda dalam rentang 2 jam.<br>
                        ✅ Satu tempat tidak boleh digunakan untuk dua kajian dalam rentang 2 jam.<br>
                        ✅ Sistem akan menolak penyimpanan jika aturan dilanggar.</p>
                        <p style="margin-top: 10px; color: #666;"><i class="fas fa-clock"></i> Total Kajian: <?php echo count($jadwal_list); ?> | 
                        <span style="color: <?php echo $conflict_count > 0 ? 'red' : 'green'; ?>;">
                            <i class="fas fa-<?php echo $conflict_count > 0 ? 'exclamation-triangle' : 'check-circle'; ?>"></i> 
                            <?php echo $conflict_count > 0 ? "$conflict_count Konflik Terdeteksi" : "Tidak Ada Konflik"; ?>
                        </span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Real-time validation
        const tanggalInput = document.getElementById('tanggal');
        const waktuInput = document.getElementById('waktu');
        const tempatInput = document.getElementById('tempat');
        const pemateriInput = document.getElementById('pemateri');
        const jadwalInfo = document.getElementById('jadwalInfo');
        const timeGapWarning = document.getElementById('timeGapWarning');
        const timeGapMessage = document.getElementById('timeGapMessage');
        const submitBtn = document.getElementById('submitBtn');
        const formKajian = document.getElementById('formKajian');
        
        // Data jadwal dari PHP
        const jadwalList = <?php echo json_encode($jadwal_list ?? []); ?>;
        const editId = <?php echo $edit_data['id'] ?? 'null'; ?>;
        
        function cekDuplikasi() {
            const tanggal = tanggalInput.value;
            const waktu = waktuInput.value;
            const tempat = tempatInput.value;
            const pemateri = pemateriInput.value;
            
            if (tanggal && waktu && tempat && pemateri) {
                let conflicts = [];
                let isBlocked = false;
                
                // Konversi waktu input ke menit
                const [jam, menit] = waktu.split(':').map(Number);
                const waktuMulaiMenit = jam * 60 + menit;
                const waktuSelesaiMenit = waktuMulaiMenit + 120; // +2 jam
                
                // Cek semua jadwal yang ada
                for (let j of jadwalList) {
                    if (j.id == editId) continue; // Skip data sendiri
                    
                    if (j.tanggal === tanggal) {
                        const [jJam, jMenit] = j.waktu.split(':').map(Number);
                        const waktuJMenit = jJam * 60 + jMenit;
                        const waktuJSelesai = waktuJMenit + 120;
                        
                        // Cek bentrok
                        if ((waktuMulaiMenit >= waktuJMenit && waktuMulaiMenit < waktuJSelesai) ||
                            (waktuSelesaiMenit > waktuJMenit && waktuSelesaiMenit <= waktuJSelesai) ||
                            (waktuMulaiMenit <= waktuJMenit && waktuSelesaiMenit >= waktuJSelesai)) {
                            
                            // Hitung selisih waktu
                            const selisih = Math.abs(waktuMulaiMenit - waktuJMenit);
                            const jamSelisih = Math.floor(selisih / 60);
                            const menitSelisih = selisih % 60;
                            
                            let conflictMsg = `Bentrok dengan "${j.judul}" di ${j.tempat} (${j.waktu}`;
                            
                            if (j.pemateri === pemateri) {
                                conflictMsg += ` - PEMATERI SAMA!`;
                                isBlocked = true;
                            }
                            
                            if (j.tempat === tempat) {
                                conflictMsg += ` - TEMPAT SAMA!`;
                                isBlocked = true;
                            }
                            
                            conflictMsg += `) - jarak ${jamSelisih} jam ${menitSelisih} menit`;
                            conflicts.push(conflictMsg);
                            
                            // Tampilkan warning
                            timeGapWarning.style.display = 'flex';
                            timeGapMessage.innerHTML = `⚠️ Peringatan: Jarak dengan kajian "${j.judul}" hanya ${jamSelisih} jam ${menitSelisih} menit! Minimal 2 jam.`;
                        }
                    }
                }
                
                if (conflicts.length > 0) {
                    jadwalInfo.style.display = 'flex';
                    jadwalInfo.className = 'jadwal-info jadwal-tidak-tersedia';
                    jadwalInfo.innerHTML = '<i class="fas fa-times-circle"></i> <strong>Jadwal tidak dapat disimpan!</strong><br>' + 
                                          conflicts.join('<br>');
                    submitBtn.disabled = true;
                } else {
                    jadwalInfo.style.display = 'flex';
                    jadwalInfo.className = 'jadwal-info jadwal-tersedia';
                    jadwalInfo.innerHTML = '<i class="fas fa-check-circle"></i> <strong>Jadwal tersedia!</strong> Silakan lanjutkan.';
                    submitBtn.disabled = false;
                    timeGapWarning.style.display = 'none';
                }
            } else {
                jadwalInfo.style.display = 'none';
                timeGapWarning.style.display = 'none';
                submitBtn.disabled = false;
            }
        }
        
        // Event listeners
        tanggalInput.addEventListener('change', cekDuplikasi);
        waktuInput.addEventListener('change', cekDuplikasi);
        tempatInput.addEventListener('input', cekDuplikasi);
        pemateriInput.addEventListener('input', cekDuplikasi);
        
        // Search functionality
        document.getElementById('searchTable').addEventListener('keyup', filterTable);
        document.getElementById('filterTempat').addEventListener('change', filterTable);
        
        function filterTable() {
            let searchText = document.getElementById('searchTable').value.toLowerCase();
            let filterTempat = document.getElementById('filterTempat').value;
            let rows = document.querySelectorAll('#kajianTable tbody tr');
            
            for (let row of rows) {
                let judul = row.cells[1]?.textContent.toLowerCase() || '';
                let pemateri = row.cells[2]?.textContent.toLowerCase() || '';
                let tempat = row.getAttribute('data-tempat') || '';
                
                let matchSearch = judul.includes(searchText) || pemateri.includes(searchText);
                let matchTempat = filterTempat === '' || tempat === filterTempat;
                
                row.style.display = (matchSearch && matchTempat) ? '' : 'none';
            }
        }
        
        // Deteksi konflik manual
        function deteksiKonflik() {
            let conflicts = [];
            let rows = document.querySelectorAll('#kajianTable tbody tr');
            let jadwalArray = [];
            
            // Kumpulkan data
            rows.forEach(row => {
                jadwalArray.push({
                    id: row.cells[0]?.textContent,
                    judul: row.cells[1]?.textContent,
                    pemateri: row.cells[2]?.textContent,
                    tanggal: row.getAttribute('data-tanggal'),
                    waktu: row.getAttribute('data-waktu'),
                    tempat: row.getAttribute('data-tempat')
                });
            });
            
            // Deteksi konflik
            for (let i = 0; i < jadwalArray.length; i++) {
                for (let j = i + 1; j < jadwalArray.length; j++) {
                    if (jadwalArray[i].tanggal === jadwalArray[j].tanggal) {
                        const waktu1 = new Date(`2000-01-01T${jadwalArray[i].waktu}`).getTime();
                        const waktu2 = new Date(`2000-01-01T${jadwalArray[j].waktu}`).getTime();
                        const selisih = Math.abs(waktu2 - waktu1) / (1000 * 60); // dalam menit
                        
                        if (selisih < 120) {
                            const jam = Math.floor(selisih / 60);
                            const menit = selisih % 60;
                            conflicts.push(
                                `• "${jadwalArray[i].judul}" (${jadwalArray[i].waktu}) dan "${jadwalArray[j].judul}" (${jadwalArray[j].waktu})\n` +
                                `  Jarak: ${jam} jam ${menit} menit (minimal 2 jam)`
                            );
                        }
                    }
                }
            }
            
            if (conflicts.length > 0) {
                alert('KONFLIK JADWAL TERDETEKSI:\n\n' + conflicts.join('\n\n'));
            } else {
                alert('✅ Tidak ada konflik jadwal. Semua jadwal memiliki jarak minimal 2 jam.');
            }
        }
        
        // Form validation before submit
        formKajian.addEventListener('submit', function(e) {
            if (submitBtn.disabled) {
                e.preventDefault();
                alert('❌ TIDAK DAPAT MENYIMPAN: Jadwal bentrok dengan kajian lain! Pastikan jarak minimal 2 jam dari kajian lain.');
            }
        });
        
        // Set minimal date
        tanggalInput.setAttribute('min', new Date().toISOString().split('T')[0]);
        
        // Initial check for edit mode
        <?php if ($edit_data): ?>
        window.addEventListener('load', function() {
            setTimeout(cekDuplikasi, 500);
        });
        <?php endif; ?>
    </script>
</body>
</html>
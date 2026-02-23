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

/**
 * CEK APAKAH USTADZ SUDAH MENGAJAR DI HARI YANG SAMA
 * Ini adalah aturan utama: 1 ustadz hanya boleh 1x mengajar per hari
 */
function cekUstadzSudahMengajarHariIni($conn, $tanggal, $pemateri, $pemateri_id, $id = null) {
    $query = "SELECT id, judul, waktu, tempat FROM kajian 
              WHERE tanggal = '$tanggal' AND (pemateri = '$pemateri' OR pemateri_id = '$pemateri_id')";
    
    if ($id) {
        $query .= " AND id != '$id'";
    }
    
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result); // Ada jadwal ustadz di hari yang sama
    }
    
    return false; // Ustadz tersedia
}

/**
 * CEK APAKAH TEMPAT SUDAH DIGUNAKAN DI WAKTU YANG SAMA PERSIS
 * Boleh beda waktu, tapi tidak boleh di jam yang sama persis
 */
function cekTempatDigunakanWaktuSama($conn, $tanggal, $waktu, $tempat, $id = null) {
    $query = "SELECT id, judul, pemateri FROM kajian 
              WHERE tanggal = '$tanggal' AND waktu = '$waktu' AND tempat = '$tempat'";
    
    if ($id) {
        $query .= " AND id != '$id'";
    }
    
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result); // Tempat sudah digunakan di jam yang sama
    }
    
    return false; // Tempat tersedia
}

// Handle Tambah/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $pemateri_id = mysqli_real_escape_string($conn, $_POST['pemateri_id']);
    
    // Ambil data pemateri dari tabel users berdasarkan ID
    $user_query = "SELECT id, username, nama_lengkap FROM users WHERE id = '$pemateri_id' AND role = 'pembina'";
    $user_result = mysqli_query($conn, $user_query);
    $user_data = mysqli_fetch_assoc($user_result);
    
    if ($user_data) {
        $pemateri = $user_data['nama_lengkap']; // Simpan nama lengkap ke field pemateri
    } else {
        $errors[] = "Pembina tidak valid!";
    }
    
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $waktu = mysqli_real_escape_string($conn, $_POST['waktu']);
    $tempat = mysqli_real_escape_string($conn, $_POST['tempat']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    
    $errors = [];
    
    // Validasi dasar
    if (strtotime($tanggal) < strtotime(date('Y-m-d'))) {
        $errors[] = "Tanggal kajian tidak boleh kurang dari hari ini!";
    }
    
    if (empty($judul) || empty($pemateri_id) || empty($tanggal) || empty($waktu) || empty($tempat)) {
        $errors[] = "Semua field wajib diisi!";
    }
    
    $id = $_POST['id'] ?? null;
    
    // VALIDASI UTAMA: Cek apakah ustadz sudah mengajar di hari yang sama
    $jadwal_ustadz = cekUstadzSudahMengajarHariIni($conn, $tanggal, $pemateri, $pemateri_id, $id);
    if ($jadwal_ustadz) {
        $errors[] = "Ustadz {$pemateri} sudah memiliki jadwal kajian '{$jadwal_ustadz['judul']}' pada hari yang sama pukul " . 
                    date('H:i', strtotime($jadwal_ustadz['waktu'])) . " di {$jadwal_ustadz['tempat']}. " .
                    "Seorang ustadz hanya boleh mengajar SATU KALI dalam sehari!";
    }
    
    // VALIDASI: Cek apakah tempat sudah digunakan di waktu yang SAMA PERSIS
    $tempat_dipakai = cekTempatDigunakanWaktuSama($conn, $tanggal, $waktu, $tempat, $id);
    if ($tempat_dipakai) {
        $errors[] = "Tempat {$tempat} sudah digunakan untuk kajian '{$tempat_dipakai['judul']}' pada jam yang sama!";
    }
    
    // Eksekusi jika tidak ada error
    if (empty($errors)) {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Mode Edit
            $id = $_POST['id'];
            $query = "UPDATE kajian SET 
                      judul='$judul', 
                      pemateri='$pemateri',
                      pemateri_id='$pemateri_id',
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
            $query = "INSERT INTO kajian (judul, pemateri, pemateri_id, tanggal, waktu, tempat, deskripsi, created_by) 
                      VALUES ('$judul', '$pemateri', '$pemateri_id', '$tanggal', '$waktu', '$tempat', '$deskripsi', '{$_SESSION['user_id']}')";
            
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

// Ambil daftar user untuk dropdown pemateri (hanya role pembina)
// HAPUS email dan no_telepon karena kolom tersebut tidak ada
$user_query = "SELECT id, username, nama_lengkap FROM users WHERE role = 'pembina' ORDER BY nama_lengkap";
$user_result = mysqli_query($conn, $user_query);

// Hitung total pembina
$total_pembina = mysqli_num_rows($user_result);

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
            margin-bottom: 8px;
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
        
        .jadwal-tersedia {
            background-color: #d4edda;
            border-left: 5px solid #28a745;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 8px;
            color: #155724;
            font-weight: 500;
        }
        
        .jadwal-tidak-tersedia {
            background-color: #f8d7da;
            border-left: 5px solid #dc3545;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 8px;
            color: #721c24;
            font-weight: 500;
        }
        
        .badge-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.95rem;
            font-weight: 600;
            display: inline-block;
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
        
        .rule-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 5px solid #667eea;
        }
        
        .rule-card h4 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .rule-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .rule-item:last-child {
            border-bottom: none;
        }
        
        .rule-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .rule-icon.success {
            background: #d4edda;
            color: #28a745;
        }
        
        .rule-icon.danger {
            background: #f8d7da;
            color: #dc3545;
        }
        
        .rule-icon.warning {
            background: #fff3cd;
            color: #ffc107;
        }
        
        .example-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 0.95rem;
        }
        
        .example-item {
            display: flex;
            gap: 10px;
            margin-bottom: 8px;
            padding: 5px 0;
        }
        
        .example-item i {
            width: 20px;
            color: #667eea;
        }
        
        .ustadz-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-left: 10px;
        }
        
        .ustadz-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 5px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .ustadz-info i {
            color: #667eea;
        }
        
        select.form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: white;
            cursor: pointer;
        }
        
        select.form-control:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        select.form-control option {
            padding: 10px;
        }
        
        .pembina-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .pembina-card i {
            font-size: 2rem;
        }
        
        .pembina-card .info {
            flex: 1;
        }
        
        .pembina-card .info h3 {
            margin: 0;
            font-size: 1.3rem;
        }
        
        .pembina-card .info p {
            margin: 5px 0 0;
            opacity: 0.9;
        }
        
        .pembina-stats {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        
        .stat-item {
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 0.9rem;
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

        <!-- Pembina Info Card -->
        <div class="pembina-card">
            <i class="fas fa-users"></i>
            <div class="info">
                <h3>Data Pembina Tersedia</h3>
                <p>Total <?php echo $total_pembina; ?> pembina terdaftar dalam sistem</p>
                <div class="pembina-stats">
                    <span class="stat-item"><i class="fas fa-check-circle"></i> Aktif</span>
                    <span class="stat-item"><i class="fas fa-calendar-check"></i> Siap mengajar</span>
                </div>
            </div>
        </div>

        <!-- Rule Card - Penjelasan Sistem -->
        <div class="rule-card">
            <h4><i class="fas fa-gavel"></i> ATURAN JADWAL KAJIAN MAKN ENDE</h4>
            
            <div class="rule-item">
                <div class="rule-icon success">
                    <i class="fas fa-check"></i>
                </div>
                <div>
                    <strong>✓ Boleh kajian di waktu yang sama</strong>
                    <p style="margin: 5px 0 0; color: #666; font-size: 0.9rem;">Asalkan di tempat/ruangan yang berbeda</p>
                </div>
            </div>
            
            <div class="rule-item">
                <div class="rule-icon danger">
                    <i class="fas fa-times"></i>
                </div>
                <div>
                    <strong>✗ Pembina TIDAK BOLEH mengajar 2 kali dalam sehari</strong>
                    <p style="margin: 5px 0 0; color: #666; font-size: 0.9rem;">Walau di tempat berbeda, satu pembina hanya bisa 1 jadwal per hari</p>
                </div>
            </div>
            
            <div class="rule-item">
                <div class="rule-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <strong>⚠ Satu tempat TIDAK BOLEH untuk 2 kajian di jam yang SAMA PERSIS</strong>
                    <p style="margin: 5px 0 0; color: #666; font-size: 0.9rem;">Tapi boleh di jam berbeda (contoh: 08:00 dan 10:00 di tempat yang sama)</p>
                </div>
            </div>
            
            <div class="example-box">
                <h5 style="margin-bottom: 10px; color: #333;"><i class="fas fa-lightbulb"></i> CONTOH:</h5>
                <div class="example-item">
                    <i class="fas fa-check-circle" style="color: #28a745;"></i>
                    <span><strong>DIIZINKAN:</strong> Kajian 08:00 di Masjid (Ustadz A) dan 08:00 di Aula (Ustadz B) - Berbeda tempat dan ustadz</span>
                </div>
                <div class="example-item">
                    <i class="fas fa-check-circle" style="color: #28a745;"></i>
                    <span><strong>DIIZINKAN:</strong> Kajian 08:00 di Masjid (Ustadz A) dan 10:00 di Masjid (Ustadz B) - Tempat sama tapi jam beda</span>
                </div>
                <div class="example-item">
                    <i class="fas fa-times-circle" style="color: #dc3545;"></i>
                    <span><strong>DILARANG:</strong> Kajian 08:00 di Masjid (Ustadz A) dan 13:00 di Aula (Ustadz A) - Ustadz A dua kali dalam sehari</span>
                </div>
                <div class="example-item">
                    <i class="fas fa-times-circle" style="color: #dc3545;"></i>
                    <span><strong>DILARANG:</strong> Kajian 08:00 di Masjid (Ustadz A) dan 08:00 di Masjid (Ustadz B) - Tempat sama di jam yang sama</span>
                </div>
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
                <label><i class="fas fa-user-tie"></i> Pilih Pembina / Ustadz</label>
                <select name="pemateri_id" id="pemateri_id" class="form-control" required>
                    <option value="">-- Pilih Pembina --</option>
                    <?php 
                    // Reset pointer result
                    mysqli_data_seek($user_result, 0);
                    while ($user = mysqli_fetch_assoc($user_result)): 
                        $selected = '';
                        if ($edit_data && $edit_data['pemateri_id'] == $user['id']) {
                            $selected = 'selected';
                        }
                    ?>
                        <option value="<?php echo $user['id']; ?>" 
                                data-nama="<?php echo htmlspecialchars($user['nama_lengkap']); ?>"
                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                <?php echo $selected; ?>>
                            <?php echo htmlspecialchars($user['nama_lengkap']); ?> 
                            (<?php echo htmlspecialchars($user['username']); ?>)
                        </option>
                    <?php endwhile; ?>
                    
                    <?php if ($total_pembina == 0): ?>
                        <option value="" disabled>⚠️ Belum ada pembina terdaftar</option>
                    <?php endif; ?>
                </select>
                
                <!-- Info pembina yang dipilih -->
                <div id="pembinaDetail" style="display: none; margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #667eea;">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                        <i class="fas fa-user-circle" style="font-size: 2rem; color: #667eea;"></i>
                        <div>
                            <strong id="selectedNama" style="font-size: 1.1rem;"></strong><br>
                            <span id="selectedUsername" style="color: #666;"></span>
                        </div>
                    </div>
                    <div style="margin-top: 10px; color: #28a745;">
                        <i class="fas fa-check-circle"></i> Pembina tersedia untuk mengajar
                    </div>
                </div>
                
                <div class="ustadz-info">
                    <i class="fas fa-info-circle"></i>
                    <span>Pilih pembina yang akan mengisi kajian. Data pembina diambil dari database users dengan role pembina.</span>
                </div>
                <small style="color: #dc3545; margin-top: 5px; display: block;">
                    <i class="fas fa-exclamation-circle"></i> Penting: Satu pembina hanya boleh mengajar SATU KALI dalam sehari!
                </small>
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

        <!-- Daftar Kajian -->
        <div style="margin-top: 50px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <h3 style="font-size: 1.8rem; color: var(--primary-color);">
                    <i class="fas fa-list"></i> 
                    Daftar Kajian
                </h3>
                <div class="badge-info">
                    <i class="fas fa-shield-alt"></i> 1 Pembina = 1x Sehari
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
                        echo "<option value='" . htmlspecialchars($tempat_row['tempat']) . "'>" . htmlspecialchars($tempat_row['tempat']) . "</option>";
                    }
                    ?>
                </select>
                <select id="filterUstadz" style="padding: 12px 25px; border: 2px solid #e0e0e0; border-radius: 50px; background: white;">
                    <option value="">Semua Pembina</option>
                    <?php
                    $ustadz_query = "SELECT DISTINCT pemateri FROM kajian ORDER BY pemateri";
                    $ustadz_result = mysqli_query($conn, $ustadz_query);
                    while ($ustadz_row = mysqli_fetch_assoc($ustadz_result)) {
                        echo "<option value='" . htmlspecialchars($ustadz_row['pemateri']) . "'>" . htmlspecialchars($ustadz_row['pemateri']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="table-container">
                <table class="table" id="kajianTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Judul</th>
                            <th>Pembina</th>
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
                        // Query dengan JOIN untuk menampilkan pembuat - HAPUS referensi ke email dan no_telepon
                        $query = "SELECT k.*, u.nama_lengkap as pembuat,
                                 up.nama_lengkap as nama_pemateri,
                                 up.username as username_pemateri
                                 FROM kajian k 
                                 LEFT JOIN users u ON k.created_by = u.id 
                                 LEFT JOIN users up ON k.pemateri_id = up.id
                                 ORDER BY k.tanggal DESC, k.waktu ASC";
                        $result = mysqli_query($conn, $query);
                        $no = 1;
                        
                        $jadwal_list = [];
                        $jadwal_per_hari = []; // Untuk deteksi ustadz double
                        
                        while ($row = mysqli_fetch_assoc($result)) {
                            $jadwal_list[] = $row;
                            
                            // Deteksi ustadz yang mengajar 2x dalam sehari
                            $key = $row['tanggal'] . '|' . $row['pemateri'];
                            if (!isset($jadwal_per_hari[$key])) {
                                $jadwal_per_hari[$key] = [];
                            }
                            $jadwal_per_hari[$key][] = $row;
                        }
                        
                        // Tandai ustadz yang double
                        $double_ustadz = [];
                        foreach ($jadwal_per_hari as $key => $jadwals) {
                            if (count($jadwals) > 1) {
                                foreach ($jadwals as $j) {
                                    $double_ustadz[$j['id']] = true;
                                }
                            }
                        }
                        
                        // Tampilkan data
                        foreach ($jadwal_list as $row) {
                            $status = '<span style="color: green;"><i class="fas fa-check-circle"></i> OK</span>';
                            $row_class = '';
                            
                            if (isset($double_ustadz[$row['id']])) {
                                $status = '<span style="color: red;"><i class="fas fa-exclamation-triangle"></i> Pembina Double!</span>';
                                $row_class = 'conflict-row';
                            }
                            
                            echo "<tr data-tempat='" . htmlspecialchars($row['tempat']) . "' 
                                      data-ustadz='" . htmlspecialchars($row['pemateri']) . "'
                                      data-judul='" . htmlspecialchars($row['judul']) . "'
                                      data-tanggal='" . $row['tanggal'] . "'
                                      data-waktu='" . $row['waktu'] . "'
                                      class='$row_class'>";
                            echo "<td>" . $no++ . "</td>";
                            echo "<td><strong>" . htmlspecialchars($row['judul']) . "</strong></td>";
                            echo "<td>
                                    " . htmlspecialchars($row['pemateri']) . "
                                    <span class='ustadz-badge' title='Username: " . ($row['username_pemateri'] ?? '') . "'>
                                        <i class='fas fa-user-check'></i> Pembina
                                    </span>
                                  </td>";
                            echo "<td>" . date('d/m/Y', strtotime($row['tanggal'])) . "</td>";
                            echo "<td>" . $row['waktu'] . " WIB</td>";
                            echo "<td>" . htmlspecialchars($row['tempat']) . "</td>";
                            echo "<td>" . ($row['pembuat'] ?? '<em>Tidak diketahui</em>') . "</td>";
                            echo "<td>" . $status . "</td>";
                            echo "<td>
                                    <a href='?edit=" . $row['id'] . "' class='btn-edit' title='Edit Data'><i class='fas fa-edit'></i> Edit</a>
                                    <a href='?hapus=" . $row['id'] . "' class='btn-hapus' onclick='return confirm(\"Yakin ingin menghapus kajian \\\"" . htmlspecialchars($row['judul']) . "\\\"?\")' title='Hapus Data'><i class='fas fa-trash'></i> Hapus</a>
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
            
            <!-- Info Box -->
            <div class="alert alert-info" style="margin-top: 20px; background: #e3f2fd; border-left: 5px solid #2196F3;">
                <div style="display: flex; align-items: start; gap: 15px;">
                    <i class="fas fa-info-circle" style="font-size: 2rem; color: #2196F3;"></i>
                    <div>
                        <strong style="font-size: 1.1rem;">Ringkasan Aturan:</strong>
                        <ul style="margin-top: 8px; margin-left: 20px;">
                            <li>✅ Kajian di waktu yang sama diperbolehkan di TEMPAT BERBEDA</li>
                            <li>❌ Seorang pembina HANYA BOLEH MENGAJAR SATU KALI dalam sehari</li>
                            <li>❌ Satu tempat TIDAK BOLEH digunakan untuk dua kajian di JAM YANG SAMA PERSIS</li>
                        </ul>
                        <p style="margin-top: 10px; color: #666;">
                            <i class="fas fa-clock"></i> Total Kajian: <?php echo count($jadwal_list); ?> | 
                            <span style="color: <?php echo !empty($double_ustadz) ? 'red' : 'green'; ?>;">
                                <i class="fas fa-<?php echo !empty($double_ustadz) ? 'exclamation-triangle' : 'check-circle'; ?>"></i> 
                                <?php echo !empty($double_ustadz) ? count($double_ustadz) . " Jadwal dengan pembina double" : "Tidak ada pembina yang double"; ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Data jadwal dari PHP
        const jadwalList = <?php echo json_encode($jadwal_list ?? []); ?>;
        const editId = <?php echo $edit_data['id'] ?? 'null'; ?>;
        
        // Tampilkan detail pembina saat dipilih
        document.getElementById('pemateri_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const pembinaDetail = document.getElementById('pembinaDetail');
            
            if (selectedOption && selectedOption.value) {
                const nama = selectedOption.getAttribute('data-nama');
                const username = selectedOption.getAttribute('data-username');
                
                document.getElementById('selectedNama').textContent = nama;
                document.getElementById('selectedUsername').textContent = '@' + username;
                
                pembinaDetail.style.display = 'block';
            } else {
                pembinaDetail.style.display = 'none';
            }
            
            // Trigger validasi ulang
            cekValidasi();
        });

        // Real-time validation
        const tanggalInput = document.getElementById('tanggal');
        const waktuInput = document.getElementById('waktu');
        const tempatInput = document.getElementById('tempat');
        const pemateriSelect = document.getElementById('pemateri_id');
        const jadwalInfo = document.getElementById('jadwalInfo');
        const submitBtn = document.getElementById('submitBtn');
        const formKajian = document.getElementById('formKajian');
        
        function cekValidasi() {
            const tanggal = tanggalInput.value;
            const waktu = waktuInput.value;
            const tempat = tempatInput.value;
            const pemateriId = pemateriSelect.value;
            const pemateriNama = pemateriSelect.selectedOptions[0]?.getAttribute('data-nama') || '';
            
            if (tanggal && waktu && tempat && pemateriId) {
                let errors = [];
                let isBlocked = false;
                
                // Cek semua jadwal yang ada
                for (let j of jadwalList) {
                    if (j.id == editId) continue; // Skip data sendiri
                    
                    // Cek apakah ustadz sudah mengajar di hari yang sama (ATURAN UTAMA)
                    if (j.tanggal === tanggal && (j.pemateri.toLowerCase() === pemateriNama.toLowerCase() || j.pemateri_id == pemateriId)) {
                        errors.push(`❌ Pembina "${pemateriNama}" SUDAH MENGAJAR pada hari yang sama pukul ${j.waktu} di ${j.tempat} dengan judul "${j.judul}". Satu pembina hanya boleh 1x sehari!`);
                        isBlocked = true;
                    }
                    
                    // Cek apakah tempat digunakan di waktu yang SAMA PERSIS
                    if (j.tanggal === tanggal && j.waktu === waktu && j.tempat.toLowerCase() === tempat.toLowerCase()) {
                        errors.push(`❌ Tempat "${tempat}" SUDAH DIGUNAKAN pada jam yang sama untuk kajian "${j.judul}" dengan pembina ${j.pemateri}`);
                        isBlocked = true;
                    }
                }
                
                if (errors.length > 0) {
                    jadwalInfo.style.display = 'flex';
                    jadwalInfo.className = 'jadwal-tidak-tersedia';
                    jadwalInfo.innerHTML = '<i class="fas fa-times-circle"></i> <strong>Jadwal TIDAK DAPAT disimpan!</strong><br><br>' + 
                                          errors.join('<br>');
                    submitBtn.disabled = true;
                } else {
                    jadwalInfo.style.display = 'flex';
                    jadwalInfo.className = 'jadwal-tersedia';
                    
                    // Cek apakah ada kajian lain di waktu yang sama (informasi saja)
                    let kajianBersamaan = [];
                    for (let j of jadwalList) {
                        if (j.id == editId) continue;
                        if (j.tanggal === tanggal && j.waktu === waktu) {
                            kajianBersamaan.push(`"${j.judul}" di ${j.tempat} (Pembina ${j.pemateri})`);
                        }
                    }
                    
                    if (kajianBersamaan.length > 0) {
                        jadwalInfo.innerHTML = '<i class="fas fa-info-circle"></i> <strong>Informasi:</strong> Ada kajian lain di waktu yang sama:<br>' + 
                                              kajianBersamaan.join('<br>') + '<br><br>' +
                                              '<span style="color: #28a745;">✅ Namun ini DIIZINKAN karena pembina berbeda dan tempat berbeda.</span>';
                    } else {
                        jadwalInfo.innerHTML = '<i class="fas fa-check-circle"></i> <strong>Jadwal tersedia!</strong> Silakan lanjutkan.';
                    }
                    
                    submitBtn.disabled = false;
                }
            } else {
                jadwalInfo.style.display = 'none';
                submitBtn.disabled = false;
            }
        }
        
        // Event listeners
        tanggalInput.addEventListener('change', cekValidasi);
        waktuInput.addEventListener('change', cekValidasi);
        tempatInput.addEventListener('input', cekValidasi);
        pemateriSelect.addEventListener('change', cekValidasi);
        
        // Search and filter functionality
        document.getElementById('searchTable').addEventListener('keyup', filterTable);
        document.getElementById('filterTempat').addEventListener('change', filterTable);
        document.getElementById('filterUstadz').addEventListener('change', filterTable);
        
        function filterTable() {
            let searchText = document.getElementById('searchTable').value.toLowerCase();
            let filterTempat = document.getElementById('filterTempat').value;
            let filterUstadz = document.getElementById('filterUstadz').value;
            let rows = document.querySelectorAll('#kajianTable tbody tr');
            
            for (let row of rows) {
                let judul = row.cells[1]?.textContent.toLowerCase() || '';
                let pemateri = row.cells[2]?.textContent.toLowerCase() || '';
                let tempat = row.getAttribute('data-tempat') || '';
                let ustadz = row.getAttribute('data-ustadz') || '';
                
                let matchSearch = judul.includes(searchText) || pemateri.includes(searchText);
                let matchTempat = filterTempat === '' || tempat === filterTempat;
                let matchUstadz = filterUstadz === '' || ustadz === filterUstadz;
                
                row.style.display = (matchSearch && matchTempat && matchUstadz) ? '' : 'none';
            }
        }
        
        // Form validation before submit
        formKajian.addEventListener('submit', function(e) {
            if (submitBtn.disabled) {
                e.preventDefault();
                alert('❌ TIDAK DAPAT MENYIMPAN: Aturan dilanggar!\n\n1. Pembina hanya boleh mengajar SATU KALI dalam sehari\n2. Satu tempat tidak boleh dipakai di jam yang sama');
            }
        });
        
        // Set minimal date
        tanggalInput.setAttribute('min', new Date().toISOString().split('T')[0]);
        
        // Initial check for edit mode
        <?php if ($edit_data): ?>
        window.addEventListener('load', function() {
            setTimeout(cekValidasi, 500);
            // Trigger change event on select to show pembina details
            const event = new Event('change');
            document.getElementById('pemateri_id').dispatchEvent(event);
        });
        <?php endif; ?>
        
        // Auto-hide success message after 5 seconds
        setTimeout(function() {
            const alert = document.querySelector('.alert-success');
            if (alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
    </script>
</body>
</html>
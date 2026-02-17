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
function cekUstadzSudahMengajarHariIni($conn, $tanggal, $pemateri, $id = null) {
    $query = "SELECT id, judul, waktu, tempat FROM kajian 
              WHERE tanggal = '$tanggal' AND pemateri = '$pemateri'";
    
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
    
    $id = $_POST['id'] ?? null;
    
    // VALIDASI UTAMA: Cek apakah ustadz sudah mengajar di hari yang sama
    $jadwal_ustadz = cekUstadzSudahMengajarHariIni($conn, $tanggal, $pemateri, $id);
    if ($jadwal_ustadz) {
        $errors[] = "Ustadz {$pemateri} sudah memiliki jadwal kajian '{$jadwal_ustadz['judul']}' pada hari yang sama pukul " . 
                    date('H:i', strtotime($jadwal_ustadz['waktu'])) . " di {$jadwal_ustadz['tempat']}. " .
                    "Seorang ustadz hanya boleh mengajar SATU KALI dalam sehari!";
    }
    
    // VALIDASI: Cek apakah tempat sudah digunakan di waktu yang SAMA PERSIS
    // (Ini tetap diperlukan karena tidak mungkin 2 kajian di tempat sama di waktu sama)
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
                    <strong>✗ Ustadz TIDAK BOLEH mengajar 2 kali dalam sehari</strong>
                    <p style="margin: 5px 0 0; color: #666; font-size: 0.9rem;">Walau di tempat berbeda, satu ustadz hanya bisa 1 jadwal per hari</p>
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
                <label><i class="fas fa-user-tie"></i> Pemateri / Ustadz</label>
                <input type="text" name="pemateri" id="pemateri" value="<?php echo $edit_data['pemateri'] ?? ''; ?>" placeholder="Contoh: Ustadz Ahmad" required>
                <small style="color: #dc3545; margin-top: 5px; display: block;">
                    <i class="fas fa-exclamation-circle"></i> Penting: Satu ustadz hanya boleh mengajar SATU KALI dalam sehari!
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
                    <i class="fas fa-shield-alt"></i> 1 Ustadz = 1x Sehari
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
                    <option value="">Semua Ustadz</option>
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
                                $status = '<span style="color: red;"><i class="fas fa-exclamation-triangle"></i> Ustadz Double!</span>';
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
                            echo "<td>" . htmlspecialchars($row['pemateri']) . "</td>";
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
                            <li>❌ Seorang ustadz HANYA BOLEH MENGAJAR SATU KALI dalam sehari</li>
                            <li>❌ Satu tempat TIDAK BOLEH digunakan untuk dua kajian di JAM YANG SAMA PERSIS</li>
                        </ul>
                        <p style="margin-top: 10px; color: #666;">
                            <i class="fas fa-clock"></i> Total Kajian: <?php echo count($jadwal_list); ?> | 
                            <span style="color: <?php echo !empty($double_ustadz) ? 'red' : 'green'; ?>;">
                                <i class="fas fa-<?php echo !empty($double_ustadz) ? 'exclamation-triangle' : 'check-circle'; ?>"></i> 
                                <?php echo !empty($double_ustadz) ? count($double_ustadz) . " Jadwal dengan ustadz double" : "Tidak ada ustadz yang double"; ?>
                            </span>
                        </p>
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
        const submitBtn = document.getElementById('submitBtn');
        const formKajian = document.getElementById('formKajian');
        
        // Data jadwal dari PHP
        const jadwalList = <?php echo json_encode($jadwal_list ?? []); ?>;
        const editId = <?php echo $edit_data['id'] ?? 'null'; ?>;
        
        function cekValidasi() {
            const tanggal = tanggalInput.value;
            const waktu = waktuInput.value;
            const tempat = tempatInput.value;
            const pemateri = pemateriInput.value;
            
            if (tanggal && waktu && tempat && pemateri) {
                let errors = [];
                let isBlocked = false;
                
                // Cek semua jadwal yang ada
                for (let j of jadwalList) {
                    if (j.id == editId) continue; // Skip data sendiri
                    
                    // Cek apakah ustadz sudah mengajar di hari yang sama (ATURAN UTAMA)
                    if (j.tanggal === tanggal && j.pemateri.toLowerCase() === pemateri.toLowerCase()) {
                        errors.push(`❌ Ustadz "${pemateri}" SUDAH MENGAJAR pada hari yang sama pukul ${j.waktu} di ${j.tempat} dengan judul "${j.judul}". Satu ustadz hanya boleh 1x sehari!`);
                        isBlocked = true;
                    }
                    
                    // Cek apakah tempat digunakan di waktu yang SAMA PERSIS
                    if (j.tanggal === tanggal && j.waktu === waktu && j.tempat.toLowerCase() === tempat.toLowerCase()) {
                        errors.push(`❌ Tempat "${tempat}" SUDAH DIGUNAKAN pada jam yang sama untuk kajian "${j.judul}" dengan ustadz ${j.pemateri}`);
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
                            kajianBersamaan.push(`"${j.judul}" di ${j.tempat} (Ustadz ${j.pemateri})`);
                        }
                    }
                    
                    if (kajianBersamaan.length > 0) {
                        jadwalInfo.innerHTML = '<i class="fas fa-info-circle"></i> <strong>Informasi:</strong> Ada kajian lain di waktu yang sama:<br>' + 
                                              kajianBersamaan.join('<br>') + '<br><br>' +
                                              '<span style="color: #28a745;">✅ Namun ini DIIZINKAN karena ustadz berbeda dan tempat berbeda.</span>';
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
        pemateriInput.addEventListener('input', cekValidasi);
        
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
                alert('❌ TIDAK DAPAT MENYIMPAN: Aturan dilanggar!\n\n1. Ustadz hanya boleh mengajar SATU KALI dalam sehari\n2. Satu tempat tidak boleh dipakai di jam yang sama');
            }
        });
        
        // Set minimal date
        tanggalInput.setAttribute('min', new Date().toISOString().split('T')[0]);
        
        // Initial check for edit mode
        <?php if ($edit_data): ?>
        window.addEventListener('load', function() {
            setTimeout(cekValidasi, 500);
        });
        <?php endif; ?>
    </script>
</body>
</html>
<?php
include '../includes/auth.php';
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Handle Hapus User dengan SweetAlert
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus']);
    // Cegah hapus admin utama
    $check = mysqli_query($conn, "SELECT role, nama_lengkap FROM users WHERE id='$id'");
    $user = mysqli_fetch_assoc($check);
    
    if ($user && $user['role'] != 'admin') {
        mysqli_query($conn, "DELETE FROM users WHERE id='$id'");
        $status = 'deleted';
        $message = 'User ' . $user['nama_lengkap'] . ' berhasil dihapus';
    } else {
        $status = 'error';
        $message = 'Tidak dapat menghapus user admin';
    }
    redirect('kelola_user.php?status=' . $status . '&message=' . urlencode($message));
}

// Handle Reset Password dengan SweetAlert
if (isset($_GET['reset'])) {
    $id = mysqli_real_escape_string($conn, $_GET['reset']);
    $new_password = md5('password123');
    mysqli_query($conn, "UPDATE users SET password='$new_password' WHERE id='$id'");
    redirect('kelola_user.php?status=reset&message=Password berhasil direset menjadi password123');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Admin MAKN ENDE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        /* Style untuk tombol aksi */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-edit, .btn-reset, .btn-hapus {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            cursor: pointer;
        }
        
        .btn-edit {
            background: #fff3cd;
            color: #856404;
            border-color: #ffeeba;
        }
        
        .btn-edit:hover {
            background: #e0a800;
            color: white;
            border-color: #e0a800;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 193, 7, 0.3);
        }
        
        .btn-reset {
            background: #e8f4fd;
            color: #17a2b8;
            border-color: #b8e2f2;
        }
        
        .btn-reset:hover {
            background: #17a2b8;
            color: white;
            border-color: #17a2b8;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(23, 162, 184, 0.3);
        }
        
        .btn-hapus {
            background: #f8d7da;
            color: #dc3545;
            border-color: #f5c6cb;
        }
        
        .btn-hapus:hover {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }
        
        /* Style untuk button tambah yang lebih menarik */
        .btn-tambah {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .btn-tambah:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn-tambah:hover:before {
            left: 100%;
        }

        .btn-tambah:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
            background: linear-gradient(135deg, #764ba2, #667eea);
        }

        .btn-tambah i {
            font-size: 18px;
            transition: transform 0.3s ease;
        }

        .btn-tambah:hover i {
            transform: rotate(90deg);
        }

        .btn-tambah:active {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        /* Style untuk statistik cards */
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-icon.admin {
            background: linear-gradient(135deg, #667eea20, #764ba220);
            color: #667eea;
        }

        .stat-icon.pembina {
            background: linear-gradient(135deg, #28a74520, #20c99720);
            color: #28a745;
        }

        /* Style untuk modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            overflow-y: auto;
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        .modal-content {
            background-color: #fff;
            margin: 30px auto;
            padding: 0;
            width: 90%;
            max-width: 550px;
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
            animation: slideDown 0.4s ease;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-70px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px 25px;
            border-radius: 20px 20px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-family: 'Amiri', serif;
        }
        
        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .close:hover {
            opacity: 0.8;
            transform: scale(1.1);
        }
        
        .form-group {
            padding: 15px 25px;
            margin: 0;
            border-bottom: 1px solid #eee;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .form-group label i {
            margin-right: 5px;
            color: #28a745;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }
        
        .modal-footer {
            padding: 20px 25px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background: #f8f9fa;
            border-radius: 0 0 20px 20px;
        }
        
        .btn-simpan, .btn-batal {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-simpan {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .btn-simpan:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        
        .btn-batal {
            background: #6c757d;
            color: white;
        }
        
        .btn-batal:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .required {
            color: #dc3545;
            margin-left: 3px;
        }
        
        .text-muted {
            color: #6c757d;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        
        .toggle-password:hover {
            color: #28a745;
        }
        
        .role-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .role-admin {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .role-pembina {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #f0f0f0;
            color: #666;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <div class="navbar-logo">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <h1>MAKN ENDE <span>Panel Admin</span></h1>
                </div>
            </div>
            <div class="navbar-menu">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="kelola_kajian.php"><i class="fas fa-calendar-alt"></i> Kelola Kajian</a>
                <a href="kelola_user.php" class="active"><i class="fas fa-users-cog"></i> Kelola User</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <a href="kelola_user.php"><i class="fas fa-users-cog"></i> Kelola User</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span>Daftar User</span>
        </div>

        <!-- Welcome Card -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: var(--border-radius); margin-bottom: 40px; color: white;">
            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 50%;">
                    <i class="fas fa-users-cog" style="font-size: 30px;"></i>
                </div>
                <div>
                    <h2 style="font-size: 1.8rem; margin-bottom: 5px; font-family: 'Amiri', serif;">
                        <i class="fas fa-hand-sparkles"></i> 
                        Kelola User
                    </h2>
                    <p style="opacity: 0.9;">Mengelola data pembina dan admin pada sistem jadwal kajian MAKN ENDE.</p>
                </div>
            </div>
        </div>

        <!-- Header dengan statistik user yang lebih menarik -->
        <?php
        $total_admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='admin'"))['total'];
        $total_pembina = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='pembina'"))['total'];
        $total_user = $total_admin + $total_pembina;
        ?>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px;">
            <h2 class="dashboard-title" style="margin-bottom: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-users" style="color: #667eea;"></i> 
                Daftar User
            </h2>
            
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div class="stat-card">
                    <div class="stat-icon admin">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <div class="stat-info">
                        <h4 style="font-size: 14px; color: #666; margin-bottom: 5px;">Total Admin</h4>
                        <span style="font-size: 24px; font-weight: 700; color: #333;"><?php echo $total_admin; ?></span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon pembina">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-info">
                        <h4 style="font-size: 14px; color: #666; margin-bottom: 5px;">Total Pembina</h4>
                        <span style="font-size: 24px; font-weight: 700; color: #333;"><?php echo $total_pembina; ?></span>
                    </div>
                </div>
                
                <div class="stat-card" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h4 style="font-size: 14px; color: rgba(255,255,255,0.8); margin-bottom: 5px;">Total User</h4>
                        <span style="font-size: 24px; font-weight: 700; color: white;"><?php echo $total_user; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tombol Tambah User yang lebih menarik -->
        <div style="margin-bottom: 30px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
            <button onclick="openTambahModal()" class="btn-tambah">
                <i class="fas fa-plus-circle"></i> Tambah Pembina Baru
            </button>
            
            <div style="margin-left: auto; display: flex; gap: 10px;">
                <span style="background: #e8f5e9; color: #2e7d32; padding: 8px 15px; border-radius: 50px; font-size: 13px;">
                    <i class="fas fa-info-circle"></i> Klik tombol untuk menambah user baru
                </span>
            </div>
        </div>

        <!-- Modal Tambah User -->
        <div id="tambahUserModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-user-plus"></i> Tambah Pembina Baru</h3>
                    <span class="close" onclick="closeTambahModal()">&times;</span>
                </div>
                <form id="formTambahUser" method="POST" action="proses_tambah_user.php" onsubmit="return validateTambahForm(event)">
                    <input type="hidden" name="action" value="tambah">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nama Lengkap <span class="required">*</span></label>
                        <input type="text" name="nama_lengkap" id="tambah_nama_lengkap" class="form-control" 
                               placeholder="Masukkan nama lengkap" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-user-circle"></i> Username <span class="required">*</span></label>
                        <input type="text" name="username" id="tambah_username" class="form-control" 
                               placeholder="Masukkan username" required>
                        <small class="text-muted">Username minimal 3 karakter, hanya huruf dan angka</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password <span class="required">*</span></label>
                        <div style="position: relative;">
                            <input type="password" name="password" id="tambah_password" class="form-control" 
                                   placeholder="Masukkan password" required>
                            <i class="fas fa-eye toggle-password" onclick="togglePassword('tambah_password', this)"></i>
                        </div>
                        <small class="text-muted">Password minimal 6 karakter</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Konfirmasi Password <span class="required">*</span></label>
                        <div style="position: relative;">
                            <input type="password" name="konfirmasi_password" id="tambah_konfirmasi_password" class="form-control" 
                                   placeholder="Ulangi password" required>
                            <i class="fas fa-eye toggle-password" onclick="togglePassword('tambah_konfirmasi_password', this)"></i>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" onclick="closeTambahModal()" class="btn-batal">
                            <i class="fas fa-times"></i> Batal
                        </button>
                        <button type="submit" class="btn-simpan" id="btnSimpanTambah">
                            <i class="fas fa-save"></i> Simpan Pembina
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Edit User -->
        <div id="editUserModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-user-edit"></i> Edit User</h3>
                    <span class="close" onclick="closeEditModal()">&times;</span>
                </div>
                <form id="formEditUser" method="POST" action="proses_tambah_user.php" onsubmit="return validateEditForm(event)">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nama Lengkap <span class="required">*</span></label>
                        <input type="text" name="nama_lengkap" id="edit_nama_lengkap" class="form-control" 
                               placeholder="Masukkan nama lengkap" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-user-circle"></i> Username <span class="required">*</span></label>
                        <input type="text" name="username" id="edit_username" class="form-control" 
                               placeholder="Masukkan username" required>
                        <small class="text-muted">Username minimal 3 karakter, hanya huruf dan angka</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Role <span class="required">*</span></label>
                        <select name="role" id="edit_role" class="form-control" required>
                            <option value="pembina">Pembina</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password Baru (Kosongkan jika tidak ingin mengubah)</label>
                        <div style="position: relative;">
                            <input type="password" name="password" id="edit_password" class="form-control" 
                                   placeholder="Masukkan password baru">
                            <i class="fas fa-eye toggle-password" onclick="togglePassword('edit_password', this)"></i>
                        </div>
                        <small class="text-muted">Minimal 6 karakter jika diisi</small>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" onclick="closeEditModal()" class="btn-batal">
                            <i class="fas fa-times"></i> Batal
                        </button>
                        <button type="submit" class="btn-simpan" id="btnSimpanEdit">
                            <i class="fas fa-save"></i> Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel User -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th><i class="fas fa-user"></i> Nama Lengkap</th>
                        <th><i class="fas fa-user-circle"></i> Username</th>
                        <th><i class="fas fa-tag"></i> Role</th>
                        <th><i class="fas fa-calendar-alt"></i> Tanggal Daftar</th>
                        <th><i class="fas fa-cog"></i> Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT * FROM users ORDER BY 
                              CASE role 
                                WHEN 'admin' THEN 1 
                                WHEN 'pembina' THEN 2 
                              END, 
                              created_at DESC";
                    $result = mysqli_query($conn, $query);
                    $no = 1;
                    
                    while ($row = mysqli_fetch_assoc($result)) {
                        $role_class = ($row['role'] == 'admin') ? 'role-admin' : 'role-pembina';
                        $role_icon = ($row['role'] == 'admin') ? 'fa-user-cog' : 'fa-user-tie';
                        
                        echo "<tr>";
                        echo "<td>" . $no++ . "</td>";
                        echo "<td><strong>" . htmlspecialchars($row['nama_lengkap']) . "</strong></td>";
                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                        echo "<td><span class='role-badge " . $role_class . "'><i class='fas " . $role_icon . "'></i> " . ucfirst($row['role']) . "</span></td>";
                        echo "<td>" . date('d/m/Y H:i', strtotime($row['created_at'])) . " WIB</td>";
                        echo "<td class='action-buttons'>";
                        
                        // === PERBAIKAN: Menggunakan htmlspecialchars untuk mengatasi tanda petik ===
                        $nama_escape = htmlspecialchars($row['nama_lengkap'], ENT_QUOTES, 'UTF-8');
                        $username_escape = htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8');
                        
                        // Tombol Edit untuk semua user
                        echo "<button onclick='editUser(" . $row['id'] . ", \"" . $nama_escape . "\", \"" . $username_escape . "\", \"" . $row['role'] . "\")' class='btn-edit' title='Edit User'>";
                        echo "<i class='fas fa-edit'></i> Edit";
                        echo "</button>";
                        
                        if ($row['role'] != 'admin') {   
                            // Tombol Reset Password
                            echo "<button onclick='resetPassword(" . $row['id'] . ", \"" . $nama_escape . "\")' class='btn-reset' title='Reset Password'>";
                            echo "<i class='fas fa-key'></i> Reset";
                            echo "</button>";
                            
                            // Tombol Hapus
                            echo "<button onclick='hapusUser(" . $row['id'] . ", \"" . $nama_escape . "\")' class='btn-hapus' title='Hapus User'>";
                            echo "<i class='fas fa-trash'></i> Hapus";
                            echo "</button>";
                        } else {
                            echo "<span class='admin-badge'><i class='fas fa-shield-alt'></i> Admin Utama</span>";
                        }
                        
                        echo "</td>";
                        echo "</tr>";
                    }
                    
                    if (mysqli_num_rows($result) == 0) {
                        echo "<tr><td colspan='6' style='text-align: center; padding: 60px;'>";
                        echo "<i class='fas fa-users-slash' style='font-size: 40px; color: #ccc; margin-bottom: 15px; display: block;'></i>";
                        echo "<h3 style='color: #999;'>Belum Ada User</h3>";
                        echo "<p style='color: #999;'>Silakan tambah user baru melalui halaman registrasi.</p>";
                        echo "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Fungsi untuk toggle password
    function togglePassword(fieldId, element) {
        var field = document.getElementById(fieldId);
        if (field.type === "password") {
            field.type = "text";
            element.classList.remove('fa-eye');
            element.classList.add('fa-eye-slash');
        } else {
            field.type = "password";
            element.classList.remove('fa-eye-slash');
            element.classList.add('fa-eye');
        }
    }

    // Modal Tambah dengan animasi
    function openTambahModal() {
        var modal = document.getElementById('tambahUserModal');
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        document.getElementById('formTambahUser').reset();
        modal.style.animation = 'fadeIn 0.3s ease';
    }

    function closeTambahModal() {
        var modal = document.getElementById('tambahUserModal');
        modal.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }, 200);
    }

    // Modal Edit
    function openEditModal() {
        var modal = document.getElementById('editUserModal');
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        modal.style.animation = 'fadeIn 0.3s ease';
    }

    function closeEditModal() {
        var modal = document.getElementById('editUserModal');
        modal.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }, 200);
    }

    // Fungsi Edit User
    function editUser(id, nama, username, role) {
        document.getElementById('edit_user_id').value = id;
        document.getElementById('edit_nama_lengkap').value = nama;
        document.getElementById('edit_username').value = username;
        document.getElementById('edit_role').value = role;
        document.getElementById('edit_password').value = '';
        openEditModal();
    }

    // Validasi form tambah yang lebih baik
    function validateTambahForm(event) {
        event.preventDefault();
        
        var nama = document.getElementById('tambah_nama_lengkap').value.trim();
        var username = document.getElementById('tambah_username').value.trim();
        var password = document.getElementById('tambah_password').value;
        var konfirmasi = document.getElementById('tambah_konfirmasi_password').value;
        
        // Validasi nama
        if (nama.length < 3) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Nama lengkap minimal 3 karakter',
                confirmButtonColor: '#dc3545',
                background: '#fff',
                backdrop: 'rgba(0,0,0,0.4)'
            });
            return false;
        }
        
        // Validasi username
        var usernameRegex = /^[a-zA-Z0-9]{3,20}$/;
        if (!usernameRegex.test(username)) {
            Swal.fire({
                icon: 'error',
                title: 'Username Tidak Valid',
                text: 'Username hanya boleh huruf dan angka, minimal 3 karakter',
                confirmButtonColor: '#dc3545'
            });
            return false;
        }
        
        // Validasi password
        if (password.length < 6) {
            Swal.fire({
                icon: 'error',
                title: 'Password Terlalu Pendek',
                text: 'Password minimal 6 karakter',
                confirmButtonColor: '#dc3545'
            });
            return false;
        }
        
        if (password !== konfirmasi) {
            Swal.fire({
                icon: 'error',
                title: 'Password Tidak Cocok',
                text: 'Password dan konfirmasi password harus sama',
                confirmButtonColor: '#dc3545'
            });
            return false;
        }
        
        // Tampilkan loading yang lebih menarik
        Swal.fire({
            title: 'Menyimpan Data...',
            html: 'Mohon tunggu sebentar',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
            background: '#fff',
            backdrop: 'rgba(102, 126, 234, 0.3)'
        });
        
        // Submit form setelah validasi berhasil
        setTimeout(() => {
            document.getElementById('formTambahUser').submit();
        }, 500);
        
        return true;
    }

    // Validasi form edit
    function validateEditForm(event) {
        event.preventDefault();
        
        var nama = document.getElementById('edit_nama_lengkap').value.trim();
        var username = document.getElementById('edit_username').value.trim();
        var password = document.getElementById('edit_password').value;
        
        // Validasi nama
        if (nama.length < 3) {
            Swal.fire({
                icon: 'error',
                title: 'Validasi Gagal',
                text: 'Nama lengkap minimal 3 karakter',
                confirmButtonColor: '#dc3545'
            });
            return false;
        }
        
        // Validasi username
        var usernameRegex = /^[a-zA-Z0-9]{3,20}$/;
        if (!usernameRegex.test(username)) {
            Swal.fire({
                icon: 'error',
                title: 'Validasi Gagal',
                text: 'Username hanya boleh huruf dan angka, minimal 3 karakter',
                confirmButtonColor: '#dc3545'
            });
            return false;
        }
        
        // Validasi password jika diisi
        if (password && password.length < 6) {
            Swal.fire({
                icon: 'error',
                title: 'Validasi Gagal',
                text: 'Password minimal 6 karakter',
                confirmButtonColor: '#dc3545'
            });
            return false;
        }
        
        // Tampilkan loading
        Swal.fire({
            title: 'Menyimpan...',
            text: 'Mohon tunggu sebentar',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
            background: '#fff',
            backdrop: 'rgba(102, 126, 234, 0.3)'
        });
        
        // Submit form
        setTimeout(() => {
            document.getElementById('formEditUser').submit();
        }, 500);
    }

    // Fungsi Hapus User dengan SweetAlert
    function hapusUser(id, nama) {
        Swal.fire({
            title: 'Konfirmasi Hapus',
            html: `Apakah Anda yakin ingin menghapus user <strong>${nama}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus!',
            cancelButtonText: '<i class="fas fa-times"></i> Batal',
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return new Promise((resolve) => {
                    window.location.href = `?hapus=${id}`;
                    resolve();
                });
            }
        });
    }

    // Fungsi Reset Password dengan SweetAlert
    function resetPassword(id, nama) {
        Swal.fire({
            title: 'Reset Password',
            html: `Reset password untuk user <strong>${nama}</strong> menjadi <strong>password123</strong>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#17a2b8',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-key"></i> Ya, Reset!',
            cancelButtonText: '<i class="fas fa-times"></i> Batal',
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return new Promise((resolve) => {
                    window.location.href = `?reset=${id}`;
                    resolve();
                });
            }
        });
    }

    // Tutup modal jika klik di luar
    window.onclick = function(event) {
        var tambahModal = document.getElementById('tambahUserModal');
        var editModal = document.getElementById('editUserModal');
        if (event.target == tambahModal) {
            closeTambahModal();
        }
        if (event.target == editModal) {
            closeEditModal();
        }
    }

    // Tampilkan SweetAlert berdasarkan status dari URL
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] == 'success'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: '<?php echo isset($_GET['message']) ? $_GET['message'] : 'User baru berhasil ditambahkan'; ?>',
                    showConfirmButton: true,
                    confirmButtonColor: '#28a745',
                    timer: 3000,
                    background: '#fff',
                    backdrop: 'rgba(40, 167, 69, 0.3)'
                });
            <?php endif; ?>
            
            <?php if ($_GET['status'] == 'updated'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: '<?php echo isset($_GET['message']) ? $_GET['message'] : 'Data user berhasil diperbarui'; ?>',
                    showConfirmButton: true,
                    confirmButtonColor: '#28a745',
                    timer: 3000
                });
            <?php endif; ?>
            
            <?php if ($_GET['status'] == 'deleted'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: '<?php echo isset($_GET['message']) ? $_GET['message'] : 'User berhasil dihapus'; ?>',
                    showConfirmButton: true,
                    confirmButtonColor: '#28a745',
                    timer: 3000
                });
            <?php endif; ?>
            
            <?php if ($_GET['status'] == 'reset'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Password Direset!',
                    text: '<?php echo isset($_GET['message']) ? $_GET['message'] : 'Password berhasil direset menjadi password123'; ?>',
                    showConfirmButton: true,
                    confirmButtonColor: '#17a2b8',
                    timer: 3000
                });
            <?php endif; ?>
            
            <?php if ($_GET['status'] == 'error'): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: '<?php echo isset($_GET['message']) ? $_GET['message'] : 'Terjadi kesalahan. Silakan coba lagi.'; ?>',
                    showConfirmButton: true,
                    confirmButtonColor: '#dc3545'
                });
            <?php endif; ?>
            
            <?php if ($_GET['status'] == 'username_exists'): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Username Sudah Digunakan',
                    text: '<?php echo isset($_GET['message']) ? $_GET['message'] : 'Username sudah digunakan oleh user lain'; ?>',
                    showConfirmButton: true,
                    confirmButtonColor: '#dc3545'
                });
            <?php endif; ?>
        <?php endif; ?>
    });
    </script>
</body>
</html>
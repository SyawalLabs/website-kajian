<?php
include '../includes/auth.php';
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Handle Hapus User
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus']);
    // Cegah hapus admin utama
    $check = mysqli_query($conn, "SELECT role FROM users WHERE id='$id'");
    $user = mysqli_fetch_assoc($check);
    
    if ($user['role'] != 'admin') {
        mysqli_query($conn, "DELETE FROM users WHERE id='$id'");
    }
    redirect('kelola_user.php');
}

// Handle Reset Password
if (isset($_GET['reset'])) {
    $id = mysqli_real_escape_string($conn, $_GET['reset']);
    $new_password = md5('password123');
    mysqli_query($conn, "UPDATE users SET password='$new_password' WHERE id='$id'");
    redirect('kelola_user.php');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Admin MAKN ENDE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <!-- Navbar (sama persis dengan dashboard) -->
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

        <!-- Header dengan statistik user -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2 class="dashboard-title" style="margin-bottom: 0;">
                <i class="fas fa-users"></i> 
                Daftar User
            </h2>
            
            <?php
            $total_admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='admin'"))['total'];
            $total_pembina = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='pembina'"))['total'];
            ?>
            <div style="display: flex; gap: 15px;">
                <div style="background: #f8f9fa; padding: 10px 20px; border-radius: 50px; font-size: 14px;">
                    <i class="fas fa-user-cog" style="color: #667eea;"></i> 
                    Admin: <strong><?php echo $total_admin; ?></strong>
                </div>
                <div style="background: #f8f9fa; padding: 10px 20px; border-radius: 50px; font-size: 14px;">
                    <i class="fas fa-user-tie" style="color: #28a745;"></i> 
                    Pembina: <strong><?php echo $total_pembina; ?></strong>
                </div>
            </div>
        </div>
        <!-- Tombol Tambah User -->
<div style="margin-bottom: 20px;">
    <button onclick="openTambahModal()" class="btn-tambah">
        <i class="fas fa-plus-circle"></i> Tambah Pembina Baru
    </button>
</div>

<!-- Modal Tambah User -->
<div id="tambahUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Tambah Pembina Baru</h3>
            <span class="close" onclick="closeTambahModal()">&times;</span>
        </div>
        <form method="POST" action="proses_tambah_user.php" onsubmit="return validateForm()">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Nama Lengkap <span class="required">*</span></label>
                <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" 
                       placeholder="Masukkan nama lengkap" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-user-circle"></i> Username <span class="required">*</span></label>
                <input type="text" name="username" id="username" class="form-control" 
                       placeholder="Masukkan username" required>
                <small class="form-text text-muted">Username minimal 3 karakter, hanya huruf dan angka</small>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password <span class="required">*</span></label>
                <div style="position: relative;">
                    <input type="password" name="password" id="password" class="form-control" 
                           placeholder="Masukkan password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('password')" 
                       style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"></i>
                </div>
                <small class="form-text text-muted">Password minimal 6 karakter</small>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Konfirmasi Password <span class="required">*</span></label>
                <div style="position: relative;">
                    <input type="password" name="konfirmasi_password" id="konfirmasi_password" class="form-control" 
                           placeholder="Ulangi password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('konfirmasi_password')" 
                       style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"></i>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="closeTambahModal()" class="btn-batal">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" name="simpan" class="btn-simpan">
                    <i class="fas fa-save"></i> Simpan Pembina
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
                        echo "<td><strong>" . $row['nama_lengkap'] . "</strong></td>";
                        echo "<td>" . $row['username'] . "</td>";
                        echo "<td><span class='role-badge " . $role_class . "'><i class='fas " . $role_icon . "'></i> " . ucfirst($row['role']) . "</span></td>";
                        echo "<td>" . date('d/m/Y H:i', strtotime($row['created_at'])) . " WIB</td>";
                        echo "<td class='action-buttons'>";
                        
                        if ($row['role'] != 'admin') {   
                            // Tombol Hapus
                            echo "<a href='?hapus=" . $row['id'] . "' class='btn-hapus' onclick='return confirm(\"Yakin ingin menghapus user " . $row['nama_lengkap'] . "?\")' title='Hapus User'>";
                            echo "<i class='fas fa-trash'></i> Hapus";
                            echo "</a>";
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

    <!-- CSS untuk memperbaiki tampilan tombol -->
    <style>
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
        
        /* Perbaikan tampilan kolom aksi */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-reset, .btn-hapus {
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
            box-shadow: 0 4px 8px rgba(23, 162, 184, 0.2);
        }
        
        .btn-hapus {
            background: rgb(76, 25, 25);
            color: #dc3545;
            border-color: rgb(128, 42, 42);
        }
        
        .btn-hapus:hover {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
        }
        
        .btn-reset i, .btn-hapus i {
            font-size: 12px;
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
        
        /* Membuat tampilan tabel lebih rapi */
        .table td {
            vertical-align: middle;
            padding: 12px 15px;
        }
        
        .table td.action-buttons {
            min-width: 180px;
        }
        
        /* Hapus style yang tidak diperlukan */
        .btn-reset, .btn-hapus {
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-reset {
            color: #17a2b8;
        }
        
        .btn-reset:hover {
            background: #e8f4fd;
        }
        
        .btn-hapus {
            color: #dc3545;
        }
        
        .btn-hapus:hover {
            background: #fee;
        }
        /* Style untuk tombol tambah */
.btn-tambah {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.btn-tambah:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
}

/* Style Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    overflow-y: auto;
}

.modal-content {
    background-color: #fff;
    margin: 30px auto;
    padding: 0;
    width: 90%;
    max-width: 600px;
    border-radius: 15px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px 25px;
    border-radius: 15px 15px 0 0;
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

/* Style Form */
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
    color: #667eea;
}

.required {
    color: #dc3545;
    margin-left: 3px;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e1e1e1;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-text {
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
    display: block;
}

/* Toggle Password */
.toggle-password {
    color: #6c757d;
    transition: all 0.3s ease;
}

.toggle-password:hover {
    color: #667eea;
}

/* Modal Footer */
.modal-footer {
    padding: 20px 25px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    background: #f8f9fa;
    border-radius: 0 0 15px 15px;
}

.btn-batal, .btn-simpan {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.3s ease;
}

.btn-batal {
    background: #6c757d;
    color: white;
}

.btn-batal:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.btn-simpan {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-simpan:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

/* Alert Messages */
.alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideIn 0.3s ease;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

@keyframes slideIn {
    from {
        transform: translateX(-20px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
    </style>
    <script>
// Modal functions
function openTambahModal() {
    document.getElementById('tambahUserModal').style.display = 'block';
    document.body.style.overflow = 'hidden'; // Mencegah scroll
}

function closeTambahModal() {
    document.getElementById('tambahUserModal').style.display = 'none';
    document.body.style.overflow = 'auto'; // Mengembalikan scroll
}

// Toggle password visibility
function togglePassword(fieldId) {
    var field = document.getElementById(fieldId);
    var icon = event.target;
    
    if (field.type === "password") {
        field.type = "text";
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = "password";
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Validasi form sebelum submit
function validateForm() {
    var nama = document.getElementById('nama_lengkap').value.trim();
    var username = document.getElementById('username').value.trim();
    var password = document.getElementById('password').value;
    var konfirmasi = document.getElementById('konfirmasi_password').value;
    var email = document.getElementById('email').value;
    
    // Validasi nama
    if (nama.length < 3) {
        alert('Nama lengkap minimal 3 karakter');
        return false;
    }
    
    // Validasi username
    var usernameRegex = /^[a-zA-Z0-9]{3,20}$/;
    if (!usernameRegex.test(username)) {
        alert('Username hanya boleh huruf dan angka, minimal 3 karakter');
        return false;
    }
    
    // Validasi password
    if (password.length < 6) {
        alert('Password minimal 6 karakter');
        return false;
    }
    
    if (password !== konfirmasi) {
        alert('Password dan konfirmasi password tidak cocok');
        return false;
    }
    
    // Validasi email jika diisi
    if (email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Format email tidak valid');
            return false;
        }
    }
    
    return true;
}

// Tutup modal jika klik di luar modal
window.onclick = function(event) {
    var modal = document.getElementById('tambahUserModal');
    if (event.target == modal) {
        closeTambahModal();
    }
}

// Tampilkan alert jika ada status dari URL
<?php if (isset($_GET['status'])): ?>
    <?php if ($_GET['status'] == 'success'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            var alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success';
            alertDiv.innerHTML = '<i class="fas fa-check-circle"></i> User baru berhasil ditambahkan!';
            
            var container = document.querySelector('.container');
            container.insertBefore(alertDiv, container.firstChild);
            
            setTimeout(function() {
                alertDiv.style.opacity = '0';
                setTimeout(function() {
                    alertDiv.remove();
                }, 300);
            }, 5000);
        });
    <?php endif; ?>
    
    <?php if ($_GET['status'] == 'error'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            var alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-error';
            alertDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Gagal menambahkan user. Silakan coba lagi.';
            
            var container = document.querySelector('.container');
            container.insertBefore(alertDiv, container.firstChild);
        });
    <?php endif; ?>
<?php endif; ?>
</script>
</body>
</html>
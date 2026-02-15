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
    </style>
</body>
</html>
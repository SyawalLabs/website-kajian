<?php
include '../includes/auth.php';
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Statistik
$total_kajian = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kajian"))['total'];
$total_pembina = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='pembina'"))['total'];
$kajian_minggu_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kajian WHERE WEEK(tanggal) = WEEK(CURDATE())"))['total'];
$kajian_bulan_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kajian WHERE MONTH(tanggal) = MONTH(CURDATE())"))['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - MAKN ENDE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
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
                <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="kelola_kajian.php"><i class="fas fa-calendar-alt"></i> Kelola Kajian</a>
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
            <span>Admin</span>
        </div>

        <!-- Welcome Card -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px; border-radius: var(--border-radius); margin-bottom: 40px; color: white;">
            <div style="display: flex; align-items: center; gap: 30px; flex-wrap: wrap;">
                <div style="flex: 1;">
                    <h2 style="font-size: 2rem; margin-bottom: 10px; font-family: 'Amiri', serif;">
                        <i class="fas fa-hand-sparkles"></i> 
                        Assalamu'alaikum, <?php echo $_SESSION['nama_lengkap']; ?>
                    </h2>
                    <p style="opacity: 0.9; font-size: 1.1rem;">Selamat datang di panel admin MAKN ENDE. Kelola jadwal kajian dan user dengan mudah.</p>
                </div>
                <div style="text-align: center;">
                    <div style="background: rgba(255,255,255,0.2); padding: 20px; border-radius: 50%; width: 100px; height: 100px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-mosque" style="font-size: 40px;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <h2 class="dashboard-title">
            <i class="fas fa-chart-pie"></i> 
            Statistik Kajian
        </h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h3>Total Kajian</h3>
                <p class="stat-number"><?php echo $total_kajian; ?></p>
                <small style="color: #666;">Semua waktu</small>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Total Pembina</h3>
                <p class="stat-number"><?php echo $total_pembina; ?></p>
                <small style="color: #666;">User aktif</small>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-week"></i>
                </div>
                <h3>Kajian Minggu Ini</h3>
                <p class="stat-number"><?php echo $kajian_minggu_ini; ?></p>
                <small style="color: #666;">7 hari ke depan</small>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3>Kajian Bulan Ini</h3>
                <p class="stat-number"><?php echo $kajian_bulan_ini; ?></p>
                <small style="color: #666;"><?php echo date('F Y'); ?></small>
            </div>
        </div>

        <!-- Recent Kajian -->
        <div style="margin-top: 50px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 1.5rem; color: var(--primary-color);">
                    <i class="fas fa-history"></i> 
                    Kajian Terbaru
                </h3>
                <a href="kelola_kajian.php" class="btn btn-primary" style="padding: 10px 20px;">
                    <i class="fas fa-plus"></i> Tambah Kajian
                </a>
            </div>
            
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Judul</th>
                            <th>Pemateri</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Tempat</th>
                            <th>Pembuat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT k.*, u.nama_lengkap as pembuat 
                                 FROM kajian k 
                                 LEFT JOIN users u ON k.created_by = u.id 
                                 ORDER BY k.created_at DESC LIMIT 5";
                        $result = mysqli_query($conn, $query);
                        
                        if (mysqli_num_rows($result) > 0) {
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . $no++ . "</td>";
                                echo "<td><strong>" . $row['judul'] . "</strong></td>";
                                echo "<td>" . $row['pemateri'] . "</td>";
                                echo "<td>" . date('d/m/Y', strtotime($row['tanggal'])) . "</td>";
                                echo "<td>" . $row['waktu'] . " WIB</td>";
                                echo "<td>" . $row['tempat'] . "</td>";
                                echo "<td>" . ($row['pembuat'] ?? '<em>Tidak diketahui</em>') . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align: center; padding: 40px;'>Belum ada data kajian</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
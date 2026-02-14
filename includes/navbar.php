<!-- <?php
include '../includes/auth.php';
include '../includes/db_connection.php'; // Jika belum include

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
    <!-- Panggil navbar -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span>Admin</span>
        </div>

        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="welcome-content">
                <h2>
                    <i class="fas fa-hand-sparkles"></i> 
                    Assalamu'alaikum, <?php echo $_SESSION['nama_lengkap']; ?>
                </h2>
                <p>Selamat datang di panel admin MAKN ENDE. Kelola jadwal kajian dan user dengan mudah.</p>
            </div>
            <div class="welcome-icon">
                <i class="fas fa-mosque"></i>
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
                <small>Semua waktu</small>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Total Pembina</h3>
                <p class="stat-number"><?php echo $total_pembina; ?></p>
                <small>User aktif</small>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-week"></i>
                </div>
                <h3>Kajian Minggu Ini</h3>
                <p class="stat-number"><?php echo $kajian_minggu_ini; ?></p>
                <small>7 hari ke depan</small>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3>Kajian Bulan Ini</h3>
                <p class="stat-number"><?php echo $kajian_bulan_ini; ?></p>
                <small><?php echo date('F Y'); ?></small>
            </div>
        </div>

        <!-- Recent Kajian -->
        <div class="recent-kajian-section">
            <div class="section-header">
                <h3>
                    <i class="fas fa-history"></i> 
                    Kajian Terbaru
                </h3>
                <a href="kelola_kajian.php" class="btn btn-primary">
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
                            echo "<tr><td colspan='7' class='empty-table'>Belum ada data kajian</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="kelola_kajian.php" class="quick-action-card">
                <div class="quick-action-icon" style="background: var(--gradient-1);">
                    <i class="fas fa-plus"></i>
                </div>
                <div class="quick-action-text">
                    <strong>Tambah Kajian</strong>
                    <small>Buat jadwal baru</small>
                </div>
            </a>
            
            <a href="kelola_user.php" class="quick-action-card">
                <div class="quick-action-icon" style="background: var(--accent-color);">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="quick-action-text">
                    <strong>Kelola User</strong>
                    <small>Tambah/Edit Pembina</small>
                </div>
            </a>
            
            <a href="../index.php" target="_blank" class="quick-action-card">
                <div class="quick-action-icon" style="background: #17a2b8;">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="quick-action-text">
                    <strong>Lihat Website</strong>
                    <small>Halaman publik</small>
                </div>
            </a>
        </div>
    </div>
</body>
</html> -->
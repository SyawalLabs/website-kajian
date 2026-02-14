<?php
include '../includes/auth.php';
if (!isLoggedIn() || !isPembina()) {
    redirect('../login.php');
}

// Ambil kajian yang dibuat oleh pembina ini
$query = "SELECT * FROM kajian WHERE created_by='{$_SESSION['user_id']}' ORDER BY tanggal DESC";
$kajian_saya = mysqli_query($conn, $query);
$total_kajian = mysqli_num_rows($kajian_saya);

// Ambil semua kajian (untuk info tambahan)
$query_all = "SELECT COUNT(*) as total FROM kajian";
$result_all = mysqli_query($conn, $query_all);
$total_all_kajian = mysqli_fetch_assoc($result_all)['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pembina - Jadwal Kajian</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>Jadwal Kajian - Pembina</h1>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="kelola_kajian.php">Kelola Kajian</a></li>
                <li><a href="../logout.php">Logout (<?php echo $_SESSION['nama_lengkap']; ?>)</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h2>Dashboard Pembina</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Kajian Saya</h3>
                <p class="stat-number"><?php echo $total_kajian; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Semua Kajian</h3>
                <p class="stat-number"><?php echo $total_all_kajian; ?></p>
            </div>
        </div>

        <div class="recent-kajian">
            <h3>Kajian Saya</h3>
            <?php if ($total_kajian > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Judul</th>
                            <th>Pemateri</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Tempat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($kajian_saya)): 
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $row['judul']; ?></td>
                            <td><?php echo $row['pemateri']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                            <td><?php echo $row['waktu']; ?></td>
                            <td><?php echo $row['tempat']; ?></td>
                            <td>
                                <a href="kelola_kajian.php?edit=<?php echo $row['id']; ?>" class="btn-edit">Edit</a>
                                <a href="kelola_kajian.php?hapus=<?php echo $row['id']; ?>" class="btn-hapus" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>Anda belum membuat kajian. <a href="kelola_kajian.php">Buat kajian sekarang</a></p>
                    
                    <h4 style="margin-top: 20px;">Semua Jadwal Kajian:</h4>
                    <?php
                    // Tampilkan semua kajian yang ada
                    $all_kajian = mysqli_query($conn, "SELECT * FROM kajian ORDER BY tanggal DESC LIMIT 5");
                    if (mysqli_num_rows($all_kajian) > 0) {
                        echo "<table class='table'>";
                        echo "<thead><tr><th>Judul</th><th>Pemateri</th><th>Tanggal</th><th>Tempat</th></tr></thead>";
                        echo "<tbody>";
                        while ($kajian = mysqli_fetch_assoc($all_kajian)) {
                            echo "<tr>";
                            echo "<td>" . $kajian['judul'] . "</td>";
                            echo "<td>" . $kajian['pemateri'] . "</td>";
                            echo "<td>" . date('d/m/Y', strtotime($kajian['tanggal'])) . "</td>";
                            echo "<td>" . $kajian['tempat'] . "</td>";
                            echo "</tr>";
                        }
                        echo "</tbody></table>";
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
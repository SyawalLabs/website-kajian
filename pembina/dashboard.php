<?php
include '../includes/auth.php';
if (!isLoggedIn() || !isPembina()) {
    redirect('../login.php');
}

// Ambil kajian yang dibuat oleh pembina ini - menggunakan prepared statement
$stmt = mysqli_prepare($conn, "SELECT * FROM kajian WHERE created_by = ? ORDER BY tanggal DESC");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$kajian_saya = mysqli_stmt_get_result($stmt);
$total_kajian = mysqli_num_rows($kajian_saya);

// Reset pointer untuk digunakan lagi nanti
mysqli_data_seek($kajian_saya, 0);

// Ambil semua kajian (untuk info tambahan)
$query_all = "SELECT COUNT(*) as total FROM kajian";
$result_all = mysqli_query($conn, $query_all);
if ($result_all) {
    $total_all_kajian = mysqli_fetch_assoc($result_all)['total'];
} else {
    $total_all_kajian = 0;
}

// Ambil nama pembina dari session
$nama_pembina = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pembina';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pembina - Jadwal Kajian</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 1.1em;
            opacity: 0.9;
        }
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin: 0;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        .table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .btn-edit, .btn-hapus {
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
        }
        .btn-edit {
            background: #28a745;
            color: white;
            margin-right: 5px;
        }
        .btn-hapus {
            background: #dc3545;
            color: white;
        }
        .alert {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .navbar {
            background: #343a40;
            color: white;
            padding: 15px 0;
        }
        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
        }
        .navbar ul li {
            margin-left: 20px;
        }
        .navbar ul li a {
            color: white;
            text-decoration: none;
        }
        .navbar ul li a:hover {
            color: #007bff;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        h2 {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>Jadwal Kajian - Pembina</h1>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="kelola_kajian.php">Kelola Kajian</a></li>
                <li><a href="../logout.php">Logout (<?php echo htmlspecialchars($nama_pembina); ?>)</a></li>
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
                            <td><?php echo htmlspecialchars($row['judul']); ?></td>
                            <td><?php echo htmlspecialchars($row['pemateri']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                            <td><?php echo htmlspecialchars($row['waktu']); ?></td>
                            <td><?php echo htmlspecialchars($row['tempat']); ?></td>
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
                    if ($all_kajian && mysqli_num_rows($all_kajian) > 0) {
                        echo "<table class='table'>";
                        echo "<thead><tr><th>Judul</th><th>Pemateri</th><th>Tanggal</th><th>Tempat</th></tr></thead>";
                        echo "<tbody>";
                        while ($kajian = mysqli_fetch_assoc($all_kajian)) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($kajian['judul']) . "</td>";
                            echo "<td>" . htmlspecialchars($kajian['pemateri']) . "</td>";
                            echo "<td>" . date('d/m/Y', strtotime($kajian['tanggal'])) . "</td>";
                            echo "<td>" . htmlspecialchars($kajian['tempat']) . "</td>";
                            echo "</tr>";
                        }
                        echo "</tbody></table>";
                    } else {
                        echo "<p>Tidak ada jadwal kajian.</p>";
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
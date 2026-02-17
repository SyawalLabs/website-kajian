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

// Ambil semua kajian untuk ditampilkan di tabel
$query_semua_kajian = "SELECT k.*, u.nama_lengkap as pembuat 
                        FROM kajian k 
                        LEFT JOIN users u ON k.created_by = u.id 
                        ORDER BY k.tanggal DESC";
$semua_kajian = mysqli_query($conn, $query_semua_kajian);

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
        .table-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        .table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .table tbody tr:hover {
            background-color: #f5f5f5;
        }
        .btn-edit, .btn-hapus {
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
            display: inline-block;
            margin: 0 2px;
        }
        .btn-edit {
            background: #28a745;
            color: white;
        }
        .btn-edit:hover {
            background: #218838;
        }
        .btn-hapus {
            background: #dc3545;
            color: white;
        }
        .btn-hapus:hover {
            background: #c82333;
        }
        .btn-view {
            background: #17a2b8;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
            display: inline-block;
        }
        .btn-view:hover {
            background: #138496;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .badge-milik-saya {
            background: #28a745;
            color: white;
        }
        .badge-bukan-milik {
            background: #6c757d;
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
        .navbar-brand {
            display: flex;
            align-items: center;
        }
        .navbar-brand h1 {
            margin: 0;
            font-size: 1.5rem;
            color: white;
        }
        .navbar-brand span {
            color: #007bff;
            font-size: 0.9rem;
            display: block;
        }
        .navbar-menu {
            display: flex;
            gap: 20px;
        }
        .navbar-menu a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .navbar-menu a:hover, .navbar-menu a.active {
            background: #007bff;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        h2 {
            margin-top: 30px;
        }
        .filter-section {
            margin: 20px 0;
            display: flex;
            gap: 10px;
        }
        .filter-btn {
            padding: 8px 15px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .filter-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        .filter-btn:hover {
            background: #e9ecef;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <div>
                    <h1>MAKN ENDE <span>Panel Pembina</span></h1>
                </div>
            </div>
            <div class="navbar-menu">
                <a href="../pembina/dashboard.php" class="active">Dashboard</a>
                <a href="../pembina/kelola_kajian.php">Kelola Kajian</a>
                <a href="../pembina/kelola_santri.php">Kelola Santri</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2>Dashboard Pembina</h2>
        <p>Selamat datang, <strong><?php echo htmlspecialchars($nama_pembina); ?></strong>!</p>
        
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

        <div class="table-container">
            <h3>Semua Jadwal Kajian</h3>
            
            <?php if ($total_all_kajian > 0): ?>
                <div class="filter-section">
                    <button class="filter-btn active" onclick="filterTable('semua')">Semua Kajian</button>
                    <button class="filter-btn" onclick="filterTable('saya')">Kajian Saya</button>
                </div>

                <table class="table" id="kajianTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Judul Kajian</th>
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
                        $no = 1;
                        if ($semua_kajian && mysqli_num_rows($semua_kajian) > 0):
                            while ($row = mysqli_fetch_assoc($semua_kajian)): 
                                $is_milik_saya = ($row['created_by'] == $_SESSION['user_id']);
                        ?>
                        <tr class="<?php echo $is_milik_saya ? 'kajian-saya' : 'kajian-lain'; ?>">
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($row['judul']); ?></td>
                            <td><?php echo htmlspecialchars($row['pemateri']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                            <td><?php echo htmlspecialchars($row['waktu']); ?></td>
                            <td><?php echo htmlspecialchars($row['tempat']); ?></td>
                            <td><?php echo htmlspecialchars($row['pembuat'] ?? 'Tidak diketahui'); ?></td>
                            <td>
                                <?php if ($is_milik_saya): ?>
                                    <span class="badge badge-milik-saya">Kajian Saya</span>
                                <?php else: ?>
                                    <span class="badge badge-bukan-milik">Kajian Lain</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($is_milik_saya): ?>
                                    <a href="kelola_kajian.php?edit=<?php echo $row['id']; ?>" class="btn-edit">Edit</a>
                                    <a href="kelola_kajian.php?hapus=<?php echo $row['id']; ?>" class="btn-hapus" onclick="return confirm('Yakin ingin menghapus kajian ini?')">Hapus</a>
                                <?php else: ?>
                                    <a href="#" class="btn-view" onclick="alert('Anda hanya dapat melihat detail kajian ini')">Lihat</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="9" style="text-align: center;">Tidak ada jadwal kajian</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>Belum ada jadwal kajian yang dibuat. <a href="kelola_kajian.php">Buat kajian sekarang</a></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Kajian Saya Section (Terpisah) -->
        <?php if ($total_kajian > 0): ?>
        <div class="table-container" style="margin-top: 30px;">
            <h3>Kajian Saya (Yang Saya Buat)</h3>
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
                    $no_saya = 1;
                    mysqli_data_seek($kajian_saya, 0); // Reset pointer
                    while ($row = mysqli_fetch_assoc($kajian_saya)): 
                    ?>
                    <tr>
                        <td><?php echo $no_saya++; ?></td>
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
        </div>
        <?php endif; ?>
    </div>

    <script>
        function filterTable(filter) {
            var rows = document.querySelectorAll('#kajianTable tbody tr');
            var buttons = document.querySelectorAll('.filter-btn');
            
            // Update active button
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter rows
            rows.forEach(row => {
                if (filter === 'semua') {
                    row.style.display = '';
                } else if (filter === 'saya') {
                    if (row.classList.contains('kajian-saya')) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        }
    </script>
</body>
</html>
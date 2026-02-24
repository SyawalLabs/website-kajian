<?php
include '../includes/auth.php';
if (!isLoggedIn() || !isPembina()) {
    redirect('../login.php');
}

// Ambil ID pembina yang sedang login
$pembina_id = $_SESSION['user_id'];
$nama_pembina = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pembina';

// Query untuk mengambil SEMUA kajian dengan informasi pembuat
$query_semua_kajian = "SELECT k.*, 
                       u.nama_lengkap as nama_pembuat
                       FROM kajian k 
                       LEFT JOIN users u ON k.created_by = u.id 
                       ORDER BY k.tanggal DESC, k.waktu ASC";

$semua_kajian = mysqli_query($conn, $query_semua_kajian);

if (!$semua_kajian) {
    die("Error query: " . mysqli_error($conn));
}

$total_semua_kajian = mysqli_num_rows($semua_kajian);

// Hitung statistik
$akan_datang_count = 0;
$sudah_lewat_count = 0;

// Reset pointer untuk menghitung
mysqli_data_seek($semua_kajian, 0);
$tanggal_sekarang = date('Y-m-d');

while ($row = mysqli_fetch_assoc($semua_kajian)) {
    // Hitung status berdasarkan tanggal
    if ($row['tanggal'] >= $tanggal_sekarang) {
        $akan_datang_count++;
    } else {
        $sudah_lewat_count++;
    }
}

// Reset pointer lagi untuk ditampilkan
mysqli_data_seek($semua_kajian, 0);

$statistik = [
    'total_semua' => $total_semua_kajian,
    'akan_datang' => $akan_datang_count,
    'sudah_lewat' => $sudah_lewat_count
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pembina - Semua Jadwal Kajian</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
        }

        .navbar {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .navbar-brand h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .navbar-brand span {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .navbar-menu {
            display: flex;
            gap: 20px;
        }

        .navbar-menu a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .navbar-menu a:hover, .navbar-menu a.active {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .welcome-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .welcome-section h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .welcome-section p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.primary { border-top: 4px solid #3498db; }
        .stat-card.success { border-top: 4px solid #2ecc71; }
        .stat-card.info { border-top: 4px solid #f39c12; }
        .stat-card.warning { border-top: 4px solid #e74c3c; }

        .stat-card h3 {
            color: #7f8c8d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #95a5a6;
            font-size: 0.9rem;
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .filter-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            flex: 1;
        }

        .filter-btn {
            padding: 10px 20px;
            border: none;
            background: #ecf0f1;
            color: #7f8c8d;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }

        .filter-btn:hover {
            background: #bdc3c7;
        }

        .filter-btn.active {
            background: #3498db;
            color: white;
        }

        .search-box {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            min-width: 250px;
        }

        .search-box:focus {
            outline: none;
            border-color: #3498db;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow-x: auto;
        }

        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title h3 {
            color: #2c3e50;
            font-size: 1.3rem;
        }

        .btn-tambah {
            background: #2ecc71;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }

        .btn-tambah:hover {
            background: #27ae60;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
            position: sticky;
            top: 0;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            color: #34495e;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }

        .badge-akan-datang {
            background: #f39c12;
            color: white;
        }

        .badge-selesai {
            background: #95a5a6;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn-edit, .btn-hapus, .btn-view {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-edit {
            background: #f39c12;
            color: white;
        }

        .btn-hapus {
            background: #e74c3c;
            color: white;
        }

        .btn-view {
            background: #3498db;
            color: white;
        }

        .btn-edit:hover, .btn-hapus:hover, .btn-view:hover {
            opacity: 0.8;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 50px;
            color: #7f8c8d;
        }

        .empty-state p {
            margin-bottom: 20px;
            font-size: 1.1rem;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #bee5eb;
        }

        .filter-indicator {
            background: #e8f8f5;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .reset-filter {
            color: #e74c3c;
            cursor: pointer;
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .navbar .container {
                flex-direction: column;
                gap: 15px;
            }
            
            .navbar-menu {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-section {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <h1>MAKN ENDE</h1>
                <span>Panel Pembina - <?php echo htmlspecialchars($nama_pembina); ?></span>
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
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h2>Dashboard Pembina</h2>
            <p>Selamat datang, <strong><?php echo htmlspecialchars($nama_pembina); ?></strong>! Berikut adalah semua jadwal kajian yang tersedia.</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <h3>Total Semua Kajian</h3>
                <div class="stat-number"><?php echo $statistik['total_semua']; ?></div>
                <div class="stat-label">Seluruh jadwal kajian</div>
            </div>
            <div class="stat-card info">
                <h3>Akan Datang</h3>
                <div class="stat-number"><?php echo $statistik['akan_datang']; ?></div>
                <div class="stat-label">Kajian mendatang</div>
            </div>
            <div class="stat-card warning">
                <h3>Sudah Lewat</h3>
                <div class="stat-number"><?php echo $statistik['sudah_lewat']; ?></div>
                <div class="stat-label">Kajian yang sudah selesai</div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="table-container">
            <div class="section-title">
                <h3>📋 Semua Jadwal Kajian</h3>
                <a href="kelola_kajian.php" class="btn-tambah">+ Tambah Kajian Baru</a>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-group">
                    <button class="filter-btn active" onclick="filterTable('semua')">📋 Semua Kajian</button>
                    <button class="filter-btn" onclick="filterTable('akan_datang')">📅 Akan Datang</button>
                    <button class="filter-btn" onclick="filterTable('selesai')">✅ Sudah Selesai</button>
                </div>
                <input type="text" class="search-box" id="searchInput" placeholder="🔍 Cari judul, pemateri, atau tempat..." onkeyup="searchTable()">
            </div>

            <!-- Active Filter Indicator -->
            <div id="filterIndicator" class="filter-indicator" style="display: none;">
                <span>🔍 Menampilkan: <span id="activeFilterText">Semua Kajian</span></span>
                <span class="reset-filter" onclick="resetFilter()">✖ Reset Filter</span>
            </div>

            <?php if ($total_semua_kajian > 0): ?>
                <table id="kajianTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Judul Kajian</th>
                            <th>Pemateri</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Tempat</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($semua_kajian)): 
                            $status = ($row['tanggal'] >= date('Y-m-d')) ? 'akan_datang' : 'selesai';
                        ?>
                        <tr data-status="<?php echo $status; ?>">
                            <td><?php echo $no++; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['judul']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($row['pemateri']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                            <td><?php echo htmlspecialchars($row['waktu']); ?></td>
                            <td><?php echo htmlspecialchars($row['tempat']); ?></td>
                            <td>
                                <?php if ($status == 'akan_datang'): ?>
                                    <span class="badge badge-akan-datang">Akan Datang</span>
                                <?php else: ?>
                                    <span class="badge badge-selesai">Selesai</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="detail_kajian.php?id=<?php echo $row['id']; ?>" class="btn-view" title="Lihat Detail">👁️ Detail</a>
                                    <?php if ($row['created_by'] == $pembina_id): ?>
                                        <a href="kelola_kajian.php?edit=<?php echo $row['id']; ?>" class="btn-edit" title="Edit">✏️ Edit</a>
                                        <a href="kelola_kajian.php?hapus=<?php echo $row['id']; ?>" class="btn-hapus" title="Hapus" onclick="return confirm('Yakin ingin menghapus kajian ini?')">🗑️ Hapus</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>✨ Belum ada jadwal kajian</p>
                    <p>Mulai dengan membuat kajian baru</p>
                    <a href="kelola_kajian.php" class="btn-tambah" style="display: inline-block;">+ Buat Kajian Baru</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Informasi Tambahan -->
        <div class="alert-info" style="margin-top: 20px;">
            <strong>📌 Informasi:</strong>
            <ul style="margin-top: 10px; margin-left: 20px;">
                <li>Halaman ini menampilkan <strong>SEMUA kajian</strong> dari semua pembina</li>
                <li>Anda hanya dapat mengedit/menghapus kajian yang Anda buat sendiri (akan muncul tombol Edit dan Hapus)</li>
                <li>Gunakan filter untuk memisahkan kajian yang akan datang dan yang sudah selesai</li>
                <li>Gunakan kotak pencarian untuk mencari judul, pemateri, atau tempat</li>
            </ul>
        </div>
    </div>

    <script>
        let currentFilter = 'semua';
        
        function filterTable(filter) {
            var rows = document.querySelectorAll('#kajianTable tbody tr');
            var buttons = document.querySelectorAll('.filter-btn');
            var filterIndicator = document.getElementById('filterIndicator');
            var activeFilterText = document.getElementById('activeFilterText');
            
            currentFilter = filter;
            
            // Update active button
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Show filter indicator
            filterIndicator.style.display = 'flex';
            
            // Update filter text
            if (filter === 'semua') {
                activeFilterText.textContent = 'Semua Kajian';
                filterIndicator.style.display = 'none';
            } else if (filter === 'akan_datang') {
                activeFilterText.textContent = 'Kajian Akan Datang';
            } else if (filter === 'selesai') {
                activeFilterText.textContent = 'Kajian Sudah Selesai';
            }
            
            // Filter rows
            rows.forEach(row => {
                if (filter === 'semua') {
                    row.style.display = '';
                } else if (filter === 'akan_datang') {
                    row.style.display = row.dataset.status === 'akan_datang' ? '' : 'none';
                } else if (filter === 'selesai') {
                    row.style.display = row.dataset.status === 'selesai' ? '' : 'none';
                }
            });
            
            // Apply search filter again if there's search text
            var searchInput = document.getElementById('searchInput');
            if (searchInput.value) {
                searchTable();
            }
        }

        function searchTable() {
            var input = document.getElementById('searchInput');
            var filter = input.value.toLowerCase();
            var rows = document.querySelectorAll('#kajianTable tbody tr');
            
            rows.forEach(row => {
                // Skip if row is hidden by filter
                if (row.style.display === 'none') return;
                
                var judul = row.cells[1].textContent.toLowerCase();
                var pemateri = row.cells[2].textContent.toLowerCase();
                var tempat = row.cells[5].textContent.toLowerCase();
                
                if (judul.includes(filter) || pemateri.includes(filter) || tempat.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function resetFilter() {
            // Reset to show all
            var buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(btn => {
                if (btn.textContent.includes('Semua Kajian')) {
                    btn.click();
                }
            });
            
            // Clear search
            document.getElementById('searchInput').value = '';
            
            // Show all rows
            var rows = document.querySelectorAll('#kajianTable tbody tr');
            rows.forEach(row => row.style.display = '');
        }

        // Add search input event listener for real-time search
        document.getElementById('searchInput').addEventListener('keyup', function() {
            searchTable();
        });

        // Initialize filter indicator
        document.addEventListener('DOMContentLoaded', function() {
            // Set first button as active
            var firstButton = document.querySelector('.filter-btn');
            if (firstButton) {
                firstButton.classList.add('active');
            }
        });
    </script>
</body>
</html>
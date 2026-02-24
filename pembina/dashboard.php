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
    <title>Dashboard Pembina - MAKN ENDE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        /* Custom styles untuk pembina */
        .welcome-card {
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
            padding: 40px;
            border-radius: var(--border-radius);
            margin-bottom: 40px;
            color: white;
        }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            box-shadow: var(--box-shadow);
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
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .filter-btn:hover {
            background: #bdc3c7;
        }
        
        .filter-btn.active {
            background: var(--primary-color);
            color: white;
        }
        
        .search-box {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            min-width: 250px;
        }
        
        .search-box:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .filter-indicator {
            background: #e8f8f5;
            padding: 10px 15px;
            border-radius: var(--border-radius);
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
        
        .badge-akan-datang {
            background: #f39c12;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .badge-selesai {
            background: #95a5a6;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .table td .keterangan {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .info-card {
            background: #d1ecf1;
            color: #0c5460;
            padding: 20px;
            border-radius: var(--border-radius);
            margin: 20px 0;
            border: 1px solid #bee5eb;
        }
        
        .info-card i {
            margin-right: 10px;
        }
        
        .info-card ul {
            margin-top: 10px;
            margin-left: 30px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <div class="navbar-logo">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div>
                    <h1>MAKN ENDE <span>Panel Pembina</span></h1>
                </div>
            </div>
            <div class="navbar-menu">
                <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="kelola_kajian.php"><i class="fas fa-calendar-alt"></i> Kelola Kajian</a>
                <a href="kelola_santri.php"><i class="fas fa-users"></i> Kelola Santri</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span>Pembina</span>
        </div>

        <!-- Welcome Card -->
        <div class="welcome-card">
            <div style="display: flex; align-items: center; gap: 30px; flex-wrap: wrap;">
                <div style="flex: 1;">
                    <h2 style="font-size: 2rem; margin-bottom: 10px; font-family: 'Amiri', serif;">
                        <i class="fas fa-hand-sparkles"></i> 
                        Assalamu'alaikum, <?php echo htmlspecialchars($nama_pembina); ?>
                    </h2>
                    <p style="opacity: 0.9; font-size: 1.1rem;">Selamat datang di panel pembina MAKN ENDE. Kelola jadwal kajian dengan mudah.</p>
                </div>
                <div style="text-align: center;">
                    <div style="background: rgba(255,255,255,0.2); padding: 20px; border-radius: 50%; width: 100px; height: 100px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-book-open" style="font-size: 40px;"></i>
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
                <h3>Total Semua Kajian</h3>
                <p class="stat-number"><?php echo $statistik['total_semua']; ?></p>
                <small style="color: #666;">Seluruh jadwal</small>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Akan Datang</h3>
                <p class="stat-number"><?php echo $statistik['akan_datang']; ?></p>
                <small style="color: #666;">Kajian mendatang</small>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Sudah Lewat</h3>
                <p class="stat-number"><?php echo $statistik['sudah_lewat']; ?></p>
                <small style="color: #666;">Kajian selesai</small>
            </div>
        </div>

        <!-- Main Content -->
        <div style="margin-top: 50px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 1.5rem; color: var(--primary-color);">
                    <i class="fas fa-calendar-alt"></i> 
                    Semua Jadwal Kajian
                </h3>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-group">
                    <button class="filter-btn active" onclick="filterTable('semua')">
                        <i class="fas fa-list"></i> Semua Kajian
                    </button>
                    <button class="filter-btn" onclick="filterTable('akan_datang')">
                        <i class="fas fa-clock"></i> Akan Datang
                    </button>
                    <button class="filter-btn" onclick="filterTable('selesai')">
                        <i class="fas fa-check-circle"></i> Sudah Selesai
                    </button>
                </div>
                <input type="text" class="search-box" id="searchInput" 
                       placeholder="🔍 Cari judul, pemateri, atau tempat..." onkeyup="searchTable()">
            </div>

            <!-- Active Filter Indicator -->
            <div id="filterIndicator" class="filter-indicator" style="display: none;">
                <span>
                    <i class="fas fa-filter"></i> 
                    Menampilkan: <span id="activeFilterText">Semua Kajian</span>
                </span>
                <span class="reset-filter" onclick="resetFilter()">
                    <i class="fas fa-times"></i> Reset Filter
                </span>
            </div>

            <!-- Table -->
            <div class="table-container">
                <?php if ($total_semua_kajian > 0): ?>
                    <table class="table" id="kajianTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Judul Kajian</th>
                                <th>Pemateri</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Tempat</th>
                                <th>Status</th>
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
                                    <div class="keterangan">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($row['nama_pembuat']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['pemateri']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                <td><?php echo htmlspecialchars($row['waktu']); ?> WIB</td>
                                <td><?php echo htmlspecialchars($row['tempat']); ?></td>
                                <td>
                                    <?php if ($status == 'akan_datang'): ?>
                                        <span class="badge badge-akan-datang">
                                            <i class="fas fa-clock"></i> Akan Datang
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-selesai">
                                            <i class="fas fa-check"></i> Selesai
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px;">
                        <i class="fas fa-calendar-times" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                        <p style="color: #7f8c8d; font-size: 1.1rem;">✨ Belum ada jadwal kajian</p>
                        <a href="kelola_kajian.php?action=tambah" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-plus"></i> Tambah Kajian Pertama
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Informasi Tambahan -->
            <div class="info-card">
                <strong><i class="fas fa-info-circle"></i> Informasi:</strong>
                <ul>
                    <li>Halaman ini menampilkan <strong>SEMUA kajian</strong> dari semua pembina</li>
                    <li>Gunakan filter untuk memisahkan kajian yang akan datang dan yang sudah selesai</li>
                    <li>Gunakan kotak pencarian untuk mencari judul, pemateri, atau tempat</li>
                    <li>Untuk mengelola kajian, silakan kunjungi menu <strong>Kelola Kajian</strong></li>
                </ul>
            </div>
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
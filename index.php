<?php
include 'config/database.php';
session_start();

// Ambil semua kajian
$query = "SELECT k.*, u.nama_lengkap as pembuat 
          FROM kajian k 
          LEFT JOIN users u ON k.created_by = u.id 
          ORDER BY k.tanggal DESC, k.waktu ASC";
$result = mysqli_query($conn, $query);

// Hitung statistik
$total_kajian = mysqli_num_rows($result);
$kajian_bulan_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kajian WHERE MONTH(tanggal) = MONTH(CURDATE())"))['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAKN ENDE - Jadwal Kajian Islami</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <div class="navbar-logo">
                    <i class="fas fa-mosque"></i>
                </div>
                <div>
                    <h1>MAKN ENDE <span>Madrasah Aliyah Kejuruan Negeri</span></h1>
                </div>
            </div>
            <div class="navbar-menu">
                <a href="index.php" class="active"><i class="fas fa-home"></i> Beranda</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo $_SESSION['role']; ?>/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <span class="hero-badge"><i class="fas fa-calendar-alt"></i> Jadwal Kajian 1447 H</span>
                <h2>Selamat Datang di <br>MAKN ENDE</h2>
                <p>Tempat menuntut ilmu, membina akhlak, dan mendekatkan diri kepada Allah SWT. Ikuti kajian rutin kami untuk memperdalam pemahaman agama Islam.</p>
                
                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <div style="background: rgba(255,255,255,0.2); padding: 15px 25px; border-radius: 50px; text-align: center;">
                        <i class="fas fa-book-open" style="font-size: 24px; margin-bottom: 5px;"></i>
                        <div style="font-size: 20px; font-weight: bold;"><?php echo $total_kajian; ?></div>
                        <div style="font-size: 12px; opacity: 0.9;">Total Kajian</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); padding: 15px 25px; border-radius: 50px; text-align: center;">
                        <i class="fas fa-users" style="font-size: 24px; margin-bottom: 5px;"></i>
                        <div style="font-size: 20px; font-weight: bold;"><?php echo $kajian_bulan_ini; ?></div>
                        <div style="font-size: 12px; opacity: 0.9;">Kajian Bulan Ini</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Beranda</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span>Jadwal Kajian</span>
        </div>

        <h2 class="dashboard-title">
            <i class="fas fa-calendar-week"></i> 
            Jadwal Kajian Terbaru
        </h2>
        
        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'pembina'): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Informasi:</strong> Anda hanya bisa mengelola kajian yang Anda buat sendiri di dashboard. 
                Kajian dari admin atau pembina lain tidak akan muncul di dashboard pribadi Anda.
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Search Bar -->
        <div style="margin: 30px 0;">
            <div style="display: flex; gap: 10px; max-width: 500px;">
                <div style="flex: 1; position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999;"></i>
                    <input type="text" id="searchKajian" placeholder="Cari kajian..." style="width: 100%; padding: 15px 15px 15px 45px; border: 2px solid #e0e0e0; border-radius: 50px; font-size: 16px;">
                </div>
                <select id="filterBulan" style="padding: 15px 25px; border: 2px solid #e0e0e0; border-radius: 50px; background: white;">
                    <option value="">Semua Bulan</option>
                    <option value="01">Januari</option>
                    <option value="02">Februari</option>
                    <option value="03">Maret</option>
                    <option value="04">April</option>
                    <option value="05">Mei</option>
                    <option value="06">Juni</option>
                    <option value="07">Juli</option>
                    <option value="08">Agustus</option>
                    <option value="09">September</option>
                    <option value="10">Oktober</option>
                    <option value="11">November</option>
                    <option value="12">Desember</option>
                </select>
            </div>
        </div>
        
        <div class="kajian-grid" id="kajianGrid">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): 
                    $tanggal = strtotime($row['tanggal']);
                    $hari = date('l', $tanggal);
                    $hari_indonesia = [
                        'Monday' => 'Senin',
                        'Tuesday' => 'Selasa',
                        'Wednesday' => 'Rabu',
                        'Thursday' => 'Kamis',
                        'Friday' => 'Jumat',
                        'Saturday' => 'Sabtu',
                        'Sunday' => 'Minggu'
                    ];
                ?>
                    <div class="kajian-card" data-tanggal="<?php echo date('m', $tanggal); ?>">
                        <div class="kajian-header">
                            <h3><?php echo $row['judul']; ?></h3>
                            <div class="kajian-date">
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo $hari_indonesia[$hari] . ', ' . date('d F Y', $tanggal); ?>
                            </div>
                        </div>
                        <div class="kajian-body">
                            <div class="kajian-info">
                                <p>
                                    <i class="fas fa-user-tie"></i>
                                    <strong>Pemateri:</strong> <?php echo $row['pemateri']; ?>
                                </p>
                                <p>
                                    <i class="fas fa-clock"></i>
                                    <strong>Waktu:</strong> <?php echo date('H:i', strtotime($row['waktu'])); ?> WIB
                                </p>
                                <p>
                                    <i class="fas fa-map-marker-alt"></i>
                                    <strong>Tempat:</strong> <?php echo $row['tempat']; ?>
                                </p>
                                <?php if (!empty($row['deskripsi'])): ?>
                                    <p>
                                        <i class="fas fa-align-left"></i>
                                        <strong>Deskripsi:</strong> <?php echo substr($row['deskripsi'], 0, 100); ?>...
                                    </p>
                                <?php endif; ?>
                            </div>
                            <?php if ($row['pembuat']): ?>
                                <div class="kajian-badge">
                                    <i class="fas fa-user"></i> Dibuat oleh: <?php echo $row['pembuat']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 60px; background: white; border-radius: var(--border-radius);">
                    <i class="fas fa-calendar-times" style="font-size: 60px; color: #ccc; margin-bottom: 20px;"></i>
                    <h3 style="color: #999;">Belum Ada Jadwal Kajian</h3>
                    <p style="color: #999;">Silakan cek kembali nanti untuk informasi kajian terbaru.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4><i class="fas fa-mosque"></i> MAKN ENDE</h4>
                    <p>Madrasah Aliyah Kejuruan Negeri Ende - Membentuk generasi berilmu, berakhlak, dan berwawasan global.</p>
                    <div style="margin-top: 20px;">
                        <i class="fas fa-map-marker-alt"></i> Jl. Pendidikan No. 123, Ende<br>
                        <i class="fas fa-phone"></i> (0381) 123456<br>
                        <i class="fas fa-envelope"></i> info@maknende.sch.id
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Link Cepat</h4>
                    <ul style="list-style: none;">
                        <li style="margin-bottom: 10px;"><a href="#" style="color: white; text-decoration: none; opacity: 0.8;"><i class="fas fa-chevron-right"></i> Tentang Kami</a></li>
                        <li style="margin-bottom: 10px;"><a href="#" style="color: white; text-decoration: none; opacity: 0.8;"><i class="fas fa-chevron-right"></i> Program Studi</a></li>
                        <li style="margin-bottom: 10px;"><a href="#" style="color: white; text-decoration: none; opacity: 0.8;"><i class="fas fa-chevron-right"></i> Fasilitas</a></li>
                        <li style="margin-bottom: 10px;"><a href="#" style="color: white; text-decoration: none; opacity: 0.8;"><i class="fas fa-chevron-right"></i> Kontak</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Jadwal Kajian</h4>
                    <ul style="list-style: none;">
                        <li style="margin-bottom: 10px;"><i class="fas fa-clock"></i> Senin - Kamis: 08.00 - 12.00</li>
                        <li style="margin-bottom: 10px;"><i class="fas fa-clock"></i> Jumat: 07.30 - 10.30</li>
                        <li style="margin-bottom: 10px;"><i class="fas fa-clock"></i> Sabtu: 08.00 - 11.00</li>
                        <li style="margin-bottom: 10px;"><i class="fas fa-clock"></i> Ahad: Libur</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 MAKN ENDE. All rights reserved. Developed with <i class="fas fa-heart" style="color: #dc3545;"></i> for Islamic Education</p>
            </div>
        </div>
    </footer>

    <script>
        // Search functionality
        document.getElementById('searchKajian').addEventListener('keyup', function() {
            filterKajian();
        });

        document.getElementById('filterBulan').addEventListener('change', function() {
            filterKajian();
        });

        function filterKajian() {
            let searchText = document.getElementById('searchKajian').value.toLowerCase();
            let bulan = document.getElementById('filterBulan').value;
            let cards = document.getElementsByClassName('kajian-card');

            for (let card of cards) {
                let judul = card.querySelector('h3').textContent.toLowerCase();
                let pemateri = card.querySelector('.kajian-info p:first-child').textContent.toLowerCase();
                let tempat = card.querySelector('.kajian-info p:nth-child(3)').textContent.toLowerCase();
                let tanggalBulan = card.getAttribute('data-tanggal');
                
                let matchSearch = judul.includes(searchText) || pemateri.includes(searchText) || tempat.includes(searchText);
                let matchBulan = bulan === '' || tanggalBulan === bulan;
                
                if (matchSearch && matchBulan) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>
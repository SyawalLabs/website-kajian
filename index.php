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

// Ambil data untuk galeri kajian (ambil 6 kajian terbaru)
$query_galeri = "SELECT k.*, u.nama_lengkap as pemateri 
                FROM kajian k 
                LEFT JOIN users u ON k.created_by = u.id 
                ORDER BY k.tanggal DESC, k.waktu ASC 
                LIMIT 6";
$result_galeri = mysqli_query($conn, $query_galeri);

// Fungsi untuk mengekstrak YouTube ID dari berbagai format URL
function getYouTubeId($url) {
    if (empty($url)) return false;
    
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    if (preg_match($pattern, $url, $match)) {
        return $match[1];
    }
    return false;
}

// Daftar video YouTube contoh (hardcoded untuk sementara)
$contoh_videos = [
    'https://youtu.be/scxZYkwXw4Y', // hari santri
    'https://youtu.be/z1jlKXSGoU4', // idul adha
    'https://youtu.be/9qll3lZCBC0', // adzan subuh
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAKN ENDE - Profil Asrama</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Style tambahan untuk galeri kajian */
        .kajian-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .kajian-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .kajian-image {
            height: 200px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            cursor: pointer;
            overflow: hidden;
        }
        
        .kajian-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            background: rgba(255, 0, 0, 0.8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            transition: all 0.3s ease;
        }
        
        .play-button:hover {
            background: #ff0000;
            transform: translate(-50%, -50%) scale(1.1);
        }
        
        .video-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
        }
        
        .video-overlay.active {
            display: flex;
        }
        
        .video-container {
            width: 80%;
            max-width: 900px;
            position: relative;
        }
        
        .video-wrapper {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
            height: 0;
            overflow: hidden;
            border-radius: 10px;
        }
        
        .video-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .close-video {
            position: absolute;
            top: -40px;
            right: 0;
            color: white;
            font-size: 30px;
            cursor: pointer;
            background: none;
            border: none;
            z-index: 10000;
        }
        
        .close-video:hover {
            color: #ff0000;
        }
        
        .kajian-content {
            padding: 20px;
        }
        
        .kajian-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .kajian-meta {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .kajian-meta i {
            width: 20px;
            color: var(--primary-color);
        }
        
        .kajian-badge {
            display: inline-block;
            padding: 5px 10px;
            background: var(--primary-color);
            color: white;
            border-radius: 5px;
            font-size: 12px;
            margin-top: 10px;
        }
        
        .no-video-badge {
            display: inline-block;
            padding: 5px 10px;
            background: #dc3545;
            color: white;
            border-radius: 5px;
            font-size: 12px;
            margin-top: 10px;
        }
        
        .section-title {
            text-align: center;
            margin: 60px 0 40px 0;
        }
        
        .section-title h3 {
            font-size: 32px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .section-title p {
            color: #666;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .view-all-btn {
            display: inline-block;
            padding: 12px 30px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            margin-top: 30px;
            transition: all 0.3s ease;
        }
        
        .view-all-btn:hover {
            background: var(--secondary-color);
            transform: scale(1.05);
        }
        
        .youtube-thumbnail {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .mode-badge {
            display: inline-block;
            padding: 3px 8px;
            background: #ffc107;
            color: #000;
            border-radius: 3px;
            font-size: 10px;
            margin-left: 5px;
        }

        /* Style baru untuk profil asrama yang lebih rapi */
        .profile-section {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px 40px;
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .profile-icon-large {
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            border: 3px solid white;
        }

        .profile-title h2 {
            font-size: 32px;
            margin-bottom: 5px;
        }

        .profile-title p {
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            padding: 30px 40px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .profile-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 40px;
        }

        .info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .info-card h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }

        .info-list {
            list-style: none;
        }

        .info-list li {
            margin-bottom: 15px;
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }

        .info-list i {
            color: var(--primary-color);
            font-size: 18px;
            min-width: 25px;
            margin-top: 3px;
        }

        .info-list strong {
            color: var(--primary-color);
        }

        .facility-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .facility-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .facility-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .facility-item i {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .facility-item span {
            display: block;
            font-size: 14px;
            color: #555;
        }

        .achievement-list {
            margin-top: 15px;
        }

        .achievement-item {
            background: white;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .achievement-item i {
            font-size: 20px;
        }

        .achievement-item .gold { color: #ffc107; }
        .achievement-item .silver { color: #c0c0c0; }
        .achievement-item .bronze { color: #cd7f32; }

        .testimonial-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            position: relative;
            margin-top: 30px;
        }

        .testimonial-card::before {
            content: '\f10d';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 40px;
            opacity: 0.3;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
        }

        .testimonial-author img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid white;
        }

        .schedule-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .schedule-item {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .schedule-item:hover {
            background: rgba(255,255,255,0.2);
            transform: translateX(5px);
        }

        .schedule-item i {
            font-size: 20px;
            color: #ffc107;
        }

        .daily-schedule {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 15px;
        }

        .daily-item {
            background: white;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #555;
        }

        .daily-item i {
            color: var(--primary-color);
            font-size: 14px;
        }

        .structure-list {
            margin-top: 15px;
        }

        .structure-item {
            background: white;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .structure-item i {
            color: var(--primary-color);
            font-size: 18px;
        }

        .structure-item .role {
            font-size: 12px;
            color: #666;
        }
    </style>
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
                <a href="index.php"><i class="fas fa-home"></i> Beranda</a>
                <a href="profil_asrama.php" class="active"><i class="fas fa-building"></i> Profil Asrama</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo $_SESSION['role']; ?>/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Video Overlay untuk memutar YouTube -->
    <div class="video-overlay" id="videoOverlay">
        <div class="video-container">
            <button class="close-video" onclick="closeVideo()"><i class="fas fa-times"></i></button>
            <div class="video-wrapper">
                <iframe id="youtubePlayer" src="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <span class="hero-badge"><i class="fas fa-building"></i> Profil Asrama</span>
                <h2>Selamat Datang di <br>Asrama MAKN ENDE</h2>
                <p>Tempat para siswa menuntut ilmu, membina akhlak mulia, dan mendekatkan diri kepada Allah SWT dalam lingkungan yang islami dan berkesan.</p>
                
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

    <!-- Main Content: Profil Asrama (Bagian yang Diperbarui) -->
    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Beranda</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span>Profil Asrama</span>
        </div>

        <h2 class="dashboard-title">
            <i class="fas fa-info-circle"></i> 
            Profil Lengkap Asrama MAKN Ende
        </h2>

        <!-- Profile Section yang Diperbarui -->
        <div class="profile-section">
            <!-- Header Profil -->
            <div class="profile-header">
                <div class="profile-icon-large">
                    <i class="fas fa-home"></i>
                </div>
                <div class="profile-title">
                    <h2>Asrama MAKN Ende</h2>
                    <p>
                        <i class="fas fa-map-marker-alt"></i> Jl. Pendidikan No. 123, Ende - NTT
                        <i class="fas fa-calendar-alt"></i> Berdiri: 15 Juli 2015
                        <i class="fas fa-users"></i> Kapasitas: 200 Santri
                    </p>
                </div>
            </div>

            <!-- Statistik Singkat -->
            <div class="profile-stats">
                <div class="stat-item">
                    <div class="stat-value">150+</div>
                    <div class="stat-label">Penghuni Aktif</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">25</div>
                    <div class="stat-label">Tenaga Pengajar</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">50+</div>
                    <div class="stat-label">Hafidz Qur'an</div>
                </div>
            </div>

            <!-- Konten Profil yang Diperbarui -->
            <div class="profile-content">
                <!-- Kolom Kiri -->
                <div>
                    <!-- Sejarah -->
                    <div class="info-card">
                        <h3><i class="fas fa-history"></i> Sejarah Singkat</h3>
                        <p style="line-height: 1.8; color: #555;">
                            <strong>Asrama MAKN Ende</strong> didirikan pada tahun 2015 sebagai jawaban atas kebutuhan hunian bagi siswa-siswi yang berasal dari luar kota Ende. Berawal dari 2 gedung dengan kapasitas 50 orang, kini asrama telah berkembang menjadi kompleks hunian dengan 4 gedung yang mampu menampung 200 santri. Asrama ini tidak hanya berfungsi sebagai tempat tinggal, tetapi juga menjadi pusat pembinaan karakter, pengembangan diri, dan pendalaman ilmu agama.
                        </p>
                    </div>

                    <!-- Visi Misi -->
                    <div class="info-card">
                        <h3><i class="fas fa-bullseye"></i> Visi & Misi</h3>
                        <div style="margin-bottom: 20px;">
                            <p style="font-weight: bold; color: var(--primary-color); margin-bottom: 5px;">Visi:</p>
                            <p style="color: #555; font-style: italic;">"Terwujudnya generasi yang unggul dalam ilmu pengetahuan, terampil dalam kejuruan, dan berkarakter islami yang kuat."</p>
                        </div>
                        <div>
                            <p style="font-weight: bold; color: var(--primary-color); margin-bottom: 10px;">Misi:</p>
                            <ul class="info-list">
                                <li><i class="fas fa-check-circle"></i> <span>Menyelenggarakan pendidikan formal dan non-formal yang berkualitas</span></li>
                                <li><i class="fas fa-check-circle"></i> <span>Membina kegiatan keagamaan dan pembiasaan akhlak mulia sehari-hari</span></li>
                                <li><i class="fas fa-check-circle"></i> <span>Menyediakan lingkungan asrama yang aman, nyaman, dan kondusif</span></li>
                                <li><i class="fas fa-check-circle"></i> <span>Mengembangkan potensi siswa di bidang seni, olahraga, dan organisasi</span></li>
                                <li><i class="fas fa-check-circle"></i> <span>Menjalin kerjasama dengan berbagai pihak untuk pengembangan santri</span></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Prestasi -->
                    <div class="info-card">
                        <h3><i class="fas fa-trophy"></i> Prestasi Terbaru</h3>
                        <div class="achievement-list">
                            <div class="achievement-item">
                                <i class="fas fa-medal gold"></i>
                                <div>
                                    <strong>Juara 1 MTQ Tingkat Provinsi 2024</strong>
                                    <div style="font-size: 12px; color: #666;">An. Ahmad Fauzi - Kelas 12</div>
                                </div>
                            </div>
                            <div class="achievement-item">
                                <i class="fas fa-medal silver"></i>
                                <div>
                                    <strong>Juara 2 Olimpiade Matematika Nasional</strong>
                                    <div style="font-size: 12px; color: #666;">An. Siti Aisyah - Kelas 11</div>
                                </div>
                            </div>
                            <div class="achievement-item">
                                <i class="fas fa-medal bronze"></i>
                                <div>
                                    <strong>Juara 3 Pidato Bahasa Arab</strong>
                                    <div style="font-size: 12px; color: #666;">An. Muhammad Rizki - Kelas 10</div>
                                </div>
                            </div>
                            <div class="achievement-item">
                                <i class="fas fa-medal"></i>
                                <div>
                                    <strong>10 Besar Karya Ilmiah Remaja</strong>
                                    <div style="font-size: 12px; color: #666;">Tim Asrama Putra</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan -->
                <div>
                    <!-- Fasilitas - DIPERBARUI dengan icon yang valid -->
                    <div class="info-card">
                        <h3><i class="fas fa-door-open"></i> Fasilitas Lengkap</h3>
                        <div class="facility-grid">
                            <!-- Baris 1 -->
                            <div class="facility-item">
                                <i class="fas fa-bed"></i>
                                <span>Kamar Tidur</span>
                            </div>
                            <div class="facility-item">
                                <i class="fas fa-utensils"></i>
                                <span>Ruang Makan</span>
                            </div>
                            <div class="facility-item">
                                <i class="fas fa-book"></i>
                                <span>Perpustakaan</span>
                            </div>
                            <div class="facility-item">
                                <i class="fas fa-mosque"></i>
                                <span>Mushola</span>
                            </div>
                            <div class="facility-item">
                                <i class="fas fa-futbol"></i>
                                <span>Lapangan</span>
                            </div>
                            <div class="facility-item">
                                <i class="fas fa-wifi"></i>
                                <span>WiFi 24 Jam</span>
                            </div>
                            <!-- DIPERBAIKI: Mengganti fa-kitchen-set dengan fa-utensil-spoon -->
                            <div class="facility-item">
                                <i class="fas fa-utensil-spoon"></i>
                                <span>Dapur Umum</span>
                            </div>
                            <div class="facility-item">
                                <i class="fas fa-tshirt"></i>
                                <span>Laundry</span>
                            </div>
                            <div class="facility-item">
                                <i class="fas fa-shower"></i>
                                <span>Kamar Mandi</span>
                            </div>
                            <div class="facility-item">
                                <i class="fas fa-car"></i>
                                <span>Parkir Luas</span>
                            </div>
                            <!-- Menambahkan fasilitas sesuai gambar -->
                            <div class="facility-item">
                                <i class="fas fa-utensils"></i>
                                <span>Dapur Umum</span>
                            </div>
                        </div>
                    </div>

                    <!-- Struktur Pengurus -->
                    <div class="info-card">
                        <h3><i class="fas fa-user-tie"></i> Pengurus Asrama Putra & Putri</h3>
                        <div class="structure-list">
                            <div class="structure-item">
                                <i class="fas fa-user"></i>
                                <div>
                                    <strong>Ustad Rifa'i Sulaiman</strong>
                                    <div class="role">Pembina Asrama</div>
                                </div>
                            </div>
                             <div class="structure-item">
                                <i class="fas fa-user"></i>
                                <div>
                                    <strong>Ustad Syafrudin</strong>
                                    <div class="role">Pembina Asrama</div>
                                </div>
                            </div> <div class="structure-item">
                                <i class="fas fa-user"></i>
                                <div>
                                    <strong>Ustad Yudi Hartandi</strong>
                                    <div class="role">Pembina Asrama</div>
                                </div>
                            </div>
                            <div class="structure-item">
                                <i class="fas fa-user"></i>
                                <div>
                                    <strong>Ustadz Sukur Pribadi</strong>
                                    <div class="role">Kepala Asrama Putra</div>
                                </div>
                            </div>
                            <div class="structure-item">
                                <i class="fas fa-user"></i>
                                <div>
                                    <strong>Ustadzah Nanda Mutiara Najib</strong>
                                    <div class="role">Kepala Asrama Putri</div>
                                </div>
                            </div>
                             <div class="structure-item">
                                <i class="fas fa-user"></i>
                                <div>
                                    <strong>Ustadzah Suhartini Usman</strong>
                                    <div class="role">Pembina Asrama</div>
                                </div>
                            </div>
                             <div class="structure-item">
                                <i class="fas fa-user"></i>
                                <div>
                                    <strong>Ustadzah Vania</strong>
                                    <div class="role">Pembina Asrama</div>
                                </div>
                            </div>
                            <div class="structure-item">
                                <i class="fas fa-users"></i>
                                <div>
                                    <strong>10 Anggota</strong>
                                    <div class="role">Pengurus Harian</div>
                                </div>
                            </div>
                            <div class="structure-item">
                                <i class="fas fa-user-graduate"></i>
                                <div>
                                    <strong>Syawal Saputra</strong>
                                    <div class="role">Ketua OSIS Asrama</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Testimoni -->
                    <div class="testimonial-card">
                        <p style="font-size: 16px; line-height: 1.8; margin-bottom: 20px;">
                            "Tinggal di asrama MAKN Ende adalah pengalaman yang luar biasa. Saya tidak hanya mendapatkan teman baru, tapi juga lingkungan yang mendukung untuk belajar agama dan dunia. Pembina asramanya sangat perhatian dan selalu membimbing kami."
                        </p>
                        <div class="testimonial-author">
                            <img src="https://ui-avatars.com/api/?name=Ahmad+Fauzi&background=ffffff&color=764ba2&size=50" alt="Ahmad Fauzi">
                            <div>
                                <strong>Ahmad Fauzi</strong>
                                <div style="font-size: 12px; opacity: 0.8;">Alumni 2023</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Jadwal Harian dan Kajian -->
            <div style="padding: 0 40px 40px 40px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- Jadwal Harian -->
                    <div class="info-card" style="margin-bottom: 0;">
                        <h3><i class="fas fa-clock"></i> Jadwal Harian Santri</h3>
                        <div class="daily-schedule">
                            <div class="daily-item"><i class="fas fa-moon"></i> 04.00 - Bangun Tidur</div>
                            <div class="daily-item"><i class="fas fa-sun"></i> 04.30 - Sholat Subuh</div>
                            <div class="daily-item"><i class="fas fa-book-open"></i> 06.00 - Dhuha</div>
                            <div class="daily-item"><i class="fas fa-book-open"></i> 07.00 - Sekolah</div>
                            <div class="daily-item"><i class="fas fa-utensils"></i> 12.00-13.00 -Ishoma(istirahat,sholat, makan) </div>
                            <div class="daily-item"><i class="fas fa-book"></i> 13.00-14.30 - Sekolah</div>
                            <div class="daily-item"><i class="fas fa-sun"></i> 16.30 - Sholat Ashar</div>
                            <div class="daily-item"><i class="fas fa-futbol"></i> 17.00 - Olahraga</div>
                            <div class="daily-item"><i class="fas fa-moon"></i> 18.30 - Sholat Maghrib</div>
                            <div class="daily-item"><i class="fas fa-moon"></i> 18.45 - Kajian rutin</div>
                            <div class="daily-item"><i class="fas fa-star"></i> 19.30 - Sholat Isya</div>
                            <div class="daily-item"><i class="fas fa-book"></i> 20.00 - Belajar Malam</div>
                            <div class="daily-item"><i class="fas fa-bed"></i> 22.00 - Istirahat</div>
                        </div>
                    </div>

                    <!-- Jadwal Kajian -->
                    <div class="schedule-card" style="margin-bottom: 0;">
                        <h3 style="color: white; margin-bottom: 20px;"><i class="fas fa-calendar-alt"></i> Jadwal Kajian Rutin</h3>
                        <div class="schedule-item">
                            <i class="fas fa-moon"></i>
                            <div>
                                <strong>Senin Malam</strong>
                                <div style="font-size: 13px; opacity: 0.9;">Kajian Tafsir Al-Qur'an - Ustadz Abdul Rahman</div>
                            </div>
                        </div>
                        <div class="schedule-item">
                            <i class="fas fa-moon"></i>
                            <div>
                                <strong>Rabu Malam</strong>
                                <div style="font-size: 13px; opacity: 0.9;">Kajian Hadits Arbain - Ustadz Muhammad Ali</div>
                            </div>
                        </div>
                        <div class="schedule-item">
                            <i class="fas fa-sun"></i>
                            <div>
                                <strong>Jumat Pagi</strong>
                                <div style="font-size: 13px; opacity: 0.9;">Kajian Fiqih Ibadah - Ustadz Ahmad Fauzi</div>
                            </div>
                        </div>
                        <div class="schedule-item">
                            <i class="fas fa-sun"></i>
                            <div>
                                <strong>Ahad Pagi</strong>
                                <div style="font-size: 13px; opacity: 0.9;">Kajian Umum & Tanya Jawab - Para Asatidz</div>
                            </div>
                        </div>
                        <div class="schedule-item">
                            <i class="fas fa-moon"></i>
                            <div>
                                <strong>Sabtu Malam</strong>
                                <div style="font-size: 13px; opacity: 0.9;">Muhadhoroh / Latihan Ceramah</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== BAGIAN GALERI KAJIAN DENGAN VIDEO YOUTUBE ===== -->
        <div class="section-title">
            <h3><i class="fas fa-book-open"></i> Galeri Video Kajian Terbaru</h3>
            <p>Dokumentasi video kajian islami yang telah dilaksanakan di Asrama MAKN Ende. Klik tombol play pada setiap video untuk menonton langsung.</p>
            <div style="margin-top: 10px;">
                <span class="mode-badge"><i class="fas fa-code"></i> Mode Sementara (Hardcoded)</span>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 40px;">
            <?php 
            // Gabungkan data dari database dengan video contoh
            $index = 0;
            if (mysqli_num_rows($result_galeri) > 0): 
                mysqli_data_seek($result_galeri, 0); // Reset pointer
                while($kajian = mysqli_fetch_assoc($result_galeri)): 
                    // Gunakan link_video dari database jika ada, jika tidak gunakan video contoh
                    $video_url = !empty($kajian['link_video']) ? $kajian['link_video'] : $contoh_videos[$index % count($contoh_videos)];
                    $youtube_id = getYouTubeId($video_url);
                    $index++;
            ?>
                    <div class="kajian-card">
                        <div class="kajian-image" onclick="playVideo('<?php echo $youtube_id; ?>')">
                            <img src="https://img.youtube.com/vi/<?php echo $youtube_id; ?>/hqdefault.jpg" alt="YouTube Thumbnail" class="youtube-thumbnail">
                            <div class="play-button">
                                <i class="fas fa-play"></i>
                            </div>
                        </div>
                        <div class="kajian-content">
                            <h4 class="kajian-title"><?php echo htmlspecialchars($kajian['judul']); ?></h4>
                            <div class="kajian-meta">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($kajian['pemateri'] ?? 'Ustadz/ustadzah'); ?>
                            </div>
                            <div class="kajian-meta">
                                <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($kajian['tanggal'])); ?>
                            </div>
                            <div class="kajian-meta">
                                <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($kajian['waktu'])); ?> WITA
                            </div>
                            <?php if (!empty($kajian['deskripsi'])): ?>
                                <p style="margin-top: 10px; font-size: 14px; color: #666;">
                                    <?php echo substr(htmlspecialchars($kajian['deskripsi']), 0, 100) . '...'; ?>
                                </p>
                            <?php endif; ?>
                            <div>
                                <span class="kajian-badge">
                                    <i class="fab fa-youtube"></i> 
                                    Tersedia Video
                                    <?php if (empty($kajian['link_video'])): ?>
                                        <span style="background: #ffc107; color: #000; padding: 2px 5px; border-radius: 3px; margin-left: 5px; font-size: 10px;">Contoh</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Tampilkan hanya video contoh jika tidak ada data kajian -->
                <?php for($i = 0; $i < 6; $i++): 
                    $youtube_id = getYouTubeId($contoh_videos[$i % count($contoh_videos)]);
                ?>
                    <div class="kajian-card">
                        <div class="kajian-image" onclick="playVideo('<?php echo $youtube_id; ?>')">
                            <img src="https://img.youtube.com/vi/<?php echo $youtube_id; ?>/hqdefault.jpg" alt="YouTube Thumbnail" class="youtube-thumbnail">
                            <div class="play-button">
                                <i class="fas fa-play"></i>
                            </div>
                        </div>
                        <div class="kajian-content">
                            <h4 class="kajian-title">Kajian Islami Pekanan <?php echo $i + 1; ?></h4>
                            <div class="kajian-meta">
                                <i class="fas fa-user"></i> Ustadz Ahmad Fauzi
                            </div>
                            <div class="kajian-meta">
                                <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime("-$i days")); ?>
                            </div>
                            <div class="kajian-meta">
                                <i class="fas fa-clock"></i> 08:00 - 10:00 WITA
                            </div>
                            <div class="kajian-meta">
                                <i class="fas fa-map-marker-alt"></i> Masjid Asrama
                            </div>
                            <p style="margin-top: 10px; font-size: 14px; color: #666;">
                                Kajian rutin pekanan membahas kitab-kitab klasik dan tafsir Al-Qur'an...
                            </p>
                            <div>
                                <span class="kajian-badge">
                                    <i class="fab fa-youtube"></i> 
                                    Video Contoh
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            <?php endif; ?>
        </div>

        <!-- Tombol Lihat Semua Kajian -->
        <div style="text-align: center; margin-bottom: 60px;">
            <a href="kajian.php" class="view-all-btn">
                <i class="fas fa-list"></i> Lihat Semua Kajian
            </a>
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
                    <h4>Jadwal Kegiatan</h4>
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
        // Fungsi untuk memutar video YouTube
        function playVideo(videoId) {
            if (!videoId) {
                alert('Maaf, video untuk kajian ini belum tersedia.');
                return;
            }
            
            const overlay = document.getElementById('videoOverlay');
            const player = document.getElementById('youtubePlayer');
            
            // Set sumber video YouTube dengan autoplay
            player.src = 'https://www.youtube.com/embed/' + videoId + '?autoplay=1&rel=0';
            
            // Tampilkan overlay
            overlay.classList.add('active');
            
            // Cegah scroll pada body
            document.body.style.overflow = 'hidden';
        }
        
        // Fungsi untuk menutup video
        function closeVideo() {
            const overlay = document.getElementById('videoOverlay');
            const player = document.getElementById('youtubePlayer');
            
            // Hentikan video dengan menghapus src
            player.src = '';
            
            // Sembunyikan overlay
            overlay.classList.remove('active');
            
            // Kembalikan scroll
            document.body.style.overflow = 'auto';
        }
        
        // Tutup overlay jika klik di luar video (pada background)
        document.getElementById('videoOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeVideo();
            }
        });
        
        // Tutup dengan tombol ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeVideo();
            }
        });
    </script>

</body>
</html>
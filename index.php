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
    'https://youtu.be/irD5aNeWfbE?list=RDirD5aNeWfbE', // Rick Astley
    'https://youtu.be/kJQP7kiw5Fk', // Despacito
    'https://www.youtube.com/watch?v=09R8_2nJtjg', // Maroon 5
    'https://youtu.be/OPf0YbXqDm0', // Uptown Funk
    'https://www.youtube.com/watch?v=YQHsXMglC9A', // Hello
    'https://youtu.be/LXb3EKWsInQ' // Shape of You
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

    <!-- Main Content: Profil Asrama -->
    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Beranda</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span>Profil Asrama</span>
        </div>

        <h2 class="dashboard-title">
            <i class="fas fa-info-circle"></i> 
            Profil Singkat Asrama MAKN Ende
        </h2>

        <!-- Konten Profil Asrama -->
        <div style="background: white; padding: 40px; border-radius: var(--border-radius); box-shadow: var(--box-shadow); margin-bottom: 40px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: start;">
                <!-- Kolom Kiri: Teks Profil -->
                <div>
                    <h3 style="color: var(--primary-color); margin-bottom: 20px; border-bottom: 2px solid var(--primary-color); padding-bottom: 10px;">
                        <i class="fas fa-home"></i> Asrama MAKN Ende
                    </h3>
                    
                    <p style="margin-bottom: 20px; line-height: 1.8; color: #555;">
                        <strong>Asrama MAKN Ende</strong> adalah lingkungan hunian bagi para siswa yang tidak hanya berfokus pada tempat tinggal, tetapi juga menjadi pusat pembinaan karakter, pengembangan diri, dan pendalaman ilmu agama. Berdiri sejak tahun 2015, asrama ini telah melahirkan generasi-generasi muda yang berprestasi dan berakhlakul karimah.
                    </p>

                    <h4 style="color: var(--primary-color); margin: 25px 0 15px 0;">
                        <i class="fas fa-bullseye"></i> Visi
                    </h4>
                    <p style="margin-bottom: 20px; line-height: 1.8; color: #555;">
                        "Terwujudnya generasi yang unggul dalam ilmu pengetahuan, terampil dalam kejuruan, dan berkarakter islami yang kuat."
                    </p>

                    <h4 style="color: var(--primary-color); margin: 25px 0 15px 0;">
                        <i class="fas fa-clipboard-list"></i> Misi
                    </h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 12px; display: flex; gap: 12px; align-items: start;">
                            <i class="fas fa-check-circle" style="color: var(--primary-color); margin-top: 3px;"></i>
                            <span style="color: #555;">Menyelenggarakan pendidikan formal dan non-formal yang berkualitas.</span>
                        </li>
                        <li style="margin-bottom: 12px; display: flex; gap: 12px; align-items: start;">
                            <i class="fas fa-check-circle" style="color: var(--primary-color); margin-top: 3px;"></i>
                            <span style="color: #555;">Membina kegiatan keagamaan dan pembiasaan akhlak mulia sehari-hari.</span>
                        </li>
                        <li style="margin-bottom: 12px; display: flex; gap: 12px; align-items: start;">
                            <i class="fas fa-check-circle" style="color: var(--primary-color); margin-top: 3px;"></i>
                            <span style="color: #555;">Menyediakan lingkungan asrama yang aman, nyaman, dan kondusif untuk belajar.</span>
                        </li>
                        <li style="margin-bottom: 12px; display: flex; gap: 12px; align-items: start;">
                            <i class="fas fa-check-circle" style="color: var(--primary-color); margin-top: 3px;"></i>
                            <span style="color: #555;">Mengembangkan potensi siswa di bidang seni, olahraga, dan organisasi.</span>
                        </li>
                    </ul>

                    <h4 style="color: var(--primary-color); margin: 25px 0 15px 0;">
                        <i class="fas fa-door-open"></i> Fasilitas Asrama
                    </h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 10px; display: flex; gap: 10px;">
                            <i class="fas fa-bed" style="color: var(--primary-color); width: 25px;"></i>
                            <span style="color: #555;">Kamar tidur dengan kasur, lemari, dan meja belajar.</span>
                        </li>
                        <li style="margin-bottom: 10px; display: flex; gap: 10px;">
                            <i class="fas fa-utensils" style="color: var(--primary-color); width: 25px;"></i>
                            <span style="color: #555;">Dapur umum dan ruang makan bersama.</span>
                        </li>
                        <li style="margin-bottom: 10px; display: flex; gap: 10px;">
                            <i class="fas fa-book" style="color: var(--primary-color); width: 25px;"></i>
                            <span style="color: #555;">Ruang belajar dan perpustakaan mini.</span>
                        </li>
                        <li style="margin-bottom: 10px; display: flex; gap: 10px;">
                            <i class="fas fa-mosque" style="color: var(--primary-color); width: 25px;"></i>
                            <span style="color: #555;">Mushola yang nyaman untuk beribadah.</span>
                        </li>
                        <li style="margin-bottom: 10px; display: flex; gap: 10px;">
                            <i class="fas fa-futbol" style="color: var(--primary-color); width: 25px;"></i>
                            <span style="color: #555;">Lapangan olahraga dan area berkumpul.</span>
                        </li>
                    </ul>
                </div>

                <!-- Kolom Kanan: Galeri/Foto -->
                <div>
                    <img src="https://via.placeholder.com/600x400/1e3c72/ffffff?text=Asrama+MAKN+Ende" alt="Gedung Asrama" style="width: 100%; border-radius: var(--border-radius); margin-bottom: 30px; box-shadow: var(--box-shadow);">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <img src="https://via.placeholder.com/300x200/28a745/ffffff?text=Kegiatan+Belajar" alt="Kegiatan" style="width: 100%; border-radius: var(--border-radius); box-shadow: var(--box-shadow);">
                        <img src="https://via.placeholder.com/300x200/ffc107/000000?text=Ibadah+Bersama" alt="Ibadah" style="width: 100%; border-radius: var(--border-radius); box-shadow: var(--box-shadow);">
                        <img src="https://via.placeholder.com/300x200/dc3545/ffffff?text=Olahraga" alt="Olahraga" style="width: 100%; border-radius: var(--border-radius); box-shadow: var(--box-shadow);">
                        <img src="https://via.placeholder.com/300x200/17a2b8/ffffff?text=Diskusi" alt="Diskusi" style="width: 100%; border-radius: var(--border-radius); box-shadow: var(--box-shadow);">
                    </div>

                    <div style="margin-top: 30px; background: #f8f9fa; padding: 25px; border-radius: var(--border-radius); border-left: 5px solid var(--primary-color);">
                        <h4 style="color: var(--primary-color); margin-bottom: 15px;"><i class="fas fa-quote-right"></i> Testimoni</h4>
                        <p style="font-style: italic; color: #555;">
                            "Tinggal di asrama MAKN Ende adalah pengalaman yang luar biasa. Saya tidak hanya mendapatkan teman baru, tapi juga lingkungan yang mendukung untuk belajar agama dan dunia. Pembina asramanya sangat perhatian."
                        </p>
                        <p style="margin-top: 15px; font-weight: bold; color: var(--primary-color);">— Ahmad Fauzi, Alumni 2023</p>
                    </div>
                </div>
            </div>

            <!-- Keunggulan Asrama -->
            <div style="margin-top: 50px; padding-top: 30px; border-top: 2px dashed #e0e0e0;">
                <h3 style="color: var(--primary-color); text-align: center; margin-bottom: 30px;">
                    <i class="fas fa-star"></i> Mengapa Memilih Asrama MAKN Ende?
                </h3>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px;">
                    <div style="text-align: center; padding: 25px; background: #f8f9fa; border-radius: var(--border-radius);">
                        <i class="fas fa-quran" style="font-size: 40px; color: var(--primary-color); margin-bottom: 15px;"></i>
                        <h4 style="margin-bottom: 10px;">Pembinaan Agama Intensif</h4>
                        <p style="color: #666;">Kajian rutin, tahsin Al-Qur'an, dan kegiatan keagamaan harian.</p>
                    </div>
                    <div style="text-align: center; padding: 25px; background: #f8f9fa; border-radius: var(--border-radius);">
                        <i class="fas fa-chalkboard-teacher" style="font-size: 40px; color: var(--primary-color); margin-bottom: 15px;"></i>
                        <h4 style="margin-bottom: 10px;">Pendampingan Belajar</h4>
                        <p style="color: #666;">Program bimbingan belajar dari kakak kelas dan pembina yang berpengalaman.</p>
                    </div>
                    <div style="text-align: center; padding: 25px; background: #f8f9fa; border-radius: var(--border-radius);">
                        <i class="fas fa-hand-holding-heart" style="font-size: 40px; color: var(--primary-color); margin-bottom: 15px;"></i>
                        <h4 style="margin-bottom: 10px;">Keluarga Kedua</h4>
                        <p style="color: #666;">Suasana kekeluargaan yang erat antara sesama penghuni asrama.</p>
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
                            <div class="kajian-meta">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($kajian['tempat'] ?? 'Masjid/Mushola Asrama'); ?>
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

        <!-- Tambahan fitur jadwal kajian mingguan -->
        <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); padding: 40px; border-radius: var(--border-radius); color: white; margin-bottom: 60px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center;">
                <div>
                    <h3 style="color: white; font-size: 28px; margin-bottom: 20px;">
                        <i class="fas fa-calendar-alt"></i> Jadwal Kajian Rutin
                    </h3>
                    <p style="margin-bottom: 25px; opacity: 0.9;">Ikuti kajian islami rutin yang diselenggarakan di Asrama MAKN Ende untuk meningkatkan keimanan dan pengetahuan agama.</p>
                    
                    <ul style="list-style: none;">
                        <li style="margin-bottom: 15px; display: flex; gap: 15px;">
                            <i class="fas fa-check-circle" style="font-size: 20px;"></i>
                            <div>
                                <strong>Senin Malam (Ba'da Isya)</strong><br>
                                <small>Kajian Tafsir Al-Qur'an - Ustadz Abdul Rahman</small>
                            </div>
                        </li>
                        <li style="margin-bottom: 15px; display: flex; gap: 15px;">
                            <i class="fas fa-check-circle" style="font-size: 20px;"></i>
                            <div>
                                <strong>Rabu Malam (Ba'da Isya)</strong><br>
                                <small>Kajian Hadits Arbain - Ustadz Muhammad Ali</small>
                            </div>
                        </li>
                        <li style="margin-bottom: 15px; display: flex; gap: 15px;">
                            <i class="fas fa-check-circle" style="font-size: 20px;"></i>
                            <div>
                                <strong>Jumat Pagi (Ba'da Subuh)</strong><br>
                                <small>Kajian Fiqih Ibadah - Ustadz Ahmad Fauzi</small>
                            </div>
                        </li>
                        <li style="margin-bottom: 15px; display: flex; gap: 15px;">
                            <i class="fas fa-check-circle" style="font-size: 20px;"></i>
                            <div>
                                <strong>Ahad Pagi (08.00 - 10.00)</strong><br>
                                <small>Kajian Umum & Tanya Jawab - Bersama Para Asatidz</small>
                            </div>
                        </li>
                    </ul>
                </div>
                <div style="text-align: center;">
                    <i class="fas fa-quran" style="font-size: 120px; opacity: 0.5;"></i>
                    <p style="margin-top: 20px; font-style: italic;">"Sebaik-baik kalian adalah yang mempelajari Al-Qur'an dan mengajarkannya." (HR. Bukhari)</p>
                </div>
            </div>
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
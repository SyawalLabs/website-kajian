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
    <title>MAKN ENDE - Profil Asrama</title>
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

                <!-- Kolom Kanan: Galeri/Foto (Tempat untuk gambar) -->
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

</body>
</html>
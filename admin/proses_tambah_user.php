<?php
include '../includes/auth.php';
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

if (isset($_POST['simpan'])) {
    // Ambil dan bersihkan data - HANYA kolom yang ada di database
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    // Validasi input
    $errors = [];
    
    // Validasi nama lengkap
    if (strlen($nama_lengkap) < 3) {
        $errors[] = "Nama lengkap minimal 3 karakter";
    }
    
    // Cek username sudah ada atau belum
    $check_username = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($check_username) > 0) {
        $errors[] = "Username sudah digunakan, silakan pilih username lain";
    }
    
    // Validasi username
    if (!preg_match('/^[a-zA-Z0-9]{3,20}$/', $username)) {
        $errors[] = "Username hanya boleh huruf dan angka, minimal 3 karakter";
    }
    
    // Validasi password
    if (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter";
    }
    
    if ($password !== $konfirmasi_password) {
        $errors[] = "Password dan konfirmasi password tidak cocok";
    }
    
    // Jika tidak ada error, simpan ke database
    if (empty($errors)) {
        // Hash password
        $hashed_password = md5($password);
        
        // Query sederhana - HANYA kolom yang pasti ada di database
        $query = "INSERT INTO users (nama_lengkap, username, password, role, created_at) 
                  VALUES ('$nama_lengkap', '$username', '$hashed_password', 'pembina', NOW())";
        
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "User baru berhasil ditambahkan! Username: $username, Password: $password";
            header("Location: kelola_user.php?status=success");
            exit();
        } else {
            $errors[] = "Gagal menyimpan data: " . mysqli_error($conn);
        }
    }
    
    // Jika ada error, simpan ke session dan redirect kembali
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header("Location: kelola_user.php?status=error");
        exit();
    }
} else {
    header("Location: kelola_user.php");
    exit();
}
?>
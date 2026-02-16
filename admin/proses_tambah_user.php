<?php
include '../includes/auth.php';
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Deteksi aksi dari form
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Handle TAMBAH user
if ($action == 'tambah' || isset($_POST['simpan'])) {
    // Ambil dan bersihkan data
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
        
        // Query simpan user baru
        $query = "INSERT INTO users (nama_lengkap, username, password, role, created_at) 
                  VALUES ('$nama_lengkap', '$username', '$hashed_password', 'pembina', NOW())";
        
        if (mysqli_query($conn, $query)) {
            // Redirect dengan status success dan pesan
            $message = "User baru berhasil ditambahkan! Username: $username";
            header("Location: kelola_user.php?status=success&message=" . urlencode($message));
            exit();
        } else {
            $errors[] = "Gagal menyimpan data: " . mysqli_error($conn);
        }
    }
    
    // Jika ada error
    if (!empty($errors)) {
        $error_message = implode(", ", $errors);
        header("Location: kelola_user.php?status=error&message=" . urlencode($error_message));
        exit();
    }
}

// Handle EDIT user
elseif ($action == 'edit') {
    // Ambil data dari form
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $password = $_POST['password'];
    
    // Validasi input
    $errors = [];
    
    // Validasi nama lengkap
    if (strlen($nama_lengkap) < 3) {
        $errors[] = "Nama lengkap minimal 3 karakter";
    }
    
    // Cek username sudah ada atau belum (kecuali untuk user ini sendiri)
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username' AND id != '$user_id'");
    if (mysqli_num_rows($check) > 0) {
        $errors[] = "Username sudah digunakan oleh user lain";
    }
    
    // Validasi username
    if (!preg_match('/^[a-zA-Z0-9]{3,20}$/', $username)) {
        $errors[] = "Username hanya boleh huruf dan angka, minimal 3 karakter";
    }
    
    // Validasi password jika diisi
    if (!empty($password) && strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter jika ingin diubah";
    }
    
    // Jika tidak ada error, update database
    if (empty($errors)) {
        // Jika ada password baru
        if (!empty($password)) {
            $hashed_password = md5($password);
            $query = "UPDATE users SET 
                      nama_lengkap='$nama_lengkap', 
                      username='$username', 
                      password='$hashed_password',
                      role='$role' 
                      WHERE id='$user_id'";
        } else {
            $query = "UPDATE users SET 
                      nama_lengkap='$nama_lengkap', 
                      username='$username',
                      role='$role' 
                      WHERE id='$user_id'";
        }
        
        if (mysqli_query($conn, $query)) {
            // Ambil nama user untuk pesan
            $user_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_lengkap FROM users WHERE id='$user_id'"));
            $message = "Data user " . $user_data['nama_lengkap'] . " berhasil diperbarui";
            header("Location: kelola_user.php?status=updated&message=" . urlencode($message));
            exit();
        } else {
            $errors[] = "Gagal memperbarui data: " . mysqli_error($conn);
        }
    }
    
    // Jika ada error
    if (!empty($errors)) {
        $error_message = implode(", ", $errors);
        header("Location: kelola_user.php?status=error&message=" . urlencode($error_message));
        exit();
    }
}

// Jika tidak ada aksi yang valid, redirect ke halaman kelola user
else {
    header("Location: kelola_user.php");
    exit();
}
?>
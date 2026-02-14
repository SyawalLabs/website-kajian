<?php
include '../includes/auth.php';
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Handle Hapus User
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus']);
    // Cegah hapus admin utama
    $check = mysqli_query($conn, "SELECT role FROM users WHERE id='$id'");
    $user = mysqli_fetch_assoc($check);
    
    if ($user['role'] != 'admin') {
        mysqli_query($conn, "DELETE FROM users WHERE id='$id'");
    }
    redirect('kelola_user.php');
}

// Handle Reset Password
if (isset($_GET['reset'])) {
    $id = mysqli_real_escape_string($conn, $_GET['reset']);
    $new_password = md5('password123');
    mysqli_query($conn, "UPDATE users SET password='$new_password' WHERE id='$id'");
    redirect('kelola_user.php');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>Jadwal Kajian - Admin</h1>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="kelola_kajian.php">Kelola Kajian</a></li>
                <li><a href="kelola_user.php">Kelola User</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h2>Daftar User</h2>
        
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Lengkap</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Tanggal Daftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT * FROM users ORDER BY created_at DESC";
                $result = mysqli_query($conn, $query);
                $no = 1;
                
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td>" . $no++ . "</td>";
                    echo "<td>" . $row['nama_lengkap'] . "</td>";
                    echo "<td>" . $row['username'] . "</td>";
                    echo "<td>" . ucfirst($row['role']) . "</td>";
                    echo "<td>" . date('d/m/Y', strtotime($row['created_at'])) . "</td>";
                    echo "<td>";
                    
                    if ($row['role'] != 'admin') {
                        echo "<a href='?reset=" . $row['id'] . "' class='btn-reset' onclick='return confirm(\"Reset password menjadi password123?\")'>Reset Password</a>";
                        echo " | ";
                        echo "<a href='?hapus=" . $row['id'] . "' class='btn-hapus' onclick='return confirm(\"Yakin ingin menghapus user ini?\")'>Hapus</a>";
                    } else {
                        echo "-";
                    }
                    
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
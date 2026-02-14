<?php
include 'config/database.php';
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: pembina/dashboard.php");
    }
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);
    
    $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role'] = $user['role'];
        
        if ($user['role'] == 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: pembina/dashboard.php");
        }
        exit();
    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MAKN ENDE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Reset CSS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        /* Navbar style sesuai dengan gambar */
        .navbar {
            background: linear-gradient(135deg, #2c3e50 0%, #1e2a36 100%);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-logo {
            background: #f39c12;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .navbar-logo i {
            font-size: 20px;
            color: white;
        }

        .navbar-brand h1 {
            font-size: 1.2rem;
            color: white;
            line-height: 1.2;
        }

        .navbar-brand h1 span {
            display: block;
            font-size: 0.7rem;
            opacity: 0.9;
            font-weight: 300;
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
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }

        .navbar-menu a:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }

        .navbar-menu a.active {
            background: #f39c12;
            color: #2c3e50;
            font-weight: 600;
        }

        .navbar-menu a i {
            font-size: 0.9rem;
        }

        /* Style untuk halaman login */
        body.login-page {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 100px 20px 40px;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            margin: 0;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #2c3e50 0%, #1e2a36 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(44, 62, 80, 0.4);
        }

        .login-logo i {
            font-size: 50px;
            color: #f39c12;
        }

        .login-header h2 {
            color: #2c3e50;
            margin: 0 0 5px;
            font-size: 2rem;
            font-weight: 600;
        }

        .login-subtitle {
            color: #666;
            font-size: 0.95rem;
        }

        .alert-error {
            background: #fee;
            border-left: 4px solid #e74c3c;
            color: #c0392b;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.3s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-group label i {
            color: #f39c12;
            margin-right: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #f39c12;
            background: white;
            box-shadow: 0 0 0 4px rgba(243, 156, 18, 0.1);
        }

        .btn {
            background: linear-gradient(135deg, #2c3e50 0%, #1e2a36 100%);
            color: white;
            padding: 15px 25px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(44, 62, 80, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn i {
            font-size: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar .container {
                flex-direction: column;
                gap: 10px;
            }

            .navbar-menu {
                flex-wrap: wrap;
                justify-content: center;
            }

            body.login-page {
                padding: 140px 15px 30px;
            }

            .login-container {
                padding: 30px 20px;
            }

            .login-logo {
                width: 80px;
                height: 80px;
            }

            .login-logo i {
                font-size: 40px;
            }

            .login-header h2 {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 480px) {
            .navbar-brand h1 {
                font-size: 1rem;
            }

            .navbar-brand h1 span {
                font-size: 0.6rem;
            }

            .navbar-menu a {
                padding: 6px 12px;
                font-size: 0.85rem;
            }

            .login-container {
                padding: 25px 15px;
            }

            .login-header h2 {
                font-size: 1.6rem;
            }

            .login-subtitle {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body class="login-page">
    <!-- Navbar sesuai dengan gambar -->
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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo $_SESSION['role']; ?>/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="login.php" class="active"><i class="fas fa-sign-in-alt"></i> Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-mosque"></i>
            </div>
            
            <h2>MAKN ENDE</h2>
            <div class="login-subtitle">Madrasah Aliyah Kejuruan Negeri Ende</div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Username</label>
                <input type="text" name="username" placeholder="Masukkan username" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; text-align: center; font-size: 0.9rem; color: #666;">
            <i class="fas fa-lock"></i> Sistem Informasi Jadwal Kajian MAKN ENDE
        </div>
    </div>
</body>
</html>
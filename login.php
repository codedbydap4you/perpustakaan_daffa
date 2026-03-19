<?php
session_start();
include 'config/koneksi.php';

if (isset($_POST['login'])) {
  $username = $_POST['username'];
  $password = $_POST['password'];

  // Ambil user berdasarkan username saja
  $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username'");
  $data = mysqli_fetch_array($query);

  // Verifikasi password menggunakan password_verify
  if ($data && password_verify($password, $data['password'])) {
    $_SESSION['username'] = $data['username'];
    $_SESSION['role'] = $data['role'];

    if ($data['role'] == 'admin') {
      header("Location:pages/buku/dashboard_admin.php");
    } else {
      header("Location:../../perpustakaan_daffa/pages/user/dashboard_user.php");
    }
  } else {
    echo "<script>alert('Login gagal!');</script>";
  }
}

?>

<!-- Tampilan Login -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Perpustakaan Online</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=VT323&family=Press+Start+2P&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --yellow: #FFC567;
            --red: #FD5A46;
            --purple: #552CB7;
            --green: #00995E;
            --blue: #058CD7;
            --dark: #222034;
            --light: #F7E9D6;
            --text: #333;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Rubik', sans-serif;
        }
        
        body {
            background-color: var(--light);
            color: var(--dark);
            background-image: radial-gradient(var(--dark) 1px, transparent 1px);
            background-size: 20px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 30px;
            box-shadow: 10px 10px 0 var(--dark);
            border: 4px solid var(--dark);
            border-radius: 12px;
            background-color: white;
            position: relative;
            overflow: hidden;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 10px;
            background: linear-gradient(90deg, var(--yellow), var(--red), var(--purple), var(--green), var(--blue));
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-family: 'Press Start 2P', cursive;
            font-size: 2rem;
            color: var(--purple);
            text-shadow: 2px 2px 0 var(--yellow);
            margin-bottom: 10px;
        }
        
        .header p {
            font-family: 'VT323', monospace;
            font-size: 1.5rem;
            color: var(--dark);
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-family: 'VT323', monospace;
            font-size: 1.3rem;
            color: var(--dark);
        }
        
        .form-group input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 3px solid var(--dark);
            border-radius: 8px;
            font-size: 1rem;
            background-color: white;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(5, 140, 215, 0.3);
        }
        
        .form-group i {
            position: absolute;
            left: 15px;
            top: 43px;
            color: var(--purple);
            font-size: 1.2rem;
        }

        .forgot-password-link {
    text-align: right;
    margin-bottom: 15px;
    font-family: 'VT323', monospace;
    font-size: 1rem; /* Lebih kecil dari register-link yang 1.2rem */
}

.forgot-password-link a {
    color: red;
    text-decoration: none;
    font-weight: normal;
}

.forgot-password-link a:hover {
    color: var(--purple);
    text-decoration: underline;
}
        
        .login-btn {
            width: 100%;
            padding: 15px;
            border: 3px solid var(--dark);
            border-radius: 8px;
            background-color: var(--blue);
            color: white;
            font-family: 'Press Start 2P', cursive;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 5px 5px 0 var(--dark);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .login-btn:hover {
            background-color: var(--yellow);
            color: var(--dark);
            transform: translateY(-3px);
        }
        
        .login-btn:active {
            transform: translateY(0);
            box-shadow: 2px 2px 0 var(--dark);
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
        }
        
        .register-link a {
            color: var(--purple);
            text-decoration: none;
            font-weight: bold;
        }
        
        .register-link a:hover {
            color: var(--blue);
            text-decoration: underline;
        }
        
        /* Animation */
        @keyframes float {
            0% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0px);
            }
        }
        
        .book-icon {
            position: absolute;
            font-size: 2rem;
            color: var(--yellow);
            text-shadow: 2px 2px 0 var(--dark);
            animation: float 3s ease-in-out infinite;
            z-index: -1;
            opacity: 0.3;
        }
        
        .book-icon:nth-child(1) {
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .book-icon:nth-child(2) {
            top: 60%;
            right: 15%;
            animation-delay: 0.5s;
        }
        
        .book-icon:nth-child(3) {
            bottom: 10%;
            left: 20%;
            animation-delay: 1s;
        }
        
        /* Responsive styles */
        @media (max-width: 576px) {
            .login-container {
                width: 90%;
                padding: 20px;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .header p {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="book-icon"><i class="fas fa-book"></i></div>
        <div class="book-icon"><i class="fas fa-book-open"></i></div>
        <div class="book-icon"><i class="fas fa-bookmark"></i></div>
        
        <div class="header">
            <h1>E-LIBRARY</h1>
            <p>Masuk ke akun perpustakaan online</p>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <i class="fas fa-user"></i>
                <input type="text" id="username" name="username" placeholder="Masukkan username anda" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Masukkan password anda" required>
            </div>

            <div class="forgot-password-link">
    <a href="pages\auth\lupa_password.php">Lupa password?</a>
</div>
            
            <button type="submit" class="login-btn" name="login">Login</button>
        </form>
        
        <div class="register-link">
            Belum punya akun? <a href="pages/auth/register.php">Daftar sekarang</a>
        </div>
    </div>
</body>
</html>
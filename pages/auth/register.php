<?php
session_start();
include '../../../perpustakaan_daffa/config/koneksi.php';

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'user'; // Default role untuk user baru
    
    // Data pertanyaan keamanan
    $tempat_lahir = $_POST['tempat_lahir'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $buku_pertama = $_POST['buku_pertama'];
    $genre_favorit = $_POST['genre_favorit'];
    
    // Cek apakah username sudah ada
    $check_username = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username'");
    
    if (mysqli_num_rows($check_username) > 0) {
        echo "<script>alert('Username sudah digunakan! Silakan pilih username lain.');</script>";
    } else {
        // Insert ke tabel users
        $insert_user = mysqli_query($koneksi, "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')");
        
        if ($insert_user) {
            // Ambil ID user yang baru dibuat
            $user_id = mysqli_insert_id($koneksi);
            
            // Insert ke tabel security_questions
            $insert_security = mysqli_query($koneksi, "INSERT INTO security_questions (user_id, tempat_lahir, tanggal_lahir, buku_pertama, genre_favorit) VALUES ('$user_id', '$tempat_lahir', '$tanggal_lahir', '$buku_pertama', '$genre_favorit')");
            
            if ($insert_security) {
                echo "<script>alert('Registrasi berhasil! Silakan login dengan akun Anda.'); window.location.href='../../login.php';
</script>";
            } else {
                // Jika gagal insert security questions, hapus user yang sudah dibuat
                mysqli_query($koneksi, "DELETE FROM users WHERE id='$user_id'");
                echo "<script>alert('Registrasi gagal! Silakan coba lagi.');</script>";
            }
        } else {
            echo "<script>alert('Registrasi gagal! Silakan coba lagi.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Perpustakaan Online</title>
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
            padding: 20px 0;
        }
        
        .register-container {
            width: 100%;
            max-width: 550px;
            padding: 30px;
            box-shadow: 10px 10px 0 var(--dark);
            border: 4px solid var(--dark);
            border-radius: 12px;
            background-color: white;
            position: relative;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .register-container::before {
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
            font-size: 1.8rem;
            color: var(--purple);
            text-shadow: 2px 2px 0 var(--yellow);
            margin-bottom: 10px;
        }
        
        .header p {
            font-family: 'VT323', monospace;
            font-size: 1.3rem;
            color: var(--dark);
        }
        
        .form-section {
            margin-bottom: 25px;
        }
        
        .form-section h3 {
            font-family: 'VT323', monospace;
            font-size: 1.4rem;
            color: var(--purple);
            margin-bottom: 15px;
            border-bottom: 2px solid var(--yellow);
            padding-bottom: 5px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
            color: var(--dark);
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 3px solid var(--dark);
            border-radius: 8px;
            font-size: 1rem;
            background-color: white;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(5, 140, 215, 0.3);
        }
        
        .form-group i {
            position: absolute;
            left: 12px;
            top: 43px;
            color: var(--purple);
            font-size: 1.1rem;
        }
        
        .register-btn {
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
            margin-top: 20px;
        }
        
        .register-btn:hover {
            background-color: var(--yellow);
            color: var(--dark);
            transform: translateY(-3px);
        }
        
        .register-btn:active {
            transform: translateY(0);
            box-shadow: 2px 2px 0 var(--dark);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
        }
        
        .login-link a {
            color: var(--purple);
            text-decoration: none;
            font-weight: bold;
        }
        
        .login-link a:hover {
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
            top: 15%;
            left: 5%;
            animation-delay: 0s;
        }
        
        .book-icon:nth-child(2) {
            top: 50%;
            right: 10%;
            animation-delay: 0.5s;
        }
        
        .book-icon:nth-child(3) {
            bottom: 15%;
            left: 15%;
            animation-delay: 1s;
        }
        
        /* Responsive styles */
        @media (max-width: 576px) {
            .register-container {
                width: 95%;
                padding: 20px;
            }
            
            .header h1 {
                font-size: 1.4rem;
            }
            
            .header p {
                font-size: 1.1rem;
            }
            
            .form-group input, .form-group select {
                padding: 10px 10px 10px 35px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="book-icon"><i class="fas fa-book"></i></div>
        <div class="book-icon"><i class="fas fa-book-open"></i></div>
        <div class="book-icon"><i class="fas fa-bookmark"></i></div>
        
        <div class="header">
            <h1>DAFTAR AKUN</h1>
            <p>Buat akun perpustakaan online Anda</p>
        </div>
        
        <form method="POST">
            <!-- Data Akun -->
            <div class="form-section">
                <h3>Data Akun Perpustakaan Anda</h3>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Masukkan username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                </div>
            </div>
            
            <!-- Pertanyaan Keamanan -->
            <div class="form-section">
                <h3><i class="fas fa-shield-alt"></i> Pertanyaan Keamanan</h3>
                <p style="font-family: 'VT323', monospace; font-size: 1.1rem; color: var(--red); margin-bottom: 15px;">
                    <i class="fas fa-info-circle"></i> Data ini akan digunakan untuk reset password jika lupa
                </p>
                
                <div class="form-group">
                    <label for="tempat_lahir">Dimana Anda dilahirkan?</label>
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" id="tempat_lahir" name="tempat_lahir" placeholder="Contoh: Jakarta" required>
                </div>
                
                <div class="form-group">
                    <label for="tanggal_lahir">Kapan Anda lahir?</label>
                    <i class="fas fa-calendar"></i>
                    <input type="date" id="tanggal_lahir" name="tanggal_lahir" required>
                </div>
                
                <div class="form-group">
                    <label for="buku_pertama">Apa judul buku pertama yang Anda pinjam?</label>
                    <i class="fas fa-book"></i>
                    <input type="text" id="buku_pertama" name="buku_pertama" placeholder="Contoh: Harry Potter" required>
                </div>
                
                <div class="form-group">
                    <label for="genre_favorit">Apa genre buku yang Anda sukai?</label>
                    <i class="fas fa-heart"></i>
                    <input type="text" id="genre_favorit" name="genre_favorit" placeholder="Contoh: Fantasi" required>
                    </input>
                </div>
            </div>

                            <p style="font-family: 'VT323', monospace; font-size: 1.1rem; color: var(--red); margin-bottom: 15px;">
                    <i class="fas fa-info-circle"></i> Jangan lupakan data pertanyaan keamanan anda
                </p>
            
            <button type="submit" class="register-btn" name="register">Daftar Sekarang</button>
        </form>
        
        <div class="login-link">
            Sudah punya akun? <a href="../../login.php">Login di sini</a>
        </div>
    </div>
</body>
</html>
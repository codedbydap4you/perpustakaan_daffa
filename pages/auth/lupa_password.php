<?php
session_start();
include '../../../perpustakaan_daffa/config/koneksi.php';

$step = 1; // Default step
$user_data = null;

// Step 1: Cek username dan ambil pertanyaan keamanan
if (isset($_POST['check_username'])) {
    $username = $_POST['username'];
    
    $query = mysqli_query($koneksi, "SELECT u.*, s.tempat_lahir, s.tanggal_lahir, s.buku_pertama, s.genre_favorit 
                                     FROM users u 
                                     JOIN security_questions s ON u.id = s.user_id 
                                     WHERE u.username='$username'");
    
    if (mysqli_num_rows($query) > 0) {
        $user_data = mysqli_fetch_array($query);
        $step = 2;
        $_SESSION['temp_user_id'] = $user_data['id'];
        $_SESSION['temp_username'] = $user_data['username'];
    } else {
        echo "<script>alert('Username tidak ditemukan!');</script>";
    }
}

// Step 2: Verifikasi jawaban pertanyaan keamanan
if (isset($_POST['verify_security'])) {
    if (isset($_SESSION['temp_user_id'])) {
        $user_id = $_SESSION['temp_user_id'];
        $tempat_lahir = $_POST['tempat_lahir'];
        $tanggal_lahir = $_POST['tanggal_lahir'];
        $buku_pertama = $_POST['buku_pertama'];
        $genre_favorit = $_POST['genre_favorit'];
        
        $query = mysqli_query($koneksi, "SELECT * FROM security_questions 
                                         WHERE user_id='$user_id' 
                                         AND tempat_lahir='$tempat_lahir' 
                                         AND tanggal_lahir='$tanggal_lahir' 
                                         AND buku_pertama='$buku_pertama' 
                                         AND genre_favorit='$genre_favorit'");
        
        if (mysqli_num_rows($query) > 0) {
            $step = 3;
            echo "<script>
                setTimeout(function() {
                    alert('Verifikasi berhasil! Silakan buat password baru.');
                    document.getElementById('resetModal').style.display = 'block';
                }, 100);
            </script>";
        } else {
            echo "<script>alert('Jawaban pertanyaan keamanan tidak cocok!');</script>";
            $step = 2;
        }
    }
}

// Step 3: Reset password
if (isset($_POST['reset_password'])) {
    if (isset($_SESSION['temp_user_id'])) {
        $user_id = $_SESSION['temp_user_id'];
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        
        $update_query = mysqli_query($koneksi, "UPDATE users SET password='$new_password' WHERE id='$user_id'");
        
        if ($update_query) {
            // Update timestamp di security_questions
            mysqli_query($koneksi, "UPDATE security_questions SET updated_at=NOW() WHERE user_id='$user_id'");
            
            // Clear session
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_username']);
            
            echo "<script>
                alert('Password berhasil direset! Silakan login dengan password baru.');
                window.location.href='../../login.php';
            </script>";
        } else {
            echo "<script>alert('Gagal mereset password! Silakan coba lagi.');</script>";
        }
    }
}

// Jika ada session temp_user_id dan step belum 3, set step ke 2
if (isset($_SESSION['temp_user_id']) && $step == 1) {
    $step = 2;
    $user_id = $_SESSION['temp_user_id'];
    $query = mysqli_query($koneksi, "SELECT u.*, s.tempat_lahir, s.tanggal_lahir, s.buku_pertama, s.genre_favorit 
                                     FROM users u 
                                     JOIN security_questions s ON u.id = s.user_id 
                                     WHERE u.id='$user_id'");
    $user_data = mysqli_fetch_array($query);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Perpustakaan Online</title>
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
        
        .forgot-container {
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
        
        .forgot-container::before {
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
            color: var(--red);
            text-shadow: 2px 2px 0 var(--yellow);
            margin-bottom: 10px;
        }
        
        .header p {
            font-family: 'VT323', monospace;
            font-size: 1.3rem;
            color: var(--dark);
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 3px solid var(--dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Press Start 2P', cursive;
            font-size: 0.8rem;
            margin: 0 10px;
            background-color: white;
            color: var(--dark);
        }
        
        .step.active {
            background-color: var(--blue);
            color: white;
        }
        
        .step.completed {
            background-color: var(--green);
            color: white;
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
        
        .submit-btn {
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
        
        .submit-btn:hover {
            background-color: var(--yellow);
            color: var(--dark);
            transform: translateY(-3px);
        }
        
        .submit-btn:active {
            transform: translateY(0);
            box-shadow: 2px 2px 0 var(--dark);
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
        }
        
        .back-link a {
            color: var(--purple);
            text-decoration: none;
            font-weight: bold;
        }
        
        .back-link a:hover {
            color: var(--blue);
            text-decoration: underline;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border: 4px solid var(--dark);
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            box-shadow: 10px 10px 0 var(--dark);
            position: relative;
        }
        
        .modal-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 10px;
            background: linear-gradient(90deg, var(--green), var(--blue), var(--purple));
        }
        
        .modal h2 {
            font-family: 'Press Start 2P', cursive;
            font-size: 1.2rem;
            color: var(--green);
            text-align: center;
            margin-bottom: 20px;
        }
        
        .close {
            color: var(--red);
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 15px;
        }
        
        /* Animation */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
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
        
        .info-text {
            font-family: 'VT323', monospace;
            font-size: 1.1rem;
            color: var(--red);
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #fff3cd;
            border: 2px solid var(--yellow);
            border-radius: 8px;
        }
        
        /* Responsive styles */
        @media (max-width: 576px) {
            .forgot-container {
                width: 95%;
                padding: 20px;
            }
            
            .header h1 {
                font-size: 1.4rem;
            }
            
            .step {
                width: 35px;
                height: 35px;
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="book-icon"><i class="fas fa-key"></i></div>
        <div class="book-icon"><i class="fas fa-shield-alt"></i></div>
        <div class="book-icon"><i class="fas fa-lock"></i></div>
        
        <div class="header">
            <h1>LUPA PASSWORD</h1>
            <p>Reset password melalui pertanyaan keamanan</p>
        </div>
        
        <div class="step-indicator">
            <div class="step <?php echo $step >= 1 ? ($step == 1 ? 'active' : 'completed') : ''; ?>">1</div>
            <div class="step <?php echo $step >= 2 ? ($step == 2 ? 'active' : 'completed') : ''; ?>">2</div>
            <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">3</div>
        </div>
        
        <?php if ($step == 1): ?>
            <!-- Step 1: Input Username -->
            <div class="info-text">
                <i class="fas fa-info-circle"></i> Masukkan username Anda untuk melanjutkan proses reset password
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Masukkan username Anda" required>
                </div>
                
                <button type="submit" class="submit-btn" name="check_username">Lanjutkan</button>
            </form>
            
        <?php elseif ($step == 2): ?>
            <!-- Step 2: Jawab Pertanyaan Keamanan -->
            <div class="info-text">
                <i class="fas fa-shield-alt"></i> Jawab pertanyaan keamanan dengan data yang sama saat mendaftar
            </div>
            
            <form method="POST">
                <div class="form-section">
                    <h3><i class="fas fa-question-circle"></i> Pertanyaan Keamanan</h3>
                    
                    <div class="form-group">
                        <label for="tempat_lahir">Dimana Anda dilahirkan?</label>
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" id="tempat_lahir" name="tempat_lahir" placeholder="Masukkan tempat lahir" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="tanggal_lahir">Kapan Anda lahir?</label>
                        <i class="fas fa-calendar"></i>
                        <input type="date" id="tanggal_lahir" name="tanggal_lahir" required>
                    </div>
                     
                    <div class="form-group">
                        <label for="buku_pertama">Apa judul buku pertama yang Anda pinjam?</label>
                        <i class="fas fa-book"></i>
                        <input type="text" id="buku_pertama" name="buku_pertama" placeholder="Masukkan judul buku pertama" required>
                    </div>
                    
                <div class="form-group">
                    <label for="genre_favorit">Apa genre buku yang Anda sukai?</label>
                    <i class="fas fa-heart"></i>
                    <input type="text" id="genre_favorit" name="genre_favorit" placeholder="Contoh: Fantasi" required>
                    </input>
                </div>
                </div>
                
                <button type="submit" class="submit-btn" name="verify_security">Verifikasi</button>
            </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="../../login.php"><i class="fas fa-arrow-left"></i> Kembali ke Login</a>
        </div>
    </div>
    
    <!-- Modal Reset Password -->
    <div id="resetModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('resetModal').style.display='none'">&times;</span>
            <h2>RESET PASSWORD</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label for="new_password">Password Baru</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="new_password" name="new_password" placeholder="Masukkan password baru" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Konfirmasi password baru" required>
                </div>
                
                <button type="submit" class="submit-btn" name="reset_password" onclick="return validatePassword()">Reset Password</button>
            </form>
        </div>
    </div>
    
    <script>
        function validatePassword() {
            var password = document.getElementById('new_password').value;
            var confirmPassword = document.getElementById('confirm_password').value;
            
            if (password.length < 6) {
                alert('Password minimal 6 karakter!');
                return false;
            }
            
            if (password !== confirmPassword) {
                alert('Konfirmasi password tidak cocok!');
                return false;
            }
            
            return true;
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('resetModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
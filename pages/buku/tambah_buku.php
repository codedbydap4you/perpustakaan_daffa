<?php
include '../../../perpustakaan_daffa/config/koneksi.php';

// Memulai session
session_start();

// Memeriksa apakah pengguna sudah login
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $judul = $_POST['judul'] ?? '';
    $pengarang = $_POST['pengarang'] ?? '';
    $penerbit = $_POST['penerbit'] ?? '';
    $tahun_terbit = $_POST['tahun_terbit'] ?? '';
    $genre = $_POST['genre'] ?? '';
    $stok = $_POST['stok'] ?? '';
    
    // Menangani upload gambar
    $cover = '';
    if(isset($_FILES['bookImage']) && $_FILES['bookImage']['error'] == 0) {
        $allowed_ext = array("jpg", "jpeg", "png", "gif");
        $file_name = $_FILES['bookImage']['name'];
        $file_size = $_FILES['bookImage']['size'];
        $file_tmp = $_FILES['bookImage']['tmp_name'];
        
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Cek ekstensi file
        if(in_array($file_ext, $allowed_ext)) {
            // Cek ukuran file (5MB max)
            if($file_size < 5000000) {
                // Buat nama file unik
                $cover = "cover_" . time() . "." . $file_ext;
                $upload_path = '../../../perpustakaan_daffa/jpg/cover/' . $cover;
                
                // Upload file
                if(move_uploaded_file($file_tmp, $upload_path)) {
                    // File berhasil diupload
                } else {
                    echo "Gagal mengupload file!";
                    exit();
                }
            } else {
                echo "Ukuran file terlalu besar! Maksimal 5MB.";
                exit();
            }
        } else {
            echo "Ekstensi file tidak diperbolehkan! Gunakan file JPG, JPEG, PNG, atau GIF.";
            exit();
        }
    }
    
    // Masukkan data ke database
    $sql = "INSERT INTO buku (judul, pengarang, penerbit, tahun_terbit, genre, stok, cover) 
            VALUES ('$judul', '$pengarang', '$penerbit', '$tahun_terbit', '$genre', '$stok', '$cover')";

            // Masukkan data ke database
$sql = "INSERT INTO buku (judul, pengarang, penerbit, tahun_terbit, genre, stok, cover) 
        VALUES ('$judul', '$pengarang', '$penerbit', '$tahun_terbit', '$genre', '$stok', '$cover')";

if (mysqli_query($koneksi, $sql)) {
    // Tambahkan log aktivitas
    $username = $_SESSION['username'];
    $aksi = "Menambahkan buku baru: $judul";
    $waktu = date("Y-m-d H:i:s");
    
    $log_query = "INSERT INTO log_aktivitas (username, aksi, waktu) 
                 VALUES ('$username', '$aksi', '$waktu')";
    mysqli_query($koneksi, $log_query);
    
    header("Location: ../../../perpustakaan_daffa\pages\buku\daftar_buku.php?status=sukses_tambah");
    exit();
} else {
    echo "Error: " . $sql . "<br>" . mysqli_error($koneksi);
}
    
    if (mysqli_query($koneksi, $sql)) {
        header("Location: ../../../perpustakaan_daffa\pages\buku\daftar_buku.php?status=sukses_tambah");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($koneksi);
    }
    
    mysqli_close($koneksi);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Buku Baru - Perpustakaan</title>
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
            padding: 30px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background-color: var(--blue);
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 5px 5px 0 var(--dark);
            border: 3px solid var(--dark);
        }
        
        .header h1 {
            font-family: 'Press Start 2P', cursive;
            font-size: 1.5rem;
            color: var(--yellow);
            text-shadow: 2px 2px 0 var(--dark);
        }
        
        .header .nav-buttons {
            display: flex;
            gap: 15px;
        }
        
        .header .nav-btn {
            padding: 8px 15px;
            background-color: var(--yellow);
            color: var(--dark);
            border: 2px solid var(--dark);
            border-radius: 5px;
            box-shadow: 2px 2px 0 var(--dark);
            text-decoration: none;
            font-family: 'VT323', monospace;
            font-size: 1.1rem;
            transition: all 0.2s ease;
        }
        
        .header .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 2px 4px 0 var(--dark);
        }
        
        .form-container {
            background-color: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 5px 5px 0 var(--dark);
            border: 3px solid var(--dark);
            position: relative;
            overflow: hidden;
        }
        
        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--yellow), var(--red), var(--purple), var(--green), var(--blue));
        }
        
        .form-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-top: 10px;
        }
        
        .form-header-icon {
            margin-right: 20px;
        }
        
        .book-icon {
            width: 60px;
            height: 60px;
        }
        
        .form-title {
            font-family: 'Press Start 2P', cursive;
            font-size: 1.5rem;
            color: var(--purple);
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .form-group {
            flex: 1;
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 10px;
            font-family: 'VT323', monospace;
            font-size: 1.3rem;
            color: var(--dark);
            font-weight: 600;
        }
        
        .form-label.required::after {
            content: " *";
            color: var(--red);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 3px solid var(--dark);
            border-radius: 8px;
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--blue);
            outline: none;
            box-shadow: 0 0 0 3px rgba(5, 140, 215, 0.2);
        }
        
        .form-file-upload {
            background-color: #f5f7fa;
            border: 3px dashed var(--dark);
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .form-file-upload:hover {
            border-color: var(--blue);
            background-color: #e3f2fd;
        }
        
        .form-file-upload input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 10;
        }
        
        .upload-icon {
            font-size: 48px;
            color: var(--purple);
            margin-bottom: 10px;
        }
        
        .upload-text {
            font-family: 'VT323', monospace;
            font-size: 1.3rem;
            color: var(--dark);
        }
        
        .upload-subtext {
            font-family: 'VT323', monospace;
            font-size: 1rem;
            color: #888;
            margin-top: 5px;
        }
        
        .image-preview {
            margin-top: 15px;
            text-align: center;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            border: 3px solid var(--dark);
            box-shadow: 3px 3px 0 var(--dark);
            display: none;
        }
        
        .btn-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 30px;
            gap: 15px;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            font-family: 'VT323', monospace;
            font-size: 1.3rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 3px solid var(--dark);
            box-shadow: 3px 3px 0 var(--dark);
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 3px 6px 0 var(--dark);
        }
        
        .btn:active {
            transform: translateY(0);
            box-shadow: 1px 1px 0 var(--dark);
        }
        
        .btn-primary {
            background-color: var(--green);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #007a4c;
        }
        
        .btn-secondary {
            background-color: var(--yellow);
            color: var(--dark);
        }
        
        .btn-secondary:hover {
            background-color: #e6a93d;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 12px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--dark);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--yellow);
            border: 2px solid var(--dark);
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .btn-container {
                flex-direction: column;
            }
            
            .form-container {
                padding: 20px;
            }
            
            body {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>TAMBAH BUKU BARU</h1>
            <div class="nav-buttons">
                <a href="daftar_buku.php" class="nav-btn">
                    <i class="fas fa-book"></i> Daftar Buku
                </a>
                <a href="dashboard_admin.php" class="nav-btn">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
        </div>
        
        <div class="form-container">
            <div class="form-header">
                <div class="form-header-icon">
                    <svg class="book-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                        <!-- Retro Book Icon SVG -->
                        <rect x="8" y="8" width="48" height="48" rx="4" fill="#552CB7" stroke="#222034" stroke-width="3"/>
                        <rect x="12" y="12" width="40" height="40" rx="2" fill="#F7E9D6" stroke="#222034" stroke-width="2"/>
                        <path d="M18 16 L46 16" stroke="#FFC567" stroke-width="3" stroke-linecap="round"/>
                        <path d="M18 24 L46 24" stroke="#FD5A46" stroke-width="3" stroke-linecap="round"/>
                        <path d="M18 32 L38 32" stroke="#FFC567" stroke-width="3" stroke-linecap="round"/>
                        <path d="M18 40 L42 40" stroke="#FD5A46" stroke-width="3" stroke-linecap="round"/>
                        <path d="M18 48 L34 48" stroke="#FFC567" stroke-width="3" stroke-linecap="round"/>
                        <rect x="10" y="6" width="4" height="52" rx="1" fill="#00995E" stroke="#222034" stroke-width="2"/>
                    </svg>
                </div>
                <h2 class="form-title">TAMBAH BUKU</h2>
            </div>
            
            <form id="addBookForm" method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="judul" class="form-label required">Judul Buku</label>
                        <input type="text" id="judul" name="judul" class="form-control" required placeholder="Masukkan judul buku">
                    </div>
                    
                    <div class="form-group">
                        <label for="pengarang" class="form-label required">Pengarang</label>
                        <input type="text" id="pengarang" name="pengarang" class="form-control" required placeholder="Masukkan nama pengarang">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="penerbit" class="form-label required">Penerbit</label>
                        <input type="text" id="penerbit" name="penerbit" class="form-control" required placeholder="Masukkan nama penerbit">
                    </div>
                    
                    <div class="form-group">
                        <label for="tahun_terbit" class="form-label required">Tahun Terbit</label>
                        <input type="number" id="tahun_terbit" name="tahun_terbit" class="form-control" min="1900" max="2025" required placeholder="Masukkan tahun terbit">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="genre" class="form-label required">Genre</label>
                        <input type="text" id="genre" name="genre" class="form-control" required placeholder="Masukkan genre buku">
                        </input>
                    </div>
                    
                    <div class="form-group">
                        <label for="stok" class="form-label required">Stok</label>
                        <input type="number" id="stok" name="stok" class="form-control" min="0" required placeholder="Masukkan jumlah stok">
                    </div>
                </div>
                
               
                
                <div class="form-group">
                    <label class="form-label">Cover Buku</label>
                    <div class="form-file-upload" id="dropArea">
                        <input type="file" id="bookImage" name="bookImage" accept="image/*" onchange="previewImage(this)">
                        <div id="uploadText">
                            <div class="upload-icon">
                                <i class="fas fa-camera"></i>
                            </div>
                            <p class="upload-text">Klik atau drop gambar di sini</p>
                            <p class="upload-subtext">Format: JPG, PNG, GIF (Maks. 5MB)</p>
                        </div>
                        <div class="image-preview">
                            <img id="imagePreview" src="#" alt="Preview Cover">
                        </div>
                    </div>
                </div>
                
                <div class="btn-container">
                    <a href="daftar_buku.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Buku
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Preview gambar yang diunggah
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const uploadText = document.getElementById('uploadText');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    uploadText.style.display = 'none';
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Setup drag and drop
        const dropArea = document.getElementById('dropArea');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropArea.classList.add('highlight');
            dropArea.style.borderColor = '#058CD7';
            dropArea.style.backgroundColor = '#e3f2fd';
        }
        
        function unhighlight() {
            dropArea.classList.remove('highlight');
            dropArea.style.borderColor = '#222034';
            dropArea.style.backgroundColor = '#f5f7fa';
        }
        
        dropArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length) {
                document.getElementById('bookImage').files = files;
                previewImage(document.getElementById('bookImage'));
            }
        }
        
    </script>
</body>
</html>
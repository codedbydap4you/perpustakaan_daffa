<?php
// Memulai session
session_start();

// Memeriksa apakah pengguna sudah login
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'user') {
    header("Location: ../../login.php");
    exit();
}

// Include file koneksi
include '../../../perpustakaan_daffa/config/koneksi.php';

// Dapatkan ID user yang sedang login
$username = $_SESSION['username'];
$query_user = "SELECT id FROM users WHERE username = '$username'";
$result_user = mysqli_query($koneksi, $query_user);
$user = mysqli_fetch_assoc($result_user);
$user_id = $user['id'];

// Proses peminjaman buku
if (isset($_GET['pinjam'])) {
    $buku_id = (int)$_GET['pinjam'];
    
    // Periksa apakah buku tersedia
    $query_cek_stok = "SELECT stok FROM buku WHERE id = $buku_id";
    $result_cek_stok = mysqli_query($koneksi, $query_cek_stok);
    $row_stok = mysqli_fetch_assoc($result_cek_stok);
    
    if ($row_stok['stok'] > 0) {
        // Kurangi stok buku
        $query_kurangi_stok = "UPDATE buku SET stok = stok - 1 WHERE id = $buku_id";
        mysqli_query($koneksi, $query_kurangi_stok);
        
        // Tambah data peminjaman
        $query_pinjam = "INSERT INTO peminjaman (user_id, buku_id, tanggal_pinjam, status) 
                         VALUES ($user_id, $buku_id, NOW(), 'dipinjam')";
        mysqli_query($koneksi, $query_pinjam);
        
        // Redirect dengan pesan sukses
        header("Location: list_buku.php?pesan=berhasil_pinjam");
        exit();
    } else {
        // Redirect dengan pesan stok habis
        header("Location: list_buku.php?pesan=stok_habis");
        exit();
    }
}

// Proses pengembalian buku
if (isset($_GET['kembali'])) {
    $buku_id = (int)$_GET['kembali'];
    
    // Kembalikan stok buku
    $query_kembalikan_stok = "UPDATE buku SET stok = stok + 1 WHERE id = $buku_id";
    mysqli_query($koneksi, $query_kembalikan_stok);
    
    // Update status peminjaman
    $query_kembali = "UPDATE peminjaman SET status = 'kembali', tanggal_kembali = NOW() 
                      WHERE user_id = $user_id AND buku_id = $buku_id AND status = 'dipinjam'";
    mysqli_query($koneksi, $query_kembali);
    
    // Redirect dengan pesan sukses
    header("Location: list_buku.php?pesan=berhasil_kembali");
    exit();
}

// Konfigurasi pagination
$buku_per_halaman = 12; // 3x4 grid
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$mulai = ($halaman - 1) * $buku_per_halaman;

// Query untuk mendapatkan total buku
$query_count = "SELECT COUNT(*) as total FROM buku";
$result_count = mysqli_query($koneksi, $query_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_buku = $row_count['total'];
$total_halaman = ceil($total_buku / $buku_per_halaman);

// Query untuk mendapatkan daftar buku yang sudah dipinjam user
$query_buku_dipinjam = "SELECT buku_id FROM peminjaman WHERE user_id = $user_id AND status = 'dipinjam'";
$result_buku_dipinjam = mysqli_query($koneksi, $query_buku_dipinjam);
$buku_dipinjam = [];
while ($row = mysqli_fetch_assoc($result_buku_dipinjam)) {
    $buku_dipinjam[] = $row['buku_id'];
}

// Query untuk mendapatkan daftar buku dengan pagination
$query_buku = "SELECT * FROM buku ORDER BY id DESC LIMIT $mulai, $buku_per_halaman";
$result_buku = mysqli_query($koneksi, $query_buku);

// Penanganan pencarian
$pencarian = "";
if (isset($_GET['cari'])) {
    $pencarian = mysqli_real_escape_string($koneksi, $_GET['cari']);
    $query_buku = "SELECT * FROM buku WHERE 
                  judul LIKE '%$pencarian%' OR 
                  pengarang LIKE '%$pencarian%' OR 
                  penerbit LIKE '%$pencarian%' OR 
                  genre LIKE '%$pencarian%' 
                  ORDER BY id DESC LIMIT $mulai, $buku_per_halaman";
    $result_buku = mysqli_query($koneksi, $query_buku);
    
    // Update total untuk pagination
    $query_count = "SELECT COUNT(*) as total FROM buku WHERE 
                   judul LIKE '%$pencarian%' OR 
                   pengarang LIKE '%$pencarian%' OR 
                   penerbit LIKE '%$pencarian%' OR 
                   genre LIKE '%$pencarian%'";
    $result_count = mysqli_query($koneksi, $query_count);
    $row_count = mysqli_fetch_assoc($result_count);
    $total_buku = $row_count['total'];
    $total_halaman = ceil($total_buku / $buku_per_halaman);
}

// Penanganan filter genre
if (isset($_GET['genre']) && $_GET['genre'] != 'semua') {
    $genre_filter = mysqli_real_escape_string($koneksi, $_GET['genre']);
    $query_buku = "SELECT * FROM buku WHERE genre = '$genre_filter' ORDER BY id DESC LIMIT $mulai, $buku_per_halaman";
    $result_buku = mysqli_query($koneksi, $query_buku);
    
    // Update total untuk pagination
    $query_count = "SELECT COUNT(*) as total FROM buku WHERE genre = '$genre_filter'";
    $result_count = mysqli_query($koneksi, $query_count);
    $row_count = mysqli_fetch_assoc($result_count);
    $total_buku = $row_count['total'];
    $total_halaman = ceil($total_buku / $buku_per_halaman);
}

// Mendapatkan daftar genre untuk filter
$query_genre = "SELECT DISTINCT genre FROM buku ORDER BY genre";
$result_genre = mysqli_query($koneksi, $query_genre);
$genre_list = [];
while ($row = mysqli_fetch_assoc($result_genre)) {
    $genre_list[] = $row['genre'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Buku - Perpustakaan Online</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=VT323&family=Press+Start+2P&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Seluruh style tetap sama seperti sebelumnya */
                :root {
            --yellow: #FFC567;
            --red: #FD5A46;
            --purple: #552CB7;
            --green: #00995E;
            --blue: #058CD7;
            --dark: #222034;
            --light: #F7E9D6;
            --text: #333;
            --sidebar-width: 250px;
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
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--purple);
            color: white;
            position: fixed;
            height: 100%;
            overflow-y: auto;
            transition: all 0.3s ease;
            box-shadow: 5px 0 15px rgba(0,0,0,0.2);
            z-index: 100;
            border-right: 4px solid var(--yellow);
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            background-color: var(--dark);
            border-bottom: 4px solid var(--yellow);
        }
        
        .sidebar-header h2 {
            font-family: 'Press Start 2P', cursive;
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: var(--yellow);
            text-shadow: 2px 2px 0 var(--red);
        }
        
        .sidebar-header p {
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
            color: var(--light);
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 8px 12px;
            border-radius: 5px;
            border: 2px solid transparent;
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
        }
        
        .sidebar-menu a:hover {
            background-color: var(--dark);
            border: 2px solid var(--yellow);
            transform: translateX(3px);
        }
        
        .sidebar-menu a.active {
            background-color: var(--blue);
            border: 2px solid var(--yellow);
            box-shadow: 3px 3px 0 var(--dark);
        }
        
        .sidebar-menu a i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-menu .divider {
            height: 4px;
            background-color: var(--yellow);
            margin: 15px 20px;
            border-radius: 2px;
        }
        
        .content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background-color: var(--blue);
            padding: 15px 20px;
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
        
        .search-filter-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .search-box {
            flex: 1;
            display: flex;
            max-width: 500px;
        }
        
        .search-box input {
            flex: 1;
            padding: 10px 15px;
            border: 3px solid var(--dark);
            border-radius: 8px 0 0 8px;
            font-size: 1rem;
            outline: none;
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
        }
        
        .search-box button {
            padding: 10px 15px;
            background-color: var(--blue);
            color: white;
            border: 3px solid var(--dark);
            border-left: none;
            border-radius: 0 8px 8px 0;
            cursor: pointer;
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
        }
        
        .search-box button:hover {
            background-color: var(--purple);
        }
        
        .filter-box {
            display: flex;
            align-items: center;
        }
        
        .filter-box select {
            padding: 10px 15px;
            border: 3px solid var(--dark);
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
            cursor: pointer;
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
            background-color: white;
        }
        
        .filter-box select:focus {
            border-color: var(--blue);
        }
        
        .add-button-container {
            text-align: right;
            margin-bottom: 20px;
        }
        
        .add-button {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background-color: var(--green);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            border: 3px solid var(--dark);
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 3px 3px 0 var(--dark);
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
        }
        
        .add-button:hover {
            background-color: var(--purple);
            transform: translateY(-3px);
            box-shadow: 3px 6px 0 var(--dark);
        }
        
        .add-button i {
            margin-right: 10px;
        }
        
    /* Updated books grid to match the screenshot layout */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .book-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 5px 5px 0 var(--dark);
            border: 3px solid var(--dark);
            transition: transform 0.3s ease;
            position: relative;
            display: flex;
            height: 180px;
        }
        
        .book-card:hover {
            transform: translateY(-5px);
        }
        
        .book-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--yellow), var(--red), var(--purple), var(--green), var(--blue));
        }
        
        .book-cover {
            width: 120px;
            height: 100%;
            object-fit: cover;
            border-right: 3px solid var(--dark);
        }
        
        .book-details {
            padding: 15px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .book-title {
            font-family: 'Rubik', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--purple);
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .book-info {
            margin-bottom: 8px;
            font-family: 'VT323', monospace;
            font-size: 1.1rem;
        }
        
        .book-info span {
            font-weight: 600;
            color: var(--red);
        }
        
        .book-genre {
            display: inline-block;
            padding: 5px 10px;
            background-color: var(--blue);
            color: white;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-top: auto;
            border: 2px solid var(--dark);
            font-family: 'VT323', monospace;
        }
        
        .stok-indicator {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-top: 5px;
            margin-right: 5px;
            border: 2px solid var(--dark);
            font-family: 'VT323', monospace;
        }
        
        .stok-low {
            background-color: var(--red);
            color: white;
        }
        
        .stok-ok {
            background-color: var(--green);
            color: white;
        }
        
        .book-actions {
            display: flex;
            margin-top: 15px;
            gap: 10px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            list-style: none;
            margin-top: 40px;
        }
        
        .pagination li {
            margin: 0 5px;
        }
        
        .pagination a {
            display: block;
            padding: 8px 15px;
            background-color: white;
            border: 2px solid var(--dark);
            border-radius: 5px;
            text-decoration: none;
            color: var(--dark);
            font-family: 'VT323', monospace;
            font-size: 1.1rem;
            transition: all 0.2s ease;
        }
        
        .pagination a:hover {
            background-color: var(--yellow);
        }
        
        .pagination .active a {
            background-color: var(--purple);
            color: white;
        }
        
        .no-books {
            text-align: center;
            padding: 50px 0;
            background-color: white;
            border-radius: 10px;
            border: 3px solid var(--dark);
            box-shadow: 5px 5px 0 var(--dark);
        }
        
        .no-books h3 {
            font-family: 'Press Start 2P', cursive;
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: var(--red);
        }
        
        .no-books p {
            font-family: 'VT323', monospace;
            font-size: 1.3rem;
            margin-bottom: 20px;
        }
        
        .no-books a {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--green);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid var(--dark);
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
        }
        
        .no-books a:hover {
            background-color: var(--purple);
        }
        
        /* Alerts for feedback */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 3px solid var(--dark);
            box-shadow: 3px 3px 0 var(--dark);
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
        }
        
        .alert-success {
            background-color: var(--green);
            color: white;
        }
        
        .alert-danger {
            background-color: var(--red);
            color: white;
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
        
        /* Sidebar toggle button */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 999;
            padding: 10px;
            background-color: var(--red);
            color: white;
            border: 3px solid var(--dark);
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.2rem;
            box-shadow: 3px 3px 0 var(--dark);
            transition: all 0.3s ease;
        }
        
        .sidebar-toggle:hover {
            background-color: var(--yellow);
            color: var(--dark);
        }
        
        /* Confirm Delete Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            border: 3px solid var(--dark);
            box-shadow: 5px 5px 0 var(--dark);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        
        .modal-content h3 {
            font-family: 'Press Start 2P', cursive;
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: var(--red);
        }
        
        .modal-content p {
            font-family: 'VT323', monospace;
            font-size: 1.3rem;
            margin-bottom: 20px;
        }
        
        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .modal-btn {
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid var(--dark);
            font-family: 'VT323', monospace;
            font-size: 1.1rem;
        }
        
        .btn-cancel {
            background-color: var(--blue);
            color: white;
        }
        
        .btn-confirm {
            background-color: var(--red);
            color: white;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .content {
                margin-left: 0;
            }
            
            .sidebar-toggle {
                display: block;
            }
        }
        
        @media (max-width: 768px) {
            .search-filter-container {
                flex-direction: column;
            }
            
            .search-box {
                max-width: 100%;
                margin-bottom: 10px;
            }
            
            .filter-box {
                width: 100%;
            }
            
            .filter-box select {
                width: 100%;
            }
            
            .add-button-container {
                text-align: center;
            }
            
            .books-grid {
                grid-template-columns: 1fr;
            }
        }
        /* Tambahkan style baru untuk pesan */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 3px solid var(--dark);
            box-shadow: 3px 3px 0 var(--dark);
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
        }
        
        .alert-success {
            background-color: var(--green);
            color: white;
        }
        
        .alert-danger {
            background-color: var(--red);
            color: white;
        }
        
        .btn-pinjam {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            margin-left: 6px;
            padding: 8px 25px;
            background-color: var(--purple);
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-family: 'VT323', monospace;
            font-size: 1.1rem;
            border: 2px solid var(--dark);
            transition: all 0.2s ease;
        }
        
        .btn-pinjam:hover {
            background-color: var(--blue);
            transform: translateY(-2px);
        }
        
        .btn-pinjam i {
            margin-right: 8px;
        }
               /* Toggle button inside sidebar */
.sidebar-toggle-inside {
    padding: 10px;
    text-align: right;
    border-bottom: 2px solid var(--yellow);
}

.sidebar-toggle-inside button {
    background-color: var(--blue);
    color: white;
    border: 2px solid var(--dark);
    border-radius: 5px;
    padding: 5px 10px;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.sidebar-toggle-inside button:hover {
    background-color: var(--yellow);
    color: var(--dark);
}

/* Collapsed sidebar */
.sidebar.collapsed {
    width: 80px;
    min-width: 80px;
}

.sidebar.collapsed .sidebar-header {
    display: none; /* Menyembunyikan header E-Library dan Panel Admin */
}

.sidebar.collapsed .sidebar-menu a span {
    display: none;
}

.sidebar.collapsed .sidebar-menu a {
    justify-content: center;
    padding: 12px 5px;
}

.sidebar.collapsed .sidebar-menu a i {
    margin-right: 0;
}

.sidebar.collapsed .sidebar-toggle-inside {
    border-bottom: none;
    text-align: center;
    padding: 30px 18px 0px;
}

.sidebar.collapsed .sidebar-toggle-inside button {
    width: 40px;
    height: 40px;
    border-radius: 20%;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Content adjustment when sidebar is collapsed */
.container.sidebar-collapsed .content {
    margin-left: 75px;  /* Ubah dari 60px menjadi 80px untuk menyelaraskan dengan lebar sidebar */
}

@media (max-width: 992px) {
    .sidebar.collapsed {
        transform: translateX(0);
        width: 60px;
    }
    
    .container.sidebar-collapsed .content {
        margin-left: 60px;
    }
}
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>E-LIBRARY</h2>
                <p>Panel User</p>
            </div>

             <div class="sidebar-toggle-inside">
    <button id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>
</div>

            <div class="sidebar-menu">
                <a href="dashboard_user.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="list_buku.php" class="active">
                    <i class="fas fa-book"></i>
                    <span>Daftar Buku</span>
                </a>
                <div class="divider"></div>
                <a href="buku_dipinjam.php">
                    <i class="fas fa-bookmark"></i>
                    <span>Buku Dipinjam</span>
                </a>
                <div class="divider"></div>
                <a >
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo $_SESSION['username']; ?></span>
                </a>
                <a href="/../../../perpustakaan_daffa\logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <div class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </div>
        
        <div class="content">
            <div class="header">
                <h1>DAFTAR BUKU</h1>
                <div class="user-info">
                    <span class="user-name"><?php echo $_SESSION['username']; ?></span>
                    <small>Users</small>
                </div>
            </div>
            
            <?php 
            // Tampilkan pesan 
            if (isset($_GET['pesan'])) {
                $pesan = $_GET['pesan'];
                if ($pesan == 'berhasil_pinjam') {
                    echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Buku berhasil dipinjam!</div>';
                } elseif ($pesan == 'stok_habis') {
                    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Stok buku habis!</div>';
                } elseif ($pesan == 'berhasil_kembali') {
                    echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Buku berhasil dikembalikan!</div>';
                }
            }
            ?>
            
            <div class="search-filter-container">
                <form class="search-box" action="" method="get">
                    <input type="text" name="cari" placeholder="Cari judul, pengarang, penerbit..." value="<?php echo $pencarian; ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
                
                <div class="filter-box">
                    <form action="" method="get">
                        <select name="genre" onchange="this.form.submit()">
                            <option value="semua">Semua Genre</option>
                            <?php foreach ($genre_list as $genre): ?>
                                <option value="<?php echo $genre; ?>" <?php echo (isset($_GET['genre']) && $_GET['genre'] == $genre) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($genre); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>

            
            <?php if (mysqli_num_rows($result_buku) > 0): ?>
                <div class="books-grid">
                    
                    <?php while ($row = mysqli_fetch_assoc($result_buku)): ?>
                        <div class="book-card">

                           <?php if (!empty($row['cover'])): ?>
                            <a href="detail_buku_user.php?id=<?php echo $row['id']; ?>">
                                <img src="../../../perpustakaan_daffa/jpg/cover/<?php echo $row['cover']; ?>" alt="<?php echo $row['judul']; ?>" class="book-cover">
                            </a>
                        <?php else: ?>
                            <a href="detail_buku_user.php?id=<?php echo $row['id']; ?>">
                                <img src="https://via.placeholder.com/400x600?text=No+Cover" alt="No Cover" class="book-cover">
                            </a>
                        <?php endif; ?>
                            
                            <div class="book-details">
                                <h3 class="book-title">
                                    <a href="detail_buku.php?id=<?php echo $row['id']; ?>" style="text-decoration: none; color: var(--purple);">
                                        <?php echo $row['judul']; ?>
                                    </a>
                                </h3>
                                
                                <div class="book-info">
                                  <?php echo $row['pengarang']; ?>
                                </div>
                                
                                <div style="margin-top: auto; display: flex; flex-wrap: wrap; gap: 5px;">
                                    <span class="book-genre"><?php echo ucfirst($row['genre']); ?></span>
                                    
                                    <?php if ($row['stok'] < 5): ?>
                                        <span class="stok-indicator stok-low">
                                            <i class="fas fa-exclamation-triangle"></i> Stok: <?php echo $row['stok']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="stok-indicator stok-ok">
                                            <i class="fas fa-check-circle"></i> Stok: <?php echo $row['stok']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <ul class="pagination">
                    <?php
                    // Tombol Previous
                    if ($halaman > 1) {
                        echo "<li><a href='?halaman=" . ($halaman - 1);
                        if (isset($_GET['cari'])) echo "&cari=" . urlencode($pencarian);
                        if (isset($_GET['genre']) && $_GET['genre'] != 'semua') echo "&genre=" . urlencode($_GET['genre']);
                        echo "'><i class='fas fa-chevron-left'></i></a></li>";
                    }

                    // Tampilkan nomor halaman
                    for ($i = 1; $i <= $total_halaman; $i++) {
                        echo "<li class='" . ($halaman == $i ? 'active' : '') . "'>";
                        echo "<a href='?halaman=$i";
                        if (isset($_GET['cari'])) echo "&cari=" . urlencode($pencarian);
                        if (isset($_GET['genre']) && $_GET['genre'] != 'semua') echo "&genre=" . urlencode($_GET['genre']);
                        echo "'>" . $i . "</a>";
                        echo "</li>";
                    }

                    // Tombol Next
                    if ($halaman < $total_halaman) {
                        echo "<li><a href='?halaman=" . ($halaman + 1);
                        if (isset($_GET['cari'])) echo "&cari=" . urlencode($pencarian);
                        if (isset($_GET['genre']) && $_GET['genre'] != 'semua') echo "&genre=" . urlencode($_GET['genre']);
                        echo "'><i class='fas fa-chevron-right'></i></a></li>";
                    }
                    ?>
                </ul>
            <?php else: ?>
                <div class="no-books">
                    <h3>Tidak Ada Buku</h3>
                    <p>Maaf, tidak ada buku yang ditemukan.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Sidebar toggle functionality
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');
        const content = document.querySelector('.content');
        
        sidebarToggle.addEventListener('click', () => {
            sidebar.style.transform = sidebar.style.transform === 'translateX(0px)' ? 'translateX(-100%)' : 'translateX(0px)';
            content.style.marginLeft = content.style.marginLeft === '0px' ? 'var(--sidebar-width)' : '0px';
        });
            // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }

 // Sidebar internal toggle functionality
const toggleSidebarBtn = document.getElementById('toggleSidebar');
const containerElement = document.querySelector('.container');

if (toggleSidebarBtn) {
    toggleSidebarBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        containerElement.classList.toggle('sidebar-collapsed');
        
        // Simpan status sidebar di localStorage
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    });
}

// Check localStorage when page loads
document.addEventListener('DOMContentLoaded', () => {
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (sidebarCollapsed) {
        sidebar.classList.add('collapsed');
        containerElement.classList.add('sidebar-collapsed');
    }
});
    </script>
</body>
</html>
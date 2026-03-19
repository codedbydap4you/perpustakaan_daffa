<?php
// Memulai session
session_start();

// Memeriksa apakah pengguna sudah login sebagai user
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'user') {
    header("Location: ../../login.php");
    exit();
}

// Include file koneksi
include '../../../perpustakaan_daffa/config/koneksi.php';

// Memeriksa apakah id buku ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: list_buku.php");
    exit();
}

$id_buku = (int)$_GET['id'];

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
        header("Location: detail_buku_user.php?id=$buku_id&pesan=berhasil_pinjam");
        exit();
    } else {
        // Redirect dengan pesan stok habis
        header("Location: detail_buku_user.php?id=$buku_id&pesan=stok_habis");
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
    header("Location: detail_buku_user.php?id=$buku_id&pesan=berhasil_kembali");
    exit();
}

// Proses hapus komentar
if (isset($_GET['hapus_komentar'])) {
    $komentar_id = (int)$_GET['hapus_komentar'];
    
    // Periksa apakah komentar milik user yang login
    $query_cek_komentar = "SELECT user_id FROM komentar WHERE id = $komentar_id";
    $result_cek_komentar = mysqli_query($koneksi, $query_cek_komentar);
    $komentar_data = mysqli_fetch_assoc($result_cek_komentar);
    
    if ($komentar_data && $komentar_data['user_id'] == $user_id) {
        // Hapus komentar
        $query_hapus = "DELETE FROM komentar WHERE id = $komentar_id AND user_id = $user_id";
        if (mysqli_query($koneksi, $query_hapus)) {
            header("Location: detail_buku_user.php?id=$id_buku&pesan=komentar_dihapus");
            exit();
        } else {
            header("Location: detail_buku_user.php?id=$id_buku&pesan=gagal_hapus_komentar");
            exit();
        }
    } else {
        header("Location: detail_buku_user.php?id=$id_buku&pesan=tidak_berhak_hapus");
        exit();
    }
}


// Proses submit komentar
if (isset($_POST['submit_komentar'])) {
    $komentar = mysqli_real_escape_string($koneksi, $_POST['komentar']);
    $rating = (int)$_POST['rating'];
    
    if (!empty($komentar) && $rating >= 1 && $rating <= 5) {
        $query_komentar = "INSERT INTO komentar (user_id, buku_id, komentar, rating) 
                          VALUES ($user_id, $id_buku, '$komentar', $rating)";
        
        if (mysqli_query($koneksi, $query_komentar)) {
            header("Location: detail_buku_user.php?id=$id_buku&pesan=komentar_berhasil");
            exit();
        } else {
            header("Location: detail_buku_user.php?id=$id_buku&pesan=komentar_gagal");
            exit();
        }
    } else {
        header("Location: detail_buku_user.php?id=$id_buku&pesan=komentar_kosong");
        exit();
    }
}

// Query untuk mendapatkan detail buku
$query_buku = "SELECT * FROM buku WHERE id = $id_buku";
$result_buku = mysqli_query($koneksi, $query_buku);

// Jika buku tidak ditemukan
if (mysqli_num_rows($result_buku) == 0) {
    header("Location: list_buku.php");
    exit();
}

$buku = mysqli_fetch_assoc($result_buku);

// Cek apakah buku sudah dipinjam user
$query_buku_dipinjam = "SELECT buku_id FROM peminjaman WHERE user_id = $user_id AND buku_id = $id_buku AND status = 'dipinjam'";
$result_buku_dipinjam = mysqli_query($koneksi, $query_buku_dipinjam);
$sudah_dipinjam = mysqli_num_rows($result_buku_dipinjam) > 0;

// Query untuk mendapatkan komentar buku
$query_komentar = "SELECT k.*, u.username 
                   FROM komentar k 
                   JOIN users u ON k.user_id = u.id 
                   WHERE k.buku_id = $id_buku 
                   ORDER BY k.tanggal_komentar DESC";
$result_komentar = mysqli_query($koneksi, $query_komentar);

// Hitung rata-rata rating
$query_avg_rating = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_rating 
                     FROM komentar 
                     WHERE buku_id = $id_buku";
$result_avg_rating = mysqli_query($koneksi, $query_avg_rating);
$rating_data = mysqli_fetch_assoc($result_avg_rating);
$avg_rating = round($rating_data['avg_rating'], 1);
$total_rating = $rating_data['total_rating'];

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Buku - <?php echo $buku['judul']; ?></title>
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
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            display: block;
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
            color: white;
        }
        
        .user-info small {
            font-family: 'VT323', monospace;
            color: var(--yellow);
        }
        
        .book-detail {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 5px 5px 0 var(--dark);
            border: 3px solid var(--dark);
            margin-bottom: 30px;
        }
        
        .book-detail::before {
            content: '';
            display: block;
            height: 8px;
            background: linear-gradient(90deg, var(--yellow), var(--red), var(--purple), var(--green), var(--blue));
        }
        
        .book-content {
            display: flex;
            padding: 20px;
        }
        
        .book-cover-container {
            flex: 0 0 300px;
            margin-right: 30px;
        }
        
        .book-cover {
            width: 100%;
            height: 400px;
            object-fit: contain;
            background-color: #f5f5f5;
            border: 3px solid var(--dark);
            box-shadow: 5px 5px 0 var(--dark);
        }
        
        .book-info {
            flex: 1;
        }
        
        .book-title {
            font-family: 'Rubik', sans-serif;
            font-size: 1.9rem;
            margin-bottom: 20px;
            color: var(--purple);
            text-shadow: 1px 1px 0 rgba(0,0,0,0.1);
        }
        
        .book-meta {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .book-meta-label {
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--red);
        }
        
        .book-meta-value {
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
        }
        
        .book-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .book-tag {
            display: inline-block;
            padding: 8px 15px;
            background-color: var(--blue);
            color: white;
            border-radius: 20px;
            font-size: 1rem;
            border: 2px solid var(--dark);
            font-family: 'VT323', monospace;
        }
        
        .stok-tag {
            display: inline-flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 1rem;
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
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            font-family: 'VT323', monospace;
            font-size: 1.1rem;
            border: 2px solid var(--dark);
            box-shadow: 3px 3px 0 var(--dark);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 3px 6px 0 var(--dark);
        }
        
        .btn:active {
            transform: translateY(0);
            box-shadow: 1px 1px 0 var(--dark);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-back {
            background-color: var(--green);
            color: white;
        }
        
        .btn-pinjam {
            background-color: var(--purple);
            color: white;
        }
        
        .btn-kembali {
            background-color: var(--green);
            color: white;
        }
        
        .btn-disabled {
            background-color: var(--red);
            color: white;
            cursor: not-allowed;
        }
        
        .btn-disabled:hover {
            transform: none;
            box-shadow: 3px 3px 0 var(--dark);
        }
        
        .description-section {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 5px 5px 0 var(--dark);
            border: 3px solid var(--dark);
            margin-bottom: 30px;
        }
        
        .section-header {
            background-color: var(--purple);
            color: white;
            padding: 15px 20px;
            font-family: 'Press Start 2P', cursive;
            font-size: 1.2rem;
            border-bottom: 3px solid var(--dark);
        }
        
        .section-content {
            padding: 20px;
        }
        
        .description-text {
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
            line-height: 1.5;
            color: var(--text);
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

        .comments-section {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 5px 5px 0 var(--dark);
            border: 3px solid var(--dark);
            margin-bottom: 30px;
        }
        
        .comment-list {
            padding: 20px;
        }
        
        .comment {
            background-color: var(--light);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border: 2px solid var(--dark);
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-family: 'VT323', monospace;
        }
        
        .comment-author {
            font-weight: bold;
            color: var(--purple);
            font-size: 1.1rem;
        }
        
        .comment-date {
            color: var(--text);
            opacity: 0.7;
        }
        
        .comment-text {
            font-family: 'Rubik', sans-serif;
            font-size: 1rem;
            line-height: 1.4;
        }
        
        .comment-form {
            padding: 20px;
            background-color: var(--light);
            border-top: 2px dashed var(--dark);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-family: 'VT323', monospace;
            font-size: 1.1rem;
            color: var(--purple);
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid var(--dark);
            border-radius: 5px;
            font-family: 'Rubik', sans-serif;
            font-size: 1rem;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        .btn-primary {
            background-color: var(--green);
            color: white;
        }
        
        .btn-secondary {
            background-color: var(--blue);
            color: var(--dark);
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
            
            .book-content {
                flex-direction: column;
            }
            
            .book-cover-container {
                margin-right: 0;
                margin-bottom: 20px;
                max-width: 250px;
            }
        }
        
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }

        .rating-display {
    display: flex;
    align-items: center;
    margin-left: 10px;
}

.rating-stars {
    display: flex;
    margin-right: 5px;
}

.star {
    color: #FFD700;
    font-size: 1rem;
    margin-right: 2px;
}

.star.empty {
    color: #ddd;
}

.rating-form {
    margin-bottom: 15px;
}

.rating-input {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.rating-input label {
    margin-right: 10px;
    font-family: 'VT323', monospace;
    font-size: 1.1rem;
    color: var(--purple);
}

.star-rating {
    display: flex;
    gap: 5px;
}

.star-rating input[type="radio"] {
    display: none;
}

.star-rating label {
    font-size: 1.5rem;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
}

.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input[type="radio"]:checked ~ label {
    color: #FFD700;
}

.book-rating-summary {
    background-color: var(--light);
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    border: 2px solid var(--dark);
    text-align: center;
}

.avg-rating {
    font-family: 'Press Start 2P', cursive;
    font-size: 1.2rem;
    color: var(--dark);
    margin-bottom: 5px;
}

.total-reviews {
    font-family: 'VT323', monospace;
    font-size: 1rem;
    color: var(--dark);
}

.btn-delete-comment {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            background-color: var(--red);
            color: white;
            text-decoration: none;
            border-radius: 3px;
            font-size: 0.8rem;
            font-family: 'VT323', monospace;
            margin-top: 5px;
            border: 1px solid var(--dark);
            transition: all 0.2s ease;
        }
        
        .btn-delete-comment:hover {
            background-color: #e74c3c;
            transform: translateY(-1px);
        }
        
        .btn-delete-comment i {
            margin-right: 5px;
            font-size: 0.7rem;
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
                <a href="/../../../perpustakaan_daffa/logout.php">
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
                <h1>DETAIL BUKU</h1>
                <div class="user-info">
                    <span class="user-name"><?php echo $_SESSION['username']; ?></span>
                    <small>User</small>
                </div>
            </div>
            
<?php 
// Ganti bagian "Tampilkan pesan" dengan kode ini
if (isset($_GET['pesan'])) {
    $pesan = $_GET['pesan'];
    if ($pesan == 'berhasil_pinjam') {
        echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Buku berhasil dipinjam!</div>';
    } elseif ($pesan == 'stok_habis') {
        echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Stok buku habis!</div>';
    } elseif ($pesan == 'berhasil_kembali') {
        echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Buku berhasil dikembalikan!</div>';
    } elseif ($pesan == 'komentar_berhasil') {
        echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Komentar berhasil ditambahkan!</div>';
    } elseif ($pesan == 'komentar_gagal') {
        echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Gagal menambahkan komentar!</div>';
    } elseif ($pesan == 'komentar_kosong') {
        echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Komentar dan rating harus diisi!</div>';
    } elseif ($pesan == 'komentar_dihapus') {
        echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Komentar berhasil dihapus!</div>';
    } elseif ($pesan == 'gagal_hapus_komentar') {
        echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Gagal menghapus komentar!</div>';
    } elseif ($pesan == 'tidak_berhak_hapus') {
        echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Anda tidak berhak menghapus komentar ini!</div>';
        }
}
?>
            
            <div class="book-detail">
                <div class="book-content">
                    <div class="book-cover-container">
                        <?php if (!empty($buku['cover'])): ?>
                            <img src="../../../perpustakaan_daffa/jpg/cover/<?php echo $buku['cover']; ?>" alt="<?php echo $buku['judul']; ?>" class="book-cover">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/400x600?text=No+Cover" alt="No Cover" class="book-cover">
                        <?php endif; ?>
                    </div>
                    
                    <div class="book-info">
                        <h2 class="book-title"><?php echo $buku['judul']; ?></h2>
                        
                        <div class="book-meta">
                            <div class="book-meta-label">Pengarang</div>
                            <div class="book-meta-value"><?php echo $buku['pengarang']; ?></div>
                            
                            <div class="book-meta-label">Penerbit</div>
                            <div class="book-meta-value"><?php echo $buku['penerbit']; ?></div>
                            
                            <div class="book-meta-label">Tahun Terbit</div>
                            <div class="book-meta-value"><?php echo $buku['tahun_terbit']; ?></div>
                            
                            <div class="book-meta-label">ISBN</div>
                            <div class="book-meta-value"><?php echo !empty($buku['isbn']) ? $buku['isbn'] : '-'; ?></div>
                            
                            <div class="book-meta-label">Bahasa</div>
                            <div class="book-meta-value"><?php echo !empty($buku['bahasa']) ? $buku['bahasa'] : 'Indonesia'; ?></div>
                            
                            <div class="book-meta-label">Jumlah Halaman</div>
                            <div class="book-meta-value"><?php echo !empty($buku['jumlah_halaman']) ? $buku['jumlah_halaman'] : '-'; ?></div>
                        </div>
                        
                        <div class="book-tags">
                            <div class="book-tag"><?php echo ucfirst($buku['genre']); ?></div>
                            
                            <?php if ($buku['stok'] < 5): ?>
                                <div class="stok-tag stok-low">
                                    <i class="fas fa-exclamation-triangle"></i>&nbsp; Stok: <?php echo $buku['stok']; ?>
                                </div>
                            <?php else: ?>
                                <div class="stok-tag stok-ok">
                                    <i class="fas fa-check-circle"></i>&nbsp; Stok: <?php echo $buku['stok']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="action-buttons">
                            <a href="list_buku.php" class="btn btn-back">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            
                            <?php if ($buku['stok'] > 0 && !$sudah_dipinjam): ?>
                                <a href="?id=<?php echo $buku['id']; ?>&pinjam=<?php echo $buku['id']; ?>" class="btn btn-pinjam">
                                    <i class="fas fa-bookmark"></i> Pinjam Buku
                                </a>
                            <?php elseif ($sudah_dipinjam): ?>
                                <a href="?id=<?php echo $buku['id']; ?>&kembali=<?php echo $buku['id']; ?>" class="btn btn-kembali">
                                    <i class="fas fa-undo"></i> Kembalikan Buku
                                </a>
                            <?php else: ?>
                                <span class="btn btn-disabled">
                                    <i class="fas fa-times-circle"></i> Stok Habis
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="description-section">
                <div class="section-header">
                    <i class="fas fa-align-left"></i> Deskripsi Buku
                </div>
                <div class="section-content">
                    <div class="description-text">
                        <?php if (!empty($buku['deskripsi'])): ?>
                            <?php echo nl2br($buku['deskripsi']); ?>
                        <?php else: ?>
                            <p>Tidak ada deskripsi untuk buku ini.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

<div class="comments-section">
    <div class="section-header">
        <i class="fas fa-comments"></i> Komentar & Rating
    </div>
                   <!-- Rating Summary -->
    <?php if ($total_rating > 0): ?>
    <div class="book-rating-summary">
        <div class="avg-rating">
            Rating: <?php echo $avg_rating; ?>/5
            <div class="rating-stars" style="justify-content: center; margin-top: 5px;">
                <?php 
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= floor($avg_rating)) {
                        echo '<i class="fas fa-star star"></i>';
                    } elseif ($i <= ceil($avg_rating) && $avg_rating - floor($avg_rating) >= 0.5) {
                        echo '<i class="fas fa-star-half-alt star"></i>';
                    } else {
                        echo '<i class="fas fa-star star empty"></i>';
                    }
                }
                ?>
            </div>
        </div>
        <div class="total-reviews">Berdasarkan <?php echo $total_rating; ?> ulasan</div>
    </div>
    <?php endif; ?>
    
    <div class="comment-list">
        <?php if (mysqli_num_rows($result_komentar) > 0): ?>
           <?php while ($komentar = mysqli_fetch_assoc($result_komentar)): ?>
            <div class="comment">
                <div class="comment-header">
                    <div style="display: flex; align-items: center;">
                        <span class="comment-author"><?php echo htmlspecialchars($komentar['username']); ?></span>
                        <div class="rating-display">
                            <div class="rating-stars">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $komentar['rating']) {
                                        echo '<i class="fas fa-star star"></i>';
                                    } else {
                                        echo '<i class="fas fa-star star empty"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <span style="font-size: 0.9rem; color: var(--text);">(<?php echo $komentar['rating']; ?>/5)</span>
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: flex-end;">
                        <span class="comment-date"><?php echo date('d M Y, H:i', strtotime($komentar['tanggal_komentar'])); ?></span>
                        <?php if ($komentar['user_id'] == $user_id): ?>
                        <a href="?id=<?php echo $id_buku; ?>&hapus_komentar=<?php echo $komentar['id']; ?>" 
                           class="btn-delete-comment" 
                           onclick="return confirm('Apakah Anda yakin ingin menghapus komentar ini?')">
                            <i class="fas fa-trash"></i> Hapus
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="comment-text">
                    <?php echo nl2br(htmlspecialchars($komentar['komentar'])); ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="comment" style="text-align: center; font-style: italic; color: var(--text); opacity: 0.7;">
                Belum ada komentar untuk buku ini. Jadilah yang pertama memberikan ulasan!
            </div>
        <?php endif; ?>
        
        <!-- Formulir untuk menambahkan komentar -->
        <div class="comment-form">
            <h3 class="form-title" style="margin-bottom: 15px; font-family: 'Press Start 2P', cursive; font-size: 1rem; color: var(--purple);">Tambahkan Komentar & Rating</h3>
            <form action="" method="post">
                <div class="form-group">
                    <label for="comment-name" class="form-label">Nama</label>
                    <input type="text" id="comment-name" class="form-control" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly>
                </div>
                
                <div class="form-group rating-form">
                    <div class="rating-input">
                        <label>Rating:</label>
                        <div class="star-rating">
                            <input type="radio" name="rating" value="5" id="star5" required>
                            <label for="star5">★</label>
                            <input type="radio" name="rating" value="4" id="star4">
                            <label for="star4">★</label>
                            <input type="radio" name="rating" value="3" id="star3">
                            <label for="star3">★</label>
                            <input type="radio" name="rating" value="2" id="star2">
                            <label for="star2">★</label>
                            <input type="radio" name="rating" value="1" id="star1">
                            <label for="star1">★</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="comment-text" class="form-label">Komentar</label>
                    <textarea name="komentar" id="comment-text" class="form-control" placeholder="Tulis komentar Anda tentang buku ini..." required></textarea>
                </div>
                
                <button type="submit" name="submit_komentar" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Kirim Komentar
                </button>
            </form>
        </div>
    </div>
</div>
            </div>

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
        
        // Auto hide alerts after 3 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            }, 3000);
        });
    </script>
</body>
</html>
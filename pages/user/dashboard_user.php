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

// Query untuk mendapatkan jumlah buku
$query_buku = "SELECT COUNT(*) as total_buku FROM buku";
$result_buku = mysqli_query($koneksi, $query_buku);
$row_buku = mysqli_fetch_assoc($result_buku);
$total_buku = $row_buku['total_buku'];

// Query untuk mendapatkan jumlah pengguna
$query_users = "SELECT COUNT(*) as total_users FROM users WHERE role = 'user'";
$result_users = mysqli_query($koneksi, $query_users);
$row_users = mysqli_fetch_assoc($result_users);
$total_users = $row_users['total_users'];

// Query untuk mendapatkan jumlah buku yang dipinjam oleh user yang sedang login
$query_pinjam = "SELECT COUNT(*) as total_pinjam FROM peminjaman WHERE user_id = $user_id AND status = 'dipinjam'";
$result_pinjam = mysqli_query($koneksi, $query_pinjam);
$row_pinjam = mysqli_fetch_assoc($result_pinjam);
$total_pinjam = $row_pinjam['total_pinjam'];

// Query untuk mendapatkan buku terbaru
$query_buku_terbaru = "SELECT * FROM buku ORDER BY id DESC LIMIT 5";
$result_buku_terbaru = mysqli_query($koneksi, $query_buku_terbaru);

// Query untuk mendapatkan aktivitas peminjaman terbaru
$query_aktivitas = "SELECT p.*, b.judul FROM peminjaman p 
                    JOIN buku b ON p.buku_id = b.id 
                    WHERE p.user_id = $user_id 
                    ORDER BY p.tanggal_pinjam DESC LIMIT 6";
$result_aktivitas = mysqli_query($koneksi, $query_aktivitas);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Head section tetap sama seperti sebelumnya -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard User - Perpustakaan Online</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=VT323&family=Press+Start+2P&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Sisa style tetap sama seperti sebelumnya -->
    <style>
        /* Style tetap sama seperti sebelumnya */
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
            display: flex;
            align-items: center;
            background-color: var(--dark);
            padding: 8px 15px;
            border-radius: 30px;
            border: 2px solid var(--yellow);
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            border: 2px solid var(--yellow);
        }
        
        .user-name {
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
            font-weight: 500;
            color: var(--light);
        }

        .user-info small {
            font-family: 'VT323', monospace;
            font-size: 1rem;
            color: var(--yellow);
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: var(--light);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 5px 5px 0 var(--dark);
            transition: transform 0.3s ease;
            border: 3px solid var(--dark);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--yellow), var(--red), var(--purple), var(--green), var(--blue));
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .stat-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 1.8rem;
            background-color: var(--dark);
            border: 3px solid var(--yellow);
        }
        
        .stat-card:nth-child(1) .stat-icon {
            color: var(--red);
        }
        
        .stat-card:nth-child(2) .stat-icon {
            color: var(--blue);
        }
        
        .stat-card:nth-child(3) .stat-icon {
            color: var(--yellow);
        }
        
        .stat-card h3 {
            font-family: 'Press Start 2P', cursive;
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--purple);
        }
        
        .stat-card p {
            color: var(--dark);
            font-size: 1rem;
            font-weight: 500;
            font-family: 'VT323', monospace;
            font-size: 1.3rem;
        }
        
        .recent-books {
            background-color: var(--light);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 5px 5px 0 var(--dark);
            margin-bottom: 30px;
            border: 3px solid var(--dark);
            position: relative;
        }
        
        .recent-books::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--yellow), var(--red), var(--purple), var(--green), var(--blue));
        }
        
        .recent-books h2 {
            font-family: 'Press Start 2P', cursive;
            margin-bottom: 20px;
            color: var(--purple);
            font-size: 1.2rem;
        }
        
        .book-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .book-table th, .book-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid var(--dark);
        }
        
        .book-table th {
            background-color: var(--blue);
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--yellow);
            text-shadow: 1px 1px 0 var(--dark);
        }
        
        .book-table tr:hover {
            background-color: rgba(255, 197, 103, 0.2);
        }
        
        .book-table tr:last-child td {
            border-bottom: none;
        }
        
        .genre-tag {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            background-color: var(--green);
            color: white;
            display: inline-block;
            font-family: 'VT323', monospace;
            border: 2px solid var(--dark);
        }
        
        .stok-indicator {
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
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
        
        .activity-log {
            background-color: var(--light);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 5px 5px 0 var(--dark);
            border: 3px solid var(--dark);
            position: relative;
        }
        
        .activity-log::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--yellow), var(--red), var(--purple), var(--green), var(--blue));
        }
        
        .activity-log h2 {
            font-family: 'Press Start 2P', cursive;
            margin-bottom: 20px;
            color: var(--purple);
            font-size: 1.2rem;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px dashed var(--dark);
        }
        
        .activity-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .activity-icon {
            width: 45px;
            height: 45px;
            border-radius: 5px;
            background-color: var(--dark);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--yellow);
            border: 3px solid var(--red);
            font-size: 1.2rem;
        }
        
        .activity-details {
            flex: 1;
        }
        
        .activity-details h4 {
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--purple);
            font-family: 'VT323', monospace;
            font-size: 1.3rem;
        }
        
        .activity-details p {
            color: var(--dark);
            font-size: 1rem;
            margin-bottom: 5px;
        }
        
        .activity-time {
            font-size: 0.9rem;
            color: var(--red);
            font-weight: bold;
            font-family: 'VT323', monospace;
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
            .sidebar-toggle {
                display: block;
            }
        
        @media (max-width: 576px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .content {
                padding: 15px 10px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .user-info {
                margin-top: 15px;
                width: 100%;
            }
            
            .header h1 {
                font-size: 1.2rem;
            }
            
            .recent-books h2, .activity-log h2 {
                font-size: 1rem;
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
                <a href="dashboard_user.php" class="active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="list_buku.php">
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
                <h1>DASHBOARD</h1>
                <div class="user-info">
                    <img src="../../../perpustakaan_daffa/img/logo-perpustakaan.png" alt="Logo Perpustakaan">
                    <div>
                        <span class="user-name"><?php echo $_SESSION['username']; ?></span>
                        <small>Users</small>
                    </div>
                </div>
            </div>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3><?php echo $total_buku; ?></h3>
                    <p>Total Judul</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3><?php echo $total_users; ?></h3>
                    <p>Total Pengguna</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-bookmark"></i>
                    </div>
                    <h3><?php echo $total_pinjam; ?></h3>
                    <p>Buku Yang Dipinjam</p>
                </div>
            </div>
            
            <div class="recent-books">
                <h2>BUKU TERBARU</h2>
                <table class="book-table">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Pengarang</th>
                            <th>Genre</th>
                            <th>Tahun</th>
                            <th>Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result_buku_terbaru)) { ?>
                            <tr>
                                <td><?php echo $row['judul']; ?></td>
                                <td><?php echo $row['pengarang']; ?></td>
                                <td><span class="genre-tag"><?php echo $row['genre']; ?></span></td>
                                <td><?php echo $row['tahun_terbit']; ?></td>
                                <td>
                                    <?php if ($row['stok'] < 5) { ?>
                                        <span class="stok-indicator stok-low"><?php echo $row['stok']; ?></span>
                                    <?php } else { ?>
                                        <span class="stok-indicator stok-ok"><?php echo $row['stok']; ?></span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if (mysqli_num_rows($result_buku_terbaru) == 0) { ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">Belum ada buku yang ditambahkan</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            
            <div class="activity-log">
                <h2>AKTIVITAS TERBARU</h2>
                <?php if (mysqli_num_rows($result_aktivitas) > 0) {
                    while ($row_aktivitas = mysqli_fetch_assoc($result_aktivitas)) {
                ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-book-reader"></i>
                    </div>
                    <div class="activity-details">
                        <h4><?php echo $row_aktivitas['status'] == 'dipinjam' ? 'Peminjaman Buku' : 'Pengembalian Buku'; ?></h4>
                        <p>Anda <?php echo $row_aktivitas['status'] == 'dipinjam' ? 'meminjam' : 'mengembalikan'; ?> buku "<?php echo $row_aktivitas['judul']; ?>"</p>
                        <span class="activity-time"><?php echo date('d F Y, H:i', strtotime($row_aktivitas['tanggal_pinjam'])); ?></span>
                    </div>
                </div>
                <?php } 
                } else {
                ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="activity-details">
                        <h4>Belum Ada Aktivitas</h4>
                        <p>Anda belum meminjam buku apa pun</p>
                        <span class="activity-time">-</span>
                    </div>
                </div>
                <?php } ?>
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
    </script>
</body>
</html>
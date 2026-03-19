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

// Proses pengembalian buku
if (isset($_GET['kembali'])) {
    $peminjaman_id = (int)$_GET['kembali'];
    
    // Dapatkan informasi buku
    $query_info = "SELECT buku_id FROM peminjaman WHERE id = $peminjaman_id AND user_id = $user_id";
    $result_info = mysqli_query($koneksi, $query_info);
    
    if ($row_info = mysqli_fetch_assoc($result_info)) {
        $buku_id = $row_info['buku_id'];
        
        // Kembalikan stok buku
        $query_kembalikan_stok = "UPDATE buku SET stok = stok + 1 WHERE id = $buku_id";
        mysqli_query($koneksi, $query_kembalikan_stok);
        
        // Update status peminjaman
        $query_kembali = "UPDATE peminjaman SET status = 'kembali', tanggal_kembali = NOW() 
                        WHERE id = $peminjaman_id AND user_id = $user_id";
        mysqli_query($koneksi, $query_kembali);
        
        // Redirect dengan pesan sukses
        header("Location: buku_dipinjam.php?pesan=berhasil_kembali");
        exit();
    }
}

// Dapatkan daftar buku yang dipinjam oleh user
$query_peminjaman = "SELECT p.id as peminjaman_id, b.id as buku_id, b.judul, b.pengarang, 
                    b.penerbit, b.tahun_terbit, b.genre, b.stok, b.cover, 
                    p.tanggal_pinjam, p.tanggal_kembali, p.status
                    FROM peminjaman p 
                    JOIN buku b ON p.buku_id = b.id 
                    WHERE p.user_id = $user_id
                    ORDER BY p.status ASC, p.tanggal_pinjam DESC";
$result_peminjaman = mysqli_query($koneksi, $query_peminjaman);

// Dapatkan daftar buku yang dipinjam oleh user (hanya yang berstatus 'dipinjam')
$query_peminjaman = "SELECT p.id as peminjaman_id, b.id as buku_id, b.judul, b.pengarang, 
                    b.penerbit, b.tahun_terbit, b.genre, b.stok, b.cover, 
                    p.tanggal_pinjam, p.tanggal_kembali, p.status
                    FROM peminjaman p 
                    JOIN buku b ON p.buku_id = b.id 
                    WHERE p.user_id = $user_id AND p.status = 'dipinjam'
                    ORDER BY p.tanggal_pinjam DESC";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Dipinjam - Perpustakaan Online</title>
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
        
        /* Table Styles */
        .table-container {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 5px 5px 0 var(--dark);
            border: 3px solid var(--dark);
            margin-bottom: 30px;
        }
        
        .buku-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .buku-table th, .buku-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid var(--light);
        }
        
        .buku-table th {
            background-color: var(--purple);
            color: white;
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
        }
        
        .buku-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .buku-table tr:hover {
            background-color: var(--light);
        }
        
        .buku-table td {
            font-family: 'VT323', monospace;
            font-size: 1.1rem;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            font-family: 'VT323', monospace;
            font-size: 1.1rem;
            border: 2px solid var(--dark);
        }
        
        .btn-kembali {
            background-color: var(--green);
            color: white;
        }
        
        .btn-kembali:hover {
            background-color: #00774a;
            transform: translateY(-2px);
        }
        
        .status-dipinjam, .status-kembali {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            border: 2px solid var(--dark);
            font-family: 'VT323', monospace;
        }
        
        .status-dipinjam {
            background-color: var(--blue);
            color: white;
        }
        
        .status-kembali {
            background-color: var(--green);
            color: white;
        }
        
        .no-data {
            padding: 50px;
            text-align: center;
        }
        
        .no-data h3 {
            font-family: 'Press Start 2P', cursive;
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: var(--red);
        }
        
        .no-data p {
            font-family: 'VT323', monospace;
            font-size: 1.3rem;
            margin-bottom: 20px;
        }
        
        .no-data .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--blue);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid var(--dark);
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
        }
        
        .no-data .btn:hover {
            background-color: var(--purple);
            transform: translateY(-3px);
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
            
            .table-container {
                overflow-x: auto;
            }
            
            .buku-table {
                min-width: 800px;
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
                <a href="list_buku.php">
                    <i class="fas fa-book"></i>
                    <span>Daftar Buku</span>
                </a>
                <div class="divider"></div>
                <a href="buku_dipinjam.php" class="active">
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
                <h1>BUKU DIPINJAM</h1>
                <div class="user-info">
                    <span class="user-name"><?php echo $_SESSION['username']; ?></span>
                    <small>Users</small>
                </div>
            </div>
            
            <?php 
            // Tampilkan pesan 
            if (isset($_GET['pesan'])) {
                $pesan = $_GET['pesan'];
                if ($pesan == 'berhasil_kembali') {
                    echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Buku berhasil dikembalikan!</div>';
                }
            }
            ?>
            
            <div class="table-container">
                <?php if (mysqli_num_rows($result_peminjaman) > 0): ?>
                    <table class="buku-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Judul</th>
                                <th>Pengarang</th>
                                <th>Stok</th>
                                <th>Tanggal Pinjam</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($result_peminjaman)): 
                                // Format tanggal
                                $tanggal_pinjam = date('d-m-Y', strtotime($row['tanggal_pinjam']));
                                $tanggal_kembali = !empty($row['tanggal_kembali']) ? date('d-m-Y', strtotime($row['tanggal_kembali'])) : '-';
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo $row['judul']; ?></td>
                                    <td><?php echo $row['pengarang']; ?></td>
                                    <td><?php echo $row['stok']; ?></td>
                                    <td><?php echo $tanggal_pinjam; ?></td>
                                    <td>
                                        <?php if ($row['status'] == 'dipinjam'): ?>
                                            <span class="status-dipinjam">
                                                <i class="fas fa-bookmark"></i> Dipinjam
                                            </span>
                                        <?php else: ?>
                                            <span class="status-kembali">
                                                <i class="fas fa-check-circle"></i> Dikembalikan (<?php echo $tanggal_kembali; ?>)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] == 'dipinjam'): ?>
                                            <a href="?kembali=<?php echo $row['peminjaman_id']; ?>" class="btn btn-kembali">
                                                <i class="fas fa-undo"></i> Kembalikan
                                            </a>
                                        <?php else: ?>
                                            <span class="btn" style="background-color: var(--light); color: var(--dark); cursor: default;">
                                                <i class="fas fa-check"></i> Selesai
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <h3>Tidak Ada Buku Dipinjam</h3>
                        <p>Anda belum meminjam buku apapun.</p>
                        <a href="list_buku.php" class="btn">
                            <i class="fas fa-book"></i> Lihat Daftar Buku
                        </a>
                    </div>
                <?php endif; ?>
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
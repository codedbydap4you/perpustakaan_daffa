<?php
// Memulai session
session_start();

// Memeriksa apakah pengguna sudah login
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Include file koneksi
include '../../../perpustakaan_daffa/config/koneksi.php';

// Query untuk mendapatkan semua pengguna
$query_users = "SELECT * FROM users ORDER BY id ASC";
$result_users = mysqli_query($koneksi, $query_users);

// Proses Tambah User
if (isset($_POST['tambah_user'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Enkripsi password
    $role = mysqli_real_escape_string($koneksi, $_POST['role']);
    
    // Cek apakah username sudah ada
    $check_query = "SELECT * FROM users WHERE username = '$username'";
    $check_result = mysqli_query($koneksi, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $pesan = "Username sudah digunakan!";
        $status = "error";
    } else {
        // Tambahkan user baru
        $query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
        if (mysqli_query($koneksi, $query)) {
            $pesan = "User berhasil ditambahkan!";
            $status = "success";
            // Refresh halaman untuk mendapatkan data terbaru
            header("Location: pengguna.php?status=$status&pesan=$pesan");
            exit();
        } else {
            $pesan = "Gagal menambahkan user: " . mysqli_error($koneksi);
            $status = "error";
        }
    }
}

// Proses Edit User
if (isset($_POST['edit_user'])) {
    $user_id = mysqli_real_escape_string($koneksi, $_POST['user_id']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $role = mysqli_real_escape_string($koneksi, $_POST['role']);
    
    // Cek apakah password diubah
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $query = "UPDATE users SET username='$username', password='$password', role='$role' WHERE id=$user_id";
    } else {
        $query = "UPDATE users SET username='$username', role='$role' WHERE id=$user_id";
    }
    
    if (mysqli_query($koneksi, $query)) {
        $pesan = "User berhasil diupdate!";
        $status = "success";
        header("Location: pengguna.php?status=$status&pesan=$pesan");
        exit();
    } else {
        $pesan = "Gagal mengupdate user: " . mysqli_error($koneksi);
        $status = "error";
    }
}

// Proses Hapus User
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Cek apakah ini bukan akun yang sedang login
    if ($id == $_SESSION['user_id']) {
        $pesan = "Tidak dapat menghapus akun yang sedang digunakan!";
        $status = "error";
    } else {
        $query = "DELETE FROM users WHERE id = $id";
        if (mysqli_query($koneksi, $query)) {
            $pesan = "User berhasil dihapus!";
            $status = "success";
            header("Location: pengguna.php?status=$status&pesan=$pesan");
            exit();
        } else {
            $pesan = "Gagal menghapus user: " . mysqli_error($koneksi);
            $status = "error";
        }
    }
}

// Ambil data user untuk edit jika diperlukan
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = "SELECT * FROM users WHERE id = $edit_id";
    $edit_result = mysqli_query($koneksi, $edit_query);
    if (mysqli_num_rows($edit_result) > 0) {
        $edit_data = mysqli_fetch_assoc($edit_result);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - Perpustakaan Online</title>
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
        
        .users-container {
            background-color: var(--light);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 5px 5px 0 var(--dark);
            border: 3px solid var(--dark);
            position: relative;
            margin-bottom: 30px;
        }
        
        .users-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--yellow), var(--red), var(--purple), var(--green), var(--blue));
        }
        
        .users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .users-container h2 {
            font-family: 'Press Start 2P', cursive;
            color: var(--purple);
            font-size: 1.2rem;
        }
        
        .btn-tambah {
            padding: 10px 16px;
            background-color: var(--green);
            color: white;
            border: 3px solid var(--dark);
            border-radius: 5px;
            font-family: 'VT323', monospace;
            font-size: 1.1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            box-shadow: 3px 3px 0 var(--dark);
        }
        
        .btn-tambah:hover {
            background-color: var(--blue);
            transform: translateY(-3px);
        }
        
        .btn-tambah i {
            margin-right: 8px;
        }
        
        .users-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .users-table th, .users-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid var(--dark);
        }
        
        .users-table th {
            background-color: var(--blue);
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--yellow);
            text-shadow: 1px 1px 0 var(--dark);
        }
        
        .users-table tr:hover {
            background-color: rgba(255, 197, 103, 0.2);
        }
        
        .users-table tr:last-child td {
            border-bottom: none;
        }
        
        .role-tag {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            border: 2px solid var(--dark);
            display: inline-block;
            font-family: 'VT323', monospace;
        }
        
        .role-admin {
            background-color: var(--purple);
            color: white;
        }
        
        .role-user {
            background-color: var(--blue);
            color: white;
        }
        
        .action-btn {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9rem;
            border: 2px solid var(--dark);
            cursor: pointer;
            margin-right: 5px;
            font-family: 'VT323', monospace;
            transition: all 0.2s ease;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-hapus {
            background-color: var(--red);
            color: white;
        }
        
        .btn-edit {
            background-color: var(--yellow);
            color: var(--dark);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            overflow: auto;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: var(--light);
            margin: 10% auto;
            padding: 30px;
            width: 50%;
            border: 4px solid var(--dark);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.5);
            border-radius: 10px;
            position: relative;
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--dark);
        }
        
        .modal-header h3 {
            font-family: 'Press Start 2P', cursive;
            color: var(--purple);
            font-size: 1.2rem;
        }
        
        .close {
            font-size: 1.8rem;
            color: var(--red);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .close:hover {
            color: var(--dark);
            transform: scale(1.2);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 3px solid var(--dark);
            border-radius: 5px;
            font-size: 1rem;
            background-color: white;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--blue);
            outline: none;
            box-shadow: 0 0 0 3px rgba(5, 140, 215, 0.3);
        }
        
        .password-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .password-container .form-control {
            flex: 1;
            padding-right: 40px;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            height: 100%;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: var(--blue);
        }
        
        .password-cell {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .password-text {
            flex: 1;
        }
        
        .cell-password-toggle {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            color: var(--dark);
            margin-left: 10px;
        }
        
        .cell-password-toggle:hover {
            color: var(--blue);
        }
        
        .btn-submit {
            padding: 12px 20px;
            background-color: var(--green);
            color: white;
            border: 3px solid var(--dark);
            border-radius: 5px;
            font-size: 1.1rem;
            cursor: pointer;
            width: 100%;
            font-family: 'VT323', monospace;
            transition: all 0.3s ease;
            box-shadow: 3px 3px 0 var(--dark);
        }
        
        .btn-submit:hover {
            background-color: var(--blue);
            transform: translateY(-3px);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 3px solid var(--dark);
            border-radius: 5px;
            font-family: 'VT323', monospace;
            font-size: 1.1rem;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .alert-success {
            background-color: var(--green);
            color: white;
        }
        
        .alert-error {
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

        .password-help {
            font-size: 0.85rem;
            color: var(--dark);
            margin-top: 5px;
            font-style: italic;
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
            
            .modal-content {
                width: 90%;
            }
        }
        
        @media (max-width: 768px) {
            .users-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .btn-tambah {
                margin-top: 15px;
            }
            
            .users-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
        
        @media (max-width: 576px) {
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
            
            .users-container h2 {
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
                <p>Panel Admin</p>
            </div>

             <div class="sidebar-toggle-inside">
    <button id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>
</div>

            <div class="sidebar-menu">
                <a href="dashboard_admin.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="daftar_buku.php">
                    <i class="fas fa-book"></i>
                    <span>Daftar Buku</span>
                </a>
               
                <div class="divider"></div>
                <a href="pengguna.php" class="active">
                    <i class="fas fa-users"></i>
                    <span>Pengguna</span>
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
                <h1>MANAJEMEN PENGGUNA</h1>
                <div class="user-info">
                    <div>
                        <span class="user-name"><?php echo $_SESSION['username']; ?></span>
                        <small>Administrator</small>
                    </div>
                </div>
            </div>
            
            <?php if (isset($pesan) && isset($status)) : ?>
            <div class="alert alert-<?php echo $status; ?>">
                <?php echo $pesan; ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['pesan']) && isset($_GET['status'])) : ?>
            <div class="alert alert-<?php echo $_GET['status']; ?>">
                <?php echo $_GET['pesan']; ?>
            </div>
            <?php endif; ?>
            
            <div class="users-container">
                <div class="users-header">
                    <h2>DAFTAR PENGGUNA SISTEM</h2>
                    <button class="btn-tambah" id="btnTambah">
                        <i class="fas fa-user-plus"></i> Tambah User
                    </button>
                </div>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if (mysqli_num_rows($result_users) > 0) :
                            while ($row = mysqli_fetch_assoc($result_users)) : 
                                // Menyembunyikan password dengan karakter bintang (hidden value)
                                $hidden_password = "••••••••";
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $row['username']; ?></td>
                                <td>
                                  <div style="display: flex; align-items: center;">
                                    <input type="password" value="<?php echo $row['password']; ?>" id="pw-<?php echo $row['id']; ?>" readonly style="border: none; background: transparent;">
                                    <button type="button" onclick="togglePassword('<?php echo $row['id']; ?>')" style="background: none; border: none; cursor: pointer;">
                                      <i class="fas fa-eye" id="add-toggle-icon"></i>
                                    </button>
                                  </div>
                                </td>


                                <td>
                                    <?php if ($row['role'] == 'admin') : ?>
                                        <span class="role-tag role-admin">Admin</span>
                                    <?php else : ?>
                                        <span class="role-tag role-user">User</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo $row['username']; ?>', '<?php echo $row['role']; ?>')" class="action-btn btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="pengguna.php?hapus=<?php echo $row['id']; ?>" class="action-btn btn-hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?');">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        else : 
                        ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">Belum ada pengguna yang terdaftar</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal Tambah User -->
    <div id="modalTambahUser" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>TAMBAH USER BARU</h3>
                <span class="close" id="closeTambahModal">&times;</span>
            </div>
            <form action="" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-container">
                        <input type="password" class="form-control" id="add-password" name="password" required>
                        <button type="button" class="password-toggle" onclick="togglePasswordInput('add-password')">
                            <i class="fas fa-eye" id="add-toggle-icon"></i>
                        </button>
                    </div>
                    <div class="password-help">
                        Password akan di-hash secara otomatis untuk keamanan
                    </div>
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select class="form-control" id="role" name="role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" name="tambah_user" class="btn-submit">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </form>
        </div>
    </div>

    <!-- Modal Edit User -->
<div id="modalEditUser" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>EDIT USER</h3>
            <span class="close" id="closeEditModal">&times;</span>
        </div>
        <form action="" method="POST">
            <input type="hidden" id="edit-user-id" name="user_id">
            <div class="form-group">
                <label for="edit-username">Username</label>
                <input type="text" class="form-control" id="edit-username" name="username" required>
            </div>
            <div class="form-group">
                <label for="edit-password">Password (Biarkan kosong jika tidak ingin mengubah)</label>
                <div class="password-container">
                    <input type="password" class="form-control" id="edit-password" name="password">
                    <button type="button" class="password-toggle" onclick="togglePasswordInput('edit-password')">
                        <i class="fas fa-eye" id="edit-toggle-icon"></i>
                    </button>
                </div>
                <div class="password-help">
                    Password baru akan di-hash secara otomatis untuk keamanan
                </div>
            </div>
            <div class="form-group">
                <label for="edit-role">Role</label>
                <select class="form-control" id="edit-role" name="role" required>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" name="edit_user" class="btn-submit">
                <i class="fas fa-save"></i> Update
            </button>
        </form>
    </div>
</div>
    
    <script>
        
            // Modal
            const modal = document.getElementById("modalTambahUser");
            const btnTambah = document.getElementById("btnTambah");
            const span = document.getElementsByClassName("close")[0];
            
            btnTambah.onclick = function() {
                modal.style.display = "block";
            }
            
            span.onclick = function() {
                modal.style.display = "none";
            }
            
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
            
            // Auto hide alert after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 5000);
            });
        
            //toggle password      
            function togglePassword(id) {
              const input = document.getElementById('pw-' + id);
              if (input.type === 'password') {
                input.type = 'text';
              } else {
                input.type = 'password';
              }
            }
            //toggle password tambah user
            function togglePasswordInput(id) {
              const input = document.getElementById(id);
              input.type = input.type === 'password' ? 'text' : 'password';
            }

                        // Script untuk fungsi modal edit
            function openEditModal(id, username, role) {
                // Set nilai pada form edit
                document.getElementById("edit-user-id").value = id;
                document.getElementById("edit-username").value = username;
                document.getElementById("edit-password").value = ""; // Password kosong untuk keamanan
                document.getElementById("edit-role").value = role;
                
                // Tampilkan modal
                const editModal = document.getElementById("modalEditUser");
                editModal.style.display = "block";
                
                // Tambahkan event untuk menutup modal
                document.getElementById("closeEditModal").onclick = function() {
                    editModal.style.display = "none";
                }
                
                // Tutup modal jika user mengklik di luar modal
                window.onclick = function(event) {
                    if (event.target == editModal) {
                        editModal.style.display = "none";
                    }
                }
            }

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
  // Sidebar toggle functionality
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const content = document.querySelector('.content');
    
    sidebarToggle.addEventListener('click', () => {
        sidebar.style.transform = sidebar.style.transform === 'translateX(0px)' ? 'translateX(-100%)' : 'translateX(0px)';
        content.style.marginLeft = content.style.marginLeft === '0px' ? 'var(--sidebar-width)' : '0px';
    });
  
</script>

</body>
</html>
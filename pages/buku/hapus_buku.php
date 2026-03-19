<?php
// Memulai session
session_start();
$username = $_SESSION['username']; // sesuaikan dengan nama session Anda


// Memeriksa apakah pengguna sudah login dan memiliki role admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Include file koneksi
include '../../../perpustakaan_daffa/config/koneksi.php';

// Memeriksa apakah ID buku tersedia
if (isset($_GET['id'])) {
    $id_buku = (int)$_GET['id'];
    
    // Dapatkan informasi buku, termasuk cover
    $query_buku = "SELECT * FROM buku WHERE id = $id_buku";
    $result_buku = mysqli_query($koneksi, $query_buku);
    
    if (mysqli_num_rows($result_buku) > 0) {
        $buku = mysqli_fetch_assoc($result_buku);
        $cover = $buku['cover'];
        
        // Hapus file cover jika ada
        if (!empty($cover)) {
            $path_cover = '../../../perpustakaan_daffa/jpg/cover/' . $cover;
            if (file_exists($path_cover)) {
                unlink($path_cover);
            }
        }
        
        // Hapus data buku dari database
        $query_hapus = "DELETE FROM buku WHERE id = $id_buku";
        if (mysqli_query($koneksi, $query_hapus)) {
            // Log aktivitas (opsional)
            $admin = $_SESSION['username'];
            $log_query = "INSERT INTO log_aktivitas (username, aktivitas, detail, waktu) 
                         VALUES ('$admin', 'hapus_buku', 'Menghapus buku: {$buku['judul']}', NOW())";
            mysqli_query($koneksi, "INSERT INTO log_aktivitas (username, aksi, waktu) VALUES ('$username', 'Menghapus data buku dengan id $id', NOW())");

            
            // Redirect kembali ke daftar buku dengan status sukses
            header("Location: daftar_buku.php?status=sukses_hapus");
            exit();
        } else {
            // Redirect kembali ke daftar buku dengan status gagal
            header("Location: daftar_buku.php?status=gagal_hapus&error=" . mysqli_error($koneksi));
            exit();
        }
    } else {
        // Buku tidak ditemukan
        header("Location: daftar_buku.php?status=buku_tidak_ditemukan");
        exit();
    }
} else {
    // ID buku tidak diberikan
    header("Location: daftar_buku.php?status=id_tidak_diberikan");
    exit();
}
?>
<?php 
$host = "localhost";
$user = "root";
$pass = "";
$db = "perpustakaan_daffa";

$koneksi = mysqli_connect(hostname:$host, username:$user, password:$pass, database:$db);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

?>
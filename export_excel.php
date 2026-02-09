<?php
include 'config.php';
session_start();

// Cek login agar orang luar tidak bisa download data
if (!isset($_SESSION['admin_logged_in'])) { 
    exit("Access Denied"); 
}

// Header untuk memaksa browser mendownload file sebagai Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Report_Digital_Card_".date('Y-m-d').".xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "
<table border='1'>
    <tr>
        <th colspan='4' style='background-color: #D4AF37; color: white; height: 30px;'>LAPORAN KUNJUNGAN DIGITAL CARD - " . date('d M Y') . "</th>
    </tr>
    <tr>
        <th style='background-color: #f2f2f2;'>ID</th>
        <th style='background-color: #f2f2f2;'>NAMA EXECUTIVE</th>
        <th style='background-color: #f2f2f2;'>JABATAN</th>
        <th style='background-color: #f2f2f2;'>TOTAL VIEWS</th>
    </tr>";

$sql = mysqli_query($conn, "SELECT id, name, title, views FROM managers ORDER BY views DESC");

if(mysqli_num_rows($sql) > 0) {
    while($row = mysqli_fetch_assoc($sql)) {
        echo "
        <tr>
            <td>{$row['id']}</td>
            <td>{$row['name']}</td>
            <td>{$row['title']}</td>
            <td>{$row['views']}</td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='4'>Tidak ada data ditemukan</td></tr>";
}

echo "</table>";
?>
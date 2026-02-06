<?php
include 'config.php';

if (isset($_GET['name'])) {
    $slug = mysqli_real_escape_string($conn, $_GET['name']);
    $query = mysqli_query($conn, "SELECT * FROM managers WHERE slug = '$slug'");
    $row = mysqli_fetch_assoc($query);

    if ($row) {
        // Membersihkan buffer agar tidak ada karakter liar yang ikut terdownload
        if (ob_get_level()) ob_end_clean();

        // Header agar browser mendownload sebagai file vCard
        header('Content-Type: text/vcard; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $slug . '.vcf"');

        // Isi vCard
        echo "BEGIN:VCARD\n";
        echo "VERSION:3.0\n";
        echo "FN:" . $row['name'] . "\n";
        echo "N:;" . $row['name'] . ";;;\n"; // Format nama lengkap untuk beberapa jenis HP
        echo "ORG:The Trans Luxury Hotel\n";
        echo "TITLE:" . $row['title'] . "\n";

        // Nomor Personal (WhatsApp) sebagai Handphone utama
        echo "TEL;TYPE=CELL,VOICE:" . $row['phone_personal'] . "\n"; 

        // Nomor Kantor sebagai nomor kerja
        echo "TEL;TYPE=WORK,VOICE:" . $row['phone_office'] . "\n"; 

        echo "EMAIL;TYPE=PREF,INTERNET:" . $row['email'] . "\n";
        echo "URL:https://www.thetranshotel.com\n";
        echo "ADR;TYPE=WORK:;;Jl. Gatot Subroto No. 289;Bandung;Jawa Barat;40273;Indonesia\n";
        echo "REV:" . date("Y-m-d\TH:i:s\Z") . "\n"; // Timestamp revisi (opsional)
        echo "END:VCARD";
        exit;
    }
}
?>
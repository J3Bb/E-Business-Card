<?php
include 'config.php';
$id = $_GET['id'];
$query = mysqli_query($conn, "SELECT * FROM managers WHERE id = '$id'");
$data = mysqli_fetch_assoc($query);

header('Content-Type: text/vcard');
header('Content-Disposition: attachment; filename="'.$data['slug'].'.vcf"');

echo "BEGIN:VCARD\n";
echo "VERSION:3.0\n";
echo "FN:" . $data['name'] . "\n";
echo "ORG:The Trans Luxury Hotel\n";
echo "TITLE:" . $data['title'] . "\n";
echo "TEL;TYPE=CELL:" . $data['phone_personal'] . "\n";
echo "TEL;TYPE=WORK:" . $data['phone_office'] . "\n";
echo "EMAIL:" . $data['email'] . "\n";
echo "END:VCARD";
?>
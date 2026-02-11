<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "hotel_db"; // Pastikan nama DB sesuai

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

/**
 * Fungsi Kompresi Gambar Otomatis (Support up to 20MB)
 * Mengubah file ke format JPG yang ringan sebelum disimpan.
 */
function uploadAndCompress($file, $folder = "pics/") {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) return false;

    $fileTmp  = $file['tmp_name'];
    $newName  = time() . "_" . uniqid() . ".jpg"; 
    $dest     = $folder . $newName;

    $info = getimagesize($fileTmp);
    if ($info['mime'] == 'image/jpeg') $image = imagecreatefromjpeg($fileTmp);
    elseif ($info['mime'] == 'image/png') $image = imagecreatefrompng($fileTmp);
    elseif ($info['mime'] == 'image/webp') $image = imagecreatefromwebp($fileTmp);
    else return false;

    // Simpan hasil kompresi (Quality 70)
    imagejpeg($image, $dest, 70);
    imagedestroy($image);
    return $newName;
}

/**
 * Fungsi Sanitasi Nomor Telepon ke Format +62
 */
function formatPhone($number) {
    $clean = ltrim(preg_replace('/[^0-9]/', '', $number), '0');
    if (substr($clean, 0, 2) == '62') $clean = substr($clean, 2);
    return "+62 " . $clean;
}

/**
 * Fungsi Tracking Visitor (IP-API)
 */
function trackVisitor($conn, $manager_id) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $device = mysqli_real_escape_string($conn, $_SERVER['HTTP_USER_AGENT']);
    $city = "Unknown"; $region = "Unknown";

    if ($ip !== '127.0.0.1' && $ip !== '::1') {
        $ctx = stream_context_create(['http' => ['timeout' => 2]]);
        $api_data = @file_get_contents("http://ip-api.com/json/{$ip}", false, $ctx);
        if ($api_data) {
            $details = json_decode($api_data);
            if ($details && $details->status !== 'fail') {
                $city = $details->city ?? 'External';
                $region = $details->regionName ?? 'Unknown';
            }
        }
    } else {
        $city = "Local"; $region = "Host";
    }

    $query = "INSERT INTO ecard_logs (manager_id, visitor_ip, city, region, device_info) 
              VALUES ('$manager_id', '$ip', '$city', '$region', '$device')";
    if (mysqli_query($conn, $query)) {
        mysqli_query($conn, "UPDATE managers SET views = views + 1 WHERE id = '$manager_id'");
        $_SESSION['last_track_time'] = time();
    }
}
?>
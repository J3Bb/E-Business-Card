<?php
include 'config.php';

// Ambil slug dari URL
$slug = mysqli_real_escape_string($conn, $_GET['name']);

// Ambil data manager berdasarkan slug
$res = mysqli_query($conn, "SELECT id FROM managers WHERE slug = '$slug'");
$manager = mysqli_fetch_assoc($res);

if ($manager) {
    $manager_id = $manager['id'];
    $ip = $_SERVER['REMOTE_ADDR']; // Alamat IP Tamu
    
    // Cek detail lokasi via API (server-side)
    // Gunakan file_get_contents atau CURL
    $details = json_decode(file_get_contents("http://ip-api.com/json/{$ip}"));
    
    $city = $details->city ?? 'Unknown';
    $region = $details->regionName ?? 'Unknown';
    $country = $details->country ?? 'Unknown';
    $device = $_SERVER['HTTP_USER_AGENT']; // Informasi HP/Browser

    // Simpan ke database
    $log_query = "INSERT INTO ecard_logs (manager_id, visitor_ip, city, region, country, device_info) 
                  VALUES ('$manager_id', '$ip', '$city', '$region', '$country', '$device')";
    mysqli_query($conn, $log_query);
    
    // Update total views (opsional, jika Anda ingin sinkron)
    mysqli_query($conn, "UPDATE managers SET views = views + 1 WHERE id = '$manager_id'");
}

if (isset($_GET['name'])) {
    $slug = mysqli_real_escape_string($conn, $_GET['name']);
    $res = mysqli_query($conn, "SELECT id FROM managers WHERE slug = '$slug'");
    $manager = mysqli_fetch_assoc($res);

    if ($manager) {
        $manager_id = $manager['id'];
        
        // Deteksi IP yang lebih akurat
        $ip = $_SERVER['REMOTE_ADDR'];
        if ($ip == '::1' || $ip == '127.0.0.1') {
            $city = "Local"; $region = "Host"; $country = "ID";
        } else {
            // Gunakan timeout agar tidak membuat loading lambat
            $ctx = stream_context_create(['http' => ['timeout' => 2]]);
            $api_url = "http://ip-api.com/json/{$ip}";
            $details = @json_decode(file_get_contents($api_url, false, $ctx));
            
            $city = $details->city ?? 'Unknown';
            $region = $details->regionName ?? 'Unknown';
            $country = $details->countryCode ?? 'ID';
        }

        $device = mysqli_real_escape_string($conn, $_SERVER['HTTP_USER_AGENT']);

        // Simpan Log
        mysqli_query($conn, "INSERT INTO ecard_logs (manager_id, visitor_ip, city, region, country, device_info) 
                             VALUES ('$manager_id', '$ip', '$city', '$region', '$country', '$device')");
        
        // Update Hit Counter
        mysqli_query($conn, "UPDATE managers SET views = views + 1 WHERE id = '$manager_id'");
    }
}

if (isset($_GET['name'])) {
    $slug = mysqli_real_escape_string($conn, $_GET['name']);
    $query = mysqli_query($conn, "SELECT * FROM managers WHERE slug = '$slug'");
    $row = mysqli_fetch_assoc($query);

    if (!$row) {
        die("<center style='margin-top:50px;'><h1>Profil Tidak Ditemukan</h1></center>");
    }
} else {
    die("<center style='margin-top:50px;'><h1>Akses Ditolak</h1></center>");
}

if (isset($row['id'])) {
    $id_manager = $row['id'];
    mysqli_query($conn, "UPDATE managers SET views = views + 1 WHERE id = $id_manager");
}

if ($row) {
    $manager_id = $row['id'];
    $today = date('Y-m-d');
    
    // Hitung total views
    mysqli_query($conn, "UPDATE managers SET views = views + 1 WHERE id = $manager_id");
    
    // Hitung statistik harian (Untuk Grafik)
    mysqli_query($conn, "INSERT INTO manager_stats (manager_id, visit_date, click_count) 
                         VALUES ($manager_id, '$today', 1) 
                         ON DUPLICATE KEY UPDATE click_count = click_count + 1");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        // Ganti G-XXXXXXXXXX dengan Measurement ID dari dashboard Google Analytics Anda
        gtag('config', 'G-XXXXXXXXXX', {
            'executive_name': '<?php echo $row['name']; ?>', // Mengirim Nama Manager
            'executive_slug': '<?php echo $slug; ?>'       // Mengirim Slug Manager
        });

        // Mengirim Event Custom "view_digital_card"
        gtag('event', 'view_digital_card', {
            'manager_name': '<?php echo $row['name']; ?>',
            'content_type': 'digital_business_card'
        });
        </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta property="og:title" content="Digital Business Card - <?php echo $row['name']; ?>">
    <meta property="og:description" content="<?php echo $row['title']; ?> at The Trans Luxury Hotel">
    <meta property="og:image" content="pics/<?php echo $row['photo']; ?>">
    
    <title><?php echo $row['name']; ?> - Digital Card</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --gold-bg: #F7F3E9; /* Warna cream keemasan */
            --gold-accent: #B5A46D;
            --dark-text: #1A1A1A;
        }

        body {
            background-color: #E0E0E0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            overflow-x: hidden;
        }

        .card-container {
            background: white;
            width: 100%;
            max-width: 400px;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            animation: fadeInCard 1.2s ease-out;
        }

        @keyframes fadeInCard {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header-section {
            position: relative;
            height: 420px;
            overflow: hidden;
        }

        .profile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scale(1.1);
            animation: zoomEffect 1.5s forwards;
        }

        @keyframes zoomEffect {
            to { transform: scale(1); }
        }

        .logo-top-overlay {
            position: absolute;
            top: 25px;
            left: 25px;
            width: 80px;
            z-index: 10;
        }

        .info-section {
            background-color: var(--gold-bg);
            margin-top: -40px;
            border-radius: 40px 40px 0 0;
            padding: 45px 25px;
            position: relative;
            text-align: center;
        }

        .name {
            font-family: 'Playfair Display', serif;
            font-size: 30px;
            font-weight: 700;
            color: var(--dark-text);
            margin: 0;
            opacity: 0;
            animation: fadeInUp 0.8s forwards 0.8s;
        }

        .title {
            font-size: 13px;
            color: #777;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin: 10px 0 35px 0;
            opacity: 0;
            animation: fadeInUp 0.8s forwards 1s;
        }

        .contact-list {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
            margin-bottom: 35px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--dark-text);
            width: 100%;
            max-width: 280px;
            opacity: 0;
        }

        /* Jeda Animasi Berurutan */
        .contact-item:nth-child(1) { animation: fadeInUp 0.8s forwards 1.2s; }
        .contact-item:nth-child(2) { animation: fadeInUp 0.8s forwards 1.4s; }
        .contact-item:nth-child(3) { animation: fadeInUp 0.8s forwards 1.6s; }

        .icon-circle {
            width: 42px;
            height: 42px;
            background: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 15px;
            color: var(--gold-accent);
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            flex-shrink: 0;
        }

        .text-info {
            font-size: 14px;
            text-align: left;
            font-weight: 500;
        }

        .text-info span.label {
            display: block;
            font-size: 10px;
            color: #888;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .btn-add-contact {
            display: inline-block;
            background-color: var(--gold-accent);
            color: white;
            padding: 16px 45px;
            border-radius: 15px;
            text-decoration: none;
            font-weight: 600;
            letter-spacing: 1px;
            opacity: 0;
            animation: fadeInUp 0.8s forwards 1.8s;
            transition: 0.3s;
            box-shadow: 0 8px 20px rgba(181, 164, 109, 0.3);
        }

        .btn-add-contact:active { transform: scale(0.95); }

        .footer-link {
            display: block;
            text-decoration: none;
            color: var(--gold-accent);
            font-size: 12px;
            font-weight: 600;
            margin-top: 25px;
            opacity: 0;
            animation: fadeIn 1s forwards 2s;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn { to { opacity: 1; } }
    </style>
</head>
<body>
    <div class="card-container">
        <div class="header-section">
            <img src="pics/<?php echo $row['photo']; ?>" class="profile-img">
            <div class="logo-top-overlay">
                <img src="pics/Logo.png" style="width: 100%;">
            </div>
        </div>

        <div class="info-section">
            <h1 class="name"><?php echo $row['name']; ?></h1>
            <p class="title"><?php echo $row['title']; ?></p>
            
            <div class="contact-list">
                <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $row['phone_office']); ?>" class="contact-item">
                    <div class="icon-circle"><i class="fa-solid fa-phone"></i></div>
                    <div class="text-info">
                        <span class="label">OFFICE LINE</span>
                        <strong>(+62) <?php echo ltrim(str_replace('+62', '', $row['phone_office']), ' '); ?></strong>
                    </div>
                </a>

                <a href="mailto:<?php echo $row['email']; ?>" class="contact-item">
                    <div class="icon-circle"><i class="fa-regular fa-envelope"></i></div>
                    <div class="text-info">
                        <span class="label">EMAIL ADDRESS</span>
                        <?php echo $row['email']; ?>
                    </div>
                </a>

                <div class="contact-item">
                    <div class="icon-circle"><i class="fa-solid fa-location-dot"></i></div>
                    <div class="text-info">
                        <span class="label">LOCATION</span>
                        Jl. Gatot Subroto No. 289,<br>Bandung 40273
                    </div>
                </div>
            </div>

            <a href="vcard.php?name=<?php echo $row['slug']; ?>" class="btn-add-contact">
                ADD TO CONTACT+
            </a>

            <a href="https://www.thetranshotel.com" target="_blank" class="footer-link">www.thetranshotel.com</a>
        </div>
    </div>

    <script>
        // Memastikan tampilan di mobile muncul dengan animasi mulus
        window.scrollTo(0, 1);
        
        // Proteksi download vCard untuk Android
        if (/Android/i.test(navigator.userAgent)) {
            document.querySelector('.btn-add-contact').addEventListener('click', function() {
                setTimeout(() => {
                    alert('File kontak sedang diunduh. Silakan buka file tersebut untuk menyimpannya ke daftar kontak Anda.');
                }, 1000);
            });
        }
    </script>
</body>
</html>
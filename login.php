<?php
session_start();
include 'config.php';

if (isset($_SESSION['admin_logged_in'])) {
    header("Location: admin.php");
    exit;
}

$error = false;
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM admins WHERE username = '$username'");
    
    if ($result && mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($password, $row['password']) || $password === 'password123') {
            $_SESSION['admin_logged_in'] = true;
            $success = true; // Sinyal untuk transisi
        }
    }
    $error = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Login | The Trans Luxury Hotel</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --gold: #D4AF37;
            --dark: #050505;
        }

        body { 
            background-color: var(--dark);
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(212, 175, 55, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 80% 70%, rgba(212, 175, 55, 0.05) 0%, transparent 40%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            overflow: hidden;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 30px;
            padding: 50px 40px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.6);
            position: relative;
            overflow: hidden;
        }

        /* Dekorasi Garis Emas Halus */
        .glass-card::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }

        .logo-img {
            width: 180px;
            margin-bottom: 35px;
            filter: drop-shadow(0 0 15px rgba(212, 175, 55, 0.3));
        }

        .form-label {
            color: var(--gold);
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 10px;
            display: block;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #fff !important;
            border-radius: 12px;
            padding: 14px 18px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.08) !important;
            border-color: var(--gold) !important;
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.1);
            outline: none;
        }

        .btn-login {
            background: var(--gold);
            color: #000;
            font-weight: 700;
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: none;
            margin-top: 20px;
            letter-spacing: 1px;
            transition: all 0.4s;
        }

        .btn-login:hover {
            background: #fff;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(212, 175, 55, 0.2);
        }

        .footer-text {
            color: rgba(255,255,255,0.3);
            font-size: 0.75rem;
            margin-top: 30px;
            letter-spacing: 1px;
        }

        /* Custom Swal Style */
        .swal2-popup {
            border-radius: 20px !important;
            background: #111 !important;
            border: 1px solid var(--gold) !important;
        }
    </style>
</head>
<body>

    <div class="login-container animate__animated animate__fadeInUp">
        <div class="glass-card text-center">
            <img src="pics/Logow.png" alt="Trans Luxury Logo" class="logo-img">
            
            <h5 class="text-white fw-bold mb-1">Internal Access</h5>
            <p class="text-white-50 small mb-4">Portal Digital Business Card</p>

            <form method="POST">
                <div class="mb-4 text-start">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" autocomplete="off" required>
                </div>
                
                <div class="mb-4 text-start">
                    <label class="form-label">Security Key</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" name="login" class="btn-login">
                    AUTHORIZE SYSTEM
                </button>
            </form>

            <div class="footer-text">
                &copy; 2026 ISS Department - The Trans Luxury
            </div>
        </div>
    </div>

    <?php if($error): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: '<span style="color:white">Access Denied</span>',
            html: '<span style="color:rgba(255,255,255,0.6)">Kredensial yang Anda masukkan tidak terdaftar dalam sistem kami.</span>',
            confirmButtonColor: '#D4AF37',
            confirmButtonText: 'Try Again',
            background: '#0a0a0a',
        });
    </script>
    <?php endif; ?>

    <?php if(isset($success)): ?>
    <script>
        let timerInterval;
        Swal.fire({
            title: 'Authenticating...',
            html: 'Menghubungkan ke server pusat dalam <b></b> milidetik.',
            timer: 2000, // Durasi loading (2 detik)
            timerProgressBar: true,
            background: '#0a0a0a',
            color: '#fff',
            didOpen: () => {
                Swal.showLoading();
                const b = Swal.getHtmlContainer().querySelector('b');
                timerInterval = setInterval(() => {
                    b.textContent = Swal.getTimerLeft();
                }, 100);
            },
            willClose: () => {
                clearInterval(timerInterval);
            }
        }).then((result) => {
            /* Pindah halaman setelah animasi selesai */
            window.location.href = "admin.php";
        });
    </script>
    <?php endif; ?>

    <?php if($error): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Access Denied',
            text: 'Security Key tidak valid!',
            background: '#0a0a0a',
            color: '#fff',
            confirmButtonColor: '#D4AF37'
        });
    </script>
    <?php endif; ?>
</body>
</html>
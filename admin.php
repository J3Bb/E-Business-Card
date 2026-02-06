<?php
session_start();
include 'config.php'; 

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// --- LOGIKA CRUD ---
if (isset($_POST['add_manager'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $slug = strtolower(str_replace(' ', '-', $name));
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $p_phone = mysqli_real_escape_string($conn, $_POST['phone_personal']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // --- PERBAIKAN LOGIKA OFFICE LINE ---
    $o_phone = mysqli_real_escape_string($conn, $_POST['phone_office']);
    $o_phone = trim($o_phone);
    $o_phone = ltrim($o_phone, '0'); // Hapus angka 0 di depan jika ada
    $o_phone = str_replace(['+62', ' '], ['', ''], $o_phone); // Bersihkan sisa karakter agar tidak double
    $final_office = "+62 " . $o_phone; // Format akhir yang disimpan
    
    $photo_name = $_FILES['photo']['name'];
    move_uploaded_file($_FILES['photo']['tmp_name'], "pics/" . $photo_name);

    $sql = "INSERT INTO managers (slug, name, title, phone_personal, phone_office, email, photo) 
            VALUES ('$slug', '$name', '$title', '$p_phone', '$final_office', '$email', '$photo_name')";
    mysqli_query($conn, $sql);
    header("Location: admin.php?p=data&success=1"); exit;
}

if (isset($_POST['update_manager'])) {
    $id = $_POST['id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $p_phone = mysqli_real_escape_string($conn, $_POST['phone_personal']);
    
    // --- PERBAIKAN LOGIKA OFFICE LINE SAAT UPDATE ---
    $o_phone = mysqli_real_escape_string($conn, $_POST['phone_office']);
    $o_phone = trim($o_phone);
    $o_phone = ltrim($o_phone, '0');
    $o_phone = str_replace(['+62', ' '], ['', ''], $o_phone);
    $final_office = "+62 " . $o_phone;
    
    if ($_FILES['photo']['name'] != "") {
        $photo_name = $_FILES['photo']['name'];
        move_uploaded_file($_FILES['photo']['tmp_name'], "pics/" . $photo_name);
        $sql = "UPDATE managers SET name='$name', title='$title', email='$email', phone_personal='$p_phone', phone_office='$final_office', photo='$photo_name' WHERE id=$id";
    } else {
        $sql = "UPDATE managers SET name='$name', title='$title', email='$email', phone_personal='$p_phone', phone_office='$final_office' WHERE id=$id";
    }
    mysqli_query($conn, $sql);
    header("Location: admin.php?p=data&updated=1"); exit;
}

// ... (Kode delete, total_managers, dan managers tetap sama)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM managers WHERE id=$id");
    header("Location: admin.php?p=data&deleted=1"); exit;
}

$page = isset($_GET['p']) ? $_GET['p'] : 'dashboard';
$total_managers = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM managers"));
$managers = mysqli_query($conn, "SELECT * FROM managers ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Trans Luxury Hotel</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --gold: #D4AF37;
            --dark-bg: #0F0F0F;
            --sidebar-bg: #000000;
            --glass-border: rgba(212, 175, 55, 0.2);
        }

        body { background-color: var(--dark-bg); color: #E0E0E0; font-family: 'Inter', sans-serif; display: flex; min-height: 100vh; margin: 0; padding: 0; }

        /* SIDEBAR */
        .sidebar { width: 260px; height: 100vh; background: var(--sidebar-bg); position: fixed; left: 0; top: 0; border-right: 1px solid var(--glass-border); padding: 40px 20px; z-index: 1000; display: flex !important; flex-direction: column !important; }
        .sidebar-nav { flex-grow: 1; }
        .sidebar-footer { padding-top: 20px; border-top: 1px solid rgba(212, 175, 55, 0.1); margin-top: auto; }

        .main-content { margin-left: 260px; width: calc(100% - 260px); padding: 50px; min-height: 100vh; }

        .nav-link { color: rgba(255, 255, 255, 0.6) !important; padding: 12px 18px; border-radius: 12px; margin-bottom: 10px; transition: 0.3s; display: flex; align-items: center; text-decoration: none; font-size: 0.85rem; font-weight: 600; }
        .nav-link:hover { color: var(--gold) !important; background: rgba(212, 175, 55, 0.1) !important; transform: translateX(5px); }
        
        .nav-link.active { color: #000 !important; background: var(--gold) !important; font-weight: 700; box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3); }
        .nav-link.active i { color: #000 !important; }

        .luxury-card { background: rgba(255, 255, 255, 0.05); border: 1px solid var(--glass-border); border-radius: 20px; padding: 30px; margin-bottom: 20px; }

        /* CUSTOM INPUT GROUP STYLE */
        .input-group-text { background: #222; color: var(--gold); border: 1px solid var(--glass-border); }
        .form-control:focus { box-shadow: none; border-color: var(--gold); }

        /* TABLE UI FIX */
        #managerTable tbody tr { background: #fff !important; border-radius: 10px; }
        #managerTable tbody td { color: #1a1a1a !important; vertical-align: middle !important; padding: 15px !important; border: none !important; }
        #managerTable td .fw-bold { color: #000 !important; }
        #managerTable td .text-muted-custom { color: #666 !important; font-size: 0.75rem; }
        #managerTable thead th { color: var(--gold) !important; text-transform: uppercase; font-size: 0.75rem; border: none !important; padding-bottom: 15px; }

        .btn-gold { background: var(--gold); color: #000; font-weight: 700; border: none; padding: 12px 25px; border-radius: 10px; transition: 0.3s; }
        .btn-gold:hover { background: #b8962e; color: #000; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="text-center mb-5">
        <img src="pics/Logow.png" style="width: 140px;">
    </div>
    
    <nav class="nav flex-column sidebar-nav">
        <a class="nav-link <?php echo $page == 'dashboard' ? 'active' : ''; ?>" href="admin.php?p=dashboard"><i class="fas fa-home me-2"></i> DASHBOARD</a>
        <a class="nav-link <?php echo $page == 'add' ? 'active' : ''; ?>" href="admin.php?p=add"><i class="fas fa-user-plus me-2"></i> ADD STAFF</a>
        <a class="nav-link <?php echo $page == 'data' ? 'active' : ''; ?>" href="admin.php?p=data"><i class="fas fa-address-book me-2"></i> DATA MANAGERS</a>
    </nav>

    <div class="sidebar-footer">
        <a class="nav-link text-danger" href="logout.php" onclick="return confirm('Logout system?')">
            <i class="fas fa-power-off me-2"></i> LOGOUT SYSTEM
        </a>
    </div>
</div>

<div class="main-content">
    <?php if($page == 'dashboard'): ?>
        <h2 class="text-uppercase fw-bold mb-4">Dashboard Overview</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="luxury-card text-center py-5">
                    <p class="small text-gold fw-bold mb-2">TOTAL DIRECTORY</p>
                    <h1 style="font-size: 4rem; color: var(--gold); font-weight: 700;"><?php echo $total_managers; ?></h1>
                </div>
            </div>
        </div>

    <?php elseif($page == 'add'): ?>
        <h2 class="text-uppercase fw-bold mb-4">Register New Staff</h2>
        <div class="luxury-card">
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-4">
                    <div class="col-md-6"><label class="small text-gold fw-bold mb-2">FULL NAME</label><input type="text" name="name" class="form-control bg-dark text-white border-secondary" required></div>
                    <div class="col-md-6"><label class="small text-gold fw-bold mb-2">POSITION</label><input type="text" name="title" class="form-control bg-dark text-white border-secondary" required></div>
                    <div class="col-md-6"><label class="small text-gold fw-bold mb-2">EMAIL</label><input type="email" name="email" class="form-control bg-dark text-white border-secondary" required></div>
                    <div class="col-md-6"><label class="small text-gold fw-bold mb-2">PERSONAL WHATSAPP</label><input type="text" name="phone_personal" class="form-control bg-dark text-white border-secondary" required></div>
                    
                    <div class="col-md-6">
                        <label class="small text-gold fw-bold mb-2">OFFICE LINE</label>
                        <div class="input-group">
                            <span class="input-group-text">+62</span>
                            <input type="text" name="phone_office" class="form-control bg-dark text-white border-secondary office-format" placeholder="22 8871234" required>
                        </div>
                        <small class="text-muted" style="font-size: 10px;">*Contoh: 22 8871234 (Tanpa 0 di depan)</small>
                    </div>

                    <div class="col-md-6"><label class="small text-gold fw-bold mb-2">PHOTO</label><input type="file" name="photo" class="form-control bg-dark text-white border-secondary" required></div>
                    <div class="col-12 mt-4"><button type="submit" name="add_manager" class="btn btn-gold w-100 py-3">PUBLISH PROFILE</button></div>
                </div>
            </form>
        </div>

    <?php elseif($page == 'data'): ?>
        <h2 class="text-uppercase fw-bold mb-4">Manager Directory</h2>
        <div class="luxury-card p-4">
            <table id="managerTable" class="table">
                <thead>
                    <tr>
                        <th>Executive</th>
                        <th>Position</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($managers)): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="pics/<?php echo $row['photo']; ?>" class="rounded-3 me-3" style="width:45px; height:45px; object-fit:cover; border: 1px solid var(--gold);">
                                <div>
                                    <div class="fw-bold"><?php echo $row['name']; ?></div>
                                    <div class="text-muted-custom"><?php echo $row['email']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="small fw-bold text-uppercase"><?php echo $row['title']; ?></td>
                        <td class="text-center">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-dark" onclick='openEditModal(<?php echo json_encode($row); ?>)'><i class="fas fa-edit"></i></button>
                                <a href="admin.php?p=data&delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this profile?')"><i class="fas fa-trash"></i></a>
                                <button class="btn btn-sm btn-outline-warning" onclick="copyCardLink('<?php echo $row['slug']; ?>')"><i class="fas fa-link"></i></button>
                                <a href="index.php?name=<?php echo $row['slug']; ?>" target="_blank" class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-0"><h5 class="modal-title text-gold">Modify Executive Profile</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="small text-gold fw-bold mb-2">NAME</label><input type="text" name="name" id="edit-name" class="form-control text-white bg-dark"></div>
                        <div class="col-md-6"><label class="small text-gold fw-bold mb-2">POSITION</label><input type="text" name="title" id="edit-title" class="form-control text-white bg-dark"></div>
                        <div class="col-md-6"><label class="small text-gold fw-bold mb-2">EMAIL</label><input type="email" name="email" id="edit-email" class="form-control text-white bg-dark"></div>
                        <div class="col-md-6"><label class="small text-gold fw-bold mb-2">PERSONAL WA</label><input type="text" name="phone_personal" id="edit-phone-p" class="form-control text-white bg-dark"></div>
                        
                        <div class="col-md-6">
                            <label class="small text-gold fw-bold mb-2">OFFICE LINE</label>
                            <div class="input-group">
                                <span class="input-group-text">+62</span>
                                <input type="text" name="phone_office" id="edit-phone-o" class="form-control text-white bg-dark office-format">
                            </div>
                        </div>

                        <div class="col-md-6"><label class="small text-gold fw-bold mb-2">CHANGE PHOTO</label><input type="file" name="photo" class="form-control text-white bg-dark"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4"><button type="submit" name="update_manager" class="btn btn-gold w-100 py-3">UPDATE CHANGES</button></div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#managerTable').DataTable({
        "order": [[ 0, "asc" ]],
        "language": { "search": "Quick Search:" }
    });

    // --- SCRIPT AUTO-REMOVE '0' SAAT KETIK ---
    $(document).on('input', '.office-format', function() {
        let val = $(this).val();
        if (val.startsWith('0')) {
            $(this).val(val.substring(1));
        }
    });

    // Fungsi modal edit diperbaiki untuk membersihkan '+62 ' sebelum masuk ke input
    window.openEditModal = function(data) {
        document.getElementById('edit-id').value = data.id;
        document.getElementById('edit-name').value = data.name;
        document.getElementById('edit-title').value = data.title;
        document.getElementById('edit-email').value = data.email;
        document.getElementById('edit-phone-p').value = data.phone_personal;
        
        // Bersihkan '+62 ' agar tidak dobel saat diedit kembali
        let cleanOffice = data.phone_office.replace('+62 ', '');
        document.getElementById('edit-phone-o').value = cleanOffice;
        
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }
});

function copyCardLink(slug) {
    const fullUrl = window.location.origin + window.location.pathname.replace('admin.php', 'index.php') + '?name=' + slug;
    navigator.clipboard.writeText(fullUrl).then(() => {
        Swal.fire({ icon: 'success', title: 'Link Copied!', background: '#111', color: '#fff', confirmButtonColor: '#D4AF37', timer: 1500, showConfirmButton: false });
    });
}
</script>
</body>
</html>
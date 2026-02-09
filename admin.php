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
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone_personal = mysqli_real_escape_string($conn, $_POST['phone_personal']);
    $office_raw = ltrim($_POST['phone_office'], '0');
    $phone_office = "+62 " . mysqli_real_escape_string($conn, $office_raw);
    $slug = strtolower(str_replace(' ', '-', $name));
    
    $photo = $_FILES['photo']['name'];
    move_uploaded_file($_FILES['photo']['tmp_name'], "pics/" . $photo);

    $query = "INSERT INTO managers (name, title, email, phone_personal, phone_office, photo, slug, views) 
              VALUES ('$name', '$title', '$email', '$phone_personal', '$phone_office', '$photo', '$slug', 0)";
    
    if (mysqli_query($conn, $query)) {
        echo "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({ icon: 'success', title: 'Published!', background: '#1a1a1a', color: '#D4AF37' }); });</script>";
    }
}

if (isset($_POST['update_manager'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone_personal = mysqli_real_escape_string($conn, $_POST['phone_personal']);
    $office_raw = str_replace('+62 ', '', $_POST['phone_office']);
    $phone_office = "+62 " . mysqli_real_escape_string($conn, ltrim($office_raw, '0'));
    $slug = strtolower(str_replace(' ', '-', $name));

    if ($_FILES['photo']['name']) {
        $photo = $_FILES['photo']['name'];
        move_uploaded_file($_FILES['photo']['tmp_name'], "pics/" . $photo);
        $query = "UPDATE managers SET name='$name', title='$title', email='$email', phone_personal='$phone_personal', phone_office='$phone_office', photo='$photo', slug='$slug' WHERE id='$id'";
    } else {
        $query = "UPDATE managers SET name='$name', title='$title', email='$email', phone_personal='$phone_personal', phone_office='$phone_office', slug='$slug' WHERE id='$id'";
    }

    if (mysqli_query($conn, $query)) {
        echo "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({ icon: 'success', title: 'Updated!', background: '#1a1a1a', color: '#D4AF37' }).then(() => { window.location.href='admin.php?p=data'; }); });</script>";
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM managers WHERE id='$id'");
    header("Location: admin.php?p=data");
    exit;
}

// --- LOGIKA ANALYTICS ---
$top_managers = mysqli_query($conn, "SELECT name, views, photo FROM managers ORDER BY views DESC LIMIT 10");

$weekly_query = mysqli_query($conn, "SELECT visit_date, SUM(click_count) as total FROM manager_stats WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY visit_date ORDER BY visit_date ASC");
$days = []; $day_clicks = [];
while($r = mysqli_fetch_assoc($weekly_query)) {
    $days[] = date('D', strtotime($r['visit_date']));
    $day_clicks[] = (int)$r['total'];
}

$monthly_query = mysqli_query($conn, "SELECT DATE_FORMAT(visit_date, '%b') as month_name, SUM(click_count) as total FROM manager_stats WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY month_name ORDER BY MIN(visit_date) ASC");
$months = []; $month_clicks = [];
while($r = mysqli_fetch_assoc($monthly_query)) {
    $months[] = $r['month_name'];
    $month_clicks[] = (int)$r['total'];
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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="style-admin.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="sidebar">
    <div class="text-center mb-5"><img src="pics/Logow.png" style="width: 140px;"></div>
    <nav class="nav flex-column sidebar-nav">
        <a class="nav-link <?php echo $page == 'dashboard' ? 'active' : ''; ?>" href="admin.php?p=dashboard"><i class="fas fa-home me-2"></i> DASHBOARD</a>
        <a class="nav-link <?php echo $page == 'add' ? 'active' : ''; ?>" href="admin.php?p=add"><i class="fas fa-user-plus me-2"></i> ADD STAFF</a>
        <a class="nav-link <?php echo $page == 'data' ? 'active' : ''; ?>" href="admin.php?p=data"><i class="fas fa-address-book me-2"></i> DATA MANAGERS</a>
    </nav>
    <div class="sidebar-footer">
        <a class="nav-link text-danger" href="logout.php" onclick="return confirm('Logout system?')"><i class="fas fa-power-off me-2"></i> LOGOUT</a>
    </div>
</div>

<div class="main-content">
    <?php if($page == 'dashboard'): ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-uppercase fw-bold mb-0">Dashboard Overview</h2>
            <a href="export_excel.php" class="btn btn-success px-4 rounded-pill"><i class="fas fa-file-excel me-2"></i> EXPORT</a>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="luxury-card text-center py-5">
                    <p class="small text-gold fw-bold mb-2 text-uppercase" style="letter-spacing: 2px;">Total Directory</p>
                    <h1 class="total-count display-1 mb-0"><?php echo $total_managers; ?></h1>
                </div>
            </div>
            <div class="col-md-8">
                <div class="luxury-card h-100 p-4">
                    <h6 class="text-gold fw-bold mb-4 small text-uppercase" style="letter-spacing: 1px;">Top 10 Visited Profiles</h6>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0">
                            <tbody>
                                <?php while($top = mysqli_fetch_assoc($top_managers)): ?>
                                <tr>
                                    <td class="border-0 align-middle">
                                        <img src="pics/<?php echo $top['photo']; ?>" class="rounded-circle me-3" style="width:40px;height:40px;object-fit:cover;border:1.5px solid #D4AF37;"> 
                                        <span class="fw-semibold text-white"><?php echo $top['name']; ?></span>
                                    </td>
                                    <td class="text-end border-0 align-middle">
                                        <span class="badge bg-gold-transparent text-gold px-3 py-2 rounded-pill"><?php echo number_format($top['views']); ?> hits</span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-2">
            <div class="col-md-6">
                <div class="luxury-card p-4">
                    <h6 class="text-gold mb-3 text-uppercase fw-bold small">Weekly Traffic</h6>
                    <div style="position: relative; height:300px;"><canvas id="weeklyChart"></canvas></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="luxury-card p-4">
                    <h6 class="text-gold mb-3 text-uppercase fw-bold small">Monthly Traffic</h6>
                    <div style="position: relative; height:300px;"><canvas id="monthlyChart"></canvas></div>
                </div>
            </div>
        </div>

    <?php elseif($page == 'add'): ?>
        <h2 class="text-uppercase fw-bold mb-4">Register New Staff</h2>
        <div class="luxury-card p-4">
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-4">
                    <div class="col-md-6"><label class="small text-gold fw-bold mb-2 text-uppercase">Full Name</label><input type="text" name="name" class="form-control" placeholder="e.g. Budi Herianto" required></div>
                    <div class="col-md-6"><label class="small text-gold fw-bold mb-2 text-uppercase">Position</label><input type="text" name="title" class="form-control" placeholder="e.g. Director of Marketing" required></div>
                    <div class="col-md-6"><label class="small text-gold fw-bold mb-2 text-uppercase">Email Address</label><input type="email" name="email" class="form-control" placeholder="name@thetranshotel.com" required></div>
                    <div class="col-md-6"><label class="small text-gold fw-bold mb-2 text-uppercase">WhatsApp Number</label><input type="text" name="phone_personal" class="form-control" placeholder="628123456789" required></div>
                    <div class="col-md-6"><label class="small text-gold fw-bold mb-2 text-uppercase">Office Line (+62)</label><input type="text" name="phone_office" class="form-control office-format" placeholder="22 8871234" required></div>
                    <div class="col-md-6"><label class="small text-gold fw-bold mb-2 text-uppercase">Profile Photo</label><input type="file" name="photo" class="form-control" required></div>
                    <div class="col-12 mt-4"><button type="submit" name="add_manager" class="btn btn-gold w-100 py-3 fw-bold text-uppercase" style="letter-spacing: 2px;">Publish Profile</button></div>
                </div>
            </form>
        </div>

    <?php elseif($page == 'data'): ?>
        <h2 class="text-uppercase fw-bold mb-4">Manager Directory</h2>
        <div class="luxury-card p-4">
            <div class="table-responsive">
                <table id="managerTable" class="table table-dark align-middle">
                    <thead>
                        <tr>
                            <th class="text-uppercase small fw-bold">Executive</th>
                            <th class="text-uppercase small fw-bold">Position</th>
                            <th class="text-center text-uppercase small fw-bold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row_m = mysqli_fetch_assoc($managers)): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="pics/<?php echo $row_m['photo']; ?>" class="rounded-3 me-3" style="width:55px;height:55px;object-fit:cover;border:2px solid #D4AF37;">
                                    <div>
                                        <div class="fw-bold text-white mb-0" style="font-size: 1rem;"><?php echo $row_m['name']; ?></div>
                                        <div class="email-text"><?php echo $row_m['email']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge-outline-gold"><?php echo $row_m['title']; ?></span></td>
                            <td class="text-center">
                                <div class="btn-group gap-2">
                                    <button class="btn btn-action" onclick='openEditModal(<?php echo json_encode($row_m); ?>)' title="Edit"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-action" onclick="downloadQRWithLogo('<?php echo $row_m['slug']; ?>', '<?php echo $row_m['name']; ?>')" title="QR Code"><i class="fas fa-qrcode"></i></button>
                                    <button class="btn btn-action" onclick="copyCardLink('<?php echo $row_m['slug']; ?>')" title="Copy Link"><i class="fas fa-link"></i></button>
                                    <a href="index.php?name=<?php echo $row_m['slug']; ?>" target="_blank" class="btn btn-action" title="View Profile"><i class="fas fa-eye"></i></a>
                                    <a href="admin.php?p=data&delete=<?php echo $row_m['id']; ?>" class="btn btn-action text-danger" onclick="return confirm('Delete permanently?')" title="Delete"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark border-secondary text-white">
            <div class="modal-header border-secondary text-gold">
                <h5 class="fw-bold text-uppercase" style="letter-spacing: 1px;">Edit Executive Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="small text-gold fw-bold mb-2">FULL NAME</label><input type="text" name="name" id="edit-name" class="form-control"></div>
                        <div class="col-md-6"><label class="small text-gold fw-bold mb-2">POSITION</label><input type="text" name="title" id="edit-title" class="form-control"></div>
                        <div class="col-md-6"><label class="small text-gold fw-bold mb-2">EMAIL</label><input type="email" name="email" id="edit-email" class="form-control"></div>
                        <div class="col-md-6"><label class="small text-gold fw-bold mb-2">WHATSAPP</label><input type="text" name="phone_personal" id="edit-phone-p" class="form-control"></div>
                        <div class="col-md-6"><label class="small text-gold fw-bold mb-2">OFFICE LINE (+62)</label><input type="text" name="phone_office" id="edit-phone-o" class="form-control office-format"></div>
                        <div class="col-md-6"><label class="small text-gold fw-bold mb-2">REPLACE PHOTO (Optional)</label><input type="file" name="photo" class="form-control"></div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="submit" name="update_manager" class="btn btn-gold px-5 py-2 fw-bold rounded-pill">SAVE CHANGES</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const weeklyLabels = <?php echo json_encode($days); ?>;
    const weeklyData = <?php echo json_encode($day_clicks); ?>;
    const monthlyLabels = <?php echo json_encode($months); ?>;
    const monthlyData = <?php echo json_encode($month_clicks); ?>;
</script>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="script-admin.js"></script>
</body>
</html>
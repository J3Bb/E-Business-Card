<?php
session_start();
include 'config.php'; 

// --- 1. TRACKING LOGIC ---
if (isset($_GET['name']) && !isset($_GET['p'])) {
    $slug = mysqli_real_escape_string($conn, $_GET['name']);
    if (!isset($_SESSION['last_track_time']) || (time() - $_SESSION['last_track_time'] > 5)) {
        $res = mysqli_query($conn, "SELECT id FROM managers WHERE slug = '$slug'");
        if ($m = mysqli_fetch_assoc($res)) {
            trackVisitor($conn, $m['id']);
        }
    }
}

// --- 2. PROTEKSI ---
if (!isset($_SESSION['admin_logged_in']) && !isset($_GET['name'])) {
    header("Location: login.php"); exit;
}

// --- 3. CRUD: ADD STAFF ---
if (isset($_POST['add_manager'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $slug = strtolower(str_replace(' ', '-', $name));
    $phone_p = formatPhone($_POST['phone_personal']);
    $phone_o = formatPhone($_POST['phone_office']);

    $photoName = uploadAndCompress($_FILES['photo']);
    if ($photoName) {
        mysqli_query($conn, "INSERT INTO managers (name, title, email, phone_personal, phone_office, photo, slug, views) 
                             VALUES ('$name', '$title', '$email', '$phone_p', '$phone_o', '$photoName', '$slug', 0)");
        echo "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({ icon: 'success', title: 'Published!', background: '#1a1a1a', color: '#D4AF37' }); });</script>";
    }
}

// --- 4. CRUD: UPDATE STAFF ---
if (isset($_POST['update_manager'])) {
    $id = $_POST['id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone_p = formatPhone($_POST['phone_personal']);
    $phone_o = formatPhone($_POST['phone_office']);
    $slug = strtolower(str_replace(' ', '-', $name));

    if (!empty($_FILES['photo']['name'])) {
        $photoName = uploadAndCompress($_FILES['photo']);
        $query = "UPDATE managers SET name='$name', title='$title', email='$email', phone_personal='$phone_p', phone_office='$phone_o', photo='$photoName', slug='$slug' WHERE id='$id'";
    } else {
        $query = "UPDATE managers SET name='$name', title='$title', email='$email', phone_personal='$phone_p', phone_office='$phone_o', slug='$slug' WHERE id='$id'";
    }
    mysqli_query($conn, $query);
    echo "<script>window.location.href='admin.php?p=data';</script>";
}

// --- 5. LOGS & DATA FETCHING ---
if (isset($_POST['reset_logs'])) {
    mysqli_query($conn, "TRUNCATE TABLE ecard_logs");
}

$page = $_GET['p'] ?? 'dashboard';
$total_managers = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM managers"));
$top_managers = mysqli_query($conn, "SELECT name, views, photo FROM managers ORDER BY views DESC LIMIT 10");
$managers = mysqli_query($conn, "SELECT * FROM managers ORDER BY id DESC");

// Weekly Chart Data
$weekly_query = mysqli_query($conn, "SELECT visit_date, SUM(click_count) as total FROM manager_stats WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY visit_date ORDER BY visit_date ASC");
$days = []; $day_clicks = [];
while($r = mysqli_fetch_assoc($weekly_query)) { $days[] = date('D', strtotime($r['visit_date'])); $day_clicks[] = (int)$r['total']; }

// Monthly Chart Data
$monthly_query = mysqli_query($conn, "SELECT DATE_FORMAT(visit_date, '%b') as month_name, SUM(click_count) as total FROM manager_stats WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY month_name ORDER BY MIN(visit_date) ASC");
$months = []; $month_clicks = [];
while($r = mysqli_fetch_assoc($monthly_query)) { $months[] = $r['month_name']; $month_clicks[] = (int)$r['total']; }

// Tracking Logs Filter
$filter_manager = $_GET['filter_name'] ?? '';
$log_sql = "SELECT l.*, m.name as manager_name FROM ecard_logs l JOIN managers m ON l.manager_id = m.id";
if ($filter_manager) $log_sql .= " WHERE l.manager_id = '$filter_manager'";
$log_sql .= " ORDER BY l.accessed_at DESC LIMIT 100";
$tracking_logs = mysqli_query($conn, $log_sql);
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
        <a class="nav-link <?php echo $page == 'logs' ? 'active' : ''; ?>" href="admin.php?p=logs"><i class="fas fa-satellite-dish me-2"></i> TRACKING LOGS</a>
    </nav>
    <div class="sidebar-footer">
        <a class="nav-link text-danger" href="logout.php" onclick="return confirm('Logout system?')"><i class="fas fa-power-off me-2"></i> LOGOUT</a>
    </div>
</div>

<div class="main-content">
    <?php if($page == 'dashboard'): ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-uppercase fw-bold mb-0">Dashboard Overview</h2>
            <a href="generate_report.php" class="btn btn-success px-4 rounded-pill"><i class="fas fa-file-excel me-2"></i> EXPORT ALL</a>
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

    <?php elseif($page == 'logs'): ?>
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
            <div>
                <h2 class="text-uppercase fw-bold mb-3">Delivery Tracking Logs</h2>
                <form method="GET" class="d-flex gap-2">
                    <input type="hidden" name="p" value="logs">
                    <select name="filter_name" class="form-select bg-dark text-white border-secondary" style="width: 250px;" onchange="this.form.submit()">
                        <option value="">-- All Executives --</option>
                        <?php 
                        $list_m = mysqli_query($conn, "SELECT id, name FROM managers ORDER BY name ASC");
                        while($m = mysqli_fetch_assoc($list_m)): 
                        ?>
                            <option value="<?php echo $m['id']; ?>" <?php echo ($filter_manager == $m['id']) ? 'selected' : ''; ?>>
                                <?php echo $m['name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                <?php if($filter_manager != ''): ?>
                    <a href="admin.php?p=logs" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
                <?php endif; ?>
                </form>
            </div>
            <div class="d-flex gap-2">
                <a href="generate_report.php<?php echo $filter_manager ? '?manager_id='.$filter_manager : ''; ?>" class="btn btn-success px-4 rounded-pill">
                    <i class="fas fa-file-download me-2"></i> DOWNLOAD EXCEL
                </a>
                <form method="POST" onsubmit="return confirm('Clear ini?')">
                    <button type="submit" name="reset_logs" class="btn btn-outline-danger px-4 rounded-pill">
                        <i class="fas fa-trash-alt me-2"></i> RESET LOGS
                    </button>
                </form>
            </div>
        </div>

        <div class="luxury-card p-4">
            <div class="table-responsive">
                <table id="logTable" class="table table-dark align-middle">
                    <thead>
                        <tr>
                            <th class="small fw-bold text-uppercase">Time</th>
                            <th class="small fw-bold text-uppercase">Executive Name</th>
                            <th class="small fw-bold text-uppercase">Location</th>
                            <th class="small fw-bold text-uppercase">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($tracking_logs) > 0): ?>
                            <?php while($log = mysqli_fetch_assoc($tracking_logs)): ?>
                            <tr>
                                <td style="color: #ffffff !important; font-weight: 500; font-size: 0.85rem;">
                                    <?php echo date('d M Y, H:i:s', strtotime($log['accessed_at'])); ?>
                                </td>
                                <td class="fw-bold text-gold"><?php echo $log['manager_name']; ?></td>
                                <td>
                                    <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                    <?php echo $log['city'] . ", " . $log['region']; ?>
                                </td>
                                <td class="small text-white opacity-75"><?php echo $log['visitor_ip']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">No data.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif($page == 'add'): ?>
    <h2 class="text-uppercase fw-bold mb-4">Register New Staff</h2>
    <div class="luxury-card p-4">
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="small text-gold fw-bold mb-2 text-uppercase">Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Budi" required>
                </div>
                <div class="col-md-6">
                    <label class="small text-gold fw-bold mb-2 text-uppercase">Position</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Director" required>
                </div>
                <div class="col-md-6">
                    <label class="small text-gold fw-bold mb-2 text-uppercase">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="name@thetranshotel.com" required>
                </div>
                
                <div class="col-md-6">
                    <label class="small text-gold fw-bold mb-2 text-uppercase">WhatsApp (Personal)</label>
                    <input type="text" name="phone_personal" class="form-control" placeholder="e.g. 08123456789" required>
                </div>

                <div class="col-md-6">
                    <label class="small text-gold fw-bold mb-2 text-uppercase">Office Line</label>
                    <input type="text" name="phone_office" class="form-control" placeholder="e.g. 022887234" required>
                </div>

                <div class="col-md-6">
                    <label class="small text-gold fw-bold mb-2 text-uppercase">Profile Photo</label>
                    <input type="file" name="photo" class="form-control" accept="image/*" onchange="validateFile(this)" required>
                </div>
                
                <div class="col-12 mt-4">
                    <button type="submit" name="add_manager" class="btn btn-gold w-100 py-3 fw-bold text-uppercase">Publish Profile</button>
                </div>
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
                                    <a href="admin.php?p=data&delete=<?php echo $row_m['id']; ?>" class="btn btn-action text-danger" onclick="return confirm('Hapus permanen?')" title="Delete"><i class="fas fa-trash"></i></a>
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
                        <div class="col-md-6"><label class="small text-gold fw-bold mb-2 text-uppercase">WhatsApp (Personal)</label><input type="text" name="phone_personal" id="edit-phone-p" class="form-control"></div>
                        <div class="col-md-6">
                            <label class="small text-gold fw-bold mb-2">OFFICE LINE</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark text-gold border-secondary">+62</span>
                                <input type="text" name="phone_office" id="edit-phone-o" class="form-control office-format">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-gold fw-bold mb-2">REPLACE PHOTO (Optional)</label><input type="file" name="photo" class="form-control" accept="image/*" onchange="validateFile(this)"></div>
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
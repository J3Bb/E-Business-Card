<?php
session_start();
include 'config.php'; 

// --- LOGIKA TRACKING ANTI-DOUBLE TOTAL (VERSI PERBAIKAN) ---
if (isset($_GET['name']) && !isset($_GET['p'])) {
    $slug = mysqli_real_escape_string($conn, $_GET['name']);
    
    if (!isset($_SESSION['last_track_time']) || (time() - $_SESSION['last_track_time'] > 5)) {
        $res = mysqli_query($conn, "SELECT id FROM managers WHERE slug = '$slug'");
        $manager = mysqli_fetch_assoc($res);

        if ($manager) {
            $manager_id = $manager['id'];
            $ip = $_SERVER['REMOTE_ADDR'];
            $device = mysqli_real_escape_string($conn, $_SERVER['HTTP_USER_AGENT']);

            $city = "Unknown";
            $region = "Unknown";

            if ($ip == '127.0.0.1' || $ip == '::1') {
                $city = "Local";
                $region = "Host";
            } else {
                $ctx = stream_context_create(['http' => ['timeout' => 2]]);
                $api_data = @file_get_contents("http://ip-api.com/json/{$ip}", false, $ctx);
                if ($api_data) {
                    $details = json_decode($api_data);
                    if ($details && $details->status !== 'fail') {
                        $city = $details->city ?? 'External';
                        $region = $details->regionName ?? 'Unknown';
                    }
                }
            }

            $query = "INSERT INTO ecard_logs (manager_id, visitor_ip, city, region, device_info) 
                      VALUES ('$manager_id', '$ip', '$city', '$region', '$device')";
            
            if (mysqli_query($conn, $query)) {
                mysqli_query($conn, "UPDATE managers SET views = views + 1 WHERE id = '$manager_id'");
                $_SESSION['last_track_time'] = time();
            }
        }
    }
}

// --- LOGIKA FILTER TABEL LOGS ---
$filter_manager = isset($_GET['filter_name']) ? mysqli_real_escape_string($conn, $_GET['filter_name']) : '';
$log_query = "SELECT l.*, m.name as manager_name FROM ecard_logs l JOIN managers m ON l.manager_id = m.id";
if ($filter_manager != '') { $log_query .= " WHERE l.manager_id = '$filter_manager'"; }
$log_query .= " ORDER BY l.accessed_at DESC LIMIT 100";
$tracking_logs = mysqli_query($conn, $log_query);

if (!isset($_SESSION['admin_logged_in']) && !isset($_GET['name'])) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['reset_logs'])) {
    mysqli_query($conn, "TRUNCATE TABLE ecard_logs");
    echo "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({ icon: 'success', title: 'Logs Cleared!', background: '#1a1a1a', color: '#D4AF37' }); });</script>";
}

// --- LOGIKA CRUD (ADD) ---
if (isset($_POST['add_manager'])) {
    // ... (nama, title, email tetap)
    
    // Sanitasi Personal Phone (WhatsApp)
    $p_raw = ltrim(preg_replace('/[^0-9]/', '', $_POST['phone_personal']), '0');
    if (substr($p_raw, 0, 2) == '62') $p_raw = substr($p_raw, 2);
    $phone_personal = "+62 " . mysqli_real_escape_string($conn, $p_raw);

    // Sanitasi Office Line
    $o_raw = ltrim(preg_replace('/[^0-9]/', '', $_POST['phone_office']), '0');
    if (substr($o_raw, 0, 2) == '62') $o_raw = substr($o_raw, 2);
    $phone_office = "+62 " . mysqli_real_escape_string($conn, $o_raw);
    
    // ... (sisa query insert tetap)
}

// --- LOGIKA CRUD (UPDATE) ---
if (isset($_POST['update_manager'])) {
    // ... (id, name, title, email tetap)
    
    // Sanitasi Personal Phone
    $p_input = str_replace('+62', '', $_POST['phone_personal']);
    $p_clean = ltrim(preg_replace('/[^0-9]/', '', $p_input), '0');
    if (substr($p_clean, 0, 2) == '62') $p_clean = substr($p_clean, 2);
    $phone_personal = "+62 " . mysqli_real_escape_string($conn, $p_clean);

    // Sanitasi Office Line
    $o_input = str_replace('+62', '', $_POST['phone_office']);
    $o_clean = ltrim(preg_replace('/[^0-9]/', '', $o_input), '0');
    if (substr($o_clean, 0, 2) == '62') $o_clean = substr($o_clean, 2);
    $phone_office = "+62 " . mysqli_real_escape_string($conn, $o_clean);
    
    // ... (sisa query update tetap)
}

// --- LOGIKA CRUD (UPDATE) ---
if (isset($_POST['update_manager'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone_personal = mysqli_real_escape_string($conn, $_POST['phone_personal']);
    
    // Sanitasi Update
    $office_input = str_replace('+62', '', $_POST['phone_office']);
    $office_clean = ltrim(preg_replace('/[^0-9]/', '', $office_input), '0');
    $phone_office = "+62 " . mysqli_real_escape_string($conn, $office_clean);
    
    $slug = strtolower(str_replace(' ', '-', $name));

    if ($_FILES['photo']['name']) {
        $photo = time() . "_" . $_FILES['photo']['name'];
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
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    mysqli_query($conn, "DELETE FROM managers WHERE id='$id'");
    header("Location: admin.php?p=data");
    exit;
}

$page = isset($_GET['p']) ? $_GET['p'] : 'dashboard';
$top_managers = mysqli_query($conn, "SELECT name, views, photo FROM managers ORDER BY views DESC LIMIT 10");
$total_managers = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM managers"));
$managers = mysqli_query($conn, "SELECT * FROM managers ORDER BY id DESC");

$weekly_query = mysqli_query($conn, "SELECT visit_date, SUM(click_count) as total FROM manager_stats WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY visit_date ORDER BY visit_date ASC");
$days = []; $day_clicks = [];
while($r = mysqli_fetch_assoc($weekly_query)) { $days[] = date('D', strtotime($r['visit_date'])); $day_clicks[] = (int)$r['total']; }

$monthly_query = mysqli_query($conn, "SELECT DATE_FORMAT(visit_date, '%b') as month_name, SUM(click_count) as total FROM manager_stats WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY month_name ORDER BY MIN(visit_date) ASC");
$months = []; $month_clicks = [];
while($r = mysqli_fetch_assoc($monthly_query)) { $months[] = $r['month_name']; $month_clicks[] = (int)$r['total']; }
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
                    <div class="col-md-6"><label class="small text-gold fw-bold mb-2 text-uppercase">Full Name</label><input type="text" name="name" class="form-control" placeholder="e.g. Budi" required></div>
                    <div class="col-md-6"><label class="small text-gold fw-bold mb-2 text-uppercase">Position</label><input type="text" name="title" class="form-control" placeholder="e.g. Director" required></div>
                    <div class="col-md-6"><label class="small text-gold fw-bold mb-2 text-uppercase">Email Address</label><input type="email" name="email" class="form-control" placeholder="name@thetranshotel.com" required></div>
                    <div class="col-md-6"><label class="small text-gold fw-bold mb-2 text-uppercase">WhatsApp (62...)</label><input type="text" name="phone_personal" class="form-control" placeholder="628123456789" required></div>
                    <div class="col-md-6">
                        <label class="small text-gold fw-bold mb-2 text-uppercase">WhatsApp (Personal)</label>
                        <div class="input-group">
                            <span class="input-group-text">+62</span>
                            <input type="text" name="phone_personal" id="edit-phone-p" class="form-control" 
                                placeholder="8123456789" required>
                        </div>
                    </div>
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
                        <div class="col-md-6"><label class="small text-gold fw-bold mb-2">WHATSAPP</label><input type="text" name="phone_personal" id="edit-phone-p" class="form-control"></div>
                        <div class="col-md-6">
                            <label class="small text-gold fw-bold mb-2">OFFICE LINE</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark text-gold border-secondary">+62</span>
                                <input type="text" name="phone_office" id="edit-phone-o" class="form-control office-format">
                            </div>
                        </div>
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
    const commonChartOptions = {
        responsive: true, maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, suggestedMax: 10, ticks: { stepSize: 1, color: '#888' }, grid: { color: 'rgba(255,255,255,0.05)' } }, x: { ticks: { color: '#888' }, grid: { display: false } } },
        plugins: { legend: { display: false } }
    };
    window.onload = function() {
        if(document.getElementById('weeklyChart')) { new Chart(document.getElementById('weeklyChart'), { type: 'line', data: { labels: <?php echo json_encode($days); ?>, datasets: [{ data: <?php echo json_encode($day_clicks); ?>, borderColor: '#D4AF37', backgroundColor: 'rgba(212, 175, 55, 0.1)', fill: true, tension: 0.4 }] }, options: commonChartOptions }); }
        if(document.getElementById('monthlyChart')) { new Chart(document.getElementById('monthlyChart'), { type: 'bar', data: { labels: <?php echo json_encode($months); ?>, datasets: [{ data: <?php echo json_encode($month_clicks); ?>, backgroundColor: '#D4AF37', borderRadius: 5 }] }, options: commonChartOptions }); }
    };

    // Script tambahan untuk menangani Edit Modal agar +62 tidak double
    function openEditModal(data) {
    document.getElementById('edit-id').value = data.id;
    document.getElementById('edit-name').value = data.name;
    document.getElementById('edit-title').value = data.title;
    document.getElementById('edit-email').value = data.email;
    
    // Bersihkan +62 saat tampil di modal edit agar user tidak bingung
    document.getElementById('edit-phone-p').value = data.phone_personal.replace('+62 ', '');
    document.getElementById('edit-phone-o').value = data.phone_office.replace('+62 ', '');
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

    // Validator Angka
    document.querySelectorAll('.office-format, [name="phone_personal"]').forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9 ]/g, '');
        });
    });
</script>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="script-admin.js"></script>
</body>
</html>
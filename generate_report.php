<?php
include 'config.php';

// Cek apakah ada filter per manager
$manager_id = isset($_GET['manager_id']) ? mysqli_real_escape_string($conn, $_GET['manager_id']) : '';

// Bangun Query
$sql = "SELECT l.accessed_at, m.name as manager_name, l.city, l.region, l.visitor_ip 
        FROM ecard_logs l 
        JOIN managers m ON l.manager_id = m.id";

if ($manager_id != '') {
    $sql .= " WHERE l.manager_id = '$manager_id'";
    $filename_suffix = "Filter_ID_".$manager_id;
} else {
    $filename_suffix = "All_Staff";
}

$sql .= " ORDER BY l.accessed_at DESC";
$query = mysqli_query($conn, $sql);

// Nama file laporan
$date_now = date('d-m-Y_His');
$filename = "TransHotel_Report_{$filename_suffix}_{$date_now}.xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
?>

<table border="1">
    <thead>
        <tr>
            <th colspan="6" style="background-color: #1a1a1a; color: #D4AF37; font-size: 16px; height: 30px;">
                THE TRANS LUXURY HOTEL - TRACKING REPORT (<?php echo str_replace('_', ' ', $filename_suffix); ?>)
            </th>
        </tr>
        <tr style="background-color: #D4AF37; color: #000; font-weight: bold;">
            <th>NO</th>
            <th>TIMESTAMP</th>
            <th>EXECUTIVE NAME</th>
            <th>CITY</th>
            <th>REGION</th>
            <th>IP ADDRESS</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $no = 1;
        while($row = mysqli_fetch_assoc($query)): ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td><?php echo date('d M Y, H:i:s', strtotime($row['accessed_at'])); ?></td>
            <td><?php echo strtoupper($row['manager_name']); ?></td>
            <td><?php echo $row['city']; ?></td>
            <td><?php echo $row['region']; ?></td>
            <td><?php echo $row['visitor_ip']; ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
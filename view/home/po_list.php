<?php
require_once '../../config/database.php';

// Sanitize and get parameters
$filters = [
    'from' => $_GET['from'] ?? '',
    'to' => $_GET['to'] ?? '',
    'date' => $_GET['date'] ?? '',
    'profile_complete' => $_GET['profile_complete'] ?? '0'
];

// Build dynamic query
$sql = "SELECT bus.id, po.nama_po, rute.kota_asal, rute.kota_tujuan, bus.tanggal_berangkat
        FROM bus
        JOIN po ON bus.po_id = po.id
        JOIN rute ON bus.rute_id = rute.id
        WHERE 1=1";

$params = [];

// Add filters dynamically
if (!empty($filters['from'])) {
    $sql .= " AND rute.kota_asal LIKE ?";
    $params[] = $filters['from'] . '%';
}
if (!empty($filters['to'])) {
    $sql .= " AND rute.kota_tujuan LIKE ?";
    $params[] = $filters['to'] . '%';
}
if (!empty($filters['date'])) {
    $sql .= " AND bus.tanggal_berangkat = ?";
    $params[] = $filters['date'];
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Month names in Indonesian
$months = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

// Helper function to format date
function formatDate($dateString, $months) {
    $timestamp = strtotime($dateString);
    return date('d', $timestamp) . ' ' . $months[(int)date('m', $timestamp)] . ' ' . date('Y', $timestamp);
}

// Generate output
foreach ($results as $row) {
    $formattedDate = formatDate($row['tanggal_berangkat'], $months);
    $bookingUrl = "../booking/booking_detail.php?bus_id={$row['id']}";
    
    // Check if profile is complete for different link behavior
    if ($filters['profile_complete'] === '0') {
        // Profile not complete - use data attribute for JS handling
        echo "<div class='po-box' data-booking-url='$bookingUrl' style='cursor: pointer;'>";
    } else {
        // Profile complete - direct link
        echo "<a href='$bookingUrl' style='text-decoration: none; color: inherit;'>";
        echo "<div class='po-box' style='cursor: pointer;'>";
    }
    
    echo "<strong>{$row['nama_po']}</strong><br>";
    echo "Dari: {$row['kota_asal']} &rarr; {$row['kota_tujuan']}<br>";
    echo "Tanggal: $formattedDate";
    echo "</div>";
    
    if ($filters['profile_complete'] !== '0') {
        echo "</a>";
    }
}

// Show message if no results
if (empty($results)) {
    echo "<div class='po-box' style='text-align: center; color: #666;'>";
    echo "<strong>Tidak ada bus ditemukan</strong><br>";
    echo "Coba ubah kriteria pencarian Anda.";
    echo "</div>";
}
?>
<?php
require_once '../../config/database.php';

// Sanitize and get parameters
$filters = [
    'from' => $_GET['from'] ?? '',
    'to' => $_GET['to'] ?? '',
    'date' => $_GET['date'] ?? '',
];

// Build dynamic query
// We need to join with 'kursi' table and group by bus to count available seats
$sql = "SELECT 
            bus.id, 
            po.nama_po, 
            rute.kota_asal, 
            rute.kota_tujuan, 
            bus.tanggal_berangkat,
            bus.jam_berangkat,
            bus.harga,
            COUNT(CASE WHEN kursi.status = 'kosong' THEN 1 END) as kursi_tersedia
        FROM bus
        JOIN po ON bus.po_id = po.id
        JOIN rute ON bus.rute_id = rute.id
        LEFT JOIN kursi ON bus.id = kursi.bus_id
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

$sql .= " GROUP BY bus.id, po.nama_po, rute.kota_asal, rute.kota_tujuan, bus.tanggal_berangkat, bus.jam_berangkat, bus.harga";
$sql .= " ORDER BY bus.jam_berangkat ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Month names in Indonesian
$months = [
    1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
];

// Helper function to format date
function formatDate($dateString, $months) {
    $timestamp = strtotime($dateString);
    return date('d', $timestamp) . ' ' . $months[(int)date('m', $timestamp)] . ' ' . date('Y', $timestamp);
}

// Generate output
if (!empty($results)) {
    foreach ($results as $row) {
        // Skip bus if no seats are available
        if ($row['kursi_tersedia'] == 0) {
            continue;
        }

        $formattedDate = formatDate($row['tanggal_berangkat'], $months);
        $bookingUrl = "../booking/booking_detail.php?bus_id={$row['id']}";

        // The entire card is a clickable element
        echo "<div class='po-card' data-booking-url='{$bookingUrl}'>";
        echo "    <div class='po-header'>";
        echo "        <div>";
        echo "            <div class='po-name'>{$row['nama_po']}</div>";
        echo "        </div>";
        echo "        <div class='po-price'>";
        echo "            <div class='price-amount'>Rp " . number_format($row['harga'], 0, ',', '.') . "</div>";
        echo "            <div class='price-label'>per orang</div>";
        echo "        </div>";
        echo "    </div>";

        echo "    <div class='po-route'>";
        echo "        <span>{$row['kota_asal']}</span>";
        echo "        <span class='route-arrow'>&rarr;</span>";
        echo "        <span>{$row['kota_tujuan']}</span>";
        echo "    </div>";

        echo "    <div class='po-details'>";
        echo "        <div class='detail-item'>";
        echo "            <div class='detail-label'>Berangkat</div>";
        echo "            <div class='detail-value'>" . date('H:i', strtotime($row['jam_berangkat'])) . "</div>";
        echo "        </div>";
        echo "        <div class='detail-item'>";
        echo "            <div class='detail-label'>Tanggal</div>";
        echo "            <div class='detail-value'>{$formattedDate}</div>";
        echo "        </div>";
        echo "        <div class='detail-item'>";
        echo "            <div class='detail-label'>Tersedia</div>";
        echo "            <div class='detail-value'>{$row['kursi_tersedia']} Kursi</div>";
        echo "        </div>";
        echo "    </div>";
        echo "</div>";
    }
} else {
    echo "<div class='alert alert-info alert-full-width'>Tidak ada bus yang tersedia untuk rute dan tanggal yang Anda pilih.</div>";
}
?>
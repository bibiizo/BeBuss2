<?php
require_once '../../config/database.php';

try {
    // Query untuk melihat struktur tabel pemesanan
    echo "<h2>Database Structure Check</h2>";
    
    // Tabel Pemesanan
    $stmt = $pdo->prepare("DESCRIBE pemesanan");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Struktur Tabel Pemesanan:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 20px;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Tabel Users
    $stmt_users = $pdo->prepare("DESCRIBE users");
    $stmt_users->execute();
    $columns_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Struktur Tabel Users:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 20px;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns_users as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Tampilkan juga sample data pemesanan
    echo "<h3>Sample Data Pemesanan:</h3>";
    $stmt_sample = $pdo->prepare("SELECT * FROM pemesanan LIMIT 3");
    $stmt_sample->execute();
    $samples = $stmt_sample->fetchAll(PDO::FETCH_ASSOC);
    
    if ($samples) {
        echo "<table border='1' style='border-collapse: collapse; margin: 20px;'>";
        echo "<tr>";
        foreach (array_keys($samples[0]) as $key) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        
        foreach ($samples as $sample) {
            echo "<tr>";
            foreach ($sample as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Tidak ada data pemesanan.</p>";
    }
    
    // Sample data users
    echo "<h3>Sample Data Users:</h3>";
    $stmt_sample_users = $pdo->prepare("SELECT id, email, nama_lengkap, no_hp, jenis_kelamin FROM users LIMIT 3");
    $stmt_sample_users->execute();
    $samples_users = $stmt_sample_users->fetchAll(PDO::FETCH_ASSOC);
    
    if ($samples_users) {
        echo "<table border='1' style='border-collapse: collapse; margin: 20px;'>";
        echo "<tr>";
        foreach (array_keys($samples_users[0]) as $key) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        
        foreach ($samples_users as $sample) {
            echo "<tr>";
            foreach ($sample as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Tidak ada data users.</p>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

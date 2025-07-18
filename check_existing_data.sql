-- Script untuk melihat data yang sudah ada di database
-- Jalankan query ini di phpMyAdmin untuk melihat struktur data

-- 1. Lihat struktur tabel bus
DESCRIBE bus;

-- 2. Lihat semua data bus yang sudah ada
SELECT * FROM bus ORDER BY id;

-- 3. Lihat data PO (untuk membuat kode perjalanan yang sesuai)
SELECT * FROM po ORDER BY id;

-- 4. Lihat data rute
SELECT * FROM rute ORDER BY id;

-- 5. Lihat relasi bus dengan PO dan rute
SELECT 
    bus.id,
    bus.po_id,
    bus.rute_id,
    bus.tanggal_berangkat,
    bus.jam_berangkat,
    bus.harga,
    po.nama_po,
    rute.kota_asal,
    rute.kota_tujuan
FROM bus
JOIN po ON bus.po_id = po.id
JOIN rute ON bus.rute_id = rute.id
ORDER BY bus.id;

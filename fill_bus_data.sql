-- Script untuk mengisi kode perjalanan dan plat nomor secara realistis
-- Jalankan setelah melihat data dari check_existing_data.sql

-- LANGKAH 1: Jalankan script check_existing_data.sql terlebih dahulu
-- untuk melihat data PO dan bus yang ada

-- LANGKAH 2: Sesuaikan kode perjalanan berdasarkan nama PO
-- Format: [3 HURUF PERTAMA PO][TAHUN][URUTAN 3 DIGIT]

-- Contoh untuk PO "Transport Express":
-- Kode: TRA2025001, TRA2025002, dst.

-- Contoh untuk PO "Alam Sutera":
-- Kode: ALA2025001, ALA2025002, dst.

-- TEMPLATE UPDATE - SESUAIKAN DENGAN DATA ANDA:

-- Untuk PO dengan ID 1 (sesuaikan nama PO):
UPDATE bus 
SET 
    kode_perjalanan = CONCAT(
        'TRA',  -- 3 huruf pertama nama PO
        '2025', -- tahun
        LPAD(ROW_NUMBER() OVER (PARTITION BY po_id ORDER BY id), 3, '0')
    ),
    plat_nomor = CONCAT(
        'B ',   -- kode wilayah Jakarta
        LPAD((id * 1111) % 9999, 4, '0'),  -- nomor unik
        ' ',
        CASE (id % 5)
            WHEN 0 THEN 'ABC'
            WHEN 1 THEN 'DEF' 
            WHEN 2 THEN 'GHI'
            WHEN 3 THEN 'JKL'
            ELSE 'MNO'
        END
    )
WHERE po_id = 1;

-- Untuk PO dengan ID 2:
UPDATE bus 
SET 
    kode_perjalanan = CONCAT(
        'ALA',  -- sesuaikan dengan nama PO kedua
        '2025',
        LPAD(ROW_NUMBER() OVER (PARTITION BY po_id ORDER BY id), 3, '0')
    ),
    plat_nomor = CONCAT(
        'B ',
        LPAD((id * 2222) % 9999, 4, '0'),
        ' ',
        CASE (id % 5)
            WHEN 0 THEN 'PQR'
            WHEN 1 THEN 'STU'
            WHEN 2 THEN 'VWX'
            WHEN 3 THEN 'YZA'
            ELSE 'BCD'
        END
    )
WHERE po_id = 2;

-- ATAU menggunakan cara yang lebih sederhana:
-- Update semua bus sekaligus dengan pattern generic

UPDATE bus 
SET 
    kode_perjalanan = CONCAT('BUS', LPAD(id, 3, '0')),
    plat_nomor = CONCAT(
        CASE 
            WHEN po_id = 1 THEN 'B '
            WHEN po_id = 2 THEN 'D '  -- Bandung
            WHEN po_id = 3 THEN 'L '  -- Surabaya
            WHEN po_id = 4 THEN 'F '  -- Bogor
            ELSE 'B '
        END,
        LPAD((id * 1234 + po_id * 111) % 9999, 4, '0'),
        ' ',
        CASE (id % 6)
            WHEN 0 THEN 'ABC'
            WHEN 1 THEN 'DEF'
            WHEN 2 THEN 'GHI' 
            WHEN 3 THEN 'JKL'
            WHEN 4 THEN 'MNO'
            ELSE 'PQR'
        END
    )
WHERE kode_perjalanan = '' OR plat_nomor = '';

-- Verifikasi hasil:
SELECT 
    bus.id,
    bus.kode_perjalanan,
    bus.plat_nomor,
    po.nama_po,
    rute.kota_asal,
    rute.kota_tujuan,
    bus.tanggal_berangkat,
    bus.jam_berangkat
FROM bus
JOIN po ON bus.po_id = po.id
JOIN rute ON bus.rute_id = rute.id
ORDER BY po.nama_po, bus.tanggal_berangkat, bus.jam_berangkat;

-- Update database untuk menambahkan identifikasi bus
-- Jalankan query ini di phpMyAdmin atau MySQL client

-- 1. Menambahkan kolom kode_perjalanan dan plat_nomor ke tabel bus
ALTER TABLE bus 
ADD COLUMN kode_perjalanan VARCHAR(20) NOT NULL DEFAULT '',
ADD COLUMN plat_nomor VARCHAR(15) NOT NULL DEFAULT '';

-- 2. Menambahkan index untuk performa yang lebih baik
ALTER TABLE bus 
ADD INDEX idx_kode_perjalanan (kode_perjalanan),
ADD INDEX idx_plat_nomor (plat_nomor);

-- 3. Contoh update data untuk bus yang sudah ada (sesuaikan dengan data Anda)
-- UPDATE bus SET kode_perjalanan = 'BUS001', plat_nomor = 'B 1234 ABC' WHERE id = 1;
-- UPDATE bus SET kode_perjalanan = 'BUS002', plat_nomor = 'B 5678 DEF' WHERE id = 2;
-- UPDATE bus SET kode_perjalanan = 'BUS003', plat_nomor = 'B 9012 GHI' WHERE id = 3;

-- Contoh pattern untuk generate kode perjalanan otomatis:
-- Format: [PO_CODE][TAHUN][URUTAN] 
-- Contoh: TRE2025001, ALS2025002, dst.

-- Contoh untuk mengisi data secara batch (sesuaikan dengan ID bus yang ada):
UPDATE bus SET 
    kode_perjalanan = CONCAT('BUS', LPAD(id, 3, '0')),
    plat_nomor = CONCAT('B ', LPAD(id * 1234, 4, '0'), ' ', 
        CASE 
            WHEN id % 4 = 0 THEN 'ABC'
            WHEN id % 4 = 1 THEN 'DEF'
            WHEN id % 4 = 2 THEN 'GHI'
            ELSE 'JKL'
        END)
WHERE kode_perjalanan = '' AND plat_nomor = '';

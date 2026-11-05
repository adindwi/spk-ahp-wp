-- MIGRASI TERBARU SPK AHP-WP
-- Jalankan di phpMyAdmin pada database spk_ahp_wp.
-- Aman dijalankan berulang kali untuk kolom/tabel yang dikelola file ini.
-- Backup database sebelum menjalankan.

USE spk_ahp_wp;

DELIMITER $$

DROP PROCEDURE IF EXISTS migrasi_spk_terbaru $$
CREATE PROCEDURE migrasi_spk_terbaru()
BEGIN
    DECLARE v_count INT DEFAULT 0;
    DECLARE v_added_nilai_periode TINYINT DEFAULT 0;

    -- Informasi sewa disimpan terpisah dan tidak menjadi kriteria WP.
    SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'alternatif' AND COLUMN_NAME = 'biaya_sewa';
    IF v_count = 0 THEN
        ALTER TABLE alternatif ADD COLUMN biaya_sewa DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER deskripsi;
    END IF;

    -- Pastikan kolom periode tersedia pada nilai alternatif untuk skema lama.
    SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nilai_alternatif' AND COLUMN_NAME = 'periode';
    IF v_count = 0 THEN
        ALTER TABLE nilai_alternatif ADD COLUMN periode VARCHAR(50) NOT NULL DEFAULT '2026-01-01' AFTER id_kriteria;
        SET v_added_nilai_periode = 1;
    END IF;

    -- Kolom periode pada alternatif.
    SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'alternatif' AND COLUMN_NAME = 'periode';
    IF v_count = 0 THEN
        ALTER TABLE alternatif ADD COLUMN periode DATE NULL;
    END IF;
    UPDATE alternatif SET periode = CURDATE() WHERE periode IS NULL;
    ALTER TABLE alternatif MODIFY COLUMN periode DATE NOT NULL;

    -- Hanya sinkronkan nilai saat kolom periode baru saja ditambahkan ke skema lama.
    IF v_added_nilai_periode = 1 THEN
        UPDATE nilai_alternatif n JOIN alternatif a ON a.id_alternatif = n.id_alternatif
           SET n.periode = a.periode;
    END IF;

    -- Kolom periode dan metadata AHP pada simulasi.
    SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'simulasi' AND COLUMN_NAME = 'periode';
    IF v_count = 0 THEN
        ALTER TABLE simulasi ADD COLUMN periode DATE NULL;
    END IF;
    UPDATE simulasi SET periode = CURDATE() WHERE periode IS NULL;
    ALTER TABLE simulasi MODIFY COLUMN periode DATE NULL;

    SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'simulasi' AND COLUMN_NAME = 'lambda_max';
    IF v_count = 0 THEN ALTER TABLE simulasi ADD COLUMN lambda_max DECIMAL(12,8) NULL; END IF;

    SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'simulasi' AND COLUMN_NAME = 'ci';
    IF v_count = 0 THEN ALTER TABLE simulasi ADD COLUMN ci DECIMAL(12,8) NULL; END IF;

    SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'simulasi' AND COLUMN_NAME = 'cr';
    IF v_count = 0 THEN ALTER TABLE simulasi ADD COLUMN cr DECIMAL(12,8) NULL; END IF;

    SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'simulasi' AND COLUMN_NAME = 'nama_acuan';
    IF v_count = 0 THEN ALTER TABLE simulasi ADD COLUMN nama_acuan VARCHAR(150) NULL; END IF;

    SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'simulasi' AND COLUMN_NAME = 'source_ahp_id';
    IF v_count = 0 THEN ALTER TABLE simulasi ADD COLUMN source_ahp_id BIGINT NULL; END IF;

    -- Tabel penyimpanan matriks perbandingan AHP.
    CREATE TABLE IF NOT EXISTS ahp_perbandingan (
        id_perbandingan BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        id_simulasi BIGINT NOT NULL,
        kode_i VARCHAR(10) NOT NULL,
        kode_j VARCHAR(10) NOT NULL,
        nilai DECIMAL(12,8) NOT NULL,
        UNIQUE KEY uq_pasangan (id_simulasi, kode_i, kode_j),
        CONSTRAINT fk_ahp_simulasi FOREIGN KEY (id_simulasi)
            REFERENCES simulasi (id_simulasi) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Tabel snapshot bobot hasil AHP yang digunakan oleh WP.
    CREATE TABLE IF NOT EXISTS ahp_bobot (
        id_ahp_bobot BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        id_simulasi BIGINT NOT NULL,
        kode_kriteria VARCHAR(10) NOT NULL,
        nama_kriteria VARCHAR(150) NOT NULL,
        tipe_kriteria VARCHAR(20) NOT NULL,
        bobot DECIMAL(18,12) NOT NULL,
        UNIQUE KEY uq_ahp_bobot (id_simulasi, kode_kriteria),
        CONSTRAINT fk_ahp_bobot_simulasi FOREIGN KEY (id_simulasi)
            REFERENCES simulasi (id_simulasi) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
END $$

CALL migrasi_spk_terbaru() $$
DROP PROCEDURE migrasi_spk_terbaru $$

DELIMITER ;

-- Sinkronkan enam kriteria berdasarkan Paper SPK.
INSERT INTO kriteria (kode, nama, tipe, bobot, skala_min, skala_max, urutan) VALUES
('C1','Biaya Renovasi dan Persiapan Toko','cost',0.1333,1,1000000000,1),
('C2','Keamanan Eksternal Kawasan','benefit',0.0501,1,5,2),
('C3','Potensi Daya Beli Lingkungan','benefit',0.4614,1,5,3),
('C4','Visibilitas Lokasi','benefit',0.2258,1,5,4),
('C5','Fleksibilitas Perombakan Struktur Bangunan','benefit',0.0271,1,5,5),
('C6','Aglomerasi atau Kepadatan Sentra Emas','benefit',0.1023,1,5,6)
ON DUPLICATE KEY UPDATE
    nama = VALUES(nama),
    tipe = VALUES(tipe),
    skala_min = VALUES(skala_min),
    skala_max = VALUES(skala_max),
    urutan = VALUES(urutan);

-- Pastikan semua halaman menampilkan kriteria dalam urutan C1 sampai C6.
UPDATE kriteria SET nama = 'Biaya Renovasi dan Persiapan Toko', tipe = 'cost' WHERE kode = 'C1';
UPDATE kriteria SET urutan = 1 WHERE kode = 'C1';
UPDATE kriteria SET urutan = 2 WHERE kode = 'C2';
UPDATE kriteria SET urutan = 3 WHERE kode = 'C3';
UPDATE kriteria SET urutan = 4 WHERE kode = 'C4';
UPDATE kriteria SET urutan = 5 WHERE kode = 'C5';
UPDATE kriteria SET urutan = 6 WHERE kode = 'C6';

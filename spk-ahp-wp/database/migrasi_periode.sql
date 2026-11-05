-- MIGRASI UNTUK DATABASE LAMA
-- Backup database terlebih dahulu. Jalankan sekali di phpMyAdmin pada database spk_ahp_wp.
-- Nilai yang sudah ada akan ditempatkan ke periode tahun berjalan (contoh: 2026).

DELIMITER $$
CREATE PROCEDURE migrasi_periode_spk()
BEGIN
  DECLARE v_count INT DEFAULT 0;
  DECLARE v_index VARCHAR(64) DEFAULT NULL;

  SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='nilai_alternatif' AND COLUMN_NAME='periode';
  IF v_count=0 THEN
    ALTER TABLE nilai_alternatif ADD COLUMN periode VARCHAR(50) NOT NULL DEFAULT '2026' AFTER id_kriteria;
  END IF;

  -- Hapus unique index lama yang hanya menggunakan id_alternatif dan id_kriteria,
  -- karena setelah migrasi satu alternatif boleh mempunyai nilai berbeda pada tiap periode.
  SELECT MAX(INDEX_NAME) INTO v_index FROM (
    SELECT INDEX_NAME FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='nilai_alternatif' AND NON_UNIQUE=0
    GROUP BY INDEX_NAME
    HAVING COUNT(*)=2 AND SUM(COLUMN_NAME='id_alternatif')=1 AND SUM(COLUMN_NAME='id_kriteria')=1
  ) AS old_indexes;
  IF v_index IS NOT NULL THEN
    SET @drop_sql=CONCAT('ALTER TABLE nilai_alternatif DROP INDEX `',REPLACE(v_index,'`','``'),'`');
    PREPARE stmt FROM @drop_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;

  SELECT COUNT(*) INTO v_count FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='nilai_alternatif' AND INDEX_NAME='uq_nilai_periode';
  IF v_count=0 THEN
    ALTER TABLE nilai_alternatif ADD UNIQUE KEY uq_nilai_periode (id_alternatif,id_kriteria,periode);
  END IF;

  SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='simulasi' AND COLUMN_NAME='periode';
  IF v_count=0 THEN
    ALTER TABLE simulasi ADD COLUMN periode VARCHAR(50) NOT NULL DEFAULT '2026' AFTER keterangan;
  END IF;

  SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='simulasi' AND COLUMN_NAME='lambda_max';
  IF v_count=0 THEN ALTER TABLE simulasi ADD COLUMN lambda_max DECIMAL(12,8) DEFAULT NULL; END IF;
  SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='simulasi' AND COLUMN_NAME='ci';
  IF v_count=0 THEN ALTER TABLE simulasi ADD COLUMN ci DECIMAL(12,8) DEFAULT NULL; END IF;
  SELECT COUNT(*) INTO v_count FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='simulasi' AND COLUMN_NAME='cr';
  IF v_count=0 THEN ALTER TABLE simulasi ADD COLUMN cr DECIMAL(12,8) DEFAULT NULL; END IF;

  CREATE TABLE IF NOT EXISTS ahp_perbandingan (
    id_perbandingan BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_simulasi BIGINT NOT NULL,
    kode_i VARCHAR(10) NOT NULL,
    kode_j VARCHAR(10) NOT NULL,
    nilai DECIMAL(12,8) NOT NULL,
    UNIQUE KEY uq_pasangan (id_simulasi,kode_i,kode_j),
    CONSTRAINT fk_ahp_simulasi FOREIGN KEY (id_simulasi) REFERENCES simulasi(id_simulasi) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
END$$
DELIMITER ;
CALL migrasi_periode_spk();
DROP PROCEDURE migrasi_periode_spk;

-- Update nama kriteria sesuai paper. Tipe Cost/Benefit dipertahankan sesuai ketentuan.
UPDATE kriteria SET nama='Biaya Renovasi dan Persiapan Toko', tipe='cost', urutan=1 WHERE kode='C1';
UPDATE kriteria SET nama='Keamanan Eksternal Kawasan', tipe='benefit', urutan=2 WHERE kode='C2';
UPDATE kriteria SET nama='Potensi Daya Beli Lingkungan', tipe='benefit', urutan=3 WHERE kode='C3';
UPDATE kriteria SET nama='Visibilitas Lokasi', tipe='benefit', urutan=4 WHERE kode='C4';
UPDATE kriteria SET nama='Fleksibilitas Perombakan Struktur Bangunan', tipe='benefit', urutan=5 WHERE kode='C5';
UPDATE kriteria SET nama='Aglomerasi atau Kepadatan Sentra Emas', tipe='benefit', urutan=6 WHERE kode='C6';

INSERT INTO kriteria (kode,nama,tipe,bobot,skala_min,skala_max,urutan) VALUES
('C1','Biaya Renovasi dan Persiapan Toko','cost',0.16666667,1,1000000000,1),
('C2','Keamanan Eksternal Kawasan','benefit',0.16666667,1,5,2),
('C3','Potensi Daya Beli Lingkungan','benefit',0.16666667,1,5,3),
('C4','Visibilitas Lokasi','benefit',0.16666667,1,5,4),
('C5','Fleksibilitas Perombakan Struktur Bangunan','benefit',0.16666667,1,5,5),
('C6','Aglomerasi atau Kepadatan Sentra Emas','benefit',0.16666667,1,5,6)
ON DUPLICATE KEY UPDATE nama=VALUES(nama), tipe=VALUES(tipe), urutan=VALUES(urutan);

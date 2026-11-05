-- Jalankan SATU KALI setelah migrasi_ahp.sql dan migrasi_periode_revisi.sql.
-- Database: spk_ahp_wp

ALTER TABLE simulasi
  ADD COLUMN nama_acuan VARCHAR(150) NULL,
  ADD COLUMN source_ahp_id BIGINT NULL;

CREATE TABLE IF NOT EXISTS ahp_bobot (
  id_ahp_bobot BIGINT NOT NULL AUTO_INCREMENT,
  id_simulasi BIGINT NOT NULL,
  kode_kriteria VARCHAR(10) NOT NULL,
  nama_kriteria VARCHAR(150) NOT NULL,
  tipe_kriteria VARCHAR(20) NOT NULL,
  bobot DECIMAL(18,12) NOT NULL,
  PRIMARY KEY (id_ahp_bobot),
  UNIQUE KEY uq_ahp_bobot (id_simulasi, kode_kriteria),
  CONSTRAINT fk_ahp_bobot_simulasi FOREIGN KEY (id_simulasi)
    REFERENCES simulasi (id_simulasi) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kolom lambda_max, ci, cr dan tabel ahp_perbandingan harus sudah tersedia
-- dari database/migrasi_ahp.sql. Kolom periode berasal dari migrasi_periode_revisi.sql.

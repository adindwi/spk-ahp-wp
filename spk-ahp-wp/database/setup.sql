CREATE DATABASE IF NOT EXISTS spk_ahp_wp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE spk_ahp_wp;

CREATE TABLE IF NOT EXISTS kriteria (
  id_kriteria INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(10) NOT NULL UNIQUE,
  nama VARCHAR(180) NOT NULL,
  tipe ENUM('cost','benefit') NOT NULL,
  bobot DECIMAL(12,8) NOT NULL DEFAULT 0.16666667,
  skala_min DECIMAL(15,2) NOT NULL DEFAULT 1,
  skala_max DECIMAL(15,2) NOT NULL DEFAULT 5,
  urutan INT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS alternatif (
  id_alternatif INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(20) NOT NULL UNIQUE,
  nama VARCHAR(150) NOT NULL,
  deskripsi TEXT NULL,
  biaya_sewa DECIMAL(15,2) NOT NULL DEFAULT 0,
  periode DATE NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE alternatif SET periode = CURDATE() WHERE periode IS NULL;
ALTER TABLE alternatif MODIFY COLUMN periode DATE NOT NULL;

CREATE TABLE IF NOT EXISTS nilai_alternatif (
  id_nilai BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_alternatif INT NOT NULL,
  id_kriteria INT NOT NULL,
  periode VARCHAR(50) NOT NULL,
  nilai DECIMAL(15,2) NOT NULL,
  UNIQUE KEY uq_nilai_periode (id_alternatif,id_kriteria,periode),
  KEY idx_nilai_periode (periode),
  CONSTRAINT fk_nilai_alternatif FOREIGN KEY (id_alternatif) REFERENCES alternatif(id_alternatif) ON DELETE CASCADE,
  CONSTRAINT fk_nilai_kriteria FOREIGN KEY (id_kriteria) REFERENCES kriteria(id_kriteria) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS simulasi (
  id_simulasi BIGINT AUTO_INCREMENT PRIMARY KEY,
  nama_simulasi VARCHAR(150) NOT NULL,
  keterangan TEXT NULL,
  periode VARCHAR(50) NOT NULL,
  metode VARCHAR(20) NOT NULL DEFAULT 'AHP-WP',
  total_bobot_input DECIMAL(12,8) NOT NULL DEFAULT 1,
  total_bobot_normalisasi DECIMAL(12,8) NOT NULL DEFAULT 1,
  jumlah_alternatif INT NOT NULL DEFAULT 0,
  jumlah_kriteria INT NOT NULL DEFAULT 0,
  lambda_max DECIMAL(12,8) NULL,
  ci DECIMAL(12,8) NULL,
  cr DECIMAL(12,8) NULL,
  status ENUM('draft','selesai') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_simulasi_periode (periode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hasil_spk (
  id_hasil BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_simulasi BIGINT NOT NULL,
  id_alternatif INT NULL,
  kode_alternatif VARCHAR(20) NOT NULL,
  nama_alternatif VARCHAR(150) NOT NULL,
  nilai_s DOUBLE NOT NULL,
  nilai_v DOUBLE NOT NULL,
  peringkat INT NOT NULL,
  CONSTRAINT fk_hasil_simulasi FOREIGN KEY (id_simulasi) REFERENCES simulasi(id_simulasi) ON DELETE CASCADE,
  CONSTRAINT fk_hasil_alternatif FOREIGN KEY (id_alternatif) REFERENCES alternatif(id_alternatif) ON DELETE SET NULL,
  KEY idx_hasil_peringkat (id_simulasi,peringkat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS detail_hasil_spk (
  id_detail BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_hasil BIGINT NOT NULL,
  kode_kriteria VARCHAR(10) NOT NULL,
  nama_kriteria VARCHAR(180) NOT NULL,
  tipe_kriteria VARCHAR(10) NOT NULL,
  bobot_input DECIMAL(12,8) NOT NULL,
  bobot_normalisasi DECIMAL(12,8) NOT NULL,
  nilai_alternatif DECIMAL(15,2) NOT NULL,
  nilai_pangkat DOUBLE NOT NULL,
  CONSTRAINT fk_detail_hasil FOREIGN KEY (id_hasil) REFERENCES hasil_spk(id_hasil) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ahp_perbandingan (
  id_perbandingan BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_simulasi BIGINT NOT NULL,
  kode_i VARCHAR(10) NOT NULL,
  kode_j VARCHAR(10) NOT NULL,
  nilai DECIMAL(12,8) NOT NULL,
  UNIQUE KEY uq_pasangan (id_simulasi,kode_i,kode_j),
  CONSTRAINT fk_ahp_simulasi FOREIGN KEY (id_simulasi) REFERENCES simulasi(id_simulasi) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO kriteria (kode,nama,tipe,bobot,skala_min,skala_max,urutan) VALUES
('C1','Biaya Renovasi dan Persiapan Toko','cost',0.1333,1,1000000000,1),
('C2','Keamanan Eksternal Kawasan','benefit',0.0501,1,5,2),
('C3','Potensi Daya Beli Lingkungan','benefit',0.4614,1,5,3),
('C4','Visibilitas Lokasi','benefit',0.2258,1,5,4),
('C5','Fleksibilitas Perombakan Struktur Bangunan','benefit',0.0271,1,5,5),
('C6','Aglomerasi atau Kepadatan Sentra Emas','benefit',0.1023,1,5,6)
ON DUPLICATE KEY UPDATE nama=VALUES(nama), tipe=VALUES(tipe), bobot=VALUES(bobot), skala_min=VALUES(skala_min), skala_max=VALUES(skala_max), urutan=VALUES(urutan);

-- Jalankan sekali di phpMyAdmin (database spk_ahp_wp) -> tab SQL
-- Menambahkan penyimpanan hasil AHP (matriks perbandingan, lambda max, CI, CR)

ALTER TABLE `simulasi`
  ADD COLUMN `lambda_max` decimal(12,8) DEFAULT NULL,
  ADD COLUMN `ci` decimal(12,8) DEFAULT NULL,
  ADD COLUMN `cr` decimal(12,8) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `ahp_perbandingan` (
  `id_perbandingan` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_simulasi` bigint(20) NOT NULL,
  `kode_i` varchar(10) NOT NULL,
  `kode_j` varchar(10) NOT NULL,
  `nilai` decimal(12,8) NOT NULL,
  PRIMARY KEY (`id_perbandingan`),
  UNIQUE KEY `uq_pasangan` (`id_simulasi`,`kode_i`,`kode_j`),
  CONSTRAINT `fk_ahp_simulasi` FOREIGN KEY (`id_simulasi`) REFERENCES `simulasi` (`id_simulasi`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

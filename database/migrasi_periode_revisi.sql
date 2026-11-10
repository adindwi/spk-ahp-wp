-- Jalankan satu kali di phpMyAdmin pada database spk_ahp_wp.
-- Menambahkan tanggal periode pada alternatif dan riwayat simulasi.
ALTER TABLE alternatif ADD COLUMN periode DATE NULL;
UPDATE alternatif SET periode = CURDATE() WHERE periode IS NULL;
ALTER TABLE alternatif MODIFY periode DATE NOT NULL;
ALTER TABLE simulasi ADD COLUMN periode DATE NULL;

-- Perbarui nama dan tipe kriteria sesuai Paper SPK.docx.
UPDATE kriteria SET nama = 'Biaya Renovasi dan Persiapan Toko', tipe = 'cost' WHERE kode = 'C1';
UPDATE kriteria SET nama = 'Keamanan Eksternal Kawasan', tipe = 'benefit' WHERE kode = 'C2';
UPDATE kriteria SET nama = 'Potensi Daya Beli Lingkungan', tipe = 'benefit' WHERE kode = 'C3';
UPDATE kriteria SET nama = 'Visibilitas Lokasi', tipe = 'benefit' WHERE kode = 'C4';
UPDATE kriteria SET nama = 'Fleksibilitas Perombakan Struktur Bangunan', tipe = 'benefit' WHERE kode = 'C5';
UPDATE kriteria SET nama = 'Aglomerasi atau Kepadatan Sentra Emas', tipe = 'benefit' WHERE kode = 'C6';

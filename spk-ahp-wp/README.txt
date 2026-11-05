SPK AHP-WP - PEMILIHAN TEMPAT USAHA

KEBUTUHAN
- XAMPP (Apache, MySQL/MariaDB)
- PHP 8+
- Database spk_ahp_wp yang dibuat dari SQL sebelumnya

INSTALASI
1. Salin folder spk-ahp-wp ke C:\xampp\htdocs\
2. Jalankan Apache dan MySQL dari XAMPP.
3. Buka http://localhost/phpmyadmin
4. Untuk instalasi baru, impor database/setup.sql.
5. Untuk database lama, pilih database spk_ahp_wp lalu jalankan database/migrasi_terbaru.sql melalui tab SQL phpMyAdmin.
6. Pastikan database berisi enam kriteria: C1 sampai C6.
7. Periksa config/database.php. Pengaturan standar XAMPP biasanya user root dan password kosong.
8. Buka http://localhost/spk-ahp-wp/

ALUR
1. Tambah minimal dua alternatif pada menu Alternatif.
2. Isi tanggal periode penilaian pada setiap alternatif menggunakan pemilih tanggal (hari, bulan, tahun). Alternatif dengan tanggal periode yang sama digunakan bersama dalam perhitungan. Biaya sewa dalam rupiah per tahun, nilai lainnya 1-5.
3. Dari halaman Alternatif, tekan “Lanjut Perhitungan AHP”. Pilih tanggal periode; sistem hanya memanggil alternatif pada tanggal tersebut. Buka menu Perhitungan/Simulasi. Masukkan nama simulasi dan isi matriks perbandingan AHP secara manual pada segitiga atas.
4. Tekan Hitung AHP → lanjutkan WP. Sistem menampilkan matriks awal, jumlah kolom, normalisasi, bobot prioritas, lambda maks, CI, CR, detail pangkat WP, vektor S, vektor V, dan ranking dalam satu halaman.
5. Jika CR > 0,10, ubah perbandingan manual sampai konsisten. Tahap WP hanya dijalankan saat CR <= 0,10.
6. Hasil ranking yang berhasil dihitung disimpan dan dapat dilihat dari menu Riwayat.

CATATAN METODE
- Bobot prioritas dihitung dari matriks perbandingan AHP, lalu disimpan sebagai snapshot.
- Bobot AHP dinormalisasi sebelum perhitungan WP.
- Cost menggunakan pangkat negatif; benefit menggunakan pangkat positif.
- Nilai WP harus positif agar perhitungan pangkat berjalan dengan baik.
- Bobot WP diambil dari hasil matriks AHP yang konsisten dan tersimpan. Nilai kriteria di database harus lengkap untuk C1 sampai C6.
- Bobot contoh pada dokumen berjumlah 1.01 akibat pembulatan, sehingga dinormalisasi.
- Hasil lama disimpan dalam tabel hasil_spk dan detail_hasil_spk sebagai snapshot.


REVISI ALUR AHP DAN WP (20-10-2026)
1. Halaman simulasi/index.php sekarang khusus perhitungan AHP.
2. Halaman simulasi/wp.php khusus perhitungan WP.
3. Hasil AHP yang konsisten disimpan dengan nama alternatif acuan dan periode.
4. Pada halaman WP, pilih hasil AHP melalui dropdown nama alternatif acuan; bobot tersimpan akan dipanggil untuk perhitungan WP.
5. Pilih minimal dua alternatif pada periode yang sama untuk dirangking.
6. Untuk database lama, cukup jalankan database/migrasi_terbaru.sql. File ini menyiapkan kolom dan tabel penyimpanan AHP/WP serta memastikan enam kriteria tersedia.


PERBAIKAN VERSI INI
- Kriteria SPK adalah tepat enam kriteria C1-C6; C1 adalah biaya renovasi dan persiapan toko (COST).
- Biaya sewa ruko disimpan pada alternatif.biaya_sewa dan hanya sebagai informasi pembanding, bukan kriteria AHP/WP.
- Jalankan database/migrasi_terbaru.sql di phpMyAdmin pada database spk_ahp_wp sebelum memakai versi ini. Migrasi menambah kolom dan tabel yang diperlukan tanpa menghapus data alternatif.
- Pada phpMyAdmin, pilih database spk_ahp_wp, buka tab Import, pilih file migrasi_terbaru.sql, lalu tekan Import/Go. Backup database terlebih dahulu.
- Untuk debugging, detail exception penyimpanan AHP/WP dicatat pada error log PHP; jangan menampilkan detail database ke pengguna.

DEFINISI KRITERIA YANG DIGUNAKAN
C1 COST: Biaya Renovasi dan Persiapan Toko, estimasi biaya menyiapkan toko agar siap beroperasi.
C2 BENEFIT: Keamanan Eksternal Kawasan, mencakup kriminalitas, kedekatan penegak hukum, dan patroli kawasan.
C3 BENEFIT: Potensi Daya Beli Lingkungan, potensi konsumen yang mungkin membeli perhiasan.
C4 BENEFIT: Visibilitas Lokasi, keterlihatan dari jalan utama atau area ramai.
C5 BENEFIT: Fleksibilitas Perombakan Struktur Bangunan, keleluasaan renovasi atau penyesuaian bangunan.
C6 BENEFIT: Aglomerasi atau Kepadatan Sentra Emas, kepadatan toko emas lain yang membentuk sentra perdagangan.
Biaya sewa ruko per tahun bukan kriteria ketujuh dan tidak dipakai dalam matriks AHP maupun WP.

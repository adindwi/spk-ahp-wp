SPK AHP-WP - PEMILIHAN TEMPAT USAHA (TOKO EMAS)

KEBUTUHAN
- Web Server: Apache (XAMPP / Laragon)
- Bahasa Pemrograman: PHP 8.0+
- Database: MySQL / MariaDB (InnoDB, utf8mb4)
- Web Browser modern dengan dukungan CSS Flexbox/Grid

INSTALASI
1. Salin folder proyek (Website) ke C:\xampp\htdocs\spk-ahp-wp (atau direktori web server Anda).
2. Jalankan Apache dan MySQL melalui XAMPP Control Panel.
3. Buka peramban ke http://localhost/phpmyadmin
4. Untuk instalasi baru:
   - Buat database atau langsung impor file database/setup.sql.
   - File setup.sql kini sudah lengkap mandiri (membuat database spk_ahp_wp, tabel kriteria, alternatif, nilai_alternatif, simulasi, hasil_spk, detail_hasil_spk, ahp_perbandingan, dan ahp_bobot).
5. Untuk database versi lama yang ingin diperbarui:
   - Pilih database spk_ahp_wp, lalu impor database/migrasi_terbaru.sql melalui tab Import / SQL di phpMyAdmin.
6. Periksa koneksi pada config/database.php (default XAMPP: user 'root', password '').
7. Buka sistem pada peramban melalui alamat:
   http://localhost/spk-ahp-wp/ (atau nama folder web Anda).

STRUKTUR NAVIGASI UTAMA (5 MENU)
1. Dashboard (index.php)          : Ringkasan metrik alternatif, kriteria acuan C1–C6, dan riwayat perankingan terbaru.
2. Alternatif (alternatif/index.php): Pengelolaan tempat usaha (CRUD) dan fitur daur ulang/duplikasi ke periode baru.
3. Perhitungan AHP (simulasi/index.php): Tahap 1 input matriks perbandingan berpasangan 6x6, uji konsistensi (CR <= 0,10), dan penyimpanan snapshot bobot.
4. Perhitungan WP (simulasi/wp.php)   : Tahap 2 pemanggilan bobot AHP tersimpan, pemilihan minimal 2 alternatif, dan kalkulasi preferensi.
5. Riwayat (simulasi/riwayat.php)     : Arsip hasil simulasi WP yang telah selesai dan cetak laporan hasil evaluasi.

ALUR PENGGUNAAN SISTEM (2 TAHAP HIBRIDA)
1. Pengisian Alternatif:
   - Masukkan minimal dua alternatif tempat usaha pada menu Alternatif.
   - Tentukan tanggal periode penilaian (alternatif pada tanggal yang sama dievaluasi bersama).
   - Isi biaya sewa ruko per tahun (sebagai data informasi tambahan pembanding).
   - Isi nilai 6 kriteria: C1 (Biaya renovasi & persiapan) dalam nominal rupiah (Rp 1 s.d. 1.000.000.000), serta C2 sampai C6 menggunakan skala bulat 1–5.
   - Jika ingin mengevaluasi alternatif yang sama pada periode berikutnya, gunakan tombol "Daur ulang" (duplikasi.php).

2. Tahap 1 - Perhitungan AHP (simulasi/index.php):
   - Pilih tanggal periode dan tentukan alternatif acuan untuk pengindeksan bobot.
   - Masukkan nama perhitungan AHP dan isi segitiga atas matriks perbandingan berpasangan (skala Saaty 1–9 atau pecahan seperti 0,5 untuk 1/2). Segitiga bawah dihitung otomatis (resiprok: a_ji = 1 / a_ij).
   - Tekan "Hitung dan Simpan AHP". Sistem menghitung jumlah kolom, normalisasi, bobot prioritas baris, lambda max, CI, dan CR.
   - Hasil AHP tersimpan ke tabel ahp_bobot jika matriks konsisten (CR <= 0,10). Jika CR > 0,10, ubah perbandingan hingga konsisten.

3. Tahap 2 - Perhitungan WP (simulasi/wp.php):
   - Buka menu Perhitungan WP. Pilih hasil AHP yang telah tersimpan melalui dropdown.
   - Sistem secara otomatis menampilkan rincian bobot AHP yang dipanggil.
   - Centang minimal dua alternatif yang berada pada periode yang sama.
   - Tekan "Hitung WP dengan Bobot AHP Terpilih". Sistem menghitung:
     * Pangkat kriteria: cost berpangkat negatif (-w_j), benefit berpangkat positif (+w_j).
     * Komputasi numerik stabil via formulasi logaritma natural: ln(S_i) = SUM(p_j * ln(x_ij)).
     * Vektor S_i = exp(ln(S_i)) dan preferensi relatif V_i = S_i / SUM(S).
   - Hasil perankingan otomatis disimpan permanen sebagai snapshot di hasil_spk dan detail_hasil_spk.

4. Evaluasi & Cetak Hasil (simulasi/hasil.php):
   - Sistem menampilkan kartu alternatif terbaik (peringkat 1), tabel peringkat lengkap dengan visualisasi persentase preferensi V, serta akordion detail kontribusi perkalian WP.
   - Klik tombol "Cetak Hasil" untuk mencetak laporan resmi via peramban dengan styling CSS print yang bersih.

DEFINISI KRITERIA TOKO EMAS (C1–C6)
- C1 (COST)   : Biaya Renovasi dan Persiapan Toko (estimasi biaya menyiapkan ruko agar siap beroperasi aman).
- C2 (BENEFIT): Keamanan Eksternal Kawasan (tingkat kriminalitas rendah, kedekatan aparat keamanan, patroli).
- C3 (BENEFIT): Potensi Daya Beli Lingkungan (kualitas traffic konsumen potensial perhiasan emas).
- C4 (BENEFIT): Visibilitas Lokasi (keterlihatan ruko dari jalan utama/akses ramai).
- C5 (BENEFIT): Fleksibilitas Perombakan Struktur Bangunan (keleluasaan modifikasi brankas baja tonase berat, partisi anti-peluru).
- C6 (BENEFIT): Aglomerasi / Kepadatan Sentra Emas (konsentrasi toko emas sejenis yang membentuk klaster pasar).
*Catatan:* Biaya sewa ruko per tahun disimpan terpisah sebagai informasi finansial pembanding, bukan sebagai kriteria perankingan matematis AHP/WP.

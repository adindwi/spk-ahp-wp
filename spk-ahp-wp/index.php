<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/config/helpers.php';

$totalAlternatif = (int) $pdo->query('SELECT COUNT(*) FROM alternatif')->fetchColumn();
$totalKriteria = (int) $pdo->query('SELECT COUNT(*) FROM kriteria')->fetchColumn();
$totalSimulasi = (int) $pdo->query("SELECT COUNT(*) FROM simulasi WHERE status = 'selesai'")->fetchColumn();
$terakhir = $pdo->query(
    "SELECT id_simulasi, nama_simulasi, created_at
     FROM simulasi WHERE status = 'selesai'
     ORDER BY id_simulasi DESC LIMIT 5"
)->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SPK AHP-WP | Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="index.php">SPK <span>AHP–WP</span></a>
    <nav>
        <a class="active" href="index.php">Dashboard</a>
        <a href="alternatif/index.php">Alternatif</a>
        <a href="simulasi/index.php">Simulasi</a>
        <a href="simulasi/riwayat.php">Riwayat</a>
    </nav>
</header>
<main class="container">
    <section class="hero">
        <div>
            <p class="eyebrow">SISTEM PENDUKUNG KEPUTUSAN</p>
            <h1>Pemilihan Tempat Usaha</h1>
            <p>Kelola alternatif, nilai kriteria, lalu hitung rekomendasi menggunakan metode Weighted Product (WP) dengan bobot kriteria yang telah ditetapkan.</p>
            <div class="actions">
                <a class="button" href="alternatif/tambah.php">+ Tambah Alternatif</a>
                <a class="button button-light" href="simulasi/index.php">Mulai Simulasi</a>
            </div>
        </div>
        <div class="hero-mark">AHP<br><span>×</span><br>WP</div>
    </section>

    <section class="stats">
        <article class="stat-card"><span>Alternatif</span><strong><?= $totalAlternatif ?></strong><small>Tempat usaha terdaftar</small></article>
        <article class="stat-card"><span>Kriteria Tetap</span><strong><?= $totalKriteria ?></strong><small>Kriteria yang digunakan</small></article>
        <article class="stat-card"><span>Simulasi Selesai</span><strong><?= $totalSimulasi ?></strong><small>Riwayat tersimpan</small></article>
    </section>

    <section class="panel">
        <div class="section-heading">
            <div><p class="eyebrow">DATA KRITERIA</p><h2>Kriteria yang digunakan</h2></div>
            <span class="muted">Ditetapkan oleh sistem</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Kode</th><th>Nama kriteria</th><th>Tipe</th><th>Bobot awal</th></tr></thead>
                <tbody>
                <?php foreach (get_kriteria($pdo) as $k): ?>
                    <tr>
                        <td><span class="code-pill"><?= e($k['kode']) ?></span></td>
                        <td><?= e($k['nama']) ?></td>
                        <td><span class="type-pill <?= e($k['tipe']) ?>"><?= strtoupper(e($k['tipe'])) ?></span></td>
                        <td><?= number_format((float)$k['bobot'] * 100, 2, ',', '.') ?>%</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="notice">Bobot awal pada data contoh berjumlah 101% karena pembulatan. Bobot akan dinormalisasi ketika perhitungan WP dijalankan.</p>
    </section>

    <section class="panel">
        <div class="section-heading"><div><p class="eyebrow">AKTIVITAS</p><h2>Simulasi terbaru</h2></div><a href="simulasi/riwayat.php">Lihat semua →</a></div>
        <?php if (!$terakhir): ?>
            <div class="empty-state"><h3>Belum ada simulasi</h3><p>Tambahkan alternatif terlebih dahulu, lalu mulai simulasi.</p></div>
        <?php else: ?>
            <div class="table-wrap"><table><thead><tr><th>Nama simulasi</th><th>Tanggal</th><th></th></tr></thead><tbody>
            <?php foreach ($terakhir as $s): ?>
                <tr><td><?= e($s['nama_simulasi']) ?></td><td><?= e($s['created_at']) ?></td><td><a href="simulasi/hasil.php?id=<?= (int)$s['id_simulasi'] ?>">Lihat hasil →</a></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </section>
</main>
<footer class="footer">SPK AHP–WP · Simulasi Pemilihan Tempat Usaha</footer>
<script src="assets/js/script.js"></script>
</body>
</html>

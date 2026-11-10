<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/config/helpers.php';

$totalAlternatif = (int) $pdo->query('SELECT COUNT(*) FROM alternatif')->fetchColumn();
$totalKriteria = (int) $pdo->query('SELECT COUNT(*) FROM kriteria')->fetchColumn();
$totalAhp = (int) $pdo->query("SELECT COUNT(*) FROM simulasi WHERE status = 'draft' AND cr <= 0.1 AND source_ahp_id IS NULL")->fetchColumn();
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
        <a href="simulasi/index.php">Perhitungan AHP</a>
        <a href="simulasi/wp.php">Perhitungan WP</a>
        <a href="simulasi/riwayat.php">Riwayat</a>
    </nav>
</header>
<main class="container">
    <section class="hero">
        <div>
            <p class="eyebrow">SISTEM PENDUKUNG KEPUTUSAN</p>
            <h1>Pemilihan Tempat Usaha Toko Emas</h1>
            <p>Kelola data alternatif ruko, tentukan bobot kriteria secara terukur menggunakan metode <strong>Analytic Hierarchy Process (AHP)</strong>, lalu rangking alternatif terbaik dengan <strong>Weighted Product (WP)</strong>.</p>
            <div class="actions">
                <a class="button" href="alternatif/tambah.php">+ Tambah Alternatif</a>
                <a class="button button-light" href="simulasi/index.php">Mulai Perhitungan AHP →</a>
            </div>
        </div>
        <div class="hero-mark">AHP<br><span>×</span><br>WP</div>
    </section>

    <section class="stats">
        <article class="stat-card"><span>Alternatif</span><strong><?= $totalAlternatif ?></strong><small>Tempat usaha terdaftar</small></article>
        <article class="stat-card"><span>Kriteria Tetap</span><strong><?= $totalKriteria ?></strong><small>Kriteria C1–C6</small></article>
        <article class="stat-card"><span>AHP Konsisten</span><strong><?= $totalAhp ?></strong><small>Bobot tersimpan (CR ≤ 0,10)</small></article>
        <article class="stat-card"><span>Simulasi WP</span><strong><?= $totalSimulasi ?></strong><small>Perankingan selesai</small></article>
    </section>

    <section class="panel">
        <div class="section-heading">
            <div><p class="eyebrow">DATA KRITERIA</p><h2>Kriteria yang digunakan</h2></div>
            <span class="muted">Ditetapkan oleh sistem</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Kode</th><th>Nama kriteria</th><th>Tipe</th><th>Bobot acuan</th></tr></thead>
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
        <p class="notice">Tabel di atas menampilkan bobot acuan dasar sistem. Pada proses evaluasi, bobot kriteria dihitung dinamis melalui <strong>Tahap 1: Perhitungan AHP</strong> dan kemudian dipanggil pada <strong>Tahap 2: Perhitungan WP</strong> untuk menghasilkan peringkat alternatif.</p>
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

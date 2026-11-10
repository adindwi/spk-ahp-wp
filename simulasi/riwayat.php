<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/helpers.php';

$riwayat = $pdo->query(
    "SELECT s.*, h.nama_alternatif AS terbaik, h.nilai_v AS nilai_terbaik
     FROM simulasi s
     LEFT JOIN hasil_spk h ON h.id_simulasi = s.id_simulasi AND h.peringkat = 1
     WHERE s.status = 'selesai'
     ORDER BY s.id_simulasi DESC"
)->fetchAll();
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Riwayat Simulasi | SPK AHP-WP</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body><header class="topbar"><a class="brand" href="../index.php">SPK <span>AHP–WP</span></a><nav><a href="../index.php">Dashboard</a><a href="../alternatif/index.php">Alternatif</a><a href="index.php">Perhitungan AHP</a><a href="wp.php">Perhitungan WP</a><a class="active" href="riwayat.php">Riwayat</a></nav></header>
<main class="container">
<div class="page-heading"><div><p class="eyebrow">ARSIP PERHITUNGAN</p><h1>Riwayat Simulasi</h1><p>Setiap hasil disimpan beserta snapshot nilai dan bobot saat perhitungan.</p></div><div class="actions"><a class="button button-light" href="index.php">Tahap 1: AHP</a><a class="button" href="wp.php">Tahap 2: WP</a></div></div>
<section class="panel">
<?php if (!$riwayat): ?><div class="empty-state"><h3>Belum ada riwayat</h3><p>Hasil simulasi yang sudah selesai akan muncul di sini.</p><a class="button" href="wp.php">Mulai Perhitungan WP</a></div>
<?php else: ?><div class="table-wrap"><table><thead><tr><th>Nama simulasi</th><th>Tanggal proses</th><th>Periode penilaian</th><th>Alternatif</th><th>Terbaik</th><th>Nilai V</th><th></th></tr></thead><tbody>
<?php foreach ($riwayat as $r): ?><tr><td><strong><?= e($r['nama_simulasi']) ?></strong><br><small><?= e($r['keterangan'] ?? '') ?></small></td><td><?= e($r['created_at']) ?></td><td><?= !empty($r['periode']) ? e(date('d-m-Y', strtotime($r['periode']))) : '—' ?></td><td><?= (int)$r['jumlah_alternatif'] ?></td><td><?= e($r['terbaik'] ?? '—') ?></td><td><?= $r['nilai_terbaik'] !== null ? number_format((float)$r['nilai_terbaik'], 6, ',', '.') : '—' ?></td><td><a class="button button-small button-light" href="hasil.php?id=<?= (int)$r['id_simulasi'] ?>">Lihat hasil</a></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
</section></main><footer class="footer">SPK AHP–WP · Riwayat simulasi</footer><script src="../assets/js/script.js"></script></body></html>

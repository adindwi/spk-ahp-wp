<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/helpers.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }
$stmt = $pdo->prepare('SELECT * FROM alternatif WHERE id_alternatif = ?');
$stmt->execute([$id]);
$alternatif = $stmt->fetch();
if (!$alternatif) { http_response_code(404); exit('Alternatif tidak ditemukan.'); }
$kriteria = get_kriteria($pdo);
$nilai = get_nilai_alternatif($pdo, (int)$id);
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Detail Alternatif | SPK AHP-WP</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<header class="topbar"><a class="brand" href="../index.php">SPK <span>AHP–WP</span></a><nav><a href="../index.php">Dashboard</a><a class="active" href="index.php">Alternatif</a><a href="../simulasi/index.php">Perhitungan AHP</a><a href="../simulasi/wp.php">Perhitungan WP</a><a href="../simulasi/riwayat.php">Riwayat</a></nav></header>
<main class="container narrow"><div class="page-heading"><div><p class="eyebrow">DETAIL ALTERNATIF</p><h1><?= e($alternatif['nama']) ?></h1><p><?= e($alternatif['kode']) ?> · Periode <?= !empty($alternatif['periode']) ? e(date('d-m-Y', strtotime($alternatif['periode']))) : '—' ?></p></div><a class="button button-light" href="index.php">← Kembali</a></div>
<section class="panel"><h2>Informasi lokasi</h2><dl class="detail-list"><dt>Nama tempat usaha</dt><dd><?= e($alternatif['nama']) ?></dd><dt>Periode penilaian</dt><dd><?= !empty($alternatif['periode']) ? e(date('d-m-Y', strtotime($alternatif['periode']))) : '—' ?></dd><dt>Deskripsi lokasi</dt><dd><?= nl2br(e($alternatif['deskripsi'] ?? '—')) ?></dd><dt>Biaya Sewa Ruko per Tahun</dt><dd><strong><?= rupiah($alternatif['biaya_sewa'] ?? 0) ?></strong></dd></dl><p class="notice">Biaya sewa adalah informasi tambahan dan tidak memengaruhi bobot AHP atau pemeringkatan WP.</p></section>
<section class="panel"><h2>Nilai enam kriteria AHP-WP</h2><div class="table-wrap"><table><thead><tr><th>Kode</th><th>Kriteria</th><th>Tipe</th><th>Nilai</th></tr></thead><tbody><?php foreach ($kriteria as $k): $kid=(int)$k['id_kriteria']; ?><tr><td><?= e($k['kode']) ?></td><td><?= e($k['nama']) ?></td><td><?= strtoupper(e($k['tipe'])) ?></td><td><?= isset($nilai[$kid]) ? ($k['kode']==='C1' ? rupiah($nilai[$kid]) : e($nilai[$kid])) : '—' ?></td></tr><?php endforeach; ?></tbody></table></div><div class="actions"><a class="button" href="edit.php?id=<?= (int)$alternatif['id_alternatif'] ?>">Edit Alternatif</a></div></section></main><footer class="footer">SPK AHP–WP · Detail alternatif</footer><script src="../assets/js/script.js"></script></body></html>

<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/helpers.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: riwayat.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM simulasi WHERE id_simulasi = ? AND status = ?');
$stmt->execute([$id, 'selesai']);
$simulasi = $stmt->fetch();
if (!$simulasi) { http_response_code(404); exit('Riwayat simulasi tidak ditemukan.'); }

$stmt = $pdo->prepare('SELECT h.*, a.biaya_sewa FROM hasil_spk h LEFT JOIN alternatif a ON a.id_alternatif = h.id_alternatif WHERE h.id_simulasi = ? ORDER BY h.peringkat');
$stmt->execute([$id]);
$hasil = $stmt->fetchAll();
$detailStmt = $pdo->prepare('SELECT * FROM detail_hasil_spk WHERE id_hasil = ? ORDER BY kode_kriteria');
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Hasil Simulasi | SPK AHP-WP</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body><header class="topbar"><a class="brand" href="../index.php">SPK <span>AHP–WP</span></a><nav><a href="../index.php">Dashboard</a><a href="../alternatif/index.php">Alternatif</a><a href="index.php">Perhitungan AHP</a><a href="wp.php">Perhitungan WP</a><a class="active" href="riwayat.php">Riwayat</a></nav></header>
<main class="container">
<div class="page-heading"><div><p class="eyebrow">HASIL PERHITUNGAN WP</p><h1><?= e($simulasi['nama_simulasi']) ?></h1><p><?= e($simulasi['created_at']) ?> · <?= (int)$simulasi['jumlah_alternatif'] ?> alternatif</p></div><div class="actions"><a class="button button-light" href="riwayat.php">Riwayat</a><a class="button" href="wp.php">Hitung WP Baru</a></div></div>
<?php if ($hasil): ?><section class="winner-card"><div><p class="eyebrow">ALTERNATIF TERBAIK</p><h2><?= e($hasil[0]['nama_alternatif']) ?></h2><p><?= e($hasil[0]['kode_alternatif']) ?> memperoleh nilai preferensi tertinggi pada simulasi ini.</p></div><div class="winner-score"><?= number_format((float)$hasil[0]['nilai_v'], 6, ',', '.') ?><small>Nilai V</small></div></section><?php endif; ?>
<section class="panel"><div class="section-heading"><div><h2>Peringkat alternatif</h2><p class="muted">Nilai preferensi V semakin tinggi berarti alternatif semakin diprioritaskan.</p></div></div>
<div class="table-wrap"><table><thead><tr><th>Peringkat</th><th>Kode</th><th>Alternatif</th><th>Biaya sewa/tahun</th><th>Vektor S</th><th>Preferensi V</th><th>Persentase</th></tr></thead><tbody>
<?php foreach ($hasil as $h): ?>
<tr class="<?= (int)$h['peringkat']===1?'top-rank':'' ?>">
    <td><span class="rank-number"><?= (int)$h['peringkat'] ?></span></td>
    <td><span class="code-pill"><?= e($h['kode_alternatif']) ?></span></td>
    <td><strong><?= e($h['nama_alternatif']) ?></strong></td>
    <td><?= rupiah($h['biaya_sewa'] ?? 0) ?></td>
    <td><?= number_format((float)$h['nilai_s'], 8, ',', '.') ?></td>
    <td><strong><?= number_format((float)$h['nilai_v'], 8, ',', '.') ?></strong></td>
    <td>
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="flex:1;background:var(--slate-200);height:8px;border-radius:999px;overflow:hidden;min-width:65px;">
                <div style="width:<?= number_format((float)$h['nilai_v']*100, 2, '.', '') ?>%;background:linear-gradient(90deg, var(--gold-500), var(--gold-400));height:100%;border-radius:999px;"></div>
            </div>
            <span style="font-weight:700;font-size:0.85rem;color:var(--slate-900);min-width:52px;"><?= number_format((float)$h['nilai_v']*100, 2, ',', '.') ?>%</span>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div></section>
<section class="panel"><h2>Detail perhitungan per alternatif</h2><p class="muted">Biaya sewa ditampilkan sebagai informasi tambahan dan tidak masuk ke perhitungan. Nilai kontribusi memakai enam kriteria AHP-WP.</p>
<?php foreach ($hasil as $h): ?><details class="detail-card"><summary><strong><?= (int)$h['peringkat'] ?>. <?= e($h['nama_alternatif']) ?></strong><span>V = <?= number_format((float)$h['nilai_v'], 6, ',', '.') ?></span></summary>
<div class="table-wrap"><table><thead><tr><th>Kriteria</th><th>Tipe</th><th>Nilai input</th><th>Bobot awal</th><th>Bobot normalisasi</th><th>Kontribusi WP</th></tr></thead><tbody>
<?php $detailStmt->execute([(int)$h['id_hasil']]); foreach ($detailStmt->fetchAll() as $d): ?><tr><td><?= e($d['kode_kriteria']) ?> · <?= e($d['nama_kriteria']) ?></td><td><?= strtoupper(e($d['tipe_kriteria'])) ?></td><td><?= $d['kode_kriteria']==='C1'?rupiah($d['nilai_alternatif']):e($d['nilai_alternatif']) ?></td><td><?= number_format((float)$d['bobot_input'], 6, ',', '.') ?></td><td><?= number_format((float)$d['bobot_normalisasi'], 6, ',', '.') ?></td><td><?= number_format((float)$d['nilai_pangkat'], 8, ',', '.') ?></td></tr><?php endforeach; ?>
</tbody></table></div></details><?php endforeach; ?>
</section>
<div class="actions"><a class="button button-light" href="../alternatif/index.php">Kelola Alternatif</a><button class="button" type="button" onclick="window.print()">Cetak Hasil</button></div>
</main><footer class="footer">SPK AHP–WP · Hasil tersimpan di database</footer><script src="../assets/js/script.js"></script></body></html>

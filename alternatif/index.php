<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/helpers.php';

$kriteria = get_kriteria($pdo);
$alternatif = $pdo->query('SELECT * FROM alternatif ORDER BY id_alternatif DESC')->fetchAll();
$nilaiStmt = $pdo->prepare(
    'SELECT n.id_kriteria, n.nilai FROM nilai_alternatif n WHERE n.id_alternatif = ?'
);
?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Alternatif | SPK AHP-WP</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<header class="topbar"><a class="brand" href="../index.php">SPK <span>AHP–WP</span></a><nav><a href="../index.php">Dashboard</a><a class="active" href="index.php">Alternatif</a><a href="../simulasi/index.php">Perhitungan AHP</a><a href="../simulasi/wp.php">Perhitungan WP</a><a href="../simulasi/riwayat.php">Riwayat</a></nav></header>
<main class="container">
    <div class="page-heading"><div><p class="eyebrow">DATA INPUT</p><h1>Alternatif Tempat Usaha</h1><p>Kelola alternatif, tanggal periode penilaian, dan nilai setiap kriteria.</p></div><a class="button" href="tambah.php">+ Tambah Alternatif</a></div>
    <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'tersimpan'): ?><div class="alert success">Alternatif berhasil disimpan.</div><?php endif; ?>
    <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'dihapus'): ?><div class="alert success">Alternatif berhasil dihapus.</div><?php endif; ?>
    <section class="panel">
    <?php if (!$alternatif): ?>
        <div class="empty-state"><h3>Belum ada alternatif</h3><p>Mulai dengan menambahkan tempat usaha pertama.</p><a class="button" href="tambah.php">Tambah Alternatif</a></div>
    <?php else: ?>
        <div class="table-wrap"><table class="wide-table">
            <thead><tr><th>Kode</th><th>Nama alternatif</th><th>Periode</th><th>Biaya sewa/tahun</th><?php foreach ($kriteria as $k): ?><th><?= e($k['nama']) ?></th><?php endforeach; ?><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($alternatif as $a): ?>
                <?php
                $nilaiStmt->execute([(int)$a['id_alternatif']]);
                $nilai = [];
                foreach ($nilaiStmt->fetchAll() as $n) $nilai[(int)$n['id_kriteria']] = (float)$n['nilai'];
                ?>
                <tr>
                    <td><span class="code-pill"><?= e($a['kode']) ?></span></td>
                    <td><a href="detail.php?id=<?= (int)$a['id_alternatif'] ?>"><strong><?= e($a['nama']) ?></strong></a><br><small><?= e($a['deskripsi'] ?? '') ?></small></td><td><?= !empty($a['periode']) ? e(date('d-m-Y', strtotime($a['periode']))) : '—' ?></td><td><?= rupiah($a['biaya_sewa'] ?? 0) ?></td>
                    <?php foreach ($kriteria as $k): ?>
                        <td><?= isset($nilai[(int)$k['id_kriteria']]) ? ($k['kode'] === 'C1' ? rupiah($nilai[(int)$k['id_kriteria']]) : e($nilai[(int)$k['id_kriteria']])) : '—' ?></td>
                    <?php endforeach; ?>
                    <td><div class="table-actions"><a class="button button-small button-light" href="edit.php?id=<?= (int)$a['id_alternatif'] ?>">Edit</a><a class="button button-small button-light" href="duplikasi.php?id=<?= (int)$a['id_alternatif'] ?>">Daur ulang</a><form method="post" action="hapus.php" data-confirm="Yakin ingin menghapus alternatif ini?"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$a['id_alternatif'] ?>"><button class="button button-small button-danger" type="submit">Hapus</button></form></div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
    </section>
<div class="actions" style="margin-top:18px"><a class="button" href="../simulasi/index.php">Lanjut Perhitungan AHP →</a></div>
</main>
<footer class="footer">SPK AHP–WP · Data alternatif</footer><script src="../assets/js/script.js"></script>
</body></html>

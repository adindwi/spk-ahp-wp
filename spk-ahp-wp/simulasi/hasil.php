<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $namaSimulasi = trim((string)($_POST['nama_simulasi'] ?? ''));
    $keterangan = trim((string)($_POST['keterangan'] ?? ''));
    if ($namaSimulasi === '' || mb_strlen($namaSimulasi) > 150) {
        exit('Nama simulasi wajib diisi dan maksimal 150 karakter.');
    }

    $kriteria = get_kriteria($pdo);
    $alternatif = $pdo->query('SELECT * FROM alternatif ORDER BY id_alternatif')->fetchAll();
    if (count($kriteria) === 0 || count($alternatif) < 2) {
        exit('Kriteria atau alternatif belum cukup untuk perhitungan.');
    }

    $totalBobot = array_sum(array_map(fn($k) => (float)$k['bobot'], $kriteria));
    if ($totalBobot <= 0) exit('Total bobot tidak valid.');

    $bobotNormal = [];
    foreach ($kriteria as $k) {
        $bobotNormal[(int)$k['id_kriteria']] = (float)$k['bobot'] / $totalBobot;
    }

    $nilaiStmt = $pdo->prepare(
        'SELECT id_kriteria, nilai FROM nilai_alternatif WHERE id_alternatif = ?'
    );
    $data = [];
    foreach ($alternatif as $a) {
        $nilaiStmt->execute([(int)$a['id_alternatif']]);
        $nilai = [];
        foreach ($nilaiStmt->fetchAll() as $n) $nilai[(int)$n['id_kriteria']] = (float)$n['nilai'];

        foreach ($kriteria as $k) {
            $kid = (int)$k['id_kriteria'];
            if (!isset($nilai[$kid])) exit('Nilai alternatif ' . e($a['nama']) . ' belum lengkap.');
            if ($nilai[$kid] <= 0) exit('Nilai setiap kriteria harus lebih besar dari 0 untuk metode WP.');
        }
        $data[] = ['alternatif' => $a, 'nilai' => $nilai];
    }

    $hasilHitung = [];
    foreach ($data as $item) {
        $logS = 0.0;
        $kontribusi = [];
        foreach ($kriteria as $k) {
            $kid = (int)$k['id_kriteria'];
            $x = (float)$item['nilai'][$kid];
            $w = $bobotNormal[$kid];
            $pangkat = $k['tipe'] === 'cost' ? -$w : $w;
            $kontribusi[$kid] = [
                'kriteria' => $k,
                'nilai' => $x,
                'bobot_normal' => $w,
                'pangkat' => $pangkat,
                'kontribusi' => pow($x, $pangkat),
            ];
            $logS += $pangkat * log($x);
        }
        $hasilHitung[] = [
            'alternatif' => $item['alternatif'],
            'nilai' => $item['nilai'],
            'log_s' => $logS,
            's' => exp($logS),
            'kontribusi' => $kontribusi,
        ];
    }

    $maxLog = max(array_column($hasilHitung, 'log_s'));
    $totalSkala = 0.0;
    foreach ($hasilHitung as &$h) {
        $h['s_rel'] = exp($h['log_s'] - $maxLog);
        $totalSkala += $h['s_rel'];
    }
    unset($h);

    foreach ($hasilHitung as &$h) $h['v'] = $h['s_rel'] / $totalSkala;
    unset($h);

    usort($hasilHitung, fn($a, $b) => $b['v'] <=> $a['v']);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "INSERT INTO simulasi
            (nama_simulasi, keterangan, metode, total_bobot_input,
             total_bobot_normalisasi, jumlah_alternatif, jumlah_kriteria, status)
             VALUES (?, ?, 'AHP-WP', ?, 1, ?, ?, 'draft')"
        );
        $stmt->execute([
            $namaSimulasi,
            $keterangan !== '' ? $keterangan : null,
            $totalBobot,
            count($alternatif),
            count($kriteria),
        ]);
        $idSimulasi = (int)$pdo->lastInsertId();

        $stmtHasil = $pdo->prepare(
            'INSERT INTO hasil_spk
            (id_simulasi, id_alternatif, kode_alternatif, nama_alternatif, nilai_s, nilai_v, peringkat)
            VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmtDetail = $pdo->prepare(
            'INSERT INTO detail_hasil_spk
            (id_hasil, kode_kriteria, nama_kriteria, tipe_kriteria,
             bobot_input, bobot_normalisasi, nilai_alternatif, nilai_pangkat)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        foreach ($hasilHitung as $index => $h) {
            $a = $h['alternatif'];
            $stmtHasil->execute([
                $idSimulasi,
                (int)$a['id_alternatif'],
                $a['kode'],
                $a['nama'],
                $h['s'],
                $h['v'],
                $index + 1,
            ]);
            $idHasil = (int)$pdo->lastInsertId();

            foreach ($h['kontribusi'] as $c) {
                $k = $c['kriteria'];
                $stmtDetail->execute([
                    $idHasil,
                    $k['kode'],
                    $k['nama'],
                    $k['tipe'],
                    (float)$k['bobot'],
                    $c['bobot_normal'],
                    $c['nilai'],
                    $c['kontribusi'],
                ]);
            }
        }

        $stmt = $pdo->prepare("UPDATE simulasi SET status = 'selesai' WHERE id_simulasi = ?");
        $stmt->execute([$idSimulasi]);
        $pdo->commit();

        header('Location: hasil.php?id=' . $idSimulasi);
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        exit('Simulasi gagal disimpan. Pastikan struktur database sudah sesuai dengan SQL yang diberikan.');
    }
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

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
<body><header class="topbar"><a class="brand" href="../index.php">SPK <span>AHP–WP</span></a><nav><a href="../index.php">Dashboard</a><a href="../alternatif/index.php">Alternatif</a><a class="active" href="index.php">Simulasi</a><a href="riwayat.php">Riwayat</a></nav></header>
<main class="container">
<div class="page-heading"><div><p class="eyebrow">HASIL PERHITUNGAN WP</p><h1><?= e($simulasi['nama_simulasi']) ?></h1><p><?= e($simulasi['created_at']) ?> · <?= (int)$simulasi['jumlah_alternatif'] ?> alternatif</p></div><div class="actions"><a class="button button-light" href="riwayat.php">Riwayat</a><a class="button" href="index.php">Simulasi Baru</a></div></div>
<?php if ($hasil): ?><section class="winner-card"><div><p class="eyebrow">ALTERNATIF TERBAIK</p><h2><?= e($hasil[0]['nama_alternatif']) ?></h2><p><?= e($hasil[0]['kode_alternatif']) ?> memperoleh nilai preferensi tertinggi pada simulasi ini.</p></div><div class="winner-score"><?= number_format((float)$hasil[0]['nilai_v'], 6, ',', '.') ?><small>Nilai V</small></div></section><?php endif; ?>
<section class="panel"><div class="section-heading"><div><h2>Peringkat alternatif</h2><p class="muted">Nilai preferensi V semakin tinggi berarti alternatif semakin diprioritaskan.</p></div></div>
<div class="table-wrap"><table><thead><tr><th>Peringkat</th><th>Kode</th><th>Alternatif</th><th>Biaya sewa/tahun</th><th>Vektor S</th><th>Preferensi V</th><th>Persentase</th></tr></thead><tbody>
<?php foreach ($hasil as $h): ?><tr class="<?= (int)$h['peringkat']===1?'top-rank':'' ?>"><td><span class="rank-number"><?= (int)$h['peringkat'] ?></span></td><td><?= e($h['kode_alternatif']) ?></td><td><strong><?= e($h['nama_alternatif']) ?></strong></td><td><?= rupiah($h['biaya_sewa'] ?? 0) ?></td><td><?= number_format((float)$h['nilai_s'], 8, ',', '.') ?></td><td><strong><?= number_format((float)$h['nilai_v'], 8, ',', '.') ?></strong></td><td><?= number_format((float)$h['nilai_v']*100, 2, ',', '.') ?>%</td></tr><?php endforeach; ?>
</tbody></table></div></section>
<section class="panel"><h2>Detail perhitungan per alternatif</h2><p class="muted">Biaya sewa ditampilkan sebagai informasi tambahan dan tidak masuk ke perhitungan. Nilai kontribusi memakai enam kriteria AHP-WP.</p>
<?php foreach ($hasil as $h): ?><details class="detail-card"><summary><strong><?= (int)$h['peringkat'] ?>. <?= e($h['nama_alternatif']) ?></strong><span>V = <?= number_format((float)$h['nilai_v'], 6, ',', '.') ?></span></summary>
<div class="table-wrap"><table><thead><tr><th>Kriteria</th><th>Tipe</th><th>Nilai input</th><th>Bobot awal</th><th>Bobot normalisasi</th><th>Kontribusi WP</th></tr></thead><tbody>
<?php $detailStmt->execute([(int)$h['id_hasil']]); foreach ($detailStmt->fetchAll() as $d): ?><tr><td><?= e($d['kode_kriteria']) ?> · <?= e($d['nama_kriteria']) ?></td><td><?= strtoupper(e($d['tipe_kriteria'])) ?></td><td><?= $d['kode_kriteria']==='C1'?rupiah($d['nilai_alternatif']):e($d['nilai_alternatif']) ?></td><td><?= number_format((float)$d['bobot_input'], 6, ',', '.') ?></td><td><?= number_format((float)$d['bobot_normalisasi'], 6, ',', '.') ?></td><td><?= number_format((float)$d['nilai_pangkat'], 8, ',', '.') ?></td></tr><?php endforeach; ?>
</tbody></table></div></details><?php endforeach; ?>
</section>
<div class="actions"><a class="button button-light" href="../alternatif/index.php">Kelola Alternatif</a><button class="button" type="button" onclick="window.print()">Cetak Hasil</button></div>
</main><footer class="footer">SPK AHP–WP · Hasil tersimpan di database</footer><script src="../assets/js/script.js"></script></body></html>

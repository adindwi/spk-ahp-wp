<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/helpers.php';

$kriteria = get_kriteria($pdo);
$errors = [];
$nama = '';
$deskripsi = '';
$biayaSewa = '0';
$periode = date('Y-m-d');
$nilaiInput = [];
foreach ($kriteria as $k) $nilaiInput[(int)$k['id_kriteria']] = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $nama = trim((string)($_POST['nama'] ?? ''));
    $deskripsi = trim((string)($_POST['deskripsi'] ?? ''));
    $biayaSewa = trim((string)($_POST['biaya_sewa'] ?? '0'));
    $periode = trim((string)($_POST['periode'] ?? ''));
    $dt = DateTime::createFromFormat('Y-m-d', $periode);
    if (!$dt || $dt->format('Y-m-d') !== $periode) $errors[] = 'Tanggal periode wajib diisi dengan format tanggal yang valid.';
    if ($biayaSewa === '' || !preg_match('/^\d+(?:\.\d{1,2})?$/', $biayaSewa) || (float)$biayaSewa < 0 || (float)$biayaSewa > 9999999999999.99) $errors[] = 'Biaya sewa harus berupa angka rupiah yang tidak negatif.';
    if ($nama === '') $errors[] = 'Nama alternatif wajib diisi.';
    if (mb_strlen($nama) > 150) $errors[] = 'Nama alternatif maksimal 150 karakter.';

    foreach ($kriteria as $k) {
        $idK = (int)$k['id_kriteria'];
        $raw = trim((string)($_POST['nilai'][$idK] ?? ''));
        $nilaiInput[$idK] = $raw;
        if ($raw === '' || !is_numeric($raw)) {
            $errors[] = 'Nilai untuk ' . $k['nama'] . ' wajib berupa angka.';
            continue;
        }
        $v = (float)$raw;
        if ($v < (float)$k['skala_min'] || $v > (float)$k['skala_max']) {
            $errors[] = 'Nilai ' . $k['nama'] . ' harus berada di antara ' . $k['skala_min'] . ' dan ' . $k['skala_max'] . '.';
        }
        if ($k['tipe'] === 'cost' && $v <= 0) {
            $errors[] = $k['nama'] . ' harus lebih besar dari 0 untuk perhitungan WP.';
        }
        if ($k['kode'] !== 'C1' && floor($v) != $v) {
            $errors[] = $k['nama'] . ' harus berupa bilangan bulat 1–5.';
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $kode = 'ALT-' . strtoupper(bin2hex(random_bytes(3)));
            $stmt = $pdo->prepare('INSERT INTO alternatif (kode, nama, deskripsi, biaya_sewa, periode) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$kode, $nama, $deskripsi !== '' ? $deskripsi : null, $biayaSewa, $periode]);
            $idAlternatif = (int)$pdo->lastInsertId();

            $stmtNilai = $pdo->prepare('INSERT INTO nilai_alternatif (id_alternatif, id_kriteria, periode, nilai) VALUES (?, ?, ?, ?)');
            foreach ($kriteria as $k) {
                $stmtNilai->execute([$idAlternatif, (int)$k['id_kriteria'], $periode, (float)$nilaiInput[(int)$k['id_kriteria']]]);
            }
            $pdo->commit();
            header('Location: index.php?pesan=tersimpan');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Data gagal disimpan. Periksa database dan coba lagi.';
        }
    }
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Tambah Alternatif | SPK AHP-WP</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<header class="topbar"><a class="brand" href="../index.php">SPK <span>AHP–WP</span></a><nav><a href="../index.php">Dashboard</a><a class="active" href="index.php">Alternatif</a><a href="../simulasi/index.php">Perhitungan AHP</a><a href="../simulasi/wp.php">Perhitungan WP</a><a href="../simulasi/riwayat.php">Riwayat</a></nav></header>
<main class="container narrow">
    <div class="page-heading"><div><p class="eyebrow">DATA INPUT</p><h1>Tambah Alternatif</h1><p>Kriteria tidak dapat diubah melalui halaman ini.</p></div><a class="button button-light" href="index.php">← Kembali</a></div>
    <?php if ($errors): ?><div class="alert error"><strong>Periksa input berikut:</strong><ul><?php foreach (array_unique($errors) as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" class="panel form-panel">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-group"><label for="nama">Nama tempat usaha *</label><input id="nama" name="nama" maxlength="150" required value="<?= e($nama) ?>" placeholder="Contoh: Ruko Strategis Sinduadi"></div>
        <div class="form-group"><label for="periode">Periode penilaian (tanggal) *</label><input id="periode" name="periode" type="date" required value="<?= e($periode) ?>"><small>Pilih tanggal, bulan, dan tahun yang menjadi periode penilaian.</small></div>
        <div class="form-group"><label for="deskripsi">Deskripsi lokasi</label><textarea id="deskripsi" name="deskripsi" rows="3" placeholder="Alamat, luas bangunan, fasilitas, dan informasi lainnya"><?= e($deskripsi) ?></textarea></div>
        <div class="form-group"><label for="biaya_sewa">Biaya Sewa Ruko per Tahun (Rp)</label><input id="biaya_sewa" name="biaya_sewa" type="number" min="0" max="9999999999999.99" step="0.01" value="<?= e($biayaSewa) ?>" placeholder="Contoh: 120000000"><small>Informasi tambahan saja, tidak memengaruhi bobot AHP atau hasil WP.</small></div>
        <div class="form-section-title"><h2>Nilai kriteria</h2><p>Isi C1 biaya renovasi dalam rupiah; C2 sampai C6 menggunakan skala 1–5.</p></div>
        <?php foreach ($kriteria as $k): $kid = (int)$k['id_kriteria']; ?>
            <div class="form-group">
                <label for="nilai_<?= $kid ?>"><?= e($k['kode']) ?> · <?= e($k['nama']) ?> <span class="type-pill <?= e($k['tipe']) ?>"><?= strtoupper(e($k['tipe'])) ?></span></label>
                <?php if ($k['kode'] === 'C1'): ?>
                    <input id="nilai_<?= $kid ?>" name="nilai[<?= $kid ?>]" type="number" min="1" max="1000000000" step="any" required value="<?= e($nilaiInput[$kid]) ?>" placeholder="Contoh: 135000000">
                    <small>Masukkan estimasi total biaya renovasi dan persiapan toko dalam rupiah.</small>
                <?php else: ?>
                    <select id="nilai_<?= $kid ?>" name="nilai[<?= $kid ?>]" required>
                        <option value="">Pilih skor</option>
                        <?php for ($i=1; $i<=5; $i++): ?><option value="<?= $i ?>" <?= (string)$nilaiInput[$kid] === (string)$i ? 'selected' : '' ?>><?= $i ?><?= $i === 1 ? ' — Sangat rendah/sulit/rawan/sepi' : ($i === 5 ? ' — Sangat tinggi/mudah/aman/ramai' : '') ?></option><?php endfor; ?>
                    </select>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div class="form-actions"><a class="button button-light" href="index.php">Batal</a><button class="button" type="submit">Simpan Alternatif</button></div>
    </form>
</main><footer class="footer">SPK AHP–WP · Tambah alternatif</footer><script src="../assets/js/script.js"></script></body></html>

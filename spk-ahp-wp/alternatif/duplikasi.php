<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/helpers.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare('SELECT * FROM alternatif WHERE id_alternatif = ?');
$stmt->execute([$id ?: 0]);
$asal = $stmt->fetch();
if (!$asal) { http_response_code(404); exit('Alternatif tidak ditemukan.'); }
$errors = [];
$periode = date('Y-m-d');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $periode = trim((string)($_POST['periode'] ?? ''));
    $dt = DateTime::createFromFormat('Y-m-d', $periode);
    if (!$dt || $dt->format('Y-m-d') !== $periode) $errors[] = 'Tanggal periode tidak valid.';
    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $kode = 'ALT-' . strtoupper(bin2hex(random_bytes(3)));
            $ins = $pdo->prepare('INSERT INTO alternatif (kode,nama,deskripsi,biaya_sewa,periode) VALUES (?,?,?,?,?)');
            $ins->execute([$kode, $asal['nama'], $asal['deskripsi'], $asal['biaya_sewa'] ?? 0, $periode]);
            $newId = (int)$pdo->lastInsertId();
            $copy = $pdo->prepare('INSERT INTO nilai_alternatif (id_alternatif,id_kriteria,periode,nilai) SELECT ?,id_kriteria,?,nilai FROM nilai_alternatif WHERE id_alternatif = ?');
            $copy->execute([$newId, $periode, (int)$asal['id_alternatif']]);
            $pdo->commit();
            header('Location: index.php?pesan=tersimpan'); exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Alternatif gagal didaur ulang. Pastikan migrasi periode sudah dijalankan.';
        }
    }
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Gunakan Kembali Alternatif</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<header class="topbar"><a class="brand" href="../index.php">SPK <span>AHP–WP</span></a><nav><a href="../index.php">Dashboard</a><a class="active" href="index.php">Alternatif</a><a href="../simulasi/index.php">Perhitungan</a><a href="../simulasi/riwayat.php">Riwayat</a></nav></header>
<main class="container narrow"><div class="page-heading"><div><p class="eyebrow">DAUR ULANG DATA</p><h1>Gunakan Kembali Alternatif</h1><p>Salin “<?= e($asal['nama']) ?>” beserta biaya sewa dan nilai kriterianya ke periode baru. Data asal tidak berubah.</p></div></div>
<?php if ($errors): ?><div class="alert error"><ul><?php foreach($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="panel form-panel"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="form-group"><label for="periode">Tanggal periode baru *</label><input id="periode" name="periode" type="date" required value="<?= e($periode) ?>"><small>Pilih tanggal, bulan, dan tahun untuk penilaian baru.</small></div><div class="form-actions"><a class="button button-light" href="index.php">Batal</a><button class="button" type="submit">Salin ke Periode Ini</button></div></form></main></body></html>

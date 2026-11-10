<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/helpers.php';
$kriteria=get_kriteria($pdo);
$periode=trim((string)($_POST['periode']??$_GET['periode']??date('Y-m-d')));
$d=DateTime::createFromFormat('Y-m-d',$periode); if(!$d||$d->format('Y-m-d')!==$periode)$periode=date('Y-m-d');
$as=$pdo->prepare('SELECT id_alternatif,nama,kode FROM alternatif WHERE periode=? ORDER BY nama');$as->execute([$periode]);$alternatif=$as->fetchAll();
$namaAcuan=(string)($_POST['nama_acuan']??''); $namaSimulasi=trim((string)($_POST['nama_simulasi']??''));$keterangan=trim((string)($_POST['keterangan']??''));
$errors=[];$matriks=$normal=$bobot=$jumlahKolom=[];$lambdaMax=$ci=$cr=null;$riTable=[1=>0,2=>0,3=>0.58,4=>0.90,5=>1.12,6=>1.24,7=>1.32,8=>1.41,9=>1.45,10=>1.49];$n=count($kriteria);$savedId=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $ids=array_column($alternatif,'nama'); if(!in_array($namaAcuan,$ids,true))$errors[]='Pilih nama alternatif acuan AHP.';
 if($namaSimulasi==='')$errors[]='Nama perhitungan AHP wajib diisi.';
 if($n!==6)$errors[]='Database harus berisi tepat enam kriteria C1 sampai C6. Jalankan migrasi_terbaru.sql.';
 for($i=0;$i<$n;$i++)for($j=0;$j<$n;$j++){if($i===$j)$matriks[$i][$j]=1.0;elseif($i<$j){$raw=$_POST['pair'][$i][$j]??'';if(!is_numeric($raw)||(float)$raw<1/9||(float)$raw>9){$errors[]='Nilai perbandingan '.$kriteria[$i]['kode'].' dan '.$kriteria[$j]['kode'].' harus 1/9 sampai 9.';$matriks[$i][$j]=1;}else $matriks[$i][$j]=(float)$raw;}}
 for($i=0;$i<$n;$i++)for($j=0;$j<$n;$j++)if($i>$j)$matriks[$i][$j]=1/($matriks[$j][$i]??1);
 if(!$errors){for($j=0;$j<$n;$j++){for($i=0;$i<$n;$i++)$jumlahKolom[$j]=($jumlahKolom[$j]??0)+$matriks[$i][$j];}
  $bobot=array_fill(0,$n,0.0);for($i=0;$i<$n;$i++)for($j=0;$j<$n;$j++){$normal[$i][$j]=$matriks[$i][$j]/$jumlahKolom[$j];$bobot[$i]+=$normal[$i][$j]/$n;}
  $ls=0;for($i=0;$i<$n;$i++){$weighted=0;for($j=0;$j<$n;$j++)$weighted+=$matriks[$i][$j]*$bobot[$j];$ls+=$weighted/$bobot[$i];}$lambdaMax=$ls/$n;$ci=($lambdaMax-$n)/max(1,$n-1);$ri=$riTable[$n]??1.49;$cr=$ri>0?$ci/$ri:0;
 }
 if(!$errors&&$cr<=0.1){try{$pdo->beginTransaction();
    $stmt=$pdo->prepare("INSERT INTO simulasi (nama_simulasi, keterangan, periode, metode, total_bobot_input, total_bobot_normalisasi, jumlah_alternatif, jumlah_kriteria, status, lambda_max, ci, cr, nama_acuan) VALUES (?, ?, ?, 'AHP-WP', 1, 1, 1, ?, 'draft', ?, ?, ?, ?)");
    $stmt->execute([
        $namaSimulasi, 
        $keterangan !== '' ? $keterangan : null, 
        $periode, 
        $n, 
        $lambdaMax, 
        $ci, 
        $cr, 
        $namaAcuan
    ]);
    $savedId = (int)$pdo->lastInsertId();
  $pb=$pdo->prepare('INSERT INTO ahp_perbandingan (id_simulasi,kode_i,kode_j,nilai) VALUES (?,?,?,?)');for($i=0;$i<$n;$i++)for($j=$i+1;$j<$n;$j++)$pb->execute([$savedId,$kriteria[$i]['kode'],$kriteria[$j]['kode'],$matriks[$i][$j]]);
  $bw=$pdo->prepare('INSERT INTO ahp_bobot (id_simulasi,kode_kriteria,nama_kriteria,tipe_kriteria,bobot) VALUES (?,?,?,?,?)');foreach($kriteria as $i=>$k)$bw->execute([$savedId,$k['kode'],$k['nama'],$k['tipe'],$bobot[$i]]);
  $pdo->commit();
 } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[SPK AHP] Gagal menyimpan hasil AHP: ' . $e->getMessage());
    $errors[] = 'Gagal menyimpan hasil AHP. Periksa log error PHP untuk detail teknis dan pastikan migrasi database terbaru sudah dijalankan.';
}}
}
function fmt($v,$d=6){return number_format((float)$v,$d,',','.');}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Perhitungan AHP | SPK</title><link rel="stylesheet" href="../assets/css/style.css"><style>.workflow{display:flex;gap:8px;flex-wrap:wrap;margin:18px 0}.workflow span{background:#eef0fb;color:#34376c;border-radius:999px;padding:8px 12px;font-size:.82rem;font-weight:700}.matrix-input{width:100%;min-width:78px;padding:9px;border:1px solid #dce1ef;border-radius:8px}.matrix-table td,.matrix-table th{text-align:center}.subtle{color:#707b97;font-size:.9rem}</style></head><body>
<header class="topbar"><a class="brand" href="../index.php">SPK <span>AHP–WP</span></a><nav><a href="../index.php">Dashboard</a><a href="../alternatif/index.php">Alternatif</a><a class="active" href="index.php">Perhitungan AHP</a><a href="wp.php">Perhitungan WP</a><a href="riwayat.php">Riwayat</a></nav></header><main class="container">
<div class="page-heading"><div><p class="eyebrow">TAHAP 1 DARI 2</p><h1>Perhitungan AHP</h1><p>Hitung bobot dan konsistensi AHP terlebih dahulu. Hasil yang konsisten disimpan untuk dipanggil dari halaman WP.</p></div></div><div class="workflow"><span>01 Data & periode</span><span>02 Input matriks AHP</span><span>03 Bobot & konsistensi</span></div>
<?php if($errors):?><div class="alert error"><ul><?php foreach(array_unique($errors) as $er):?><li><?=e($er)?></li><?php endforeach;?></ul></div><?php endif;?>
<?php if($savedId):?><section class="panel"><div class="result-good"><strong>Perhitungan AHP berhasil disimpan.</strong><br>Nama acuan: <?=e($namaAcuan)?> · Periode <?=e(date('d-m-Y',strtotime($periode)))?> · CR <?=fmt($cr,5)?></div><p>Bobot AHP tersimpan dan bisa dipanggil di halaman WP dengan memilih nama alternatif acuan tersebut.</p><a class="button" href="wp.php?periode=<?=e($periode)?>&nama_acuan=<?=rawurlencode($namaAcuan)?>">Lanjut ke Perhitungan WP →</a></section><?php endif;?>
<form method="post" action="index.php"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><section class="panel"><h2>Data perhitungan</h2><div class="form-group"><label>Periode penilaian *</label><input type="date" name="periode" required value="<?=e($periode)?>" onchange="window.location.href='index.php?periode='+this.value"></div><div class="form-group"><label>Nama alternatif acuan AHP *</label><select name="nama_acuan" required><option value="">-- Pilih nama alternatif --</option><?php foreach($alternatif as $a):?><option value="<?=e($a['nama'])?>" <?=($namaAcuan===$a['nama']?'selected':'')?>><?=e($a['nama'])?> (<?=e($a['kode'])?>)</option><?php endforeach;?></select><small>Nama ini digunakan untuk menemukan hasil AHP saat dipanggil di halaman WP.</small></div><div class="form-group"><label>Nama perhitungan AHP *</label><input name="nama_simulasi" required maxlength="150" value="<?=e($namaSimulasi)?>" placeholder="Contoh: Bobot AHP lokasi toko"></div><div class="form-group"><label>Keterangan</label><textarea name="keterangan" rows="2"><?=e($keterangan)?></textarea></div><p class="notice">Alternatif pada periode terpilih: <strong><?=count($alternatif)?></strong>. Pilih alternatif acuan yang namanya nanti digunakan untuk memanggil bobot AHP.</p></section>
<section class="panel"><h2>Input manual matriks perbandingan AHP</h2><p class="subtle">Isi segitiga atas dengan skala Saaty 1 sampai 9 atau nilai kebalikannya seperti 0,5. Sisi bawah dihitung otomatis.</p><div class="table-wrap"><table class="matrix-table"><thead><tr><th>Kriteria</th><?php foreach($kriteria as $k):?><th><?=e($k['kode'])?></th><?php endforeach;?></tr></thead><tbody><?php for($i=0;$i<$n;$i++):?><tr><th><?=e($kriteria[$i]['kode'])?><br><small><?=e($kriteria[$i]['nama'])?></small></th><?php for($j=0;$j<$n;$j++):?><td><?php if($i===$j):?>1<?php elseif($i<$j):?><input class="matrix-input" type="number" min="0.111111" max="9" step="any" required name="pair[<?=$i?>][<?=$j?>]" value="<?=e($_POST['pair'][$i][$j]??'1')?>"><?php else:?>otomatis<?php endif;?></td><?php endfor;?></tr><?php endfor;?></tbody></table></div><button class="button" type="submit">Hitung dan Simpan AHP</button></section></form>
<?php if($_SERVER['REQUEST_METHOD']==='POST'&&$bobot&&!$errors):?><section class="panel"><h2>Hasil perhitungan AHP</h2><div class="table-wrap"><table><thead><tr><th>Kode</th><th>Kriteria</th><th>Bobot prioritas</th></tr></thead><tbody><?php foreach($kriteria as $i=>$k):?><tr><td><?=e($k['kode'])?></td><td><?=e($k['nama'])?></td><td><?=fmt($bobot[$i],8)?></td></tr><?php endforeach;?></tbody></table></div><div class="stats"><article class="stat-card"><span>Lambda Maks</span><strong><?=fmt($lambdaMax,5)?></strong></article><article class="stat-card"><span>CI</span><strong><?=fmt($ci,5)?></strong></article><article class="stat-card"><span>CR</span><strong><?=fmt($cr,5)?></strong></article></div><div class="<?=$cr<=0.1?'result-good':'result-bad'?>"><?=$cr<=0.1?'Matriks konsisten. Hasil AHP disimpan jika proses penyimpanan berhasil.':'Matriks belum konsisten. Ubah nilai perbandingan hingga CR ≤ 0,10.'?></div></section><?php endif;?>
<section class="panel"><h2>Hasil AHP yang tersimpan</h2><div class="table-wrap"><table><thead><tr><th>Nama acuan alternatif</th><th>Nama perhitungan</th><th>Periode</th><th>CR</th><th>Status</th></tr></thead><tbody><?php $q=$pdo->query("SELECT id_simulasi,nama_acuan,nama_simulasi,periode,cr FROM simulasi WHERE status='draft' AND cr IS NOT NULL AND source_ahp_id IS NULL ORDER BY id_simulasi DESC");$saved=$q->fetchAll();if(!$saved):?><tr><td colspan="5">Belum ada hasil AHP tersimpan.</td></tr><?php else:foreach($saved as $s):?><tr><td><?=e($s['nama_acuan']??'—')?></td><td><?=e($s['nama_simulasi'])?></td><td><?=!empty($s['periode'])?e(date('d-m-Y',strtotime($s['periode']))):'—'?></td><td><?=fmt($s['cr']??0,5)?></td><td><a class="button button-small" href="wp.php?ahp_id=<?=(int)$s['id_simulasi']?>">Pakai di WP</a></td></tr><?php endforeach;endif;?></tbody></table></div></section>
</main><footer class="footer">SPK AHP–WP · Tahap 1: AHP</footer><script src="../assets/js/script.js"></script></body></html>
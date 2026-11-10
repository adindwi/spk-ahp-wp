<?php
declare(strict_types=1);

/** Random Index (Saaty) untuk n = 1..10 */
const RI_TABEL = [1 => 0.0, 2 => 0.0, 3 => 0.58, 4 => 0.90, 5 => 1.12, 6 => 1.24, 7 => 1.32, 8 => 1.41, 9 => 1.45, 10 => 1.49];

/** Ubah "3" atau "1/3" menjadi angka. Hanya skala Saaty 1-9 dan kebalikannya yang diterima. */
function saaty_parse(?string $s): ?float
{
    static $map = null;
    if ($map === null) {
        $map = [];
        for ($i = 1; $i <= 9; $i++) {
            $map[(string) $i] = (float) $i;
            if ($i > 1) $map["1/$i"] = 1.0 / $i;
        }
    }
    return $map[trim((string) $s)] ?? null;
}

/** Perhitungan AHP lengkap dari matriks perbandingan berpasangan $a (n x n). */
function ahp_hitung(array $a): array
{
    $n = count($a);
    $col = array_fill(0, $n, 0.0);
    for ($i = 0; $i < $n; $i++) for ($j = 0; $j < $n; $j++) $col[$j] += $a[$i][$j];

    $norm = [];
    $bobot = [];
    for ($i = 0; $i < $n; $i++) {
        $jumlahBaris = 0.0;
        for ($j = 0; $j < $n; $j++) {
            $norm[$i][$j] = $a[$i][$j] / $col[$j];
            $jumlahBaris += $norm[$i][$j];
        }
        $bobot[$i] = $jumlahBaris / $n;
    }

    $aw = [];
    $rasio = [];
    for ($i = 0; $i < $n; $i++) {
        $aw[$i] = 0.0;
        for ($j = 0; $j < $n; $j++) $aw[$i] += $a[$i][$j] * $bobot[$j];
        $rasio[$i] = $aw[$i] / $bobot[$i];
    }

    $lambda = array_sum($rasio) / $n;
    $ci = $n > 1 ? max(0.0, ($lambda - $n) / ($n - 1)) : 0.0;
    $ri = RI_TABEL[$n] ?? 1.49;
    $cr = $ri > 0 ? $ci / $ri : 0.0;

    return [
        'n' => $n, 'col' => $col, 'norm' => $norm, 'bobot' => $bobot,
        'aw' => $aw, 'rasio' => $rasio, 'lambda' => $lambda,
        'ci' => $ci, 'ri' => $ri, 'cr' => $cr, 'konsisten' => $cr <= 0.1,
    ];
}

/**
 * Weighted Product.
 * $alt: [['id'=>, 'kode'=>, 'nama'=>, 'nilai'=>[nilai per kriteria, urut sama dengan $kriteria]], ...]
 * $bobot: bobot AHP per kriteria (jumlah = 1)
 */
function wp_hitung(array $kriteria, array $bobot, array $alt): array
{
    $hasil = [];
    foreach ($alt as $a) {
        $suku = [];
        $s = 1.0;
        foreach ($kriteria as $i => $k) {
            $x = (float) $a['nilai'][$i];
            $p = $k['tipe'] === 'cost' ? -$bobot[$i] : $bobot[$i];
            $h = pow($x, $p);
            $s *= $h;
            $suku[] = ['x' => $x, 'w' => $bobot[$i], 'p' => $p, 'h' => $h];
        }
        $hasil[] = ['alt' => $a, 'suku' => $suku, 's' => $s];
    }
    $total = array_sum(array_column($hasil, 's'));
    foreach ($hasil as &$h) $h['v'] = $total > 0 ? $h['s'] / $total : 0.0;
    unset($h);

    $urut = $hasil;
    usort($urut, fn($x, $y) => $y['v'] <=> $x['v']);
    return ['hasil' => $hasil, 'total_s' => $total, 'urut' => $urut];
}

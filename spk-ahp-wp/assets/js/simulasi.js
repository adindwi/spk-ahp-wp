(() => {
const $ = (id) => document.getElementById(id);
const n = KRI.length;
const sels = [...document.querySelectorAll('select.pw')];
const totalPasangan = n * (n - 1) / 2;
let tersimpan = false;

/* ---------- format angka (gaya Indonesia) ---------- */
const koma = (s) => String(s).replace('.', ',');
const f = (x, d = 4) => koma(x.toFixed(d));
const sg = (x) => { // 6 angka signifikan
    if (x === 0) return '0';
    const a = Math.abs(x);
    return koma(a >= 1e-6 && a < 1e12 ? +x.toPrecision(6) : x.toExponential(4));
};
const parseV = (v) => { if (!v) return null; if (v.includes('/')) { const [a, b] = v.split('/').map(Number); return a / b; } return +v; };
const frac = (x) => {
    if (Math.abs(x - Math.round(x)) < 1e-9) return String(Math.round(x));
    const r = 1 / x;
    return Math.abs(r - Math.round(r)) < 1e-9 ? '1/' + Math.round(r) : f(x, 3);
};
const nilaiTampil = (k, x) => /rupiah/i.test(k.satuan || '') ? 'Rp ' + x.toLocaleString('id-ID') : koma(x);
const kode = (k) => `<span class="code-pill">${k.kode}</span>`;
const lock = (msg) => `<div class="locked">🔒 ${msg}</div>`;
const sub = (t, d = '') => `<h3 class="sub">${t}</h3>${d ? `<p class="muted">${d}</p>` : ''}`;
const tbl = (head, rows, cls = 'mx') => `<div class="table-wrap"><table class="${cls}"><thead><tr>${head}</tr></thead><tbody>${rows}</tbody></table></div>`;

/* ---------- baca input manual ---------- */
function bacaMatriks() {
    const A = Array.from({ length: n }, (_, i) => Array.from({ length: n }, (_, j) => (i === j ? 1 : null)));
    let terisi = 0;
    sels.forEach((s) => {
        const i = +s.dataset.i, j = +s.dataset.j, v = parseV(s.value);
        const lo = $(`lo-${j}-${i}`); // sel di baris i, kolom j → kebalikannya di baris j, kolom i
        if (v) { A[i][j] = v; A[j][i] = 1 / v; terisi++; }
        if (lo) lo.textContent = v ? frac(1 / v) : '—';
    });
    return { A, terisi };
}

/* ---------- AHP ---------- */
function hitungAHP(A) {
    const col = Array(n).fill(0);
    for (let i = 0; i < n; i++) for (let j = 0; j < n; j++) col[j] += A[i][j];
    const norm = A.map((r) => r.map((v, j) => v / col[j]));
    const jmlBaris = norm.map((r) => r.reduce((a, b) => a + b, 0));
    const w = jmlBaris.map((x) => x / n);
    const aw = A.map((r) => r.reduce((t, v, j) => t + v * w[j], 0));
    const rasio = aw.map((x, i) => x / w[i]);
    const lambda = rasio.reduce((a, b) => a + b, 0) / n;
    const ci = n > 1 ? Math.max(0, (lambda - n) / (n - 1)) : 0;
    const ri = RI[n] ?? 1.49;
    const cr = ri > 0 ? ci / ri : 0;
    return { col, norm, jmlBaris, w, aw, rasio, lambda, ci, ri, cr, ok: cr <= 0.1 };
}

function htmlAHP(A, r) {
    const hd = '<th></th>' + KRI.map((k) => `<th>${k.kode}</th>`).join('');
    let h = '';

    h += sub('3a. Matriks perbandingan lengkap', 'Segitiga bawah = kebalikan dari segitiga atas (a<sub>ji</sub> = 1 / a<sub>ij</sub>). Baris Σ = jumlah tiap kolom.');
    h += tbl(hd, A.map((row, i) => `<tr><th>${KRI[i].kode}</th>${row.map((v) => `<td>${frac(v)}</td>`).join('')}</tr>`).join('')
        + `<tr class="sum"><th>Σ kolom</th>${r.col.map((v) => `<td>${f(v)}</td>`).join('')}</tr>`);

    h += sub('3b. Normalisasi matriks &amp; bobot prioritas', 'Setiap sel dibagi jumlah kolomnya. Bobot (w) = rata-rata tiap baris = jumlah baris / n.');
    h += tbl(hd + '<th>Σ baris</th><th>Bobot w</th><th>%</th>',
        r.norm.map((row, i) => `<tr><th>${KRI[i].kode}</th>${row.map((v) => `<td>${f(v)}</td>`).join('')}<td>${f(r.jmlBaris[i])}</td><td><b>${f(r.w[i], 6)}</b></td><td>${f(r.w[i] * 100, 2)}%</td></tr>`).join('')
        + `<tr class="sum"><th>Total</th>${'<td></td>'.repeat(n)}<td>${f(r.jmlBaris.reduce((a, b) => a + b, 0))}</td><td>${f(r.w.reduce((a, b) => a + b, 0), 6)}</td><td>100%</td></tr>`);

    h += sub('3c. Uji konsistensi', 'Hitung A·w (matriks perbandingan × vektor bobot), lalu bagi tiap elemen dengan bobotnya.');
    h += tbl('<th>Kriteria</th><th>A · w</th><th>w</th><th>(A · w) / w</th>',
        KRI.map((k, i) => `<tr><th>${k.kode}</th><td>${f(r.aw[i], 6)}</td><td>${f(r.w[i], 6)}</td><td>${f(r.rasio[i], 6)}</td></tr>`).join(''));

    const hasilCR = r.ok ? `${f(r.cr)} ≤ 0,1 → <b class="ok">KONSISTEN</b>` : `${f(r.cr)} &gt; 0,1 → <b class="bad">TIDAK KONSISTEN</b>`;
    h += `<div class="formula">
        <div>λ<sub>maks</sub> = rata-rata (A·w)/w = ${f(r.rasio.reduce((a, b) => a + b, 0), 4)} / ${n} = <b>${f(r.lambda, 4)}</b></div>
        <div>CI = (λ<sub>maks</sub> − n) / (n − 1) = (${f(r.lambda, 4)} − ${n}) / ${n - 1} = <b>${f(r.ci, 4)}</b></div>
        <div>RI (n = ${n}) = <b>${koma(r.ri)}</b> <small>(tabel Saaty)</small></div>
        <div>CR = CI / RI = ${f(r.ci, 4)} / ${koma(r.ri)} = ${r.ri > 0 ? hasilCR : '0 (n ≤ 2, otomatis konsisten)'}</div>
    </div>`;
    h += r.ok
        ? `<div class="alert success">Matriks konsisten. Bobot AHP berikut dipakai pada perhitungan WP:<div class="chips">${KRI.map((k, i) => `<span>${k.kode} ${k.nama}: <b>${f(r.w[i], 4)}</b></span>`).join('')}</div></div>`
        : `<div class="alert error"><b>CR melebihi 0,1.</b> Penilaian belum konsisten, jadi Langkah 5 dan 6 dikunci. Ubah beberapa perbandingan di Langkah 2 (misalnya jika A &gt; B dan B &gt; C, maka A harus &gt; C), lalu perhitungan akan diperbarui otomatis.</div>`;
    return h;
}

/* ---------- WP ---------- */
function hitungWP(w) {
    const baris = ALT.map((a) => {
        const suku = KRI.map((k, i) => { const p = k.tipe === 'cost' ? -w[i] : w[i]; return { x: a.nilai[i], w: w[i], p, h: Math.pow(a.nilai[i], p) }; });
        return { a, suku, S: suku.reduce((t, s) => t * s.h, 1) };
    });
    const total = baris.reduce((t, r) => t + r.S, 0);
    baris.forEach((r) => (r.V = r.S / total));
    const urut = [...baris].sort((x, y) => y.V - x.V);
    urut.forEach((r, i) => (r.rank = i + 1));
    return { baris, total, urut };
}

function htmlWP(w, wp) {
    let h = '';
    h += sub('5a. Bobot &amp; pangkat tiap kriteria', 'Bobot sudah berjumlah 1 (hasil AHP), jadi tidak perlu dinormalisasi lagi. Pangkat bernilai <b>negatif</b> untuk cost dan <b>positif</b> untuk benefit.');
    h += tbl('<th>Kriteria</th><th>Tipe</th><th>Bobot w</th><th>Pangkat</th>',
        KRI.map((k, i) => `<tr><td>${kode(k)} ${k.nama}</td><td><span class="type-pill ${k.tipe}">${k.tipe.toUpperCase()}</span></td><td>${f(w[i], 6)}</td><td><b>${k.tipe === 'cost' ? '−' : '+'}${f(w[i], 6)}</b></td></tr>`).join('')
        + `<tr class="sum"><td>Total</td><td></td><td>${f(w.reduce((a, b) => a + b, 0), 6)}</td><td></td></tr>`, '');

    h += sub('5b. Vektor S tiap alternatif', 'Rumus: S<sub>i</sub> = Π (x<sub>ij</sub>)<sup>pangkat<sub>j</sub></sup> — setiap nilai dipangkatkan, lalu seluruhnya dikalikan.');
    wp.baris.forEach((r, idx) => {
        h += `<div class="alt-calc"><h4>S<sub>${idx + 1}</sub> · ${r.a.nama}</h4>` + tbl('<th>Kriteria</th><th>Nilai x</th><th>Pangkat</th><th>x<sup>pangkat</sup></th>',
            r.suku.map((s, i) => `<tr><td>${kode(KRI[i])} ${KRI[i].nama}</td><td>${nilaiTampil(KRI[i], s.x)}</td><td>${s.p < 0 ? '−' : '+'}${f(Math.abs(s.p), 6)}</td><td>${sg(s.h)}</td></tr>`).join(''), '')
            + `<div class="formula"><div>S<sub>${idx + 1}</sub> = ${r.suku.map((s) => sg(s.h)).join(' × ')}</div><div>S<sub>${idx + 1}</sub> = <b>${sg(r.S)}</b></div></div></div>`;
    });

    h += sub('5c. Vektor V (preferensi relatif)', 'Rumus: V<sub>i</sub> = S<sub>i</sub> / Σ S. Total semua V harus 1.');
    h += tbl('<th>Alternatif</th><th>S</th><th>V = S / ΣS</th><th>%</th>',
        wp.baris.map((r, i) => `<tr><td><b>${r.a.nama}</b></td><td>${sg(r.S)}</td><td>${sg(r.S)} / ${sg(wp.total)} = <b>${f(r.V, 6)}</b></td><td>${f(r.V * 100, 2)}%</td></tr>`).join('')
        + `<tr class="sum"><td>Σ</td><td>${sg(wp.total)}</td><td>${f(wp.baris.reduce((t, r) => t + r.V, 0), 6)}</td><td>100%</td></tr>`, '');
    return h;
}

function htmlHasil(wp) {
    const top = wp.urut[0];
    return `<div class="winner-card"><div><p class="eyebrow">ALTERNATIF TERBAIK</p><h2>${top.a.nama}</h2><p>Memperoleh nilai preferensi V tertinggi.</p></div><div class="winner-score">${f(top.V, 6)}<small>Nilai V</small></div></div>`
        + tbl('<th>Peringkat</th><th>Alternatif</th><th>Vektor S</th><th>Nilai V</th><th>%</th>',
            wp.urut.map((r) => `<tr class="${r.rank === 1 ? 'top-rank' : ''}"><td><span class="rank-number">${r.rank}</span></td><td><b>${r.a.nama}</b></td><td>${sg(r.S)}</td><td><b>${f(r.V, 6)}</b></td><td>${f(r.V * 100, 2)}%</td></tr>`).join(''), '');
}

/* ---------- penyegaran seluruh halaman ---------- */
function refresh() {
    const { A, terisi } = bacaMatriks();
    const lengkap = n >= 2 && terisi === totalPasangan;
    const fs = $('fillStatus');
    if (fs) fs.innerHTML = lengkap ? `<span class="ok">✓ Semua ${totalPasangan} perbandingan terisi.</span>` : `Terisi <b>${terisi}</b> dari <b>${totalPasangan}</b> perbandingan.`;

    let r = null, wp = null;
    if (lengkap) r = hitungAHP(A);
    const bolehWP = !!(r && r.ok && ALT_OK);
    if (bolehWP) wp = hitungWP(r.w);

    $('out-ahp').innerHTML = lengkap ? htmlAHP(A, r) : lock(`Lengkapi dulu semua perbandingan di Langkah 2 (${terisi}/${totalPasangan}).`);
    $('out-wp').innerHTML = bolehWP ? htmlWP(r.w, wp)
        : lock(!ALT_OK ? 'Data alternatif belum memenuhi syarat (minimal 2 alternatif dengan nilai lengkap).' : !lengkap ? 'Menunggu bobot AHP dari Langkah 3.' : 'Bobot AHP belum valid (CR &gt; 0,1). Perbaiki matriks di Langkah 2.');
    $('out-hasil').innerHTML = bolehWP ? htmlHasil(wp) : lock('Hasil akhir muncul setelah perhitungan WP selesai.');
    $('saveBox').hidden = !bolehWP;
    if (tersimpan) { tersimpan = false; $('saveMsg').innerHTML = ''; $('btnSave').disabled = false; }

    // progres
    const selesai = { 1: true, 2: lengkap, 3: !!(r && r.ok), 4: ALT_OK, 5: bolehWP, 6: bolehWP };
    let aktif = 0;
    document.querySelectorAll('#stepper li').forEach((li) => {
        const s = +li.dataset.s;
        li.classList.toggle('done', selesai[s]);
        const isActive = !selesai[s] && !aktif;
        if (isActive) aktif = s;
        li.classList.toggle('active', isActive);
    });
    $('bar').style.width = (Object.values(selesai).filter(Boolean).length / 6) * 100 + '%';
}

sels.forEach((s) => s.addEventListener('change', refresh));
$('btnReset')?.addEventListener('click', () => { sels.forEach((s) => (s.value = '')); refresh(); });

/* ---------- simpan (tanpa pindah halaman) ---------- */
$('btnSave').addEventListener('click', async () => {
    const msg = $('saveMsg'), btn = $('btnSave');
    if (!$('nama_simulasi').value.trim()) { msg.innerHTML = '<div class="alert error">Nama simulasi wajib diisi.</div>'; $('nama_simulasi').focus(); return; }
    btn.disabled = true; btn.textContent = 'Menyimpan…';
    try {
        const res = await fetch(location.pathname, { method: 'POST', body: new FormData($('spk')) });
        const data = await res.json();
        if (data.ok) {
            msg.innerHTML = `<div class="alert success">${data.pesan} <a href="hasil.php?id=${data.id}">Buka di Riwayat →</a></div>`;
            tersimpan = true;
        } else { msg.innerHTML = `<div class="alert error">${data.pesan}</div>`; btn.disabled = false; }
    } catch (e) { msg.innerHTML = '<div class="alert error">Gagal menyimpan. Periksa koneksi atau server.</div>'; btn.disabled = false; }
    btn.textContent = 'Simpan ke Riwayat';
});

refresh();
})();

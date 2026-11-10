<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit('Permintaan tidak valid. Silakan kembali dan coba lagi.');
    }
}

function get_kriteria(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM kriteria ORDER BY urutan, id_kriteria')->fetchAll();
}

function get_nilai_alternatif(PDO $pdo, int $idAlternatif): array
{
    $stmt = $pdo->prepare(
        'SELECT id_kriteria, nilai FROM nilai_alternatif WHERE id_alternatif = ?'
    );
    $stmt->execute([$idAlternatif]);
    $hasil = [];
    foreach ($stmt->fetchAll() as $row) {
        $hasil[(int) $row['id_kriteria']] = (float) $row['nilai'];
    }
    return $hasil;
}

function rupiah(float|int|string $angka): string
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
verify_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: index.php');
    exit;
}
$stmt = $pdo->prepare('DELETE FROM alternatif WHERE id_alternatif = ?');
$stmt->execute([$id]);
header('Location: index.php?pesan=dihapus');
exit;

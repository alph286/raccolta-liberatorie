<?php
declare(strict_types=1);
// Download della propria copia, protetto dal token casuale ricevuto alla firma.
require_once __DIR__ . '/lib/bootstrap.php';
$tok = (string)($_GET['r'] ?? '');
if (!preg_match('/^[a-f0-9]{32}$/', $tok)) { http_response_code(404); exit; }
$st = db()->prepare('SELECT id, pdf_file FROM consents WHERE token = ?');
$st->execute([$tok]);
$row = $st->fetch();
$path = $row ? storage_path('pdf') . '/' . basename($row['pdf_file']) : '';
if (!$row || !is_file($path)) { http_response_code(404); exit; }
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="liberatoria-' . (int)$row['id'] . '.pdf"');
header('Content-Length: ' . filesize($path));
readfile($path);

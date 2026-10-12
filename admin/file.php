<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_admin();
$st = db()->prepare('SELECT id, pdf_file, firma_file FROM consents WHERE id = ?');
$st->execute([(int)($_GET['id'] ?? 0)]);
$c = $st->fetch();
if (!$c) { http_response_code(404); exit; }
if (($_GET['t'] ?? '') === 'firma') { $p = storage_path('firme') . '/' . basename($c['firma_file']); $type = 'image/png'; $name = 'firma-' . $c['id'] . '.png'; $disp = 'inline'; }
else { $p = storage_path('pdf') . '/' . basename($c['pdf_file']); $type = 'application/pdf'; $name = 'liberatoria-' . $c['id'] . '.pdf'; $disp = 'attachment'; }
if (!is_file($p)) { http_response_code(404); exit; }
header("Content-Type: $type");
header("Content-Disposition: $disp; filename=\"$name\"");
header('Content-Length: ' . filesize($p));
readfile($p);

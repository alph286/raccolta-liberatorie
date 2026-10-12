<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

function admin_user(): ?array {
    if (empty($_SESSION['admin_id'])) return null;
    if (time() - (int)($_SESSION['last_seen'] ?? 0) > 8 * 3600) { unset($_SESSION['admin_id'], $_SESSION['last_seen']); return null; }
    $_SESSION['last_seen'] = time();
    $st = db()->prepare('SELECT id, username FROM admins WHERE id = ?');
    $st->execute([$_SESSION['admin_id']]);
    return $st->fetch() ?: null;
}
function require_admin(string $loginQuery = ''): array {
    $u = admin_user();
    if (!$u) { header('Location: ' . base_url() . 'admin/login.php' . $loginQuery); exit; }
    return $u;
}
function login_blocked(): bool {
    $st = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND created_at > (NOW() - INTERVAL 15 MINUTE)');
    $st->execute([client_ip()]);
    return (int)$st->fetchColumn() >= 5;
}
function login_try(string $user, string $pass): bool {
    if (login_blocked()) return false;
    $st = db()->prepare('SELECT * FROM admins WHERE username = ?');
    $st->execute([$user]);
    $row = $st->fetch();
    // verifica a tempo costante anche se l'utente non esiste
    $hash = $row['password_hash'] ?? '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ01234';
    $ok = password_verify($pass, $hash) && $row;
    if (!$ok) {
        db()->prepare('INSERT INTO login_attempts (ip, created_at) VALUES (?, NOW())')->execute([client_ip()]);
        return false;
    }
    db()->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([client_ip()]);
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$row['id'];
    $_SESSION['last_seen'] = time();
    return true;
}

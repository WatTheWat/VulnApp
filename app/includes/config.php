<?php
// Shared bootstrap: session, DB connection, small helpers.

session_start();

// Return false on query errors instead of throwing, so pages can print
// the raw DB error (INTENTIONAL: makes error-based SQLi visible).
mysqli_report(MYSQLI_REPORT_OFF);

$db = mysqli_connect(
    getenv('DB_HOST') ?: 'db',
    getenv('DB_USER') ?: 'coffee',
    getenv('DB_PASS') ?: '',
    getenv('DB_NAME') ?: 'coffeeshop'
);

if (!$db) {
    http_response_code(503);
    exit('The espresso machine is warming up. Try again in a moment.');
}
mysqli_set_charset($db, 'utf8mb4');

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function setting(mysqli $db, string $name, string $default = ''): string
{
    $stmt = $db->prepare('SELECT value FROM settings WHERE name = ?');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row['value'] ?? $default;
}

function is_admin(): bool
{
    return !empty($_SESSION['admin_user']);
}

function require_admin(): void
{
    if (!is_admin()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function money($n): string
{
    return '$' . number_format((float) $n, 2);
}

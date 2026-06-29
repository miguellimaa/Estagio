<?php
$pdo = new PDO("mysql:host=localhost;dbname=irtrade_estoque;charset=utf8","root","");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// Ensure session is started (many pages call this file)
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

require_once __DIR__ . '/helpers.php';

// CSRF token (one per session)
if (empty($_SESSION['csrf_token'])) {
	try {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(16));
	} catch (Exception $e) {
		// Fallback if random_bytes unavailable
		$_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(16));
	}
}
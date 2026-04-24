<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
header('Content-Type: application/json');
requireAdminAuth();
$users = getSalesUsers();
echo json_encode(['success' => true, 'sales_users' => $users]);

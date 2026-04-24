<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
header('Content-Type: application/json');
requireAdminAuth();
$designers = getDesigners();
echo json_encode(['success' => true, 'designers' => $designers]);

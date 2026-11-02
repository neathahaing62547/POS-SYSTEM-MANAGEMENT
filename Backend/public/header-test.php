<?php
header('Content-Type: application/json');
header('X-PHP-Test: PHP-Header-Reached');

echo json_encode([
    'message' => 'Direct PHP test',
]);
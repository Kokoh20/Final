<?php
declare(strict_types=1);
require __DIR__ . '/_utils.php';
require __DIR__ . '/db.php';

$pdo = db();

$stmt = $pdo->query('SELECT p.slug as id, p.name, p.price, p.image_url as image, c.slug as category
                      FROM products p JOIN categories c ON c.id = p.category_id
                      WHERE p.is_active = 1
                      ORDER BY p.id ASC');
$rows = $stmt->fetchAll();

send_json([ 'products' => $rows ]);


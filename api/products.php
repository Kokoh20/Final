<?php
declare(strict_types=1);
require __DIR__ . '/_utils.php';

// Simple in-memory catalog for demo. In a real app, load from DB.
$products = [
    [ 'id' => 'beef-bulgogi', 'name' => 'Beef Bulgogi', 'price' => 99, 'category' => 'rice-bowl', 'image' => 'assets/images/coffee7.jpg' ],
    [ 'id' => 'chicken-teriyaki', 'name' => 'Chicken Teriyaki', 'price' => 99, 'category' => 'rice-bowl', 'image' => 'assets/images/dessert3.jpg' ],
    [ 'id' => 'pork-samyeoupsal', 'name' => 'Pork Samyeoupsal', 'price' => 88, 'category' => 'rice-bowl', 'image' => 'assets/images/drink1.jpg' ],
    [ 'id' => 'spicy-korean-wings', 'name' => 'Spicy Korean Chicken Wings', 'price' => 99, 'category' => 'rice-bowl', 'image' => 'assets/images/cake16.jpg' ],
    [ 'id' => 'spicy-korean-dumplings', 'name' => 'Spicy Korean Dumplings', 'price' => 99, 'category' => 'rice-bowl', 'image' => 'assets/images/coffee6.jpg' ],
    [ 'id' => 'spicy-korean-pork-ribs', 'name' => 'Spicy Korean Pork Ribs', 'price' => 99, 'category' => 'rice-bowl', 'image' => 'assets/images/cake11.jpg' ],
];

send_json([ 'products' => $products ]);


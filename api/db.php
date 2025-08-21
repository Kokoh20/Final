<?php
declare(strict_types=1);

// Returns a PDO connection to a local SQLite database file and ensures schema exists.
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dataDir = __DIR__ . '/../data';
    if (!is_dir($dataDir)) {
        @mkdir($dataDir, 0775, true);
    }
    $dsn = 'sqlite:' . $dataDir . '/mauiz.sqlite';
    $pdo = new PDO($dsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    migrate($pdo);
    seed_if_empty($pdo);
    return $pdo;
}

function migrate(PDO $pdo): void {
    $pdo->exec('CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slug TEXT NOT NULL UNIQUE,
        name TEXT NOT NULL
    );');

    $pdo->exec('CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_id INTEGER NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        name TEXT NOT NULL,
        price NUMERIC NOT NULL,
        image_url TEXT,
        is_active INTEGER NOT NULL DEFAULT 1,
        FOREIGN KEY (category_id) REFERENCES categories(id)
    );');

    $pdo->exec('CREATE TABLE IF NOT EXISTS extras (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slug TEXT NOT NULL UNIQUE,
        name TEXT NOT NULL,
        price NUMERIC NOT NULL,
        is_active INTEGER NOT NULL DEFAULT 1
    );');

    $pdo->exec('CREATE TABLE IF NOT EXISTS product_extras (
        product_id INTEGER NOT NULL,
        extra_id INTEGER NOT NULL,
        PRIMARY KEY (product_id, extra_id),
        FOREIGN KEY (product_id) REFERENCES products(id),
        FOREIGN KEY (extra_id) REFERENCES extras(id)
    );');

    $pdo->exec('CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code TEXT NOT NULL UNIQUE,
        status TEXT NOT NULL DEFAULT "new",
        order_type TEXT NOT NULL DEFAULT "pickup",
        customer_name TEXT NOT NULL,
        customer_phone TEXT NOT NULL,
        customer_address TEXT,
        subtotal NUMERIC NOT NULL,
        discount NUMERIC NOT NULL DEFAULT 0,
        total NUMERIC NOT NULL,
        created_at TEXT NOT NULL DEFAULT (datetime("now"))
    );');

    $pdo->exec('CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        product_id INTEGER,
        product_name TEXT NOT NULL,
        unit_price NUMERIC NOT NULL,
        quantity INTEGER NOT NULL,
        notes TEXT,
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    );');

    $pdo->exec('CREATE TABLE IF NOT EXISTS order_item_extras (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_item_id INTEGER NOT NULL,
        extra_id INTEGER,
        extra_name TEXT NOT NULL,
        unit_price NUMERIC NOT NULL,
        quantity INTEGER NOT NULL,
        FOREIGN KEY (order_item_id) REFERENCES order_items(id),
        FOREIGN KEY (extra_id) REFERENCES extras(id)
    );');

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_products_category ON products(category_id);');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders(created_at);');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_order_items_order_id ON order_items(order_id);');
}

function seed_if_empty(PDO $pdo): void {
    $count = (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    if ($count > 0) {
        return;
    }
    // Seed category
    $pdo->prepare('INSERT INTO categories (slug, name) VALUES (?,?)')->execute(['rice-bowl','Rice Bowl']);
    $categoryId = (int)$pdo->lastInsertId();

    // Seed products
    $products = [
        ['beef-bulgogi','Beef Bulgogi', 99,'assets/images/coffee7.jpg'],
        ['chicken-teriyaki','Chicken Teriyaki', 99,'assets/images/dessert3.jpg'],
        ['pork-samyeoupsal','Pork Samyeoupsal', 88,'assets/images/drink1.jpg'],
        ['spicy-korean-wings','Spicy Korean Chicken Wings', 99,'assets/images/cake16.jpg'],
        ['spicy-korean-dumplings','Spicy Korean Dumplings', 99,'assets/images/coffee6.jpg'],
        ['spicy-korean-pork-ribs','Spicy Korean Pork Ribs', 99,'assets/images/cake11.jpg'],
    ];
    $stmt = $pdo->prepare('INSERT INTO products (category_id, slug, name, price, image_url) VALUES (?,?,?,?,?)');
    foreach ($products as $p) {
        $stmt->execute([$categoryId, $p[0], $p[1], $p[2], $p[3]]);
    }

    // Seed extras (global)
    $extras = [
        ['kimchi','kimchi',15],
        ['lettuce','lettuce',15],
        ['corn','corn',10],
        ['egg','sunny side up egg',15],
        ['seaweeds','seaweeds',10],
        ['pickles','pickles',10],
    ];
    $stmt2 = $pdo->prepare('INSERT INTO extras (slug, name, price) VALUES (?,?,?)');
    foreach ($extras as $e) {
        $stmt2->execute([$e[0], $e[1], $e[2]]);
    }
}


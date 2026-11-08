<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO((string) config('db.dsn'), config('db.user'), config('db.pass'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        if (db_driver() === 'sqlite') {
            $pdo->exec('PRAGMA foreign_keys = ON');
        }
    }
    return $pdo;
}

function db_driver(): string
{
    return explode(':', (string) config('db.dsn'), 2)[0];
}

function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_installed(): bool
{
    try {
        q('SELECT 1 FROM products LIMIT 1');
        return true;
    } catch (PDOException) {
        return false;
    }
}

/** Creates the tables and seeds the First Edition catalog. Safe to run once. */
function db_install(): void
{
    $mysql = db_driver() === 'mysql';
    $pk = $mysql ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $ref = $mysql ? 'INT UNSIGNED' : 'INTEGER';
    $suffix = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';

    $tables = [
        "CREATE TABLE products (
            id $pk,
            slug VARCHAR(64) NOT NULL UNIQUE,
            name VARCHAR(120) NOT NULL,
            subtitle VARCHAR(160) NOT NULL DEFAULT '',
            description TEXT NOT NULL,
            price_cents INT NOT NULL,
            edition_size INT NOT NULL,
            next_number INT NOT NULL DEFAULT 1,
            image VARCHAR(160) NOT NULL DEFAULT '',
            sort INT NOT NULL DEFAULT 0,
            active INT NOT NULL DEFAULT 1
        )$suffix",
        "CREATE TABLE variants (
            id $pk,
            product_id $ref NOT NULL,
            label VARCHAR(40) NOT NULL,
            stock INT NOT NULL DEFAULT 0,
            sort INT NOT NULL DEFAULT 0,
            FOREIGN KEY (product_id) REFERENCES products(id)
        )$suffix",
        "CREATE TABLE orders (
            id $pk,
            token VARCHAR(32) NOT NULL UNIQUE,
            status VARCHAR(16) NOT NULL DEFAULT 'pending',
            email VARCHAR(190) NOT NULL DEFAULT '',
            name VARCHAR(190) NOT NULL DEFAULT '',
            ship_name VARCHAR(190) NOT NULL DEFAULT '',
            ship_line1 VARCHAR(190) NOT NULL DEFAULT '',
            ship_line2 VARCHAR(190) NOT NULL DEFAULT '',
            ship_city VARCHAR(120) NOT NULL DEFAULT '',
            ship_state VARCHAR(60) NOT NULL DEFAULT '',
            ship_postal VARCHAR(20) NOT NULL DEFAULT '',
            ship_country VARCHAR(2) NOT NULL DEFAULT '',
            subtotal_cents INT NOT NULL DEFAULT 0,
            shipping_cents INT NOT NULL DEFAULT 0,
            tax_cents INT NOT NULL DEFAULT 0,
            total_cents INT NOT NULL DEFAULT 0,
            stripe_session_id VARCHAR(255) NOT NULL DEFAULT '',
            stripe_payment_intent VARCHAR(255) NOT NULL DEFAULT '',
            tracking_number VARCHAR(64) NOT NULL DEFAULT '',
            created_at VARCHAR(19) NOT NULL,
            paid_at VARCHAR(19) NULL,
            shipped_at VARCHAR(19) NULL
        )$suffix",
        "CREATE TABLE order_items (
            id $pk,
            order_id $ref NOT NULL,
            product_id $ref NOT NULL,
            variant_id $ref NOT NULL,
            product_name VARCHAR(120) NOT NULL,
            variant_label VARCHAR(40) NOT NULL,
            unit_cents INT NOT NULL,
            qty INT NOT NULL,
            edition_numbers VARCHAR(255) NOT NULL DEFAULT '',
            FOREIGN KEY (order_id) REFERENCES orders(id)
        )$suffix",
        "CREATE TABLE signups (
            id $pk,
            email VARCHAR(190) NOT NULL UNIQUE,
            created_at VARCHAR(19) NOT NULL
        )$suffix",
    ];

    $pdo = db();
    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }

    $pdo->beginTransaction();
    foreach (catalog_seed() as $sort => $product) {
        q('INSERT INTO products (slug, name, subtitle, description, price_cents, edition_size, image, sort)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
            $product['slug'], $product['name'], $product['subtitle'], $product['description'],
            $product['price_cents'], $product['edition_size'], $product['image'], $sort,
        ]);
        $productId = (int) $pdo->lastInsertId();
        $variantSort = 0;
        foreach ($product['variants'] as $label => $stock) {
            q('INSERT INTO variants (product_id, label, stock, sort) VALUES (?, ?, ?, ?)',
                [$productId, (string) $label, $stock, $variantSort++]);
        }
    }
    $pdo->commit();
}

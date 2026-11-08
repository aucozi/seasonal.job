<?php
declare(strict_types=1);

/** The First Edition run, as counted on Nov 8, 2026. Used once, when the database is installed. */
function catalog_seed(): array
{
    return [
        [
            'slug' => 'lithusa-tee',
            'name' => 'LITHUSA Tee',
            'subtitle' => 'First Edition · 33 made',
            'description' => "The original LITHUSA tee from the fair run, with its original tags and packaging. "
                . "Only 33 exist. Every order ships with a card numbered out of 33.\n\n"
                . "LITHUSA will return later as a printed-to-order Edition 02. This is the first one.",
            'price_cents' => 4000,
            'edition_size' => 33,
            'image' => 'img/lithusa-tee.svg',
            'variants' => ['S' => 4, 'M' => 10, 'L' => 12, 'XL' => 4, '2XL' => 3],
        ],
        [
            'slug' => 'lithusa-trucker',
            'name' => 'LITHUSA Trucker Hat',
            'subtitle' => 'First Edition · 9 made',
            'description' => "The trucker hat from the original fair run. Only 9 exist, "
                . "and each ships with a card numbered out of 9.",
            'price_cents' => 3200,
            'edition_size' => 9,
            'image' => 'img/lithusa-trucker.svg',
            'variants' => ['One size' => 9],
        ],
        [
            'slug' => 'lithusa-cap',
            'name' => 'LITHUSA Cap',
            'subtitle' => 'First Edition · 22 made',
            'description' => "The cap from the original fair run. Only 22 exist, "
                . "and each ships with a card numbered out of 22.",
            'price_cents' => 3000,
            'edition_size' => 22,
            'image' => 'img/lithusa-cap.svg',
            'variants' => ['One size' => 22],
        ],
    ];
}

function products_all(): array
{
    $products = q('SELECT * FROM products WHERE active = 1 ORDER BY sort')->fetchAll();
    foreach ($products as &$product) {
        $product['variants'] = variants_for((int) $product['id']);
        $product['remaining'] = array_sum(array_column($product['variants'], 'stock'));
    }
    return $products;
}

function product_by_slug(string $slug): ?array
{
    $product = q('SELECT * FROM products WHERE slug = ? AND active = 1', [$slug])->fetch();
    if (!$product) {
        return null;
    }
    $product['variants'] = variants_for((int) $product['id']);
    $product['remaining'] = array_sum(array_column($product['variants'], 'stock'));
    return $product;
}

function variants_for(int $productId): array
{
    return q('SELECT * FROM variants WHERE product_id = ? ORDER BY sort', [$productId])->fetchAll();
}

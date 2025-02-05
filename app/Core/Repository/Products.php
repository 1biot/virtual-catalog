<?php

namespace App\Core\Repository;

use FQL\Enum;
use FQL\Exception;
use FQL\Interface;
use FQL\Results;
use FQL\Stream;

class Products
{
    private Interface\Stream $productsFile;

    /**
     * @throws Exception\FileNotFoundException
     */
    public function __construct(private readonly string $productXmlFile = '', private readonly ?string $tempDir = null)
    {
        $this->productsFile = Stream\Xml::open($this->productXmlFile);
    }

    public function startQuery(): Interface\Query
    {
        return $this->productsFile->query();
    }

    public function getBaseProductsQuery(): Interface\Query
    {
        return $this->productsFile->query()
            ->select('NAME')->as('name')
            ->md5('name')->as('slug')
            ->coalesce('MANUFACTURER', "")->as('manufacturer')
            ->md5('manufacturer')->as('manufacturerSlug')
            ->coalesceNotEmpty('CATEGORIES.DEFAULT_CATEGORY', 'CATEGORIES.CATEGORY')->as('category')
            ->md5('category')->as('categorySlug')
            ->coalesce('DESCRIPTION', "")->as('description')
            ->select('SUPPLIER')->as('supplier')
            ->select('IMAGES.IMAGE')->as('image')
            ->select('INFORMATION_PARAMETERS.INFORMATION_PARAMETER')->as('parameter')
            ->select('CODE')->as('code')
            ->coalesce('EAN', "")->as('ean')
            ->select('AVAILABILITY_OUT_OF_STOCK')->as('availability')
            ->concat('VAT', "%")->as('vat')
            ->round('PURCHASE_PRICE', 2)->as('purchasePrice')
            ->from('SHOP.SHOPITEM');
    }

    public function getProductBySlug(string $slug): array
    {
        return $this->getBaseProductsQuery()
            ->where('VISIBILITY', Enum\Operator::EQUAL_STRICT, 'visible')
            ->having('slug', Enum\Operator::EQUAL, $slug)
            ->limit(1)
            ->execute(Results\InMemory::class)
            ->fetch();
    }

    public function productsMatchAgainstQuery(string $query, int $limit = 120): ?Interface\Query
    {
        $queryWords = array_filter(explode(" ", $query), fn ($v) => strlen($v) > 2);
        if (count($queryWords) === 0) {
            return null;
        }

        return $this->getBaseProductsQuery()
            ->fulltext(['ean', 'code', 'name', 'manufacturer', 'description'], $query)->as('_score')
            ->having('_score', Enum\Operator::GREATER_THAN, count($queryWords) < 3 ? 0 : 3)
            ->limit($limit);
    }

    /**
     * @throws Exception\InvalidFormatException
     * @throws Exception\FileNotFoundException
     * @throws \Exception When failure get Iterator from query results
     */
    public function cacheQuery(Interface\Query $query): Interface\Query
    {
        $tempFile = implode(DIRECTORY_SEPARATOR, [$this->tempDir, 'cache', md5((string) $query)]);
        if (!file_exists($tempFile)) {
            file_put_contents($tempFile, json_encode(iterator_to_array($query->execute()->getIterator())));
        }

        bdump(md5((string) $query));
        return Stream\Provider::fromFile($tempFile, Enum\Format::JSON_STREAM)->query();
    }
}

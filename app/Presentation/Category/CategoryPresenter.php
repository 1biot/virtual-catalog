<?php

declare(strict_types=1);

namespace App\Presentation\Category;

use App\Presentation\AppPresenter;
use FQL\Enum;
use FQL\Exception;
use FQL\Interface;
use FQL\Results;
use FQL\Stream;
use Nette;

final class CategoryPresenter extends AppPresenter
{
    private const PER_PAGE_DEFAULT = 12;
    private ?array $category = null;

    public function __construct(string $productXmlFile, private readonly string $tempDir)
    {
        parent::__construct($productXmlFile);
    }

    public function actionDefault(string $slug, int $page): void
    {
        $category = $this->getBaseCategoryQuery($slug)
            ->limit(1)
            ->execute(Results\InMemory::class)
            ->fetch();

        if (!$category) {
            throw new Nette\Application\BadRequestException('Category not found', Nette\Http\IResponse::S404_NotFound);
        }

        $this->category = $category;
    }

    public function renderDefault(string $slug, int $page): void
    {
        $this->getTemplate()->add('category', [
            'category' => $this->category['category'],
            'slug' => $this->category['slug'],
        ]);

        $resultsQuery = $this->getBaseCategoryQuery($slug);

        $paginator = new Nette\Utils\Paginator;
        $paginator->setBase(1);
        $paginator->setPage($page);
        $paginator->setItemsPerPage(self::PER_PAGE_DEFAULT);
        $paginator->setItemCount($resultsQuery->execute(Results\InMemory::class)->count());

        $results = $resultsQuery
            ->page($paginator->getPage(), $paginator->getItemsPerPage())
            ->execute(Results\InMemory::class);

        $this->getTemplate()->add('products', $results->fetchAll());
        $this->getTemplate()->add('paginator', $paginator);
    }

    /**
     * @throws Exception\FileNotFoundException
     * @throws Exception\InvalidFormatException
     */
    private function getBaseCategoryQuery(string $slug): Interface\Query
    {
        $query = $this->getProductsQuery()
            ->select('NAME')->as('name')
            ->coalesceNotEmpty('CATEGORIES.DEFAULT_CATEGORY', 'CATEGORIES.CATEGORY')->as('category')
            ->md5('category')->as('slug')
            ->md5('NAME')->as('productSlug')
            ->select('DESCRIPTION')->as('description')
            ->select('MANUFACTURER')->as('manufacturer')
            ->select('SUPPLIER')->as('supplier')
            ->select('IMAGES.IMAGE')->as('image')
            ->select('INFORMATION_PARAMETERS.INFORMATION_PARAMETER')->as('parameter')
            ->select('CODE')->as('code')
            ->select('EAN')->as('ean')
            ->select('AVAILABILITY_OUT_OF_STOCK')->as('availability')
            ->concat('VAT', "%")->as('vat')
            ->from('SHOP.SHOPITEM')
            ->where('VISIBILITY', Enum\Operator::EQUAL_STRICT, 'visible')
            ->having('category', Enum\Operator::NOT_EQUAL_STRICT, "")
            ->and('slug', Enum\Operator::EQUAL_STRICT, $slug);

        $tempFile = implode(DIRECTORY_SEPARATOR, [$this->tempDir, 'cache', md5((string) $query)]);
        if (!file_exists($tempFile)) {
            file_put_contents($tempFile, json_encode(iterator_to_array($query->execute()->getIterator())));
        }

        bdump(md5((string) $query));
        return Stream\Provider::fromFile($tempFile, Enum\Format::JSON_STREAM)->query();
    }
}

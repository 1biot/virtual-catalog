<?php

declare(strict_types=1);

namespace App\Presentation\Search;

use App\Presentation\AppPresenter;
use FQL\Enum;
use FQL\Exception;
use FQL\Interface;
use FQL\Results;
use Nette;


final class SearchPresenter extends AppPresenter
{
    private const PER_PAGE_DEFAULT = 12;

    public function renderDefault(string $query, int $page): void
    {
        $queryCountWords = count(array_filter(explode(" ", $query)));
        if ($queryCountWords === 1 && strlen($query) < 3) {
            $this->flashMessage('Musíš zadat alespoň tři znaky');
            $this->getTemplate()->add('query', $query);
            $this->getTemplate()->add('products', []);
            return;
        }

        $resultsQuery = $this->getBaseProductQuery();
        $resultsQuery->fulltext(['ean', 'code', 'name', 'manufacturer', 'description'], $query)->as('_score');
        $resultsQuery->having('_score', Enum\Operator::GREATER_THAN, $queryCountWords < 3 ? 0 : 3)
            ->limit(120);

        $paginator = new Nette\Utils\Paginator;
        $paginator->setBase(1);
        $paginator->setPage($page);
        $paginator->setItemsPerPage(self::PER_PAGE_DEFAULT);
        $paginator->setItemCount($resultsQuery->execute(Results\InMemory::class)->count());

        $results = new \ArrayIterator([]);
        if ($paginator->getItemCount()) {
            $results = $resultsQuery
                ->orderBy('_score')->desc()
                ->page($page, $paginator->getItemsPerPage())
                ->execute(Results\InMemory::class)
                ->getIterator();
        }

        $this->getTemplate()->add('query', $query);
        $this->getTemplate()->add('products', $results);
        $this->getTemplate()->add('paginator', $paginator);
    }

    /**
     * @throws Exception\FileNotFoundException
     * @throws Exception\InvalidFormatException
     */
    private function getBaseProductQuery(): Interface\Query
    {
        return $this->getProductsQuery()
            ->select('NAME')->as('name')
            ->md5('NAME')->as('productSlug')
            ->coalesceNotEmpty('CATEGORIES.DEFAULT_CATEGORY', 'CATEGORIES.CATEGORY')->as('category')
            ->md5('category')->as('categorySlug')
            ->coalesce('DESCRIPTION', "")->as('description')
            ->coalesce('MANUFACTURER', "")->as('manufacturer')
            ->select('IMAGES.IMAGE')->as('image')
            ->select('CODE')->as('code')
            ->coalesce('EAN', "")->as('ean')
            ->select('AVAILABILITY_OUT_OF_STOCK')->as('availability')
            ->concat('VAT', "%")->as('vat')
            ->from('SHOP.SHOPITEM')
            ->where('VISIBILITY', Enum\Operator::EQUAL_STRICT, 'visible');
    }
}

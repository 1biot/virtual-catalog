<?php

declare(strict_types=1);

namespace App\Presentation\ManufacturerDetail;

use App\Presentation\Auth\AuthPresenter;
use FQL\Enum;
use FQL\Exception;
use FQL\Interface;
use FQL\Results;
use Nette;

final class ManufacturerDetailPresenter extends AuthPresenter
{
    private ?array $manufacturer = null;

    public function actionDefault(string $slug, int $page = 1, array $filter =[]): void
    {
        $manufacturer = $this->getBaseManufacturerQuery($slug)
            ->limit(1)
            ->execute(Results\InMemory::class)
            ->fetch();

        if (!$manufacturer) {
            throw new Nette\Application\BadRequestException('Manunfactuerer not found', Nette\Http\IResponse::S404_NotFound);
        }

        $this->manufacturer = $manufacturer;
    }

    public function renderDefault(string $slug, int $page = 1, array $filter =[]): void
    {
        $this->getTemplate()->add('manufacturer', [
            'manufacturer' => $this->manufacturer['manufacturer'],
            'slug' => $this->manufacturer['manufacturerSlug'],
        ]);

        $resultsQuery = $this->getBaseManufacturerQuery($slug);

        $paginator = $this->createPaginator($page, $resultsQuery->execute(Results\InMemory::class)->count());
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
    private function getBaseManufacturerQuery(string $slug): Interface\Query
    {
        $query = $this->products->getBaseProductsQuery()
            ->where('VISIBILITY', Enum\Operator::EQUAL_STRICT, 'visible')
            ->having('manufacturer', Enum\Operator::NOT_EQUAL_STRICT, "")
            ->and('manufacturerSlug', Enum\Operator::EQUAL_STRICT, $slug);
        return $this->products->cacheQuery($query);
    }
}

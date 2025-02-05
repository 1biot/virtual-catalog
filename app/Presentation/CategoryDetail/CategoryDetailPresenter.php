<?php

declare(strict_types=1);

namespace App\Presentation\CategoryDetail;

use app\Presentation\Auth\AuthPresenter;
use FQL\Enum;
use FQL\Exception;
use FQL\Interface;
use FQL\Results;
use Nette;

final class CategoryDetailPresenter extends AuthPresenter
{
    private ?array $category = null;

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
    private function getBaseCategoryQuery(string $slug): Interface\Query
    {
        $query = $this->products->getBaseProductsQuery()
            ->where('VISIBILITY', Enum\Operator::EQUAL_STRICT, 'visible')
            ->having('category', Enum\Operator::NOT_EQUAL_STRICT, "")
            ->and('categorySlug', Enum\Operator::EQUAL_STRICT, $slug);
        return $this->products->cacheQuery($query);
    }
}

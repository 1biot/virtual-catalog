<?php

declare(strict_types=1);

namespace App\Presentation\CategoryDetail;

use app\Presentation\Auth\AuthPresenter;
use FQL\Enum;
use FQL\Exception;
use FQL\Interface;
use FQL\Results;
use Nette;
use Nette\Application\UI\Form;

final class CategoryDetailPresenter extends AuthPresenter
{
    private ?array $category = null;
    private array $actualFilter = [];
    private array $filter = [];

    public function actionDefault(string $slug, int $page = 1, array $filter = []): void
    {
        $categoryResult = $this->getBaseCategoryQuery($slug)
            ->execute(Results\InMemory::class);

        $category = $categoryResult->fetch();
        if (!$category) {
            throw new Nette\Application\BadRequestException('Category not found', Nette\Http\IResponse::S404_NotFound);
        }

        $this->category = $category;
        $this->actualFilter = $filter;
        $this->filter = $this->buildFilter($categoryResult);
    }

    public function renderDefault(string $slug, int $page = 1, array $filter = []): void
    {
        $this->getTemplate()->add('category', [
            'category' => $this->category['category'],
            'slug' => $this->category['categorySlug'],
        ]);

        $resultsQuery = $this->applyFilters(
            $this->getBaseCategoryQuery($slug),
            $filter
        );

        $paginator = $this->createPaginator($page, $resultsQuery->execute(Results\InMemory::class)->count());
        $results = $resultsQuery
            ->orderBy('name')->asc()
            ->page($paginator->getPage(), $paginator->getItemsPerPage())
            ->execute(Results\InMemory::class);

        $this->getTemplate()->add('products', $results->fetchAll());
        $this->getTemplate()->add('filter', $filter);
        $this->getTemplate()->add('paginator', $paginator);
    }

    protected function createComponentFilterForm(): Form
    {
        $form = new Form();

        $brands = $this->filter['brand'] ?? [];
        asort($brands);
        $form->addCheckboxList('brand', 'Brand:', $brands)
            ->setRequired(false)
            ->setDefaultValue($this->actualFilter['brand'] ?? [])
            ->setHtmlAttribute('class', 'form-check-input');

        $form->addSubmit('submit', 'Filter');

        $form->onSuccess[] = function (Form $form, array $values): void {
            $this->redirect('this', ['filter' => $values]);
        };

        return $form;
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

    private function applyFilters(Interface\Query $query, array $filter): Interface\Query
    {
        if (empty($filter)) {
            return $query;
        }

        if (!empty($filter['brand'])) {
            $query->having('manufacturerSlug', Enum\Operator::IN, $filter['brand']);
        }

        return $query;
    }

    private function buildFilter(Interface\Results $results): array
    {
        $brands = [];
        foreach ($results->fetchAll() as $product) {
            $brands[$product['manufacturerSlug']] = $product['manufacturer'];
        }

        return [
            'brand' => $brands,
        ];
    }
}

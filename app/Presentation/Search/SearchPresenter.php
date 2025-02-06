<?php

declare(strict_types=1);

namespace App\Presentation\Search;

use app\Presentation\Auth\AuthPresenter;
use FQL\Results;

final class SearchPresenter extends AuthPresenter
{
    public function renderDefault(string $query, int $page = 1, array $filter = []): void
    {
        $resultsQuery = $this->products->productsMatchAgainstQuery(rawurldecode($query), 1);
        if (!$resultsQuery) {
            $this->getTemplate()->add('query', $query);
            $this->getTemplate()->add('products', new \ArrayIterator([]));
            $this->getTemplate()->add('paginator', $this->createPaginator($page, 0));
            return;
        }

        $results = $resultsQuery->execute(Results\InMemory::class);
        if (!$results->exists())
        {
            $this->getTemplate()->add('query', rawurldecode($query));
            $this->getTemplate()->add('products', new \ArrayIterator([]));
            $this->getTemplate()->add('paginator', $this->createPaginator($page, 0));
            return;
        }

        $productsCount = $results->count();
        $paginator = $this->createPaginator($page, $productsCount);
        $products = $this->products->productsMatchAgainstQuery(rawurldecode($query))
            ->orderBy('_score')->desc()
            ->page($page, $paginator->getItemsPerPage())
            ->execute(Results\InMemory::class)
            ->getIterator();

        $this->getTemplate()->add('query', rawurldecode($query));
        $this->getTemplate()->add('products', $products);
        $this->getTemplate()->add('paginator', $paginator);
    }
}

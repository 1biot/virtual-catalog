<?php

declare(strict_types=1);

namespace App\Presentation\Categories;

use app\Presentation\Auth\AuthPresenter;
use FQL\Enum;
use FQL\Exception;

final class CategoriesPresenter extends AuthPresenter
{
    /**
     * @throws Exception\InvalidFormatException
     * @throws Exception\FileNotFoundException
     */
    public function actionDefault(): void
    {
        $results = $this->products->startQuery()
            ->distinct()
            ->coalesceNotEmpty('CATEGORIES.DEFAULT_CATEGORY', 'CATEGORIES.CATEGORY')->as('category')
            ->md5('CATEGORIES.DEFAULT_CATEGORY')->as('slug')
            ->select('VISIBILITY')->as('visibility')
            ->from('SHOP.SHOPITEM')
            ->where('VISIBILITY', Enum\Operator::EQUAL_STRICT, 'visible')
            ->having('category', Enum\Operator::NOT_EQUAL, '')
            ->orderBy('category')->asc()
            ->execute();

        $sortedCategories = [];
        foreach ($results->fetchAll() as $category) {
            $parts = explode(' > ', $category['category']);
            $mainCategory = $parts[0];
            if (!isset($sortedCategories[$mainCategory])) {
                $sortedCategories[$mainCategory] = [];
            }

            $sortedCategories[$mainCategory][] = $category;
        }

        $this->getTemplate()->add('categories', $sortedCategories);
    }
}

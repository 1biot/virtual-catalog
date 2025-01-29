<?php

declare(strict_types=1);

namespace App\Presentation\Home;

use App\Presentation\AppPresenter;
use FQL\Enum;
use FQL\Exception;
use Nette;

final class HomePresenter extends AppPresenter
{
    /**
     * @throws Exception\InvalidFormatException
     * @throws Exception\FileNotFoundException
     */
    public function actionDefault(): void
    {
        $results = $this->getProductsQuery()
            ->distinct()
            ->coalesceNotEmpty('CATEGORIES.DEFAULT_CATEGORY', 'CATEGORIES.CATEGORY')->as('category')
            ->md5('CATEGORIES.DEFAULT_CATEGORY')->as('slug')
            ->select('VISIBILITY')->as('visibility')
            ->from('SHOP.SHOPITEM')
            ->where('VISIBILITY', Enum\Operator::EQUAL_STRICT, 'visible')
            ->having('category', Enum\Operator::NOT_EQUAL, '')
            ->orderBy('category')->asc()
            ->execute();

        if (!$results->exists()) {
            throw new Nette\Application\BadRequestException('Product not found', Nette\Http\IResponse::S404_NotFound);
        }

        $this->getTemplate()->add('categories', $results->fetchAll());
    }
}

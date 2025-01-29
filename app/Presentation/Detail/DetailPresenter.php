<?php

declare(strict_types=1);

namespace App\Presentation\Detail;

use App\Presentation\AppPresenter;
use FQL\Enum;
use FQL\Exception;
use FQL\Results;
use Nette;


final class DetailPresenter extends AppPresenter
{
    /**
     * @throws Exception\FileNotFoundException
     * @throws Exception\InvalidFormatException
     */
    public function actionDefault(string $slug): void
    {
        $results = $this->getProductsQuery()
            ->select('NAME')->as('name')
            ->md5('NAME')->as('slug')
            ->select('DESCRIPTION')->as('description')
            ->select('MANUFACTURER')->as('manufacturer')
            ->select('SUPPLIER')->as('supplier')
            ->coalesceNotEmpty('CATEGORIES.DEFAULT_CATEGORY', 'CATEGORIES.CATEGORY')->as('category')
            ->md5('category')->as('categorySlug')
            ->select('IMAGES.IMAGE')->as('image')
            ->select('INFORMATION_PARAMETERS.INFORMATION_PARAMETER')->as('parameter')
            ->select('CODE')->as('code')
            ->select('EAN')->as('ean')
            ->concat('VAT', "%")->as('vat')
            ->select('AVAILABILITY_OUT_OF_STOCK')->as('availability')
            ->from('SHOP.SHOPITEM')
            ->where('VISIBILITY', Enum\Operator::EQUAL_STRICT, 'visible')
            ->having('slug', Enum\Operator::EQUAL_STRICT, $slug)
            ->limit(1)
            ->execute(Results\InMemory::class);

        if (!$results->exists()) {
            throw new Nette\Application\BadRequestException('Product not found', Nette\Http\IResponse::S404_NotFound);
        }

        $this->getTemplate()->add('product', $results->fetch());
    }
}

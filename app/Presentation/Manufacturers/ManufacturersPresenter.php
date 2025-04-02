<?php

declare(strict_types=1);

namespace App\Presentation\Manufacturers;

use App\Presentation\Auth\AuthPresenter;
use FQL\Enum;
use FQL\Results;

final class ManufacturersPresenter extends AuthPresenter
{
    public function actionDefault(): void
    {
        $results = $this->getProductsQuery()
            ->distinct()
            ->coalesce('MANUFACTURER')->as('manufacturer')
            ->md5('manufacturer')->as('slug')
            ->select('VISIBILITY')->as('visibility')
            ->from('SHOP.SHOPITEM')
            ->where('VISIBILITY', Enum\Operator::EQUAL_STRICT, 'visible')
            ->having('manufacturer', Enum\Operator::NOT_EQUAL, '')
            ->orderBy('manufacturer')->asc()
            ->execute(Results\InMemory::class);

        $sortedManufacturers = [];
        foreach ($results->fetchAll() as $manufacturer) {
            $mainManufacturer = mb_strtoupper(mb_substr($manufacturer['manufacturer'], 0, 1));
            if (!isset($sortedManufacturers[$mainManufacturer])) {
                $sortedManufacturers[$mainManufacturer] = [];
            }

            $sortedManufacturers[$mainManufacturer][] = $manufacturer;
        }

        $this->getTemplate()->add('manufacturers', $sortedManufacturers);
    }
}

<?php

declare(strict_types=1);

namespace App\Presentation\Detail;

use app\Presentation\Auth\AuthPresenter;
use FQL\Exception;
use Nette;

final class DetailPresenter extends AuthPresenter
{
    /**
     * @throws Exception\FileNotFoundException
     * @throws Exception\InvalidFormatException
     */
    public function actionDefault(string $slug): void
    {
        $product = $this->products->getProductBySlug($slug);
        if (!$product) {
            throw new Nette\Application\BadRequestException('Product not found', Nette\Http\IResponse::S404_NotFound);
        }

        $this->getTemplate()->add('product', $product);
    }
}

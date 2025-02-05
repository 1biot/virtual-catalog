<?php

namespace App\Presentation;

use App\Core\Repository\Products;
use FQL\Interface;
use Nette;

class AppPresenter extends Nette\Application\UI\Presenter
{
    protected const int PER_PAGE_DEFAULT = 12;

    public function __construct(protected readonly Products $products)
    {
        parent::__construct();
    }

    /** @return void */
    protected function beforeRender()
    {
        parent::beforeRender();
        $this->getTemplate()->add('lastUpdate', (new \DateTime())->format('Y-m-d H:i:s'));
    }

    protected function createComponentSearchForm(): Nette\Application\UI\Form
    {
        $form = new Nette\Application\UI\Form;
        $form->addText('query')
            ->setRequired('Type a search query.')
            ->addRule(Nette\Forms\Form::MinLength, 'Search term must have at least 3 characters', 3)
            ->addRule(Nette\Forms\Form::Pattern, 'The search term contains illegal characters.', '[a-zA-Z0-9á-žÁ-Ž,\.\-_\s]+');

        $form->addSubmit('submit', 'Search');
        $form->onSuccess[] = [$this, 'searchFormSucceeded'];

        return $form;
    }

    public function searchFormSucceeded(Nette\Application\UI\Form $form, array $values): void
    {
        // Redirect to search results page
        $this->redirect('Search:default', ['query' => rawurlencode(trim($values['query'])), 'page'=> 1]);
    }

    protected function getProductsQuery(): Interface\Query
    {
        return $this->products->startQuery();
    }

    protected function createPaginator(int $page, ?int $count = null): Nette\Utils\Paginator
    {
        $paginator = new Nette\Utils\Paginator;
        $paginator->setBase(1);
        $paginator->setPage($page);
        $paginator->setItemsPerPage(self::PER_PAGE_DEFAULT);
        $paginator->setItemCount($count);
        return $paginator;
    }
}

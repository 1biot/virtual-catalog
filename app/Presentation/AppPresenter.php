<?php

namespace App\Presentation;

use FQL\Enum;
use FQL\Exception;
use FQL\Interface;
use FQL\Stream;
use Nette;
use Nette\Application\UI\Form;

class AppPresenter extends Nette\Application\UI\Presenter
{
    public function __construct(private readonly string $productXmlFile = '')
    {
        parent::__construct();
    }

    protected function startup()
    {
        parent::startup();
        if (!file_exists($this->productXmlFile)) {
            throw new Nette\Application\BadRequestException('Products file not found', Nette\Http\IResponse::S404_NotFound);
        }
    }

    protected function beforeRender()
    {
        parent::beforeRender();

        $timestamp = filemtime($this->productXmlFile); // Get last modification time of the file
        $datetime = new Nette\Utils\DateTime();
        $datetime->setTimestamp($timestamp);

        $this->getTemplate()->add('lastUpdate', $datetime->format('Y-m-d H:i:s'));
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

    public function searchFormSucceeded(Form $form, array $values): void
    {
        // Redirect to search results page
        $this->redirect('Search:default', ['query' => trim($values['query']), 'page'=> 1]);
    }

    /**
     * @implements Interface\Stream<Stream\Xml>
     * @return Interface\Stream
     * @throws Exception\FileNotFoundException
     * @throws Exception\InvalidFormatException
     */
    protected function getProductXmlFile(): Interface\Stream
    {
        return Stream\Provider::fromFile($this->productXmlFile, Enum\Format::XML);
    }

    /**
     * @throws Exception\InvalidFormatException
     * @throws Exception\FileNotFoundException
     */
    protected function getProductsQuery(): Interface\Query
    {
        return $this->getProductXmlFile()->query();
    }
}

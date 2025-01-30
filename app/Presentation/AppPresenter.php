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

    protected function createComponentSearchForm(): Form
    {
        $form = new Form;
        $form->addText('query')
            ->setRequired('Zadejte hledaný výraz.')
            ->addRule(Form::MIN_LENGTH, 'Hledaný výraz musí mít alespoň %d znaky.', 3)
            ->addRule(Form::PATTERN, 'Hledaný výraz obsahuje nepovolené znaky.', '[a-zA-Z0-9á-žÁ-Ž,\.\-_\s]+');

        $form->addSubmit('submit', 'Hledat');
        $form->onSuccess[] = [$this, 'searchFormSucceeded'];

        return $form;
    }

    public function searchFormSucceeded(Form $form, array $values): void
    {
        // Přesměrování na stránku výsledků
        $this->redirect('Search:default', ['query' => trim($values['query']), 'page'=> 1]);
    }

    protected function getProductXmlUrl(): string
    {
        return $this->productXmlUrl;
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

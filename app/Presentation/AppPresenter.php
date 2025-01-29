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
    public function __construct(private readonly string $productXmlUrl = '', private readonly string $productXmlFile = '')
    {
        parent::__construct();
    }

    protected function startup()
    {
        parent::startup();
        // file exists and later, not to old too
        if (file_exists($this->productXmlFile)) {
            return;
        } elseif ($this->productXmlUrl === '') {
            throw new Nette\Application\BadRequestException('Products file not found', Nette\Http\IResponse::S404_NotFound);
        }

        $this->downloadProducts();
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
        $this->redirect('Search:default', ['query' => trim($values['query'])]);
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

    private function downloadProducts()
    {
        $savePath = $this->productXmlFile;
        $fp = fopen($savePath, 'w+'); // Otevření souboru pro zápis
        if (!$fp) {
            die("Nelze otevřít soubor pro zápis.");
        }

        $ch = curl_init($this->getProductXmlUrl());
        curl_setopt($ch, CURLOPT_FILE, $fp); // Ukládá přímo do souboru
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Povolit přesměrování
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Timeout pro celé stahování (sekundy)
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Timeout pro spojení
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0'); // Fake User-Agent, pokud server blokuje boti

        curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            fclose($fp);
            throw new Nette\Application\BadRequestException('Products file not found', Nette\Http\IResponse::S404_NotFound);
        }


        $this->flashMessage('Products file downloaded');
        curl_close($ch);
        fclose($fp);
    }
}

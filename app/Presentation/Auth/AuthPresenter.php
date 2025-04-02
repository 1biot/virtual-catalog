<?php

namespace App\Presentation\Auth;

use App\Core\Repository\Products;
use App\Presentation\AppPresenter;

class AuthPresenter extends AppPresenter
{
    public function __construct(Products $products)
    {
        parent::__construct($products);
    }

    protected function startup()
    {
        parent::startup();
        if (!$this->getUser()->isLoggedIn()) {
            match ($this->getUser()->getLogoutReason()) {
                1 => $this->flashMessage('You have been logged out due to inactivity.'),
                2 => $this->flashMessage('You have been logged out due to security reasons.'),
                3 => $this->flashMessage('You have been logged out due to an error.'),
                default => $this->flashMessage('You are not logged in.'),
            };
            $this->redirect('Home:');
        }
    }

    public function actionSignOut(): void
    {
        $this->getUser()->logout();
        $this->flashMessage('You have been logged out.');
        $this->redirect('Home:');
    }
}

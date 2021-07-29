<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class CreateController extends AbstractController
{
    /**
     * @Route("/create/{type}/{inventorynumber}", name="create", defaults={"inventorynumber"=""})
     */
    public function create($type, $inventorynumber)
    {
        return $this->render('create.html.twig');
    }
}

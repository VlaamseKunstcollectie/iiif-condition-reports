<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ViewController extends AbstractController
{
    /**
     * @Route("/view/{id}", name="view")
     */
    public function create($id)
    {
        return $this->render('view.html.twig');
    }
}

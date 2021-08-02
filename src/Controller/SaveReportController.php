<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SaveReportController extends AbstractController
{
    /**
     * @Route("/save/{type}", name="save")
     */
    public function save(Request $request, $type)
    {
        return $this->render('main.html.twig');
    }
}

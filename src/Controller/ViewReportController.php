<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ViewReportController extends AbstractController
{
    /**
     * @Route("/view/{id}", name="view")
     */
    public function view($id)
    {
        return $this->render('view.html.twig', [
            'id' => $id
        ]);
    }
}

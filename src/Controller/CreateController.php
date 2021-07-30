<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class CreateController extends AbstractController
{
    /**
     * @Route("/create/{id}", name="create", defaults={ "id"="" })
     */
    public function create($id)
    {
        return $this->render('create.html.twig', [
            'id' => $id
        ]);
    }
}

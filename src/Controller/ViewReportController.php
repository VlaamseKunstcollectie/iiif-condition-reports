<?php

namespace App\Controller;

use App\Utils\ReportTemplateData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ViewReportController extends AbstractController
{
    /**
     * @Route("/view/{id}", name="view")
     */
    public function view($id)
    {
        $em = $this->get('doctrine')->getManager();
        return $this->render('report.html.twig', ReportTemplateData::getViewData($em, $id));
    }
}

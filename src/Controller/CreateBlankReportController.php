<?php

namespace App\Controller;

use App\Entity\DatahubData;
use App\Entity\InventoryNumber;
use App\Entity\Organization;
use App\Entity\Representative;
use App\Utils\CurlUtil;
use App\Utils\ReportTemplateData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CreateBlankReportController extends AbstractController
{
    /**
     * @Route("/create/blank/{id}", name="create_blank")
     */
    public function createBlank(Request $request, $id)
    {
        $em = $this->container->get('doctrine')->getManager();
        $reportReasons = $this->getParameter('report_reasons');
        $request->setLocale('nl');

        return $this->render('report.html.twig', ReportTemplateData::getDataToCreateBlank($em, $reportReasons, $id));
    }
}

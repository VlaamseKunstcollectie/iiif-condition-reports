<?php

namespace App\Controller;

use App\Entity\Annotation;
use App\Entity\DeletedAnnotation;
use App\Entity\Organization;
use App\Entity\Report;
use App\Entity\ReportData;
use App\Entity\ReportHistory;
use App\Entity\Representative;
use App\Utils\CurlUtil;
use App\Utils\ReportTemplateData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CreateExistingReportController extends AbstractController
{
    /**
     * @Route("/create/existing/{id}", name="create_existing")
     */
    public function createExisting(Request $request, $id)
    {
        $em = $this->container->get('doctrine')->getManager();
        $reportReasons = $this->getParameter('report_reasons');
        $request->setLocale('nl');

        return $this->render('report.html.twig', ReportTemplateData::getDataToCreateExisting($em, $reportReasons, $id));
    }
}

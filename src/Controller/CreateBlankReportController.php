<?php

namespace App\Controller;

use App\Entity\DatahubData;
use App\Entity\InventoryNumber;
use App\Entity\Organisation;
use App\Entity\Representative;
use App\Utils\CurlUtil;
use App\Utils\ReportTemplateData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CreateBlankReportController extends AbstractController
{
    /**
     * @Route("/{_locale}/create/blank/{id}", name="create_blank")
     */
    public function createBlank(Request $request, $id)
    {
        $em = $this->container->get('doctrine')->getManager();
        $reportReasons = $this->getParameter('report_reasons');
        $reportFields = $this->getParameter('report_fields');

        $locale = $request->get('_locale');
        $locales = $this->getParameter('locales');
        $translatedRoutes = array();
        foreach($locales as $l) {
            $translatedRoutes[] = array(
                'lang' => $l,
                'url' => $this->generateUrl('create_blank', array('_locale' => $l, 'id' => $id)),
                'active' => $l === $locale
            );
        }

        $data = ReportTemplateData::getDataToCreateBlank($em, $reportReasons, $reportFields, $id, $translatedRoutes);
        if($data === null) {
            return $this->redirectToRoute('main', array('_locale' => $locale));
        } else {
            return $this->render('report.html.twig', $data);
        }
    }
}

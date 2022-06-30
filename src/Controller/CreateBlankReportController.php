<?php

namespace App\Controller;

use App\Entity\DatahubData;
use App\Entity\InventoryNumber;
use App\Entity\Organisation;
use App\Entity\Representative;
use App\Utils\CurlUtil;
use App\Utils\LocaleUtil;
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
        $locale = $request->get('_locale');
        $locales = $this->getParameter('locales');
        //Set default locale if locale is missing
        if($locale === null || !in_array($locale, $locales)) {
            return $this->redirectToRoute('create_blank', array('_locale' => $locales[0], 'id' => $id));
        }
        if(!$this->getUser()) {
            return $this->redirectToRoute('main');
        } else if(!$this->getUser()->getRoles()) {
            return $this->redirectToRoute('main');
        } else if (!in_array('ROLE_USER', $this->getUser()->getRoles(), true)) {
            return $this->redirectToRoute('main');
        }

        $em = $this->container->get('doctrine')->getManager();
        $reportReasons = $this->getParameter('report_reasons');
        $objectTypes = $this->getParameter('object_types');
        $reportFields = $this->getParameter('report_fields');
        $pictures = $this->getParameter('pictures');

        $translatedRoutes = array();
        foreach($locales as $l) {
            $translatedRoutes[] = array(
                'lang' => $l,
                'url' => $this->generateUrl('create_blank', array('_locale' => $l, 'id' => $id)),
                'active' => $l === $locale
            );
        }

        $data = ReportTemplateData::getDataToCreateBlank($em, $this->getUser(), $reportReasons, $objectTypes, $reportFields, $pictures, $id, $translatedRoutes);
        if($data === null) {
            return $this->redirectToRoute('main', array('_locale' => $locale));
        } else {
            return $this->render('report.html.twig', $data);
        }
    }
}

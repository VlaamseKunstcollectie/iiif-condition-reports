<?php

namespace App\Controller;

use App\Utils\ReportTemplateData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ViewReportController extends AbstractController
{
    /**
     * @Route("/{_locale}/view/{id}", name="view")
     */
    public function view(Request $request, $id)
    {
        $em = $this->get('doctrine')->getManager();
        $reportReasons = $this->getParameter('report_reasons');

        $locale = $request->get('_locale');
        $locales = $this->getParameter('locales');
        $translatedRoutes = array();
        foreach($locales as $l) {
            $translatedRoutes[] = array(
                'lang' => $l,
                'url' => $this->generateUrl('view', array('_locale' => $l, 'id' => $id)),
                'active' => $l === $locale
            );
        }

        return $this->render('report.html.twig', ReportTemplateData::getViewData($em, $reportReasons, $id, $translatedRoutes));
    }
}

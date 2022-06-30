<?php

namespace App\Controller;

use App\Entity\Annotation;
use App\Entity\DeletedAnnotation;
use App\Entity\Organisation;
use App\Entity\Report;
use App\Entity\ReportData;
use App\Entity\ReportHistory;
use App\Entity\Representative;
use App\Utils\CurlUtil;
use App\Utils\ReportTemplateData;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CreateExistingReportController extends AbstractController
{
    /**
     * @Route("/{_locale}/create/existing/{baseId}", name="create_existing")
     */
    public function createExisting(Request $request, $baseId)
    {
        $locale = $request->get('_locale');
        $locales = $this->getParameter('locales');
        //Set default locale if locale is missing
        if($locale === null || !in_array($locale, $locales)) {
            return $this->redirectToRoute('create_existing', array('_locale' => $locales[0], 'baseId' => $baseId));
        }
        if(!$this->getUser()) {
            return $this->redirectToRoute('main');
        } else if(!$this->getUser()->getRoles()) {
            return $this->redirectToRoute('main');
        } else if (!in_array('ROLE_USER', $this->getUser()->getRoles(), true)) {
            return $this->redirectToRoute('main');
        }

        $em = $this->container->get('doctrine')->getManager();

        // Do not allow creation of a report with a baseId which in turn is also the id of a report (unless the baseId and the report id are the same)
        $reportData = $em->createQueryBuilder()
            ->select('r')
            ->from(Report::class, 'r')
            ->where('r.id = :id')
            ->setParameter('id', $baseId)
            ->getQuery()
            ->getResult();
        foreach ($reportData as $data) {
            if($data->getId() != $data->getBaseId()) {
                return $this->redirectToRoute('create_existing', [ 'baseId' => $data->getBaseId() ]);
            }
        }

        // Find the highest report ID for this baseId
        $reportData = $em->createQueryBuilder()
            ->select('r')
            ->from(Report::class, 'r')
            ->where('r.baseId = :baseId')
            ->setParameter('baseId', $baseId)
            ->orderBy('r.id')
            ->getQuery()
            ->getResult();
        $highestId = $baseId;
        foreach ($reportData as $data) {
            if($data->getId() > $highestId) {
                $highestId = $data->getId();
            }
        }
        $reportReasons = $this->getParameter('report_reasons');
        $objectTypes = $this->getParameter('object_types');
        $reportFields = $this->getParameter('report_fields');
        $pictures = $this->getParameter('pictures');

        $translatedRoutes = array();
        foreach($locales as $l) {
            $translatedRoutes[] = array(
                'lang' => $l,
                'url' => $this->generateUrl('create_existing', array('_locale' => $l, 'baseId' => $baseId)),
                'active' => $l === $locale
            );
        }

        return $this->render('report.html.twig', ReportTemplateData::getDataToCreateExisting($em, $this->getUser(), $reportReasons, $objectTypes, $reportFields, $pictures, $highestId, $translatedRoutes));
    }
}

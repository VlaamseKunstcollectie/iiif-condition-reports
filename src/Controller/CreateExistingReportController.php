<?php

namespace App\Controller;

use App\Entity\Organization;
use App\Entity\Report;
use App\Entity\ReportData;
use App\Entity\ReportHistory;
use App\Entity\Representative;
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
        $prefilledData = array();
        $reportReasons = $this->getParameter('report_reasons');
        $em = $this->container->get('doctrine')->getManager();
        $reportData = $em->createQueryBuilder()
            ->select('r.id, r.inventoryId, r.baseId, r.timestamp, d.name, d.value')
            ->from(Report::class, 'r')
            ->leftJoin(ReportData::class, 'd', 'WITH', 'd.id = r.id')
            ->where('r.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
        foreach ($reportData as $data) {
            if(empty($prefilledData['base_id'])) {
                $prefilledData['base_id'] = $data['baseId'];
            }
            if(empty($prefilledData['inventory_id'])) {
                $prefilledData['inventory_id'] = $data['inventoryId'];
            }
            $prefilledData[$data['name']] = $data['value'];
        }
        $reportHistory = $em->createQueryBuilder()
            ->select('h')
            ->from(ReportHistory::class, 'h')
            ->where('h.id = :id')
            ->setParameter('id', $id)
            ->orderBy('h.order', 'DESC')
            ->getQuery()
            ->getResult();
        foreach($reportHistory as $history) {
            if(!array_key_exists('report_history', $prefilledData)) {
                $prefilledData['report_history'] = array($id => $history->getOrder() + 1);
            }
            $prefilledData['report_history'][$history->getPreviousId()] = $history->getOrder();
        }

        $organizations = array();
        $organizationData = $em->createQueryBuilder()
            ->select('o')
            ->from(Organization::class, 'o')
            ->orderBy('o.alias')
            ->getQuery()
            ->getResult();
        foreach ($organizationData as $organization) {
            $organizations[$organization->getId()] = $organization;
        }

        $representatives = array();
        $representativeData = $em->createQueryBuilder()
            ->select('r')
            ->from(Representative::class, 'r')
            ->orderBy('r.alias')
            ->getQuery()
            ->getResult();
        foreach ($representativeData as $representative) {
            $representatives[$representative->getId()] = $representative;
        }

        return $this->render('create.html.twig', [
            'prefilled_data' => $prefilledData,
            'report_reasons' => $reportReasons,
            'organizations' => $organizations,
            'representatives' => $representatives
        ]);
    }
}

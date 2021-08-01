<?php

namespace App\Controller;

use App\Entity\DatahubData;
use App\Entity\InventoryNumber;
use App\Entity\Report;
use App\Entity\ReportData;
use App\Entity\ReportHistory;
use App\Entity\Search;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CreateExistingReportController extends AbstractController
{
    /**
     * @Route("/create/existing/{id}", name="create_existing")
     */
    public function createExisting(Request $request, $id)
    {
        $prefilledData = [
            'report_history' => array(),
            'base_id' => '',
            'inventory_id' => '',
            'inventory_number' => '',
            'thumbnail' => '',
            'title' => '',
            'creator' => '',
            'creation_date' => '',
            'copyright' => '',
            'iiif_manifest_url' => ''
        ];
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
            if(empty($prefilledData['report_history'])) {
                $prefilledData['report_history'][$id] = $history->getOrder() + 1;
            }
            $prefilledData['report_history'][$history->getPreviousId()] = $history->getOrder();
        }
        return $this->render('create_existing.html.twig', [
            'prefilled_data' => $prefilledData,
            'report_reasons' => $reportReasons
        ]);
    }
}

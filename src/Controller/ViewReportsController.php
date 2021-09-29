<?php

namespace App\Controller;

use App\Entity\DatahubData;
use App\Entity\InventoryNumber;
use App\Entity\Report;
use App\Utils\IIIFUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ViewReportsController extends AbstractController
{
    /**
     * @Route("/view_reports/{baseId}", name="view_reports")
     */
    public function viewReports(Request $request, $baseId)
    {
        $em = $this->container->get('doctrine')->getManager();

        $searchResults = array();
        $reportData = $em->createQueryBuilder()
            ->select('r.id, r.baseId, r.inventoryId, r.timestamp, i.inventoryNumber, d.name, d.value')
            ->from(Report::class, 'r')
            ->leftJoin(InventoryNumber::class, 'i', 'WITH', 'i.id = r.inventoryId')
            ->leftJoin(DatahubData::class, 'd', 'WITH', 'd.id = r.inventoryId')
            ->where('r.baseId = :id')
            ->setParameter('id', $baseId)
            ->orderBy('r.timestamp', 'DESC')
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
        foreach ($reportData as $data) {
            if(!array_key_exists($data['id'], $searchResults)) {
                $searchResults[$data['id']] = [
                    'id' => $data['id'],
                    'inventory_id' => $data['inventoryId'],
                    'timestamp' => $data['timestamp']->format('Y-m-d H:i:s'),
                    'inventory_number' => $data['inventoryNumber'],
                    'thumbnail' => '',
                    'title_nl' => '',
                    'creator' => ''
                ];
            }
            $searchResults[$data['id']][$data['name']] = $data['value'];
        }
        if(count($searchResults) == 1) {
            foreach($searchResults as $result) {
                return $this->redirectToRoute('view', ['id' => $result['id']]);
            }
        }

        return $this->render('view_reports.html.twig', [
            'current_page' => 'reports',
            'search_results' => $searchResults
        ]);
    }
}

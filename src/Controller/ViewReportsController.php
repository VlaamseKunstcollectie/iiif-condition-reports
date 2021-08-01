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
     * @Route("/reports/{action}", name="reports")
     */
    public function reports(Request $request, $action) : Response
    {
        $em = $this->container->get('doctrine')->getManager();

        $searchResults = array();
        $reportData = $em->createQueryBuilder()
            ->select('r.id, r.baseId, r.inventoryId, r.timestamp, i.inventoryNumber, d.name, d.value')
            ->from(Report::class, 'r')
            ->leftJoin(InventoryNumber::class, 'i', 'WITH', 'i.id = r.inventoryId')
            ->leftJoin(DatahubData::class, 'd', 'WITH', 'd.id = r.inventoryId')
            ->orderBy('r.timestamp', 'DESC')
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
        foreach ($reportData as $data) {
            if(!array_key_exists($data['baseId'], $searchResults)) {
                $searchResults[$data['baseId']] = [
                    'id' => $data['id'],
                    'inventory_id' => $data['inventoryId'],
                    'timestamp' => $data['timestamp']->format('Y-m-d H:i:s'),
                    'inventory_number' => $data['inventoryNumber'],
                    'thumbnail' => '',
                    'title' => '',
                    'creator' => ''
                ];
            }
            $searchResults[$data['baseId']][$data['name']] = $data['value'];
        }

        return $this->render('reports.html.twig', [
            'action' => $action,
            'type' => 'existing',
            'search_results' => $searchResults
        ]);
    }
}

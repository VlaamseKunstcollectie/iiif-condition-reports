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

class ReportsController extends AbstractController
{
    /**
     * @Route("/reports/{action}", name="reports")
     */
    public function reports(Request $request, $action) : Response
    {
        $em = $this->container->get('doctrine')->getManager();

        $searchResults = array();
        $reportData = $em->createQueryBuilder()
            ->select('r.id, r.inventoryId, r.lastModified, i.inventoryNumber, d.name, d.value')
            ->from(Report::class, 'r')
            ->leftJoin(InventoryNumber::class, 'i', 'WITH', 'i.id = r.inventoryId')
            ->leftJoin(DatahubData::class, 'd', 'WITH', 'd.id = r.inventoryId')
            ->orderBy('r.lastModified', 'DESC')
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
        foreach ($reportData as $data) {
            if(!array_key_exists($data['id'], $searchResults)) {
                $searchResults[$data['id']] = [
                    'id' => $data['id'],
                    'inventory_id' => $data['inventoryId'],
                    'last_modified' => $data['lastModified']->format('Y-m-d H:i:s'),
                    'inventory_number' => $data['inventoryNumber'],
                    'thumbnail' => '',
                    'title' => '',
                    'creator' => ''
                ];
            }
            switch($data['name']) {
                case 'iiif_image_url':
                    $searchResults[$data['id']]['thumbnail'] = IIIFUtil::generateThumbnail($data['value']);
                    break;
                case 'nl-titleartwork':
                    $searchResults[$data['id']]['title'] = $data['value'];
                    break;
                case 'creatorofartworkobje':
                    $searchResults[$data['id']]['creator'] = $data['value'];
                    break;
            }
        }

        return $this->render('reports.html.twig', [
            'action' => $action,
            'type' => 'existing',
            'search_results' => $searchResults
        ]);
    }
}

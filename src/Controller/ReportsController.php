<?php

namespace App\Controller;

use App\Entity\DatahubData;
use App\Entity\Report;
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
            ->select('r', 'd')
            ->from(Report::class, 'r')
            ->leftJoin(DatahubData::class, 'd', 'WITH', 'd.id = r.inventoryNumber')
            ->orderBy('r.lastModified', 'DESC')
            ->getQuery()
            ->getResult();
        $currentReport = array();
        foreach ($reportData as $data) {
            if($data instanceof Report) {
                if(!empty($currentReport)) {
                    $searchResults[] = $currentReport;
                }
                $currentReport = array(
                    'id' => $data->getId(),
                    'inventory_number' => $data->getInventoryNumber(),
                    'last_modified' => $data->getLastModified()->format('Y-m-d H:i:s'),
                    'title' => '',
                    'creator' => '',
                    'thumbnail' => ''
                );
            } elseif($data instanceof DatahubData) {
                if(!empty($currentReport)) {
                    switch($data->getName()) {
                        case 'nl-titleartwork':
                            $currentReport['title'] = $data->getValue();
                            break;
                        case 'creatorofartworkobje':
                            $currentReport['creator'] = $data->getValue();
                            break;
                        case 'iiif_image_url':
                            if(strpos($data->getValue(), '/public@') !== false) {
                                $currentReport['thumbnail'] = $data->getValue() . '/full/100,/0/default.jpg';
                            }
                            break;
                    }
                }
            }
        }
        if(!empty($currentReport)) {
            $searchResults[] = $currentReport;
        }

        return $this->render('reports.html.twig', [
            'action' => $action,
            'type' => 'existing',
            'search_results' => $searchResults
        ]);
    }
}

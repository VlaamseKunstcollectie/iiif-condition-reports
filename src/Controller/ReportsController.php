<?php

namespace App\Controller;

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
        $reports = $em->createQueryBuilder()
            ->select('i')
            ->from(Report::class, 'i')
//            ->orderBy('i.last_modified', 'DESC')
            ->getQuery()
            ->getResult();
        foreach ($reports as $report) {
            $searchResults[] = [
                'inventory_number' => $report->getInventoryNumber(),
                'last_modified' => $report->getLastModified()->format('Y-m-d H:i:s')
            ];
        }

        return $this->render('reports.html.twig', [
            'action' => $action,
            'type' => 'existing',
            'search_results' => $searchResults
        ]);
    }
}

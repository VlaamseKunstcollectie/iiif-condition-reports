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

class CreateBlankReportController extends AbstractController
{
    /**
     * @Route("/create/blank/{id}", name="create_blank")
     */
    public function createBlank(Request $request, $id)
    {
        $prefilledData = [
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
        $datahubData = $em->createQueryBuilder()
            ->select('i.id, i.inventoryNumber, d.name, d.value')
            ->from(InventoryNumber::class, 'i')
            ->leftJoin(DatahubData::class, 'd', 'WITH', 'd.id = i.id')
            ->where('i.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
        foreach ($datahubData as $data) {
            if (empty($prefilledData['inventory_id'])) {
                $prefilledData['inventory_id'] = $data['id'];
            }
            if (empty($prefilledData['inventory_number'])) {
                $prefilledData['inventory_number'] = $data['inventoryNumber'];
            }
            $prefilledData[$data['name']] = $data['value'];
        }
        return $this->render('create_blank.html.twig', [
            'prefilled_data' => $prefilledData,
            'report_reasons' => $reportReasons
        ]);
    }
}

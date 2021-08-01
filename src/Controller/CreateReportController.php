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

class CreateReportController extends AbstractController
{
    /**
     * @Route("/create/{type}/{id}", name="create", defaults={ "id"="" })
     */
    public function create(Request $request, $type, $id)
    {
        $formView = null;
        $searchResults = null;
        $prefilledData = [
            'report_history' => array(),
            'base_id' => '',
            'inventory_number' => '',
            'thumbnail' => '',
            'title' => '',
            'creator' => '',
            'creation_date' => '',
            'copyright' => '',
            'iiif_manifest_url' => ''
        ];
        $reportReasons = $this->getParameter('report_reasons');
        if($id === '') {
            $search = new Search();
            $form = $this->createFormBuilder($search)
                ->add('match_type', ChoiceType::class, [ 'label' => 'Type zoekopdracht', 'choices' => [ 'Volledig' => 0, 'Gedeeltelijk' => 1, 'Begint met' => 2 ]])
                ->add('inventory_number', TextType::class, [ 'label' => 'Inventarisnummer' ])
                ->add('submit', SubmitType::class, [ 'label' => 'Zoeken' ])
                ->getForm();
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid()) {
                $formData = $form->getData();
                $inventoryNumber = $formData->getInventoryNumber();
                $matchType = $formData->getMatchType();

                $searchParameter = $inventoryNumber;
                if($matchType == '1') {
                    $searchParameter = '%' . $searchParameter . '%';
                } else if($matchType == '2') {
                    $searchParameter .= '%';
                }

                $em = $this->container->get('doctrine')->getManager();

                $searchResults = array();
                $datahubData = $em->createQueryBuilder()
                    ->select('i.id, i.inventoryNumber, d.name, d.value')
                    ->from(InventoryNumber::class, 'i')
                    ->leftJoin(DatahubData::class, 'd', 'WITH', 'd.id = i.id')
                    ->where('i.inventoryNumber ' . ($matchType == '0' ? '=' : 'LIKE') . ' :inventory_number')
                    ->setParameter('inventory_number', $searchParameter)
                    ->orderBy('d.id')
                    ->getQuery()
                    ->getResult();
                foreach($datahubData as $data) {
                    if(!array_key_exists($data['id'], $searchResults)) {
                        $searchResults[$data['id']] = [
                            'id' => $data['id'],
                            'inventory_number' => $data['inventoryNumber'],
                            'thumbnail' => '',
                            'title' => '',
                            'creator' => ''
                        ];
                    }
                    $searchResults[$data['id']][$data['name']] = $data['value'];
                }
            }
            $formView = $form->createView();
        } else {
            $em = $this->container->get('doctrine')->getManager();
            if($type ===  'existing') {
                $reportData = $em->createQueryBuilder()
                    ->select('r.id, r.baseId, r.timestamp, d.name, d.value')
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
            } else if($type === 'new') {
                $datahubData = $em->createQueryBuilder()
                    ->select('i.inventoryNumber, d.name, d.value')
                    ->from(InventoryNumber::class, 'i')
                    ->leftJoin(DatahubData::class, 'd', 'WITH', 'd.id = i.id')
                    ->where('i.id = :id')
                    ->setParameter('id', $id)
                    ->getQuery()
                    ->getResult();
                foreach ($datahubData as $data) {
                    if (empty($prefilledData['inventory_number'])) {
                        $prefilledData['inventory_number'] = $data['inventoryNumber'];
                    }
                    $prefilledData[$data['name']] = $data['value'];
                }
                $ok = true;
                if(!array_key_exists('iiif_manifest_url', $prefilledData)) {
                    $ok = false;
                } else if(empty($prefilledData['iiif_manifest_url'])) {
                    $ok = false;
                }
                if(!$ok) {
                    //TODO give error

                }
            }
        }
        return $this->render('create.html.twig', [
            'form' => $formView,
            'type' => $type,
            'id' => $id,
            'prefilled_data' => $prefilledData,
            'report_reasons' => $reportReasons,
            'search_results' => $searchResults
        ]);
    }
}

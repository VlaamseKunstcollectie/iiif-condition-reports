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

class SearchController extends AbstractController
{
    /**
     * @Route("/search/", name="search")
     */
    public function search(Request $request)
    {
        $search = new Search();
        $form = $this->createFormBuilder($search)
            ->add('match_type', ChoiceType::class, [ 'label' => 'Type zoekopdracht', 'choices' => [ 'Volledig' => 0, 'Gedeeltelijk' => 1, 'Begint met' => 2 ]])
            ->add('inventory_number', TextType::class, [ 'label' => 'Inventarisnummer' ])
            ->add('submit', SubmitType::class, [ 'label' => 'Zoeken' ])
            ->getForm();
        $form->handleRequest($request);
        $searchResults = null;
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
        return $this->render('search.html.twig', [
            'form' => $form->createView(),
            'search_results' => $searchResults
        ]);
    }
}

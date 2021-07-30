<?php

namespace App\Controller;

use App\Entity\DatahubData;
use App\Entity\InventoryNumber;
use App\Entity\Search;
use App\Utils\IIIFUtil;
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
            }
            $formView = $form->createView();
        }
        return $this->render('create.html.twig', [
            'form' => $formView,
            'search_results' => $searchResults,
            'id' => $id
        ]);
    }
}

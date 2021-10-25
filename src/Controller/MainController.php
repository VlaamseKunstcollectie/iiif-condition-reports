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
use Symfony\Contracts\Translation\TranslatorInterface;

class MainController extends AbstractController
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("/")
     * @Route("/{_locale}", name="main")
     */
    public function reports(Request $request)
    {
        $t = $this->translator;
        $locale = $request->get('_locale');
        //Set default locale if locale is missing
        if($locale === null) {
            return $this->redirectToRoute('main', array('_locale' => $t->getLocale()));
        }

        $search = new Search();
        $form = $this->createFormBuilder($search)
            ->add('match_type', ChoiceType::class, [ 'label' => $t->trans('Search type'), 'choices' => [ $t->trans('Exact') => 0, $t->trans('Partly') => 1, $t->trans('Starts with') => 2 ]])
            ->add('inventory_number', TextType::class, [ 'label' => $t->trans('Inventory number'), 'required' => false, 'empty_data' => '', 'attr' => ['placeholder' => $t->trans('Search by inventory number ...')] ])
            ->add('submit', SubmitType::class, [ 'label' => $t->trans('Search') ])
            ->getForm();
        $form->handleRequest($request);
        $searchResults = array();

        $em = $this->container->get('doctrine')->getManager();

        $inventoryNumber = '';
        $matchType = '0';

        if($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $inventoryNumber = $formData->getInventoryNumber();
            $matchType = $formData->getMatchType();
        }

        $searchParameter = $inventoryNumber;
        if ($matchType == '1') {
            $searchParameter = '%' . $searchParameter . '%';
        } else if ($matchType == '2') {
            $searchParameter .= '%';
        }

        $datahubData = $em->createQueryBuilder()
            ->select('i.id, i.inventoryNumber, d.name, d.value')
            ->from(InventoryNumber::class, 'i')
            ->leftJoin(DatahubData::class, 'd', 'WITH', 'd.id = i.id')
            ->where('i.inventoryNumber ' . ($matchType == '0' ? '=' : 'LIKE') . ' :inventory_number')
            ->setParameter('inventory_number', $searchParameter)
            ->orderBy('d.id')
            ->setMaxResults(1000)
            ->getQuery()
            ->getResult();
        $datahubData = array_reverse($datahubData);
        foreach ($datahubData as $data) {
            $id = $data['id'] . '_0';
            if (!array_key_exists($id, $searchResults)) {
                $searchResults[$id] = [
                    'id' => '',
                    'base_id' => '',
                    'inventory_id' => $data['id'],
                    'inventory_number' => $data['inventoryNumber'],
                    'timestamp' => '',
                    'thumbnail' => '',
                    'title_nl' => '',
                    'creator_nl' => ''
                ];
            }
            $searchResults[$id][$data['name']] = $data['value'];
        }

        $queryBuilder = $em->createQueryBuilder()
            ->select('r.id, r.baseId, r.inventoryId, r.timestamp, i.inventoryNumber, d.name, d.value')
            ->from(Report::class, 'r')
            ->leftJoin(InventoryNumber::class, 'i', 'WITH', 'i.id = r.inventoryId')
            ->leftJoin(DatahubData::class, 'd', 'WITH', 'd.id = r.inventoryId');
        if($searchParameter != null) {
            $queryBuilder = $queryBuilder->where('i.inventoryNumber ' . ($matchType == '0' ? '=' : 'LIKE') . ' :inventory_number')
                ->setParameter('inventory_number', $searchParameter);
        }
        $reportData = $queryBuilder->orderBy('r.timestamp', 'DESC')
            ->orderBy('r.id', 'DESC')
            ->setMaxResults(1000)
            ->getQuery()
            ->getResult();
        $reportData = array_reverse($reportData);
        foreach ($reportData as $data) {
            $id = $data['inventoryId'] . '_' . $data['baseId'];
            if(!array_key_exists($id, $searchResults)) {
                $searchResults[$id] = array();
                // Remove any results of Datahub data when a report exists for this inventory number
                if(array_key_exists($data['inventoryId'] . '_0', $searchResults)) {
                    unset($searchResults[$data['inventoryId'] . '_0']);
                }
            }
            $searchResults[$id]['id'] = $data['id'];
            $searchResults[$id]['base_id'] = $data['baseId'];
            $searchResults[$id]['inventory_id'] = $data['inventoryId'];
            $searchResults[$id]['inventory_number'] = $data['inventoryNumber'];
            $searchResults[$id]['timestamp'] = $data['timestamp']->format('Y-m-d H:i:s');
            $searchResults[$id][$data['name']] = $data['value'];
        }
        foreach($searchResults as $id => $data) {
            if(!array_key_exists('thumbnail', $data)) {
                $searchResults[$id]['thumbnail'] = '';
            }
            if(!array_key_exists('title_nl', $data)) {
                $searchResults[$id]['title_nl'] = '';
            }
            if(!array_key_exists('creator_nl', $data)) {
                $searchResults[$id]['creator_nl'] = '';
            }
        }
        usort($searchResults, array('App\Controller\MainController', 'cmp'));

        $locales = $this->getParameter('locales');
        $translatedRoutes = array();
        foreach($locales as $l) {
            $translatedRoutes[] = array(
                'lang' => $l,
                'url' => $this->generateUrl('main', array('_locale' => $l)),
                'active' => $l === $locale
            );
        }

        return $this->render('reports.html.twig', [
            'current_page' => 'reports',
            'form' => $form->createView(),
            'search_results' => $searchResults,
            'translated_routes' => $translatedRoutes
        ]);
    }

    function cmp($a, $b)
    {
        if ($a['timestamp'] == $b['timestamp']) {
            return $a['id'] > $b['id'] ? -1 : 1;
        }
        return $a['timestamp'] > $b['timestamp'] ? -1 : 1;
    }
}

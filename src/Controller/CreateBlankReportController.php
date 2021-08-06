<?php

namespace App\Controller;

use App\Entity\DatahubData;
use App\Entity\InventoryNumber;
use App\Entity\Organization;
use App\Entity\Representative;
use App\Utils\CurlUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CreateBlankReportController extends AbstractController
{
    /**
     * @Route("/create/blank/{id}", name="create_blank")
     */
    public function createBlank(Request $request, $id)
    {
        $prefilledData = array();
        $iiifImageData = '{}';
        $patternSize = 100;
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
            if (!array_key_exists('inventory_id', $prefilledData)) {
                $prefilledData['inventory_id'] = $data['id'];
            }
            if (!array_key_exists('inventory_number', $prefilledData)) {
                $prefilledData['inventory_number'] = $data['inventoryNumber'];
            }
            if(!empty($data['value'])) {
                if($data['name'] === 'iiif_image_url') {
                    $iiifImageData = CurlUtil::get($data['value'] . '/info.json');
                    if(!empty($iiifImageData)) {
                        $dataDecoded = json_decode($iiifImageData);
                        $patternSize = round(($dataDecoded->height > $dataDecoded->width ? $dataDecoded->height : $dataDecoded->width) / 100);
                    }
                }
                $prefilledData[$data['name']] = $data['value'];
            }
        }

        $organizations = array();
        $organizationData = $em->createQueryBuilder()
            ->select('o')
            ->from(Organization::class, 'o')
            ->orderBy('o.alias')
            ->getQuery()
            ->getResult();
        foreach ($organizationData as $organization) {
            $organizations[$organization->getId()] = $organization;
        }

        $representatives = array();
        $representativeData = $em->createQueryBuilder()
            ->select('r')
            ->from(Representative::class, 'r')
            ->orderBy('r.alias')
            ->getQuery()
            ->getResult();
        foreach ($representativeData as $representative) {
            $representatives[$representative->getId()] = $representative;
        }

        return $this->render('create.html.twig', [
            'prefilled_data' => $prefilledData,
            'iiif_image_data' => $iiifImageData,
            'pattern_size' => $patternSize,
            'stroke_width' => round($patternSize / 15),
            'annotation_history' => array(),
            'annotations' => array(),
            'deleted_annotations' => array(),
            'report_reasons' => $reportReasons,
            'organizations' => $organizations,
            'representatives' => $representatives
        ]);
    }
}

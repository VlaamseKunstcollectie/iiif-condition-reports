<?php

namespace App\Utils;

use App\Entity\Annotation;
use App\Entity\DatahubData;
use App\Entity\DeletedAnnotation;
use App\Entity\InventoryNumber;
use App\Entity\Organization;
use App\Entity\Report;
use App\Entity\ReportData;
use App\Entity\ReportHistory;
use App\Entity\Representative;
use Doctrine\ORM\EntityManager;

class ReportTemplateData
{
    public static function getViewData(EntityManager $em, $id)
    {
        $data = self::getExistingReportData($em, $id);
        $data['readonly'] = true;
        return $data;
    }

    public static function getDataToCreateExisting(EntityManager $em, $reportReasons, $id)
    {
        $data = self::getExistingReportData($em, $id);
        $data['report_reasons'] = $reportReasons;
        $data['organizations'] = self::getOrganizations($em);
        $data['representatives'] = self::getRepresentatives($em);
        $data['readonly'] = false;
        return $data;
    }

    public static function getDataToCreateBlank(EntityManager $em, $reportReasons, $id)
    {
        $prefilledData = array();
        $iiifImageData = '{}';
        $patternSize = 100;
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
                        $patternSize = self::getPatternSize($iiifImageData);
                    }
                }
                $prefilledData[$data['name']] = $data['value'];
            }
        }

        return [
            'prefilled_data' => $prefilledData,
            'iiif_image_data' => $iiifImageData,
            'pattern_size' => $patternSize,
            'stroke_width' => round($patternSize / 15),
            'annotation_history' => array(),
            'annotations' => array(),
            'deleted_annotations' => array(),
            'report_reasons' => $reportReasons,
            'organizations' => self::getOrganizations($em),
            'representatives' => self::getRepresentatives($em),
            'readonly' => false
        ];
    }

    public static function getExistingReportData(EntityManager $em, $id)
    {
        $prefilledData = array();
        $iiifImageData = '{}';
        $patternSize = 100;
        $reportData = $em->createQueryBuilder()
            ->select('r.id, r.inventoryId, r.baseId, r.timestamp, d.name, d.value')
            ->from(Report::class, 'r')
            ->leftJoin(ReportData::class, 'd', 'WITH', 'd.id = r.id')
            ->where('r.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
        $lastReportTimestamp = '';
        foreach ($reportData as $data) {
            if(empty($prefilledData['inventory_id'])) {
                $prefilledData['inventory_id'] = $data['inventoryId'];
            }
            if(empty($prefilledData['base_id'])) {
                $prefilledData['base_id'] = $data['baseId'];
            }
            if(empty($lastReportTimestamp)) {
                $lastReportTimestamp = $data['timestamp']->format('Y-m-d H:i:s');
            }
            if($data['name'] === 'iiif_image_url') {
                $iiifImageData = CurlUtil::get($data['value'] . '/info.json');
                if(!empty($iiifImageData)) {
                    $patternSize = self::getPatternSize($iiifImageData);
                }
            }
            $prefilledData[$data['name']] = $data['value'];
        }
        $reportHistory = $em->createQueryBuilder()
            ->select('h.id, h.previousId, h.sortOrder, r.timestamp')
            ->from(ReportHistory::class, 'h')
            ->innerJoin(Report::class, 'r', 'WITH', 'r.id = h.previousId')
            ->where('h.id = :id')
            ->setParameter('id', $id)
            ->orderBy('h.sortOrder', 'DESC')
            ->getQuery()
            ->getResult();
        $previousIds = array();
        $annotationHistory = array();
        foreach($reportHistory as $history) {
            if(!array_key_exists('report_history', $prefilledData)) {
                $prefilledData['report_history'] = array($id => $history['sortOrder'] + 1);
                $previousIds[] = $id;
                $annotationHistory[$id] = $lastReportTimestamp;
            }
            $prefilledData['report_history'][$history['previousId']] = $history['sortOrder'];
            $previousIds[] = $history['previousId'];
            $annotationHistory[$history['previousId']] = $history['timestamp']->format('Y-m-d H:i:s');
        }
        if(!array_key_exists('report_history', $prefilledData)) {
            $prefilledData['report_history'] = array($id => 1);
            $previousIds[] = $id;
            $annotationHistory[$id] = $lastReportTimestamp;
        }
        $annotationData = $em->createQueryBuilder()
            ->select('a')
            ->from(Annotation::class, 'a')
            ->where('a.id IN (:ids)')
            ->setParameter('ids', $previousIds)
            ->orderBy('a.id')
            ->getQuery()
            ->getResult();
        $annotations = array();
        foreach($annotationData as $annotation) {
            if(!array_key_exists($annotation->getId(), $annotations)) {
                $annotations[$annotation->getId()] = array();
            }
            $annotations[$annotation->getId()][$annotation->getAnnotationId()] = $annotation->getAnnotation();
        }

        $deletedAnnotationData = $em->createQueryBuilder()
            ->select('d')
            ->from(DeletedAnnotation::class, 'd')
            ->where('d.id IN (:ids)')
            ->setParameter('ids', $previousIds)
            ->orderBy('d.id')
            ->getQuery()
            ->getResult();
        $deletedAnnotations = array();
        foreach($deletedAnnotationData as $deletedAnnotation) {
            if(!array_key_exists($deletedAnnotation->getId(), $deletedAnnotations)) {
                $deletedAnnotations[$deletedAnnotation->getId()] = array();
            }
            $deletedAnnotations[$deletedAnnotation->getId()][$deletedAnnotation->getAnnotationId()] = $deletedAnnotation->getAnnotationId();
        }

        $annotationData = $em->createQueryBuilder()
            ->select('a')
            ->from(Annotation::class, 'a')
            ->where('a.id IN (:ids)')
            ->setParameter('ids', $previousIds)
            ->orderBy('a.id')
            ->getQuery()
            ->getResult();
        $annotations = array();
        foreach($annotationData as $annotation) {
            if(!array_key_exists($annotation->getId(), $annotations)) {
                $annotations[$annotation->getId()] = array();
            }
            $annotations[$annotation->getId()][$annotation->getAnnotationId()] = $annotation->getAnnotation();
        }

        $deletedAnnotationData = $em->createQueryBuilder()
            ->select('d')
            ->from(DeletedAnnotation::class, 'd')
            ->where('d.id IN (:ids)')
            ->setParameter('ids', $previousIds)
            ->orderBy('d.id')
            ->getQuery()
            ->getResult();
        $deletedAnnotations = array();
        foreach($deletedAnnotationData as $deletedAnnotation) {
            if(!array_key_exists($deletedAnnotation->getId(), $deletedAnnotations)) {
                $deletedAnnotations[$deletedAnnotation->getId()] = array();
            }
            $deletedAnnotations[$deletedAnnotation->getId()][$deletedAnnotation->getAnnotationId()] = $deletedAnnotation->getAnnotationId();
        }

        foreach($annotationHistory as $id => $timestamp) {
            if(!array_key_exists($id, $annotations) && !array_key_exists($id, $deletedAnnotations)) {
                unset($annotationHistory[$id]);
            }
        }

        return [
            'prefilled_data' => $prefilledData,
            'iiif_image_data' => $iiifImageData,
            'pattern_size' => $patternSize,
            'stroke_width' => round($patternSize / 10),
            'annotation_history' => $annotationHistory,
            'annotations' => $annotations,
            'deleted_annotations' => $deletedAnnotations
        ];
   }

   public static function getPatternSize($iiifImageData)
   {
       $dataDecoded = json_decode($iiifImageData);
       return round(($dataDecoded->height > $dataDecoded->width ? $dataDecoded->height : $dataDecoded->width) / 100);
   }

    public static function getOrganizations(EntityManager $em)
    {
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
        return $organizations;
    }

    public static function getRepresentatives(EntityManager $em)
    {
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
        return $representatives;
    }
}
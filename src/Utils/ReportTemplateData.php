<?php

namespace App\Utils;

use App\Entity\Annotation;
use App\Entity\DatahubData;
use App\Entity\DeletedAnnotation;
use App\Entity\Image;
use App\Entity\InventoryNumber;
use App\Entity\Organisation;
use App\Entity\Report;
use App\Entity\ReportData;
use App\Entity\ReportHistory;
use App\Entity\Representative;
use Doctrine\ORM\EntityManager;

class ReportTemplateData
{
    public static function getViewData(EntityManager $em, $reportReasons, $objectTypes, $reportFields, $id, $translatedRoutes)
    {
        $data = self::getExistingReportData($em, $id, '../..');
        $data['report_reasons'] = $reportReasons;
        $data['object_types'] = $objectTypes;
        $data['report_fields'] = $reportFields;
        $data['readonly'] = true;
        $data['pattern_size'] = 20;
        $data['stroke_width'] = 2;
        $data['translated_routes'] = $translatedRoutes;
        return $data;
    }

    public static function getDataToCreateExisting(EntityManager $em, $reportReasons, $objectTypes, $reportFields, $id, $translatedRoutes)
    {
        $data = self::getExistingReportData($em, $id, '../../..');
        $data['report_reasons'] = $reportReasons;
        $data['object_types'] = $objectTypes;
        $data['report_fields'] = $reportFields;
        $data['readonly'] = false;
        $data['pattern_size'] = 20;
        $data['stroke_width'] = 2;
        $data['translated_routes'] = $translatedRoutes;
        return $data;
    }

    public static function getDataToCreateBlank(EntityManager $em, $reportReasons, $objectTypes, $reportFields, $id, $translatedRoutes)
    {
        // Prevent creation of a blank report if there is already a report for this inventory number
        $canCreate = true;
        $reportData = $em->createQueryBuilder()
            ->select('r.id')
            ->from(Report::class, 'r')
            ->where('r.inventoryId = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
        foreach ($reportData as $data) {
            $canCreate = false;
        }
        if(!$canCreate) {
            return null;
        }

        $prefilledData = self::getDatahubData($em, $id, array());
        $images = self::getImages($em, $prefilledData, '../../..');

        return [
            'current_page' => 'reports',
            'prefilled_data' => $prefilledData,
            'images' => $images,
            'annotation_history' => array(),
            'annotations' => array(),
            'deleted_annotations' => array(),
            'report_reasons' => $reportReasons,
            'object_types' => $objectTypes,
            'report_fields' => $reportFields,
            'organisations' => self::getOrganisations($em),
            'representatives' => self::getRepresentatives($em),
            'readonly' => false,
            'pattern_size' => 20,
            'stroke_width' => 2,
            'translated_routes' => $translatedRoutes
        ];
    }

    public static function getExistingReportData(EntityManager $em, $id, $imageRelPath)
    {
        $prefilledData = array();
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
            $prefilledData[$data['name']] = $data['value'];
        }

        $prefilledData = self::getDatahubData($em, $prefilledData['inventory_id'], $prefilledData);
        $images = self::getImages($em, $prefilledData, $imageRelPath);

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
        $annotationTimestamps = array();
        foreach($reportHistory as $history) {
            if(!array_key_exists('report_history', $prefilledData)) {
                $prefilledData['report_history'] = array($id => $history['sortOrder'] + 1);
                $previousIds[] = $id;
                $annotationTimestamps[$id] = $lastReportTimestamp;
            }
            $prefilledData['report_history'][$history['previousId']] = $history['sortOrder'];
            $previousIds[] = $history['previousId'];
            $annotationTimestamps[$history['previousId']] = $history['timestamp']->format('Y-m-d H:i:s');
        }
        if(!array_key_exists('report_history', $prefilledData)) {
            $prefilledData['report_history'] = array($id => 1);
            $previousIds[] = $id;
            $annotationTimestamps[$id] = $lastReportTimestamp;
        }

        $annotationData = $em->createQueryBuilder()
            ->select('a')
            ->from(Annotation::class, 'a')
            ->where('a.reportId IN (:reportIds)')
            ->setParameter('reportIds', $previousIds)
            ->orderBy('a.reportId')
            ->getQuery()
            ->getResult();
        $annotations = array();
        foreach($annotationData as $annotation) {
            if(!array_key_exists($annotation->getImage(), $annotations)) {
                $annotations[$annotation->getImage()] = array();
            }
            if(!array_key_exists($annotation->getReportId(), $annotations[$annotation->getImage()])) {
                $annotations[$annotation->getImage()][$annotation->getReportId()] = array();
            }
            $annotations[$annotation->getImage()][$annotation->getReportId()][$annotation->getAnnotationId()] = $annotation->getAnnotation();
        }

        $deletedAnnotationData = $em->createQueryBuilder()
            ->select('d')
            ->from(DeletedAnnotation::class, 'd')
            ->where('d.reportId IN (:reportIds)')
            ->setParameter('reportIds', $previousIds)
            ->orderBy('d.reportId')
            ->getQuery()
            ->getResult();
        $deletedAnnotations = array();
        foreach($deletedAnnotationData as $deletedAnnotation) {
            if(!array_key_exists($deletedAnnotation->getImage(), $annotations)) {
                $annotations[$deletedAnnotation->getImage()] = array();
            }
            if(!array_key_exists($deletedAnnotation->getReportId(), $annotations[$deletedAnnotation->getImage()])) {
                $annotations[$deletedAnnotation->getImage()][$deletedAnnotation->getReportId()] = array();
            }
            $deletedAnnotations[$deletedAnnotation->getImage()][$deletedAnnotation->getReportId()][$deletedAnnotation->getAnnotationId()] = $deletedAnnotation->getAnnotationId();
        }

        $annotationHistory = array();
        foreach($annotationTimestamps as $reportId => $timestamp) {
            foreach($annotations as $image => $reports) {
                if(array_key_exists($reportId, $reports)) {
                    if(!array_key_exists($image, $annotationHistory)) {
                        $annotationHistory[$image] = array();
                    }
                    if(!array_key_exists($reportId, $annotationHistory[$image])) {
                        $annotationHistory[$image][$reportId] = $timestamp;
                    }
                }
            }
            foreach($deletedAnnotations as $image => $reports) {
                if(array_key_exists($reportId, $reports)) {
                    if(!array_key_exists($image, $annotationHistory)) {
                        $annotationHistory[$image] = array();
                    }
                    if(!array_key_exists($reportId, $annotationHistory[$image])) {
                        $annotationHistory[$image][$reportId] = $timestamp;
                    }
                }
            }
        }

        return [
            'current_page' => 'reports',
            'prefilled_data' => $prefilledData,
            'images' => $images,
            'annotation_history' => $annotationHistory,
            'annotations' => $annotations,
            'deleted_annotations' => $deletedAnnotations,
            'organisations' => self::getOrganisations($em),
            'representatives' => self::getRepresentatives($em)
        ];
   }

   public static function getDatahubData(EntityManager $em, $id, $prefilledData)
   {
       $datahubData = $em->createQueryBuilder()
           ->select('i.id, i.inventoryNumber, d.name, d.value')
           ->from(InventoryNumber::class, 'i')
           ->leftJoin(DatahubData::class, 'd', 'WITH', 'd.id = i.id')
           ->where('i.id = :id')
           ->setParameter('id', $id)
           ->getQuery()
           ->getResult();
       foreach ($datahubData as $data) {
           $prefilledData['inventory_id'] = $data['id'];
           $prefilledData['inventory_number'] = $data['inventoryNumber'];
           if(!empty($data['value']) && !array_key_exists($data['name'], $prefilledData)) {
               $prefilledData[$data['name']] = $data['value'];
           }
       }
       return $prefilledData;
   }

   public static function getImages(EntityManager $em, $prefilledData, $imageRelPath)
   {
       $images = array();
       if(array_key_exists('images', $prefilledData)) {
           $hashes = explode(',', $prefilledData['images']);
           $imageData = $em->createQueryBuilder()
               ->select('i')
               ->from(Image::class, 'i')
               ->where('i.hash IN (:hashes)')
               ->setParameter('hashes', $hashes)
               ->getQuery()
               ->getResult();
           $tmpImages = array();
           foreach ($imageData as $image) {
/*               if(StringUtil::endsWith($image->getImage(), '/info.json')) {
                   $patternSize = self::getIIIFPatternSize(CurlUtil::get($image->getImage()));
               } else {
                   $imageSize = getimagesize($image->getImage());
                   $patternSize = self::getPatternSize($imageSize['width'], $imageSize['height']);
               }
               $strokeWidth = round($patternSize / 10);
               if($strokeWidth < 1) {
                   $strokeWidth = 1;
               }*/
               $img = $image->getImage();
               $thm = $image->getThumbnail();
               if(strpos($img, '/') === 0) {
                   $img = $imageRelPath . $img;
               }
               if(strpos($thm, '/') === 0) {
                   $thm = $imageRelPath . $thm;
               }
               $tmpImages[$image->getHash()] = array(
                   'hash' => $image->getHash(),
                   'image' => $img,
                   'thumbnail' => $thm
               );
           }
           foreach($hashes as $hash) {
               if(array_key_exists($hash, $tmpImages)) {
                   $images[] = $tmpImages[$hash];
               }
           }
       }
       return $images;
   }

   public static function getIIIFPatternSize($iiifImageData)
   {
       $dataDecoded = json_decode($iiifImageData);
       if($dataDecoded == null) {
           return 20;
       } else {
           return self::getPatternSize($dataDecoded->height, $dataDecoded->width);
       }
   }

   public static function getPatternSize($height, $width)
   {
       return round(($height > $width ? $height : $width) / 100);
   }

    public static function getOrganisations(EntityManager $em)
    {
        $organisations = array();
        $organisationData = $em->createQueryBuilder()
            ->select('o')
            ->from(Organisation::class, 'o')
            ->orderBy('o.alias')
            ->getQuery()
            ->getResult();
        foreach ($organisationData as $organisation) {
            $organisations[$organisation->getId()] = $organisation;
        }
        return $organisations;
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
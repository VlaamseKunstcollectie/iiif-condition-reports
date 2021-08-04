<?php

namespace App\Controller;

use App\Entity\Annotation;
use App\Entity\DeletedAnnotation;
use App\Entity\Organization;
use App\Entity\Report;
use App\Entity\ReportData;
use App\Entity\ReportHistory;
use App\Entity\Representative;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CreateExistingReportController extends AbstractController
{
    /**
     * @Route("/create/existing/{id}", name="create_existing")
     */
    public function createExisting(Request $request, $id)
    {
        $prefilledData = array();
        $reportReasons = $this->getParameter('report_reasons');
        $em = $this->container->get('doctrine')->getManager();
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
            'annotation_history' => $annotationHistory,
            'annotations' => $annotations,
            'deleted_annotations' => $deletedAnnotations,
            'report_reasons' => $reportReasons,
            'organizations' => $organizations,
            'representatives' => $representatives
        ]);
    }
}

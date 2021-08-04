<?php

namespace App\Controller;

use App\Entity\Annotation;
use App\Entity\DeletedAnnotation;
use App\Entity\Report;
use App\Entity\ReportData;
use App\Entity\ReportHistory;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SaveReportController extends AbstractController
{
    /**
     * @Route("/save/{type}", name="save")
     */
    public function save(Request $request, $type)
    {
        if($request->getMethod() === 'POST') {
            $reportData = array();
            $fields = explode('&', $request->getContent());
            $annotationData = array();
            $reportHistory = array();
            $baseId = '';
            $inventoryId = '';
            foreach($fields as $field) {
                $fieldData = explode('=', $field);
                if($fieldData[0] === 'annotation_data') {
                    $annotationData = json_decode(urldecode($fieldData[1]));
                } else if($fieldData[0] === 'base_id') {
                    $baseId = urldecode($fieldData[1]);
                } else if($fieldData[0] === 'inventory_id') {
                    $inventoryId = urldecode($fieldData[1]);
                } else if($fieldData[0] === 'report_history') {
                    $reportHistory = json_decode(urldecode($fieldData[1]));
                } else {
                    $reportData[urldecode($fieldData[0])] = urldecode($fieldData[1]);
                }
            }

            if(!empty($inventoryId)) {

                $em = $this->container->get('doctrine')->getManager();

                $report = new Report();
                $report->setInventoryId($inventoryId);
                $report->setTimestamp(new DateTime());
                if(!empty($baseId)) {
                    $report->setBaseId($baseId);
                }
                $em->persist($report);
                $em->flush();

                if(empty($baseId)) {
                    $report->setBaseId($report->getId());
                    $em->persist($report);
                    $em->flush();
                }

                $i = 0;
                foreach($reportData as $key => $value) {
                    if(!empty($value)) {
                        $reportDataEntity = new ReportData();
                        $reportDataEntity->setId($report->getId());
                        $reportDataEntity->setName($key);
                        $reportDataEntity->setValue($value);
                        $em->persist($reportDataEntity);
                        $i++;
                        if ($i == 500) {
                            $em->flush();
                            $i = 0;
                        }
                    }
                }
                $em->flush();

                if(empty($reportHistory) || empty($baseId)) {
                    foreach ($annotationData as $annotation) {
                        $annotationEntity = new Annotation();
                        $annotationEntity->setId($report->getId());
                        $annotationEntity->setAnnotationId($annotation->id);
                        $annotationEntity->setAnnotation(json_encode($annotation));
                        $em->persist($annotationEntity);
                    }
                    $em->flush();
                } else {
                    $previousIds = array();
                    foreach($reportHistory as $id => $order) {
                        $idInt = intval($id);
                        $orderInt = intval($order);
                        $previousIds[] = $idInt;
                        $reportHistoryEntity = new ReportHistory();
                        $reportHistoryEntity->setId($report->getId());
                        $reportHistoryEntity->setPreviousId($idInt);
                        $reportHistoryEntity->setSortOrder($orderInt);
                        $em->persist($reportHistoryEntity);
                    }
                    $em->flush();

                    $annotations = array();
                    foreach($annotationData as $annotation) {
                        $annotations[$annotation->id] = json_encode($annotation);
                    }

                    $oldAnnotationEntities = $em->createQueryBuilder()
                        ->select('a')
                        ->from(Annotation::class, 'a')
                        ->where('a.id IN (:ids)')
                        ->setParameter('ids', $previousIds)
                        ->orderBy('a.id')
                        ->getQuery()
                        ->getResult();
                    $oldAnnotationsToAdd = array();
                    foreach($oldAnnotationEntities as $annotation) {
                        if(!array_key_exists($annotation->getId(), $oldAnnotationsToAdd)) {
                            $oldAnnotationsToAdd[$annotation->getId()] = array();
                        }
                        $oldAnnotationsToAdd[$annotation->getId()][$annotation->getAnnotationId()] = $annotation->getAnnotation();
                    }

                    $oldDeletedAnnotationEntities = $em->createQueryBuilder()
                        ->select('d')
                        ->from(DeletedAnnotation::class, 'd')
                        ->where('d.id IN (:ids)')
                        ->setParameter('ids', $previousIds)
                        ->orderBy('d.id')
                        ->getQuery()
                        ->getResult();
                    $oldAnnotationsToDelete = array();
                    foreach($oldDeletedAnnotationEntities as $deletedAnnotation) {
                        if(!array_key_exists($deletedAnnotation->getId(), $oldAnnotationsToDelete)) {
                            $oldAnnotationsToDelete[$deletedAnnotation->getId()] = array();
                        }
                        $oldAnnotationsToDelete[$deletedAnnotation->getId()][$deletedAnnotation->getAnnotationId()] = $deletedAnnotation->getAnnotationId();
                    }

                    $oldAnnotations = array();
                    foreach($previousIds as $id) {
                        if(array_key_exists($id, $oldAnnotationsToDelete)) {
                            foreach($oldAnnotationsToDelete[$id] as $key => $val) {
                                unset($oldAnnotations[$key]);
                            }
                        }
                        if(array_key_exists($id, $oldAnnotationsToAdd)) {
                            foreach($oldAnnotationsToAdd[$id] as $key => $anno) {
                                $oldAnnotations[$key] = $anno;
                            }
                        }
                    }

                    $deleted = array();
                    $added = array();
                    foreach($oldAnnotations as $id => $annotation) {
                        if(!array_key_exists($id, $annotations)) {
                            $deleted[] = $id;
                        } else if($annotation !== $annotations[$id]) {
                            $deleted[] = $id;
                        }
                    }
                    foreach ($annotations as $id => $annotation) {
                        if(!array_key_exists($id, $oldAnnotations)) {
                            $added[$id] = $annotation;
                        } else if($annotation !== $oldAnnotations[$id]) {
                            $added[$id] = $annotation;
                        }
                    }
                    foreach($deleted as $id) {
                        $deletedEntity = new DeletedAnnotation();
                        $deletedEntity->setId($report->getId());
                        $deletedEntity->setAnnotationId($id);
                        $em->persist($deletedEntity);
                    }
                    $em->flush();
                    foreach($added as $id => $annotation) {
                        $addedEntity = new Annotation();
                        $addedEntity->setId($report->getId());
                        $addedEntity->setAnnotationId($id);
                        $addedEntity->setAnnotation($annotation);
                        $em->persist($addedEntity);
                    }
                    $em->flush();
                }
                //TODO add signature

//                return $this->redirectToRoute('view', [ 'id' => $report->getId() ]);
            } else {
                //TODO appropriate error message
            }
        }
        return $this->render('main.html.twig');
    }
}

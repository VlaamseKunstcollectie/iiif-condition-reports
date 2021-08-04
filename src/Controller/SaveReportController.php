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
                    $highestOrder = 0;
                    $highestId = 0;
                    foreach($reportHistory as $id => $order) {
                        $idInt = intval($id);
                        $orderInt = intval($order);
                        $reportHistoryEntity = new ReportHistory();
                        $reportHistoryEntity->setId($report->getId());
                        $reportHistoryEntity->setPreviousId($idInt);
                        $reportHistoryEntity->setSortOrder($orderInt);
                        if($orderInt > $highestOrder) {
                            $highestOrder = $orderInt;
                            $highestId = $idInt;
                        }
                        $em->persist($reportHistoryEntity);
                    }
                    $em->flush();

                    $annotations = array();
                    $oldAnnotations = array();
                    foreach($annotationData as $annotation) {
                        $annotations[$annotation->id] = json_encode($annotation);
                    }

                    $oldAnnotationEntities = $em->createQueryBuilder()
                        ->select('a')
                        ->from(Annotation::class, 'a')
                        ->where('a.id = :id')
                        ->setParameter('id', $highestId)
                        ->getQuery()
                        ->getResult();
                    $deleted = array();
                    $added = array();
                    foreach($oldAnnotationEntities as $annotation) {
                        $oldAnnotations[$annotation->getAnnotationId()] = $annotation->getAnnotation();
                        if(!array_key_exists($annotation->getAnnotationId(), $annotations)) {
                            $deleted[] = $annotation->getAnnotationId();
                        } else if($annotation->getAnnotation() !== $annotations[$annotation->getAnnotationId()]) {
                            $deleted[] = $annotation->getAnnotationId();
                            echo 'Different: ';
                            var_dump($annotation->getAnnotation());
                            echo 'Versus: ';
                            var_dump($annotations[$annotation->getAnnotationId()]);
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

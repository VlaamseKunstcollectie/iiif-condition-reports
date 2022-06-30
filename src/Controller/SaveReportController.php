<?php

namespace App\Controller;

use App\Entity\Annotation;
use App\Entity\DeletedAnnotation;
use App\Entity\Report;
use App\Entity\ReportData;
use App\Entity\ReportHistory;
use App\Utils\IIIFUtil;
use DateTime;
use http\Env\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SaveReportController extends AbstractController
{
    /**
     * @Route("/{_locale}/save", name="save")
     */
    public function save(Request $request)
    {
        $locale = $request->get('_locale');
        $locales = $this->getParameter('locales');
        //Set default locale if locale is missing
        if($locale === null || !in_array($locale, $locales)) {
            return $this->redirectToRoute('save', array('_locale' => $locales[0]));
        }
        if(!$this->getUser()) {
            return $this->redirectToRoute('main');
        } else if(!$this->getUser()->getRoles()) {
            return $this->redirectToRoute('main');
        } else if (!in_array('ROLE_USER', $this->getUser()->getRoles(), true)) {
            return $this->redirectToRoute('main');
        }

        if($request->getMethod() === 'POST') {
            $reportData = array();
            $fields = explode('&', $request->getContent());
            $annotationData = array();
            $reportHistory = array();
            $baseId = '';
            $inventoryId = '';
            $reason = '';
            $images = array();
            $organisations = array();
            $representatives = array();
            foreach($fields as $field) {
                $fieldData = explode('=', $field);
                $name = urldecode($fieldData[0]);
                $value = urldecode($fieldData[1]);
                $orgNamePos = strpos($name, '_name');
                if($orgNamePos !== false && !empty($value)) {
                    $organisations[] = substr($name, 0, $orgNamePos);
                }
                $repNamePos = strpos($name, '_rep_name');
                if($repNamePos !== false && !empty($value)) {
                    $representatives[] = substr($name, 0, $repNamePos);
                }
                if($name === 'reason') {
                    $reason = $value;
                }
                if($name === 'annotation_data') {
                    $annotationData = json_decode($value);
                } else if($name === 'base_id') {
                    $baseId = $value;
                } else if($name === 'inventory_id') {
                    $inventoryId = $value;
                } else if($name === 'report_history') {
                    $reportHistory = json_decode($value);
                } else if($name === 'images[]') {
                    $images[] = json_decode(str_replace('\\"', '"', $value));
                } else if($name !== 'choose-colour') {
                    $reportData[$name] = $value;
                }
            }

            $signaturesRequired = 0;
            foreach($representatives as $representative) {
                if(in_array($representative, $organisations)) {
                    $signaturesRequired++;
                }
            }

            $imageHashes = array();
            foreach($images as $image) {
                $imageHashes[] = $image->hash;
            }
            $reportData['images'] = implode(',', $imageHashes);

            if(!empty($inventoryId)) {

                $em = $this->container->get('doctrine')->getManager();

                $report = new Report();
                $report->setInventoryId($inventoryId);
                $report->setTimestamp(new DateTime());
                $report->setReason($reason);
                $report->setSignaturesRequired($signaturesRequired);
                if(!empty($baseId)) {
                    $report->setBaseId($baseId);
                }

                $em->persist($report);
                $em->flush();

                if(empty($baseId)) {
                    $report->setBaseId($report->getId());
                    // Because datetime is converted into UTC, we have to set it again
                    $report->setTimestamp(new DateTime());
                    $em->persist($report);
                    $em->flush();
                }

                $reportData['manifest'] = IIIFUtil::generateManifest($em, $report->getId(), $reportData, $images, $annotationData, $this->getParameter('service_url'),
                                           $this->getParameter('validate_manifests'), $this->getParameter('validator_url'),
                                           $this->getParameter('authentication_url'), $this->getParameter('authentication_service_description'));

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
                    foreach ($annotationData as $image => $newAnnotations) {
                        foreach($newAnnotations as $annotation) {
                            $annotationEntity = new Annotation();
                            $annotationEntity->setReportId($report->getId());
                            $annotationEntity->setImage($image);
                            $annotationEntity->setAnnotationId($annotation->id);
                            $annotationEntity->setAnnotation(json_encode($annotation));
                            $em->persist($annotationEntity);
                        }
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

                    sort($previousIds);

                    $newAnnotations = array();
                    foreach($annotationData as $image => $annos) {
                        $newAnnotations[$image] = array();
                        foreach($annos as $annotation) {
                            $newAnnotations[$image][$annotation->id] = json_encode($annotation);
                        }
                    }

                    $oldAnnotationEntities = $em->createQueryBuilder()
                        ->select('a')
                        ->from(Annotation::class, 'a')
                        ->where('a.reportId IN (:reportIds)')
                        ->setParameter('reportIds', $previousIds)
                        ->orderBy('a.reportId')
                        ->getQuery()
                        ->getResult();
                    $oldAnnotationsToAdd = array();
                    foreach($oldAnnotationEntities as $annotation) {
                        if(!array_key_exists($annotation->getImage(), $oldAnnotationsToAdd)) {
                            $oldAnnotationsToAdd[$annotation->getImage()] = array();
                        }
                        if(!array_key_exists($annotation->getReportId(), $oldAnnotationsToAdd[$annotation->getImage()])) {
                            $oldAnnotationsToAdd[$annotation->getImage()][$annotation->getReportId()] = array();
                        }
                        $oldAnnotationsToAdd[$annotation->getImage()][$annotation->getReportId()][$annotation->getAnnotationId()] = $annotation->getAnnotation();
                    }

                    $oldDeletedAnnotationEntities = $em->createQueryBuilder()
                        ->select('d')
                        ->from(DeletedAnnotation::class, 'd')
                        ->where('d.reportId IN (:reportIds)')
                        ->setParameter('reportIds', $previousIds)
                        ->orderBy('d.reportId')
                        ->getQuery()
                        ->getResult();
                    $oldAnnotationsToDelete = array();
                    foreach($oldDeletedAnnotationEntities as $deletedAnnotation) {
                        if(!array_key_exists($deletedAnnotation->getImage(), $oldAnnotationsToDelete)) {
                            $oldAnnotationsToDelete[$deletedAnnotation->getImage()] = array();
                        }
                        if(!array_key_exists($deletedAnnotation->getReportId(), $oldAnnotationsToDelete[$deletedAnnotation->getImage()])) {
                            $oldAnnotationsToDelete[$deletedAnnotation->getImage()][$deletedAnnotation->getReportId()] = array();
                        }
                        $oldAnnotationsToDelete[$deletedAnnotation->getImage()][$deletedAnnotation->getReportId()][$deletedAnnotation->getAnnotationId()] = $deletedAnnotation->getAnnotationId();
                    }

                    $oldAnnotations = array();
                    foreach($imageHashes as $image) {
                        foreach ($previousIds as $reportId) {
                            if(array_key_exists($image, $oldAnnotations) && array_key_exists($image, $oldAnnotationsToDelete)) {
                                if (array_key_exists($reportId, $oldAnnotationsToDelete[$image])) {
                                    foreach ($oldAnnotationsToDelete[$image][$reportId] as $key => $val) {
                                        unset($oldAnnotations[$image][$key]);
                                    }
                                }
                            }
                            if (array_key_exists($image, $oldAnnotationsToAdd)) {
                                if (array_key_exists($reportId, $oldAnnotationsToAdd[$image])) {
                                    foreach ($oldAnnotationsToAdd[$image][$reportId] as $key => $anno) {
                                        if(!array_key_exists($image, $oldAnnotations)) {
                                            $oldAnnotations[$image] = array();
                                        }
                                        $oldAnnotations[$image][$key] = $anno;
                                    }
                                }
                            }
                        }
                    }

                    foreach($imageHashes as $image) {
                        $added = array();
                        $deleted = array();
                        if(array_key_exists($image, $oldAnnotations)) {
                            foreach ($oldAnnotations[$image] as $annoId => $annotation) {
                                if(!array_key_exists($image, $newAnnotations)) {
                                    $deleted[] = $annoId;
                                } else if (!array_key_exists($annoId, $newAnnotations[$image])) {
                                    $deleted[] = $annoId;
                                } else if ($annotation !== $newAnnotations[$image][$annoId]) {
                                    $deleted[] = $annoId;
                                }
                            }
                        }
                        if(array_key_exists($image, $newAnnotations)) {
                            foreach ($newAnnotations[$image] as $annoId => $annotation) {
                                if(!array_key_exists($image, $oldAnnotations)) {
                                    $added[$annoId] = $annotation;
                                } else if (!array_key_exists($annoId, $oldAnnotations[$image])) {
                                    $added[$annoId] = $annotation;
                                } else if ($annotation !== $oldAnnotations[$image][$annoId]) {
                                    $added[$annoId] = $annotation;
                                }
                            }
                        }
                        foreach ($deleted as $id) {
                            $deletedEntity = new DeletedAnnotation();
                            $deletedEntity->setReportId($report->getId());
                            $deletedEntity->setImage($image);
                            $deletedEntity->setAnnotationId($id);
                            $em->persist($deletedEntity);
                        }
                        $em->flush();
                        foreach($added as $id => $annotation) {
                            $addedEntity = new Annotation();
                            $addedEntity->setReportId($report->getId());
                            $addedEntity->setImage($image);
                            $addedEntity->setAnnotationId($id);
                            $addedEntity->setAnnotation($annotation);
                            $em->persist($addedEntity);
                        }
                        $em->flush();
                    }
                }

                return $this->redirectToRoute('view', array('_locale' => $locale, 'id' => $report->getId()));
            } else {
                //TODO appropriate error message
                return $this->redirectToRoute('main', array('_locale' => $locale));
            }
        } else {
            return $this->redirectToRoute('main', array('_locale' => $locale));
        }
    }
}

<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AnnotationRepository")
 * @ORM\Table(name="annotations")
 */
class Annotation
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $reportId;

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=64)
     */
    private $image;

    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    private $annotationId;

    /**
     * @ORM\Column(type="text")
     */
    private $annotation;

    public function getReportId()
    {
        return $this->reportId;
    }

    public function setReportId($reportId)
    {
        $this->reportId = $reportId;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image)
    {
        $this->image = $image;
    }

    public function getAnnotationId()
    {
        return $this->annotationId;
    }

    public function setAnnotationId($annotationId)
    {
        $this->annotationId = $annotationId;
    }

    public function getAnnotation()
    {
        return $this->annotation;
    }

    public function setAnnotation($annotation)
    {
        $this->annotation = $annotation;
    }
}

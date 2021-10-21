<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DeletedAnnotationRepository")
 * @ORM\Table(name="deleted_annotations")
 */
class DeletedAnnotation
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
}

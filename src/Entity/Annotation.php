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
    private $id;

    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    private $annotationId;

    /**
     * @ORM\Column(type="text")
     */
    private $annotation;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
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

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
    private $id;

    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    private $annotationId;

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
}

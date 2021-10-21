<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\IIIFManifestRepository")
 * @ORM\Table(name="iiif_manifests")
 */
class IIIFManifest
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $manifestId;

    /**
     * @ORM\Column(type="text")
     */
    private $data;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getManifestId()
    {
        return $this->manifestId;
    }

    public function setManifestId($manifestId)
    {
        $this->manifestId = $manifestId;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }
}

<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ReportRepository")
 * @ORM\Table(name="reports")
 */
class Report
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $inventoryNumber;

    /**
     * @ORM\Column(type="datetime")
     */
    private $lastModified;

    public function getId()
    {
        return $this->id;
    }

    public function setId(string $id)
    {
        $this->id = $id;
    }

    public function getInventoryNumber()
    {
        return $this->inventoryNumber;
    }

    public function setInventoryNumber($inventoryNumber)
    {
        $this->inventoryNumber = $inventoryNumber;
    }

    public function getLastModified()
    {
        return $this->lastModified;
    }

    public function setLastModified($lastModified)
    {
        $this->lastModified = $lastModified;
    }
}

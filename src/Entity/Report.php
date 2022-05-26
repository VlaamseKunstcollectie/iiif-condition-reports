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
     * @ORM\Column(type="integer")
     */
    private $baseId;

    /**
     * @ORM\Column(type="integer")
     */
    private $inventoryId;

    /**
     * @ORM\Column(type="datetime")
     */
    private $timestamp;

    /**
     * @ORM\Column(type="integer")
     */
    private $reason;

    /**
     * @ORM\Column(type="integer")
     */
    private $signaturesRequired;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getBaseId()
    {
        return $this->baseId;
    }

    public function setBaseId($baseId)
    {
        $this->baseId = $baseId;
    }

    public function getInventoryId()
    {
        return $this->inventoryId;
    }

    public function setInventoryId($inventoryId)
    {
        $this->inventoryId = $inventoryId;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    public function getReason() {
        return $this->reason;
    }

    public function setReason($reason)
    {
        $this->reason = $reason;
    }

    public function getSignaturesRequired()
    {
        return $this->signaturesRequired;
    }

    public function setSignaturesRequired($signaturesRequired)
    {
        $this->signaturesRequired = $signaturesRequired;
    }
}

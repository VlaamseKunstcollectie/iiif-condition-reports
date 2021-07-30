<?php

namespace App\Entity;

class Search
{
    private $inventoryNumber;

    private $matchType;

    public function getInventoryNumber()
    {
        return $this->inventoryNumber;
    }

    public function setInventoryNumber($inventoryNumber)
    {
        $this->inventoryNumber = $inventoryNumber;
    }

    public function getMatchType()
    {
        return $this->matchType;
    }

    public function setMatchType($matchType)
    {
        $this->matchType = $matchType;
    }
}

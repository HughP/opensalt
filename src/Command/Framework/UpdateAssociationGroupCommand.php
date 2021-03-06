<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDefAssociationGrouping;

class UpdateAssociationGroupCommand extends BaseCommand
{
    /**
     * @var LsDefAssociationGrouping
     */
    private $associationGrouping;

    public function __construct(LsDefAssociationGrouping $associationGrouping)
    {
        $this->associationGrouping = $associationGrouping;
    }

    public function getAssociationGrouping(): LsDefAssociationGrouping
    {
        return $this->associationGrouping;
    }
}

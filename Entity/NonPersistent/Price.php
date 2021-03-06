<?php

namespace OswisOrg\OswisCalendarBundle\Entity\NonPersistent;

class Price
{
    public ?int $price = null;

    public ?int $deposit = null;

    public function __construct(?int $price = null, ?int $deposit = null)
    {
        $this->price = $price;
        $this->deposit = $deposit;
    }
}

<?php

declare(strict_types=1);

namespace Signalise\Plugin\Model;

use Magento\Framework\DataObject;

class signaliseApiClient
{
    public function pushData($dataObject): void
    {
        //@ todo send data to rest api
       dd($dataObject);
    }
}

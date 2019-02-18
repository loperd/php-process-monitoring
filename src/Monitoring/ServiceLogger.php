<?php declare(strict_types=1);

namespace Renaay\Monitoring;

use Renaay\Monitoring\ServiceCheck;

interface ServiceLogger
{
    public function logServiceEvent(ServiceCheck $service);
    public function logCurrentStatus(ServiceCheck $service);
}
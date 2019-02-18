<?php declare(strict_types=1);

namespace Renaay\Loggers;

use Renaay\Monitoring\ServiceLogger;
use Renaay\Monitoring\ServiceCheck;

class EmailServiceLogger implements ServiceLogger
{
    public function logServiceEvent(ServiceCheck $service) : void
    {
        if ($service->current_status() == ServiceCheck::FAILURE) {
            $message = "We have trouble with service {$service->description()}" . "\r\n";
            
            mail('renaay01@gmail.com', 'Service Event', $message);
            
            if ($service->consecutive_failures() > 5) {
               mail('renaay01@gmail.com', 'Service Event', $message);
            }
        }

        return;
    }

    public function logCurrentStatus(ServiceCheck $service)
    {
        return;
    }
}
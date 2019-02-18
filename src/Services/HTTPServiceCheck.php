<?php declare(strict_types=1);

namespace Renaay\Services;

use Renaay\Monitoring\ServiceCheck;

class HTTPServiceCheck extends ServiceCheck
{
    public $url;

    public function __construct($params)
    {
        foreach($params as $key => $param) {
            $this->$key = $param;
        }
    }

    public function run()
    {
        return ServiceCheck::SUCCESS;
    }
}
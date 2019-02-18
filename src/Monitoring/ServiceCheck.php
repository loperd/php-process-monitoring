<?php declare(strict_types=1);

namespace Renaay\Monitoring;

abstract class ServiceCheck
{
    const FAILURE = 0;
    const SUCCESS = 1;

    protected $timeout = 10;
    protected $next_attempt;
    protected $current_status = self::SUCCESS;
    protected $previous_status = self::SUCCESS;
    protected $frequency = 30;
    protected $description;
    protected $consecutive_failures = 0;
    protected $status_time;
    protected $failure_time;
    protected $loggers = [];

    public abstract function __construct($params);

    public abstract function run();

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
    }

    /**
     * @return void
     */
    public function setNextAttempt() : void
    {
        $this->next_attempt = time() + $this->frequency;
    }

    /**
     * @param $status
     * @return void
     */
    public function postRun($status) : void
    {
        if ($status !== $this->current_status) {
            $this->previous_status = $this->current_status;
        }

        if ($status === self::FAILURE) {
            if ($this->current_status === self::FAILURE) {
                $this->consecutive_failures++;
            } else {
                $this->failure_time = time();
            }
        } else {
            $this->consecutive_failures = 0;
        }
        
        $this->status_time = time();
        $this->current_status = $status;
        $this->logServiceEvent();

        return;
    }

    /**
     * @return void
     */
    public function logCurrentStatus() : void
    {
        foreach ($this->loggers as $logger) {
            $logger->logCurrentStatus($this);
        }
    }

    /**
     * @return void
     */
    public function logServiceEvent() : void
    {
        foreach ($this->loggers as $logger) {
            $logger->logServiceEvent($this);
        }
    }

    /**
     * @param ServiceLogger $logger
     * @return void
     */
    public function registerLogger(ServiceLogger $logger) : void
    {
        $this->loggers[] = $logger;
    }
}
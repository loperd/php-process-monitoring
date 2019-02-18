<?php declare(strict_types=1);

namespace Renaay\Monitoring;

use ReflectionClass;
use Renaay\Loggers\EmailServiceLogger;
use Renaay\Services\HTTPServiceCheck;

class ServiceCheckRunner
{
    private $num_children;
    private $services = [];
    private $children = [];

    /**
     * ServiceCheckRunner constructor.
     * @param $conf
     * @param $num_children
     * @throws \ReflectionException
     */
    public function __construct($conf, $num_children)
    {
        $loggers = [];
        $this->num_children = $num_children;
        $conf = simplexml_load_file($conf);

        foreach ($conf->loggers->logger as $logger) {
            $class = new ReflectionClass((string) $logger->class);
            if ($class->isInstantiable()) {
                $loggers[(string) $logger->id] = $class->newInstance();
            } else {
                fputs(STDERR, "Невозможно создать объект класса {$logger->class}.\n");

                exit;
            }
        }
        foreach ($conf->services->service as $service) {
            $class = new ReflectionClass((string) $service->class);
            if ($class->isInstantiable()) {
                $item = $class->newInstance((array) $service->params);
                foreach($service->loggers->logger as $logger) {
                    $item->registerLogger($loggers[(string) $logger]);
                }
                $this->services[] = $item;
            } else {
                fputs(STDERR, "Объект класса {$service->class} не создается.\n");

                exit;
            }
        }
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    private function nextAttemptSort($a, $b)
    {
        if ($a->nextAttempt() == $b->nextAttempt()) {
            return 0;
        }

        return ($a->nextAttempt() < $b->nextAttempt()) ? -1 : 1;
    }

    /**
     * @return mixed
     */
    private function next()
    {
        usort($this->services, [$this, 'nextAttemptSort']);
        return $this->services[0];
    }

    /**
     * @return void
     */
    public function loop()
    {
        declare(ticks=1);
        pcntl_signal(SIGCHLD, array($this, "sigChild"));
        pcntl_signal(SIGUSR1, array($this, "sigUsr1"));

        while(true) {
            $now = time();

            if (count($this->children) < $this->num_children) {
                $service = $this->next();
                if ($now < $service->nextAttempt()) {
                    sleep(1);
                    continue;
                }

                $service->setNextAttempt();
                if ($pid = pcntl_fork()) {
                    $this->children[$pid] = $service;
                } else {
                    pcntl_alarm((int) $service->timeout());
                    exit($service->run());

//                    exit($service->timeout());
                }
            }
        }
    }

    /**
     * @return void
     */
    public function logCurrentStatus()
    {
        foreach($this->services as $service) {
            $service->logCurrentStatus();
        }
    }

    /**
     * @return void
     */
    private function sigChild()
    {
        $status = \Renaay\Monitoring\ServiceCheck::FAILURE;
        pcntl_signal(SIGCHLD, [$this, "sigChild"]);
        while(($pid = pcntl_wait($status, WNOHANG)) > 0) {
            $service = $this->children[$pid];
            unset($this->children[$pid]);
            if (pcntl_wifexited($status) && pcntl_wexitstatus($status) == ServiceCheck::SUCCESS) {
                $status = ServiceCheck::SUCCESS;
            }
            $service->postRun($status);
        }
    }

    /**
     * @return void
     */
    private function sigUsr1() : void
    {
        pcntl_signal(SIGALRM, [$this, "sigUsr1"]);
        $this->logCurrentStatus();
    }
}
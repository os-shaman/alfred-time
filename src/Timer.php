<?php

namespace AlfredTime;

use AlfredTime\Toggl;
use AlfredTime\Config;
use AlfredTime\Harvest;

class Timer
{
    /**
     * @var mixed
     */
    private $config;

    /**
     * @var mixed
     */
    private $harvest;

    /**
     * @var mixed
     */
    private $toggl;

    /**
     * @param Config $config
     */
    public function __construct(Config $config, Toggl $toggl, Harvest $harvest)
    {
        $this->config = $config;
        $this->harvest = $harvest;
        $this->toggl = $toggl;
    }

    /**
     * @param  array   $timerData
     * @return mixed
     */
    public function delete(array $timerData = [])
    {
        $res = [];

        if (empty($timerData) === true) {
            return [];
        }

        foreach ($timerData as $service => $id) {
            $res[$service] = $this->deleteServiceTimer($service, $id);
        }

        return $res;
    }

    /**
     * @param  $service
     * @param  $timerId
     * @return boolean
     */
    public function deleteServiceTimer($service, $timerId)
    {
        if ($this->$service->deleteTimer($timerId) === false) {
            return false;
        }

        if ($timerId === $this->getProperty($service . '_id')) {
            $this->updateProperty($service . '_id', null);
            $this->updateProperty('is_running', false);
        }

        return true;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->getProperty('description');
    }

    /**
     * @return mixed
     */
    public function getPrimaryService()
    {
        return $this->getProperty('primary_service');
    }

    /**
     * @return mixed
     */
    public function isRunning()
    {
        return $this->getProperty('is_running');
    }

    public function reinitializeTimerIds()
    {
        foreach ($this->config->activatedServices() as $service) {
            $this->updateProperty($service . '_id', null);
        }
    }

    /**
     * @param  $description
     * @param  array              $projectData
     * @param  array              $tagData
     * @param  $specificService
     * @return array
     */
    public function start($description = '', array $projectData = [], array $tagData = [], array $services = [])
    {
        $res = [];
        $oneServiceStarted = false;

        if (empty($services) === true) {
            return [];
        }

        $this->reinitializeTimerIds();

        foreach ($services as $service) {
            $timerId = $this->$service->startTimer(
                $description,
                $projectData[$service],
                $tagData[$service]
            );
            $res[$service] = $timerId;
            $this->updateProperty($service . '_id', $timerId);
            $oneServiceStarted = $oneServiceStarted || ($timerId !== null);
        }

        if ($oneServiceStarted === true) {
            $this->updateProperty('description', $description);
            $this->updateProperty('is_running', true);
        }

        return $res;
    }

    /**
     * @return mixed
     */
    public function stop()
    {
        $res = [];
        $oneServiceStopped = false;

        foreach ($this->config->runningServices() as $service) {
            $timerId = $this->getProperty($service . '_id');
            $res[$service] = $this->$service->stopTimer($timerId);
            $oneServiceStopped = $oneServiceStopped || $res[$service];
        }

        if ($oneServiceStopped === true) {
            $this->updateProperty('is_running', false);
        }

        return $res;
    }

    /**
     * @return string
     */
    public function undo()
    {
        $timerData = [];

        foreach ($this->config->runningServices() as $service) {
            $timerData[$service] = $this->getProperty($service . '_id');
        }

        return $this->delete($timerData);
    }

    /**
     * @param $name
     * @param $value
     */
    public function updateProperty($name, $value)
    {
        $this->config->update('timer', $name, $value);
    }

    /**
     * @param  $name
     * @return mixed
     */
    private function getProperty($name)
    {
        return $this->config->get('timer', $name);
    }
}

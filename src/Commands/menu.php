<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use AlfredTime\Time;
use Alfred\Workflows\Workflow;
use AlfredTime\Config;

$workflow = new Workflow();
$config = new Config(getenv('alfred_workflow_data') . '/config.json');
$time = new Time($config);

$query = trim($argv[1]);

/*
 * Check for config file
 * If cannot find, Workflow is not usable
 */
if ($config->isConfigured() === false) {
    $workflow->result()
        ->uid('')
        ->arg('config')
        ->title('No config file found')
        ->subtitle('Generate and edit the config file')
        ->type('default')
        ->valid(true);
} else {
    if ($query === 'config') {
        $workflow->result()
            ->uid('')
            ->arg('edit')
            ->title('Edit config file')
            ->subtitle('Open the config file in your favorite editor!')
            ->type('default')
            ->valid(true);
    } elseif ($query === 'sync') {
        $workflow->result()
            ->uid('')
            ->arg('sync')
            ->title('Sync projects and tags from online to local cache')
            ->subtitle('Update local projects and tags data')
            ->type('default')
            ->valid(true);
    } elseif ($query === 'undo') {
        $servicesToUndo = $config->servicesToUndo();

        if (empty($servicesToUndo) === true) {
            $workflow->result()
                ->uid('')
                ->arg('')
                ->title('Undo ""')
                ->subtitle('Nothing to undo!')
                ->type('default')
                ->valid(false);
        } else {
            $subtitle = $config->hasTimerRunning() === true ? 'Stop and delete current timer for ' : 'Delete timer for ';
            $subtitle .= implode(' and ', array_map('ucfirst', $servicesToUndo));

            $workflow->result()
                ->uid('')
                ->arg('undo')
                ->title('Undo "' . $config->getTimerDescription() . '"')
                ->subtitle($subtitle)
                ->type('default')
                ->valid(true);
        }
    } elseif ($query === 'delete') {
        $workflow->result()
            ->uid('')
            ->arg('delete')
            ->title('Delete a timer')
            ->subtitle('Press enter to load recent timers list')
            ->type('default')
            ->valid(true);
    } elseif ($query === 'continue') {
        $workflow->result()
            ->uid('')
            ->arg('continue')
            ->title('Continue a timer')
            ->subtitle('Press enter to load the list of recent timers')
            ->type('default')
            ->valid(true);
    } elseif ($config->hasTimerRunning() === false) {
        $services = $config->implementedServicesForFeature('start');

        if (empty($services) === true) {
            $subtitle = 'No timer services activated. Edit config file to active services';
        } else {
            $subtitle = 'Start new timer for ' . implode(' and ', array_map('ucfirst', $services));
        }

        $workflow->result()
            ->uid('')
            ->arg('start ' . $query)
            ->title('Start "' . $query . '"')
            ->subtitle($subtitle)
            ->type('default')
            ->mod('cmd', $subtitle . ' and Harvest with default project and tags', 'start_default ' . $query)
            ->mod('alt', 'Continue timer for Toggl and Harvest ("' . $config->getTimerDescription() . '") with default project and tags', 'start_default ' . $config->getTimerDescription())
            ->valid(true);
    } else {
        $services = $config->activatedServices();

        if (empty($services) === true) {
            $subtitle = 'No timer services activated. Edit config file to active services';
        } else {
            $subtitle = 'Stop current timer for ' . implode(' and ', array_map('ucfirst', $services));
        }

        $workflow->result()
            ->uid('')
            ->arg('stop')
            ->title('Stop "' . $config->getTimerDescription() . '"')
            ->subtitle($subtitle)
            ->type('default')
            ->valid(true);
    }
}

echo $workflow->output();

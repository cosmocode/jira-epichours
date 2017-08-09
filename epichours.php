#!/usr/bin/php
<?php

require 'vendor/autoload.php';

class EpicHours extends AbstractCLI
{

    /**
     * Register options and arguments on the given $options object
     *
     * @param \splitbrain\phpcli\Options $options
     * @return void
     */
    protected function setup(\splitbrain\phpcli\Options $options)
    {
        $options->setHelp('Summarize worklogs for Jira Epics');
        $options->registerArgument('project', 'The three letter code of the Jira Project');
    }

    /**
     * Your main program
     *
     * Arguments and options have been parsed when this is run
     *
     * @param \splitbrain\phpcli\Options $options
     * @return void
     */
    protected function main(\splitbrain\phpcli\Options $options)
    {
        $this->loadCredentials();

        $args = $options->getArgs();
        echo "Hours\tDays\tEpic\tSummary\n";
        foreach ($args as $project) {
            $this->printWorklogs($project);
        }
    }

    /**
     * Print the work logs for the given project
     */
    protected function printWorklogs($project)
    {
        $epics = $this->jiraApi('/rest/api/latest/search/', "project = $project AND type = Epic ORDER BY key");
        foreach ($epics['issues'] as $epic) {
            $ticket = $epic['key'];

            $time = 0;
            $issues = $this->jiraApi('/rest/api/latest/search/?maxResults=1000', "project = $project AND \"Epic Link\" = $ticket");
            foreach ($issues['issues'] as $issue) {
                $time += $issue['fields']['aggregatetimespent'];
            }
            $hours = $time / 3600;

            echo round($hours, 2);
            echo "\t";
            echo round($hours / 8, 2);
            echo "\t";
            echo $ticket;
            echo "\t";
            echo $epic['fields']['summary'];
            echo "\n";
        }
    }
}

$cli = new EpicHours();
$cli->run();
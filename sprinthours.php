#!/usr/bin/php
<?php

require 'vendor/autoload.php';

class SprintHours extends AbstractCLI
{


    /**
     * Register options and arguments on the given $options object
     *
     * @param \splitbrain\phpcli\Options $options
     * @return void
     */
    protected function setup(\splitbrain\phpcli\Options $options)
    {
        $options->setHelp('Print Issues in the given sprint with their logged work hours');
        $options->registerArgument('sprints...', 'The ID of the sprints');
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

        $sprints = $options->getArgs();
        $sprints = array_map('intval', $sprints);
        $this->printIssues($sprints);
    }

    /**
     * Print the issues for the given sprints
     *
     * @param int[] $sprints
     */
    protected function printIssues($sprints)
    {
        echo "Status\tType\tSpID\tSprint\tHours\tDays\tIssue\tSummary\n";

        $sprints = join(',', $sprints);
        $issues = $this->jiraApi('/rest/api/latest/search/?maxResults=1000', "sprint IN ($sprints)");
        foreach ($issues['issues'] as $issue) {
            #print_r($issue);

            $type = $issue['fields']['issuetype']['name'];
            $status = $issue['fields']['status']['name'];
            $ticket = $issue['key'];
            $title = $issue['fields']['summary'];
            $time = $issue['fields']['aggregatetimespent'];
            $hours = $hours = $time / 3600;
            $sprint = $this->findSprint($issue['fields']);

            echo $status;
            echo "\t";
            echo $type;
            echo "\t";
            echo $sprint['id'];
            echo "\t";
            echo $sprint['name'];
            echo "\t";
            echo round($hours, 2);
            echo "\t";
            echo round($hours / 8, 2);
            echo "\t";
            echo $ticket;
            echo "\t";
            echo $title;
            echo "\n";
        }
    }

    /**
     * Find sprint info in list of fields
     *
     * @param $fields
     * @return array
     */
    protected function findSprint($fields)
    {
        $sprint = array();

        foreach ($fields as $key => $val) {
            if (substr($key, 0, 12) != 'customfield_') continue;
            if (!is_array($val) || !isset($val[0]) || !is_string($val[0])) continue;
            if (!preg_match('/^com.atlassian.greenhopper.service.sprint.Sprint/', $val[0])) continue;

            $data = explode('[', substr($val[0], 0, -1));
            $data = explode(',', $data[1]);
            foreach ($data as $line) {
                list($k, $v) = explode('=', $line);
                $sprint[$k] = $v;
            }
            break;
        }

        return $sprint;
    }

}

$cli = new SprintHours();
$cli->run();
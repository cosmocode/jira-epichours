#!/usr/bin/php
<?php

require 'vendor/autoload.php';

class IssueTable extends AbstractCLI
{


    /**
     * Register options and arguments on the given $options object
     *
     * @param \splitbrain\phpcli\Options $options
     * @return void
     */
    protected function setup(\splitbrain\phpcli\Options $options)
    {
        $options->setHelp('Print Issues as DokuWiki table');
        $options->registerOption('columns', 'Additional empty columns for each item', 'c', 'number');
        $options->registerArgument('epics...', 'The Issue IDs of the epics the tickets belong to');
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

        $cols = (int)$options->getOpt('columns', 6);
        printf("^  % -48s ||", 'Issue');

        $now = date('W');
        $max = $this->maxWeek();
        for ($i = 0; $i < $cols; $i++) {
            $week = $now + $i;
            if($week > $max) $week = $week - $max;
            printf('  % 3s  |', 'W' . $week);
        }
        echo "\n";

        $args = $options->getArgs();
        foreach ($args as $epic) {
            list($project) = explode('-', $epic);
            $this->printIssues($project, $epic, $cols);
        }
    }

    /**
     * Print the issues for the given project and epic
     *
     * @param string $project
     * @param string $epic
     * @param int $cols
     */
    protected function printIssues($project, $epic, $cols)
    {

        $issues = $this->jiraApi('/rest/api/latest/search/', "project = $project AND resolution = Unresolved AND \"Epic Link\" = $epic ORDER BY key");
        foreach ($issues['issues'] as $issue) {
            $ticket = $issue['key'];
            $title = $issue['fields']['summary'];

            printf("^  % 16s ^ % -30s |", "[[jira>$ticket]]", $title);
            for ($i = 0; $i < $cols; $i++) printf('  % 3s  |', '');
            echo "\n";
        }
    }

    /**
     * Get the week number of the last week in the year
     *
     * @link https://stackoverflow.com/a/21480444/172068
     * @return int
     */
    protected function maxWeek() {
        return (int)  (new DateTime('December 28th'))->format('W');
    }
}

$cli = new IssueTable();
$cli->run();
#!/usr/bin/php
<?php

require 'vendor/autoload.php';

class SprintList extends AbstractCLI
{


    /**
     * Register options and arguments on the given $options object
     *
     * @param \splitbrain\phpcli\Options $options
     * @return void
     */
    protected function setup(\splitbrain\phpcli\Options $options)
    {
        $options->setHelp('Print Issues in the given sprint by assignee as DokuWiki list');
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
        $data = array();

        $sprints = join(',', $sprints);
        $issues = $this->jiraApi('/rest/api/latest/search/', "resolution = Unresolved AND sprint IN ($sprints)");
        foreach ($issues['issues'] as $issue) {
            $ticket = $issue['key'];
            $title = $issue['fields']['summary'];
            $user = $issue['fields']['assignee']['displayName'];

            if(!isset($data[$user])) $data[$user] = array();
            $data[$user][$ticket] = $title;
        }

        foreach($data as $user => $info) {
            echo "  * $user\n";
            foreach($info as $ticket => $title) {
                echo "    - [[jira>$ticket]] $title\n";
            }
        }
        echo "\n";
    }


}

$cli = new SprintList();
$cli->run();
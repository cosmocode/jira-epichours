#!/usr/bin/php
<?php

require 'vendor/autoload.php';

class IssueTable extends \splitbrain\phpcli\CLI
{

    protected $user;
    protected $pass;
    protected $jira;

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
        for ($i = 0; $i < $cols; $i++) {
            printf('  % 3s  |', 'W' . ($now + $i));
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
     * Loads the credentials from an ini file
     */
    protected function loadCredentials()
    {
        $creds = __DIR__ . '/epichours.ini';

        if (!file_exists($creds)) throw new \RuntimeException('Credentials not found at ' . $creds);
        $ini = parse_ini_file($creds, false);

        $this->jira = trim($ini['jira']);
        $this->user = trim($ini['user']);
        $this->pass = trim($ini['pass']);
    }


    /**
     * Run a Jira API query
     *
     * @param string $endpoint
     * @param string $jql
     * @return mixed
     */
    protected function jiraApi($endpoint, $jql)
    {
        $url = $this->jira . $endpoint;

        $options = array(
            'auth' => $this->user . ':' . $this->pass,
            'header' => array(
                'Content-Type' => 'application/json'
            )
        );

        $response = \EasyRequest\Client::request($url, 'GET', $options)
            ->withQuery(array('jql' => $jql))
            ->send();

        if ($response->getStatusCode() > 299) {
            throw new \RuntimeException(
                'Status ' . $response->getStatusCode() . " GET\n" .
                $url . "\n" .
                $response->getBody()->getContents(),
                $response->getStatusCode()
            );
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}

$cli = new IssueTable();
$cli->run();
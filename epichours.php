<?php

require 'vendor/autoload.php';

class EpicHours extends \splitbrain\phpcli\CLI {

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
    protected function printWorklogs($project) {
        $epics = $this->jiraApi('/rest/api/latest/search/', "project = $project AND type = Epic ORDER BY key");
        foreach($epics['issues'] as $epic) {
            $ticket = $epic['key'];
            $hours = $this->jiraApi('/rest/tempo-timesheets/3/worklogs/timespent/', "project = $project AND \"Epic Link\" = $ticket");
            echo $hours['hours'];
            echo "\t";
            echo round($hours['hours']/8, 2);
            echo "\t";
            echo $ticket;
            echo "\t";
            echo $epic['fields']['summary'];
            echo "\n";
        }
    }

    /**
     * Loads the credentials from an ini file
     */
    protected function loadCredentials() {
        $creds = __DIR__ . '/epichours.ini';

        if(!file_exists($creds)) throw new \RuntimeException('Credentials not found at '.$creds);
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
    protected function jiraApi($endpoint, $jql) {
        $url = $this->jira . $endpoint;

        $options = array(
            'auth' => $this->user.':'.$this->pass,
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

$cli = new EpicHours();
$cli->run();
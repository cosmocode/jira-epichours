<?php

abstract class AbstractCLI extends \splitbrain\phpcli\CLI {
    protected $user;
    protected $pass;
    protected $jira;

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
<?php

abstract class AbstractCLI extends \splitbrain\phpcli\CLI
{
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
     * Run jiraquery and return all pages
     *
     * @param $endpoint
     * @param $jql
     * @return mixed
     */
    protected function jiraApi($endpoint, $jql)
    {
        $result = array();
        $startAt = 0;
        do {

            $data = $this->jiraCall($endpoint, $jql, $startAt);
            $result = array_merge_recursive($result, $data);

            $total = $data['total'];
            $startAt += $data['maxResults'];
        } while ($startAt < $total);

        return $result;
    }

    /**
     * Run a Jira API query
     *
     * @param string $endpoint
     * @param string $jql
     * @param int $from
     * @return mixed
     */
    protected function jiraCall($endpoint, $jql, $from = 0)
    {
        $url = $this->jira . $endpoint;

        $options = array(
            'auth' => $this->user . ':' . $this->pass,
            'header' => array(
                'Content-Type' => 'application/json'
            )
        );

        $response = \EasyRequest\Client::request($url, 'GET', $options)
            ->withQuery(array('jql' => $jql, 'startAt' => $from, 'maxResults' => 100))
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
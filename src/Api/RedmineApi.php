<?php

namespace Redify\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Redify\Utils\Helper;

class RedmineApi {

  /**
   * @var string
   */
  protected $url;

  /**
   * @var string
   */
  protected $api_key;

  /**
   * @var Client
   */
  protected $client;

  function __construct($url, $api_key) {
    $this->url = $url;
    $this->api_key = $api_key;

    $this->client = new Client();
  }

  public function getTimeEntriesForUser($updated_after, $user_id, $project_id) {
    $time_entry_url = $this->url . "/time_entries.json?updated_on=>=$updated_after&user_id=$user_id&limit=100&project_id=$project_id";
    try {
      $response = $this->getDataFromApiCall($time_entry_url);
      return $response['time_entries'];
    }
    catch (RequestException $e) {
      echo "Error fetching time entries for user. Reason: {$e->getResponse()}\n";
      return FALSE;
    }
  }

  protected function callApi($url) {
    return $this->client->get($url, [
      'headers' => [
        'X-Redmine-API-Key' => $this->api_key,
      ]
    ]);
  }

  protected function getDataFromApiCall($url) {
    return json_decode($this->callApi($url)->getBody()->getContents(), TRUE);
  }

  public function getRedmineUsers() {
    try {
      $user_filter_url = $this->url . "/users.json?limit=100";
      $response = $this->getDataFromApiCall($user_filter_url);
      return !empty($response) ? $response['users']: NULL;
    }
    catch (RequestException $e) {
      echo "Error fetching users. Reason: {$e->getResponse()}\n";
      return FALSE;
    }
  }

  public function getRedmineProjects() {
    try {
      $projects_url = $this->url . "/projects.json?limit=100";
      $response = $this->getDataFromApiCall($projects_url);
      return !empty($response) ? $response['projects']: NULL;
    }
    catch (RequestException $e) {
      echo "Error fetching projects. Reason: {$e->getResponse()}\n";
      return FALSE;
    }
  }

}

<?php

namespace Redify\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

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

  public function getTimeEntriesForUser($updated_after, $user_id) {
    $time_entry_url = $this->url . "/time_entries.json?updated_on=>=$updated_after&user_id=$user_id&limit=100";
    try {
      $response = $this->callApi($time_entry_url);
      return $response['time_entries'];
    }
    catch (RequestException $e) {
      echo "Error fetching time entries for user. Reason: {$e->getResponse()}\n";
      return FALSE;
    }
  }

  public function getUserByEmail($email) {
    try {
      $user_filter_url = $this->url . "/users.json?name=$email";
      $response = $this->callApi($user_filter_url);
      return !empty($response) ? $response['users'][0] : NULL;
    }
    catch (RequestException $e) {
      echo "Error fetching data for user $email. Reason: {$e->getResponse()}\n";
      return FALSE;
    }
  }

  protected function callApi($url) {
    $response = $this->client->get($url, [
      'headers' => [
        'X-Redmine-API-Key' => $this->api_key,
      ]
    ])->getBody();
    return json_decode($response, TRUE);
  }

  public function getRedmineUsers() {
    try {
      $user_filter_url = $this->url . "/users.json?limit=100";
      $response = $this->callApi($user_filter_url);
      return !empty($response) ? $response['users']: NULL;
    }
    catch (RequestException $e) {
      echo "Error fetching users. Reason: {$e->getResponse()}\n";
      return FALSE;
    }
  }

  public function getRedmineProjects() {
    try {
      $user_filter_url = $this->url . "/projects.json?limit=100";
      $response = $this->callApi($user_filter_url);
      return !empty($response) ? $response['projects']: NULL;
    }
    catch (RequestException $e) {
      echo "Error fetching projects. Reason: {$e->getResponse()}\n";
      return FALSE;
    }
  }

  public function getClockifyProjectId($redmine_project_id) {
    try {
      $url = $this->url . "/projects/{$redmine_project_id}.json";
      $response = $this->callApi($url);
      if (empty($response)) {
        return NULL;
      }

      foreach ($response['custom_fields'] as $custom_field) {
        if ($custom_field['name'] == 'Clockify workspace ID') {
          return $custom_field['value'];
        }
      }

      return NULL;
    }
    catch (RequestException $e) {
      echo "Error fetching Clockify workspace id for project {$redmine_project_id}. Reason: {$e->getResponse()}\n";
      return FALSE;
    }
  }

}
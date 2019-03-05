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

  public function getAllTimeEntries($updated_after) {
    $time_entries = [];
    $page = 1;
    while (!empty($fetched_time_entries = $this->getAllTimeEntriesPage($updated_after, $page))) {
      $page++;
      $time_entries = array_merge($time_entries, $fetched_time_entries);
    }
    return $time_entries;
  }

  public function getAllTimeEntriesPage($updated_after, $page) {
    $time_entry_url = $this->url . "/time_entries.json?updated_on=>=$updated_after&limit=100&page=$page";
    try {
      $response = $this->getDataFromApiCall($time_entry_url);
      return $response['time_entries'];
    }
    catch (RequestException $e) {
      echo "Error fetching time entries. Reason: {$e->getResponse()->getBody()}\n";
      return FALSE;
    }
  }

  public function getTimeEntriesForUser($updated_after, $user_id, $project_id) {
    $time_entries = [];
    $page = 1;
    while (!empty($fetched_time_entries = $this->getTimeEntriesForUserPage($updated_after, $user_id, $project_id, $page))) {
      $page++;
      $time_entries = array_merge($time_entries, $fetched_time_entries);
    }
    return $time_entries;
  }

  public function getTimeEntriesForUserPage($updated_after, $user_id, $project_id, $page) {
    $time_entry_url = $this->url . "/time_entries.json?updated_on=>=$updated_after&user_id=$user_id&limit=100&project_id=$project_id&page=$page";
    try {
      $response = $this->getDataFromApiCall($time_entry_url);
      return $response['time_entries'];
    }
    catch (RequestException $e) {
      echo "Error fetching time entries for user. Reason: {$e->getResponse()->getBody()}\n";
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
    $users = [];
    $page = 1;
    while (!empty($fetched_users = $this->getRedmineUsersPage($page))) {
      $page++;
      $users = array_merge($users, $fetched_users);
    }
    return $users;
  }

  public function getRedmineUsersPage($page) {
    try {
      $user_filter_url = $this->url . "/users.json?limit=100&page=$page";
      $response = $this->getDataFromApiCall($user_filter_url);
      return !empty($response) ? $response['users']: NULL;
    }
    catch (RequestException $e) {
      echo "Error fetching users. Reason: {$e->getResponse()->getBody()}\n";
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
      echo "Error fetching projects. Reason: {$e->getResponse()->getBody()}\n";
      return FALSE;
    }
  }

  public function getProjectData($project_id) {
    try {
      $project_url = $this->url . "/projects/$project_id.json";
      $response = $this->getDataFromApiCall($project_url);
      return !empty($response) ? $response['project']: NULL;
    }
    catch (RequestException $e) {
      echo "Error fetching project $project_id. Reason: {$e->getResponse()->getBody()}\n";
      return FALSE;
    }
  }

  public function getUserData($user_id) {
    try {
      $user_url = $this->url . "/users/$user_id.json";
      $response = $this->getDataFromApiCall($user_url);
      return !empty($response) ? $response['user']: NULL;
    }
    catch (RequestException $e) {
      echo "Error fetching user $user_id. Reason: {$e->getResponse()->getBody()}\n";
      return FALSE;
    }
  }

}

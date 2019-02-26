<?php

class RedmineApi {

  protected $url;

  protected $api_key;

  protected $client;

  function __construct($url, $api_key) {
    $this->url = $url;
    $this->api_key = $api_key;

    $this->client = new GuzzleHttp\Client();
  }

  public function getTimeEntriesForUser($updated_after, $user_id) {
    $time_entry_url = $this->url . "/time_entries.json?updated_on=>=$updated_after&user_id=$user_id&limit=100";
    try {
      $response = $this->callApi($time_entry_url);
      return $response['time_entries'];
    }
    catch (\GuzzleHttp\Exception\RequestException $e) {
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
    catch (\GuzzleHttp\Exception\RequestException $e) {
      echo "Error fetching data for user $email. Reason: {$e->getResponse()}\n";
      return FALSE;
    }
  }

  public function callApi($url) {
    $response = $this->client->get($url, [
      'headers' => [
        'X-Redmine-API-Key' => $this->api_key,
      ]
    ])->getBody();
    return json_decode($response, TRUE);
  }

}
<?php

namespace Redify\Api;

use GuzzleHttp\Client;

class ClockifyApi {

  const BASE_URL = 'https://api.clockify.me/api/workspaces/';

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

  function __construct($workspace_id, $api_key) {
    $this->api_key = $api_key;

    $this->url = static::BASE_URL . $workspace_id . '/';

    $this->client = new Client();
  }

  public function addTimeEntry($project_id, $start_date, $end_date, $description = '') {
    $url = $this->url . 'timeEntries/';
    $data = [
      'billable' => FALSE,
      'start' => $start_date,
      'end' => $end_date,
      'description' => $description,
      'projectId' => $project_id,
    ];
    return $this->apiCall('POST', $url, $data);
  }

  public function updateTimeEntry($time_entry_id, $project_id, $start_date, $end_date, $description = '') {
    $url = $this->url . "timeEntries/$time_entry_id/";
    $data = [
      'billable' => FALSE,
      'start' => $start_date,
      'end' => $end_date,
      'description' => $description,
      'projectId' => $project_id,
    ];
    return $this->apiCall('PUT', $url, $data);
  }

  public function apiCall(string $method, string $url, array $data = NULL) {
    $settings = [
      'headers' => [
        'Content-Type' => 'application/json',
        'X-Api-Key' => $this->api_key,
        'cache-control' => 'no-cache',
      ],
    ];
    if (!empty($data)) {
      $settings['json'] = $data;
    }
    return $this->client->request($method, $url, $settings);
  }

}

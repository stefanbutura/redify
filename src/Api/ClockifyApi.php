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
    $post_url = $this->url . 'timeEntries/';
    $data = [
      'start' => $start_date,
      'end' => $end_date,
      'description' => $description,
      'projectId' => $project_id,
    ];
    $response = $this->client->post($post_url, [
      'json' => $data,
      'headers' => [
        'Content-Type' => 'application/json',
        'X-Api-Key' => $this->api_key,
        'cache-control' => 'no-cache',
      ],
    ]);
    return $response;
  }

  public function updateTimeEntry($time_entry_id, $project_id, $start_date, $end_date, $description = '') {
    $post_url = $this->url . "timeEntries/$time_entry_id/";
    $data = [
      'billable' => FALSE,
      'start' => $start_date,
      'end' => $end_date,
      'description' => $description,
      'projectId' => $project_id,
    ];
    $response = $this->client->put($post_url, [
      'json' => $data,
      'headers' => [
        'Content-Type' => 'application/json',
        'X-Api-Key' => $this->api_key,
        'cache-control' => 'no-cache',
      ],
    ]);
    return $response;
  }
}

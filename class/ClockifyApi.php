<?php

class ClockifyApi {

  protected $url;

  protected $api_key;

  protected $client;

  function __construct($api_key) {
    $this->api_key = $api_key;
    $this->url = 'https://api.clockify.me/api/workspaces/5c3cc4efb079871b774277c2/';

    $this->client = new GuzzleHttp\Client();
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

    $response_contents = json_decode($response->getBody()->getContents(), TRUE);

    return $response_contents['id'];
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
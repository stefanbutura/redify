<?php

namespace Redify\App;

use GuzzleHttp\Exception\RequestException;
use Redify\Api\ClockifyApi;
use Redify\Api\RedmineApi;
use Redify\Database\Database;
use Symfony\Component\Yaml\Yaml;

class Redify {

  /**
   * @var array
   */
  protected $config;

  /**
   * @var Database
   */
  protected $database;

  /**
   * @var string
   */
  protected $workspaceId;

  public function __construct() {
    $this->config = Yaml::parseFile(realpath(getcwd() . '/config.yml'));
    $this->database = new Database([
      'database_type' => $this->config['database']['database_type'],
      'database_name' => $this->config['database']['database_name'],
      'server' => $this->config['database']['host'],
      'username' => $this->config['database']['username'],
      'password' => $this->config['database']['password'],
      'port' => $this->config['database']['port'],
    ]);

    $this->database->createDatabase();
    $this->workspaceId = $this->config['keys']['clockify']['workspace_id'];
  }

  protected function getLastUpdate(): string {
    $updated_after = $this->database->getVariable('last_run');
    if (empty($updated_after)) {
      return date('Y-m-d');
    }
    return $updated_after;
  }

  protected function getTaskDescription(string $task_id, string $comments = NULL): string {
    $description = "#$task_id";
    if (!empty($comments)) {
      $description .= ' - ' . $comments;
    }

    $description .= ' [Redify]';
    return $description;
  }

  protected function getEndDateFromSpentOn(string $spent_on, $duration): string {
    $spent_time = sprintf('%02d:%02d', $duration, fmod($duration, 1) * 60);
    return "{$spent_on}T{$spent_time}:00Z";
  }

  protected function getStartDateFromSpentOn($spent_on) {
    return "{$spent_on}T00:00:00Z";
  }

  protected function createOrUpdateClockifyEntry(string $clockify_key, string $redmine_entry_id, string $project_id, string $start_date, string $end_date, string $description, string $redmine_email) {
    $clockify_api = new ClockifyApi($this->workspaceId, $clockify_key);
    $clockify_entry_id = $this->database->getClockifyId($redmine_entry_id);
    if (!empty($clockify_entry_id)) {
      try {
        $clockify_api->updateTimeEntry($clockify_entry_id, $project_id, $start_date, $end_date, $description);
        return;
      }
      catch (RequestException $e) {
        if ($e->getCode() == 403) {
          // @todo: Log library.
          echo "Could not update redmine time entry {$redmine_entry_id} for user {$redmine_email} because it was probably deleted from their clockify. Trying to recreate...\n";
        }
      }
    }

    try {
      $response = $clockify_api->addTimeEntry($project_id, $start_date, $end_date, $description);
      $clockify_entry_id = json_decode($response->getBody()->getContents(), TRUE)['id'];

      $this->database->insertMapping($redmine_entry_id, $clockify_entry_id);
    }
    catch (RequestException $e) {
      echo "Could not sync time entry $redmine_entry_id for user $redmine_email. Reason: {$e->getResponse()}\n";
    }
  }

  protected function updateLastRunVariable() {
    $this->database->setVariable('last_run', date('Y-m-d'));
  }

  protected function syncUserEntries(RedmineApi $redmine_api, $redmine_email, $redmine_settings, $clockify_key, $updated_after) {
    $user = $redmine_api->getUserByEmail($redmine_email);

    if (empty($user)) {
      return;
    }

    $user_id = $user['id'];
    $time_entries = $redmine_api->getTimeEntriesForUser($updated_after, $user_id);

    if (empty($time_entries)) {
      return;
    }

    foreach ($time_entries as $time_entry) {
      $redmine_entry_id = $time_entry['id'];
      $description = $this->getTaskDescription($time_entry['issue']['id'], $time_entry['comments']);

      $start_date = $this->getStartDateFromSpentOn($time_entry['spent_on']);
      $end_date = $this->getEndDateFromSpentOn($time_entry['spent_on'], $time_entry['hours']);

      $project_id = !empty($redmine_settings['projects'][$time_entry['project']['name']]) ?
        $redmine_settings['projects'][$time_entry['project']['name']] : NULL;

      if (empty($project_id)) {
        continue;
      }

      $this->createOrUpdateClockifyEntry($clockify_key, $redmine_entry_id, $project_id, $start_date, $end_date, $description, $redmine_email);
    }
  }

  public function syncEntries() {
    $updated_after = $this->getLastUpdate();
    foreach ($this->config['keys']['redmine'] as $id => $redmine) {
      $redmine_api = new RedmineApi($redmine['url'], $redmine['api_key']);

      foreach ($this->config['keys']['clockify']['users'] as $redmine_email => $clockify_key) {
        $this->syncUserEntries($redmine_api, $redmine_email, $redmine, $clockify_key, $updated_after);
      }
    }

    $this->updateLastRunVariable();
  }

}

<?php

namespace Redify\App;

use GuzzleHttp\Exception\RequestException;
use Redify\Api\ClockifyApi;
use Redify\Api\RedmineApi;
use Redify\Database\Database;
use Redify\Utils\Helper;
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

  public function __construct() {
    $this->config = Yaml::parseFile(realpath(getcwd() . '/config.yml'));

    $this->database = new Database($this->config['database']);

    $this->database->createDatabase();
  }

  protected function getLastUpdate(): string {
    $updated_after = $this->database->getVariable('last_run');
    if (empty($updated_after)) {
      return date('Y-m-d');
    }
    return $updated_after;
  }

  protected function createOrUpdateClockifyEntry(string $clockify_workspace_id, string $clockify_key, string $redmine_entry_id, string $clockify_project_id, string $start_date, string $end_date, string $description, string $redmine_email) {
    $clockify_api = new ClockifyApi($clockify_workspace_id, $clockify_key);
    $clockify_entry_id = $this->database->getClockifyId($redmine_entry_id);
    if (!empty($clockify_entry_id)) {
      try {
        $clockify_api->updateTimeEntry($clockify_entry_id, $clockify_project_id, $start_date, $end_date, $description);
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
      $response = $clockify_api->addTimeEntry($clockify_project_id, $start_date, $end_date, $description);
      $clockify_entry_id = json_decode($response->getBody()->getContents(), TRUE)['id'];

      $this->database->insertMapping($redmine_entry_id, $clockify_entry_id);
    }
    catch (RequestException $e) {
      echo "Could not sync time entry $redmine_entry_id for user $redmine_email. Reason: {$e->getResponse()->getBody()->getContents()}\n";
    }
  }

  protected function updateLastRunVariable() {
    $this->database->setVariable('last_run', date('Y-m-d'));
  }

  public function syncEntries() {
    $updated_after = $this->getLastUpdate();
    foreach ($this->config['redmine'] as $id => $redmine_settings) {
      $redmine_api = new RedmineApi($redmine_settings['url'], $redmine_settings['api_key']);
      $time_entries = $redmine_api->getAllTimeEntries($updated_after);
      $projects = [];
      $users = [];

      foreach ($time_entries as $time_entry) {
        $project_id = $time_entry['project']['id'];
        $user_id = $time_entry['user']['id'];

        if (empty($projects[$project_id])) {
          $projects[$project_id] = $redmine_api->getProjectData($project_id);
        }

        if (empty($users[$user_id])) {
          $users[$user_id] = $redmine_api->getUserData($user_id);
        }

        $clockify_project_id = Helper::getCustomFieldValue($projects[$project_id], 'Clockify project ID');
        $clockify_workspace_id = Helper::getCustomFieldValue($projects[$project_id], 'Clockify workspace ID');
        $clockify_user_key = Helper::getCustomFieldValue($users[$user_id], 'Clockify key');
        if (empty($clockify_workspace_id) || empty($clockify_project_id) || empty($clockify_user_key)) {
          continue;
        }

        $description = Helper::getDescriptionForClockify($time_entry['issue']['id'], $time_entry['comments']);

        $start_date = Helper::getStartDateFromSpentOn($time_entry['spent_on']);
        $end_date = Helper::getEndDateFromSpentOn($time_entry['spent_on'], $time_entry['hours']);
        $this->createOrUpdateClockifyEntry($clockify_workspace_id, $clockify_user_key, $time_entry['id'], $clockify_project_id, $start_date, $end_date, $description, $users[$user_id]['mail']);
      }
    }
    $this->updateLastRunVariable();
  }

}

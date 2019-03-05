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

  /**
   * @var string
   */
  protected $workspaceId;

  public function __construct() {
    $this->config = Yaml::parseFile(realpath(getcwd() . '/config.yml'));

    $this->workspaceId = $this->config['keys']['clockify']['workspace_id'];

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
      echo "Could not sync time entry $redmine_entry_id for user $redmine_email. Reason: {$e->getResponse()->getBody()}\n";
    }
  }

  protected function updateLastRunVariable() {
    $this->database->setVariable('last_run', date('Y-m-d'));
  }

  protected function syncUserEntries(RedmineApi $redmine_api, $redmine_project_id, $redmine_user_id, $redmine_email, $clockify_key, $clockify_project_id, $updated_after) {
    $time_entries = $redmine_api->getTimeEntriesForUser($updated_after, $redmine_user_id, $redmine_project_id);

    if (empty($time_entries)) {
      return;
    }

    foreach ($time_entries as $time_entry) {
      $redmine_entry_id = $time_entry['id'];

      $description = Helper::getDescriptionForClockify($time_entry['issue']['id'], $time_entry['comments']);

      $start_date = Helper::getStartDateFromSpentOn($time_entry['spent_on']);
      $end_date = Helper::getEndDateFromSpentOn($time_entry['spent_on'], $time_entry['hours']);

      $this->createOrUpdateClockifyEntry($clockify_key, $redmine_entry_id, $clockify_project_id, $start_date, $end_date, $description, $redmine_email);
    }
  }

  public function syncEntries() {
    $updated_after = $this->getLastUpdate();
    foreach ($this->config['keys']['redmine'] as $id => $redmine_settings) {
      $redmine_api = new RedmineApi($redmine_settings['url'], $redmine_settings['api_key']);
      $redmine_users = $redmine_api->getRedmineUsers();

      $redmine_projects = $redmine_api->getRedmineProjects();

      if (empty($redmine_users) || empty($redmine_projects)) {
        return;
      }

      foreach ($redmine_projects as $redmine_project) {
        $clockify_project_id = Helper::getCustomFieldValue($redmine_project, 'Clockify project ID');
        if (empty($clockify_project_id)) {
          continue;
        }

        foreach ($redmine_users as $redmine_user) {
          $redmine_user_id = $redmine_user['id'];
          $redmine_email = $redmine_user['mail'];
          $clockify_key = Helper::getCustomFieldValue($redmine_user, 'Clockify key');
          if (!empty($clockify_key)) {
            $this->syncUserEntries($redmine_api, $redmine_project['id'], $redmine_user_id, $redmine_email, $clockify_key, $clockify_project_id, $updated_after);
          }
        }
      }
    }

    $this->updateLastRunVariable();
  }

}

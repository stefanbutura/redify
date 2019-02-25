<?php

use Symfony\Component\Yaml\Yaml;

class Redify {

  protected $config;
  
  protected $database;

  public function __construct() {
    $this->config = Yaml::parseFile(realpath(__DIR__ . '/../config.yml'));
    $this->database = new Database([
      'database_type' => $this->config['database']['database_type'],
      'database_name' => $this->config['database']['database_name'],
      'server' => $this->config['database']['host'],
      'username' => $this->config['database']['username'],
      'password' => $this->config['database']['password'],
    ]);

    $this->database->createDatabase();
  }

  public function getConfig() {
    return $this->config;
  }

  protected function getLastUpdate() {
    $updated_after = $this->database->getVariable('last_run');
    if (empty($updated_after)) {
      return date('Y-m-d');
    }
    return $updated_after;
  }

  protected function getTaskDescription($task_id, $comments) {
    $description = "#$task_id";
    if (!empty($comments)) {
      $description .= ' - ' . $comments;
    }
    return $description;
  }

  protected function getEndDateFromSpentOn($spent_on, $duration) {
    $spent_time = sprintf('%02d:%02d', $duration, fmod($duration, 1) * 60);
    return "{$spent_on}T{$spent_time}:00Z";
  }

  protected function getStartDateFromSpentOn($spent_on) {
    return "{$spent_on}T00:00:00Z";
  }

  protected function createOrUpdateClockifyEntry($clockify_key, $redmine_entry_id, $project_id, $start_date, $end_date, $description) {
    $clockify_api = new ClockifyApi($clockify_key);
    $clockify_entry_id = $this->database->getClockifyId($redmine_entry_id);
    if (!empty($clockify_entry_id)) {
      $clockify_api->updateTimeEntry($clockify_entry_id, $project_id, $start_date, $end_date, $description);
      return;
    }

    $clockify_api->addTimeEntry($project_id, $start_date, $end_date, $description);
    $this->database->insertMapping($redmine_entry_id, $clockify_entry_id);
  }

  protected function updateLastRunVariable() {
    $this->database->setVariable('last_run', date('Y-m-d'));
  }

  protected function updateTimeEntriesForUser(RedmineApi $redmine_api, $redmine_email, $redmine_settings, $clockify_key, $updated_after) {
    $user = $redmine_api->getUserByEmail($redmine_email);
    $user_id = $user['id'];
    $time_entries = $redmine_api->getTimeEntriesForUser($updated_after, $user_id);
    foreach ($time_entries as $time_entry) {
      $redmine_entry_id = $time_entry['id'];
      $description = $this->getTaskDescription($redmine_entry_id, $time_entry['comments']);

      $start_date = $this->getStartDateFromSpentOn($time_entry['spent_on']);
      $end_date = $this->getEndDateFromSpentOn($time_entry['spent_on'], $time_entry['hours']);

      $project_id = $redmine_settings['projects'][$time_entry['project']['name']];

      $this->createOrUpdateClockifyEntry($clockify_key, $redmine_entry_id, $project_id, $start_date, $end_date, $description);
    }
  }

  public function readTimeEntries() {
    foreach ($this->config['keys']['redmine'] as $id => $redmine) {
      $redmine_api = new RedmineApi($redmine['url'], $redmine['api_key']);
      $updated_after = $this->getLastUpdate();

      foreach ($this->config['keys']['clockify'] as $redmine_email => $clockify_key) {
        $this->updateTimeEntriesForUser($redmine_api, $redmine_email, $redmine, $clockify_key, $updated_after);
      }
    }

    $this->updateLastRunVariable();
  }

}

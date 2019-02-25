<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

spl_autoload_register(function($className) {
  $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
  include_once __DIR__ . '/class/' . $className . '.php';
});

$config = Yaml::parseFile(realpath(__DIR__ . '/config.yml'));

$database = new Database([
  'database_type' => $config['database']['database_type'],
  'database_name' => $config['database']['database_name'],
  'server' => $config['database']['host'],
  'username' => $config['database']['username'],
  'password' => $config['database']['password'],
]);

$database->createDatabase();

foreach ($config['keys']['redmine'] as $id => $redmine) {
  $redmine_api = new RedmineApi($redmine['url'], $redmine['api_key']);
  $updated_after = $database->getVariable('last_run');
  if (empty($updated_after)) {
    $updated_after = date('Y-m-d');
  }

  foreach ($config['keys']['clockify'] as $redmine_email => $clockify_key) {
    $user = $redmine_api->getUserByEmail($redmine_email);
    $user_id = $user['id'];
    $time_entries = $redmine_api->getTimeEntriesForUser($updated_after, $user_id);
    foreach ($time_entries as $time_entry) {
      $redmine_entry_id = $time_entry['id'];
      $description = "#$redmine_entry_id";
      if (!empty($time_entry['comments'])) {
        $description .= ' - ' . $time_entry['comments'];
      }

      $clockify_api = new ClockifyApi($clockify_key);

      $clockify_entry_id = $database->getClockifyId($redmine_entry_id);

      $project_id = $redmine['projects'][$time_entry['project']['name']];
      $start_date = $time_entry['spent_on'];
      $time = $time_entry['hours'];
      $spent_time = sprintf('%02d:%02d', $time, fmod($time, 1) * 60);
      $end_date = "{$start_date}T{$spent_time}:00Z";

      if (!empty($clockify_entry_id)) {
        $clockify_api->updateTimeEntry($clockify_entry_id, $project_id, "{$start_date}T00:00:00Z", $end_date, $description);
        continue;
      }

      $clockify_entry_id = $clockify_api->addTimeEntry($project_id, "{$start_date}T00:00:00Z", $end_date, $description);
      $database->insertMapping($redmine_entry_id, $clockify_entry_id);
    }
  }
}

$database->setVariable('last_run', date('Y-m-d'));

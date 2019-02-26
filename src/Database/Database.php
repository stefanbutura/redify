<?php

namespace Redify\Database;

use Medoo\Medoo;

class Database extends Medoo {

  public function createDatabase() {
    $result = $this->query('SHOW TABLES');
    if (empty($result)) {
      $this->query("CREATE TABLE IF NOT EXISTS time_entry_mapping (
        id INT AUTO_INCREMENT,
        redmine_id VARCHAR(255) NOT NULL,
        clockify_id VARCHAR(255) NOT NULL,
        PRIMARY KEY (id)
      );");

      $this->query("CREATE TABLE IF NOT EXISTS variables (
        id VARCHAR(255),
        value VARCHAR(255) NOT NULL,
        PRIMARY KEY (id)
      );");
    }
  }

  public function getClockifyId($redmine_id) {
    $result = $this->select('time_entry_mapping', 'clockify_id', [
      'redmine_id' => $redmine_id,
    ]);
    return !empty($result) ? $result[0] : NULL;
  }

  public function insertMapping($redmine_id, $clockify_id) {
    if (!empty($this->select('time_entry_mapping', 'redmine_id', [
      'redmine_id' => $redmine_id,
    ]))
    ) {
      $this->update('time_entry_mapping', ['clockify_id' => $clockify_id], [
        'redmine_id' => $redmine_id,
      ]);
      return;
    }

    $this->insert('time_entry_mapping', [
      'redmine_id' => $redmine_id,
      'clockify_id' => $clockify_id,
    ]);
  }

  public function setVariable($variable_id, $value) {
    if (!empty($this->select('variables', 'value', [
      'id' => $variable_id,
    ]))
    ) {
      $this->update('variables', ['value' => $value], [
        'id' => $variable_id,
      ]);
      return;
    }
    $this->insert('variables', [
      'id' => $variable_id,
      'value' => $value,
    ]);
  }

  public function getVariable($variable_id) {
    $result = $this->select('variables', 'value', [
      'id' => $variable_id,
    ]);
    return !empty($result) ? $result[0] : NULL;
  }

}

<?php

namespace Redify\Utils;

class Helper {

  public static function getDescriptionForClockify(string $issue_number, string $time_entry_description = NULL): string {
    $description = "#$issue_number";
    if (!empty($time_entry_description)) {
      $description .= ' - ' . $time_entry_description;
    }

    $description .= ' [Redify]';
    return $description;
  }

  public static function getStartDateFromSpentOn(string $spent_on) {
    return "{$spent_on}T00:00:00Z";
  }

  public static function getEndDateFromSpentOn(string $spent_on, $duration): string {
    $spent_time = sprintf('%02d:%02d', $duration, fmod($duration, 1) * 60);
    return "{$spent_on}T{$spent_time}:00Z";
  }

  public static function getCustomFieldValue(array $redmine_content_data, $field) {
    if (empty($redmine_content_data)) {
      return NULL;
    }
    foreach ($redmine_content_data['custom_fields'] as $custom_field) {
      if ($custom_field['name'] == $field) {
        return $custom_field['value'];
      }
    }
    return NULL;
  }

}

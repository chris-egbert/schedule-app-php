<?php

namespace Spark\Project\Utility;

class Time {

  // Convert from a valid time string to mysql time
  static public function toMysqlTime($timeString) {
    $date = new \DateTime($timeString);
    return $date->format('Y-m-d H:i:s');
  }

  // Convert from a valid time string to RFC2822
  static public function toRFC2822($timeString) {
    $date = new \DateTime($timeString);
    return $date->format(\DateTime::RFC2822);
  }


}
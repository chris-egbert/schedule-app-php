<?php

namespace Spark\Project\Utility;

class Validate {

  static public function timeString($timeString, $format) {

    if (empty($timeString)) {
      return false;
    }

    if (date_create_from_format($format, $timeString) === false) {
      return false;
    }

    return true;
  }

  static public function typeValue($type, $value) {
    switch ($type) {

      case 'string': 
        // Not being very strick here - pretty much everything is going to loosely qualify as a "string"
        return true;
        break;

      case 'date':
        return static::timeString($value, \DateTime::RFC2822);

      case 'id':
      case 'fk':
      case 'int': 
        $var = filter_var($value, FILTER_VALIDATE_INT);
        break;

      case 'float': 
        $var = filter_var($value, FILTER_VALIDATE_FLOAT);
        break;

    }

    if ($var === false) {
      return false;
    } else {
      return true;
    }
  } 

}
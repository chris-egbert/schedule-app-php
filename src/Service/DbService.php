<?php

namespace Spark\Project\Service;

class DbService {

  // Re-Usable PDO DB Instance
  static protected $db;

  // Singleton for getting the PDO DB connector
  static public function getDb() {
    if (!isset(static::$db)) {

      // TODO: Read MySQL data from config
      $dbUser = 'when_i_work';
      $dbPass = 'R1g_M0-8';
      $dbName = 'schedule_app_php';
      $dsn = "mysql:dbname={$dbName};host=127.0.0.1";

      // Init the DB
      try {
        $db = new \PDO($dsn, $dbUser, $dbPass);
        static::$db = $db;
      } catch (PDOException $e) {
        // TODO: Log this exception
        // echo "Error connecting: " . $e->getMessage();
        return false;
      }

    }

    return static::$db;
  }

  // Quote / Proper escaping
  static public function quote($value) {
    if (empty($value)) {
      return "''";
    }
    $quotedValue = '';
    $db = static::getDb();
    if (is_array($value)) {
      $quotedValues = array();
      foreach ($value as $subvalue) {
        $quotedValues[] = $db->quote($subvalue);
      }
      $quotedValue = implode(',', $quotedValues);
    } else {
      $quotedValue = $db->quote($value);
    }
    return $quotedValue;
  }

  // Last Insert ID (For newly created objects)
  static public function lastInsertId() {
    $db = static::getDb();
    if ($db === false) {
      return false;
    }
    return $db->lastInsertId();    
  }

  // Save to a table
  static public function save($table, $data) {
    $db = static::getDb();
    if ($db === false) {
      return false;
    }

    // Loop through the data to create a set statement
    $setArray = array();
    foreach ($data as $key => $value) {
      $setArray[] = "`{$key}` = " . (!empty($value) ? $db->quote($value) : "''");
    }

    $set = implode(',', $setArray);
    $sql = "INSERT INTO {$table} SET {$set} ON DUPLICATE KEY UPDATE {$set}";
    if ($db->query($sql)) {
      return true;
    } else {
      return false;
    } 
  }


  // Delete from table
  static public function delete($table, $id) {
    if (empty($table)) {
      return false;
    }

    if (empty($id)) {
      return false;
    }

    $db = static::getDb();
    if ($db === false) {
      return false;
    }

    $sql = "DELETE FROM {$table} WHERE id='{$id}'";
    if ($db->query($sql)) {
      return true;
    } else {
      return false;
    }
  }

  // Search table
  static public function search($table, $params = array()) {
    $db = static::getDb();
    if ($db === false) {
      return false;
    }

    $criteria = array();
    foreach ($params as $key => $value) {
      switch ($key) {

        // Responsibility for proper escaping falls upstream
        case '_custom':
          $criteria[] = '(' . $value . ')';
          break;

        // Use the key as field for search
        default:
          $criteria[] = "`{$key}` IN (" . (!empty($value) ? $db->quote($value) : "''") . ")";
          break;
      }
    }

    if (empty($criteria)) {
      $criteria[] = "1=1";
    } 

    $where = implode(" AND ", $criteria);
    $sql = "SELECT * FROM {$table} WHERE {$where}";
    // echo $sql . "\n";
    // $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    return $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
  }

}
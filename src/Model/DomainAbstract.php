<?php

namespace Spark\Project\Model;

use Spark\Adr\DomainInterface;
use Spark\Payload;
use Zend\Diactoros\ServerRequestFactory;
use Spark\Project\Service\DbService;
use Spark\Project\Service\PermissionService;
use Spark\Project\Utility\Validate;
use Spark\Project\Utility\Time;


abstract class DomainAbstract implements DomainInterface
{
    // Property Definitions and values
    protected $_props = array();

    // Corresponding DB table 
    static protected $_table;

    // Provided Input
    protected $input;

    /**
     * Route entry point
     */
    public function __invoke(array $input)
    {

        // Something strange going on - there's no return on PUT data from Zend.
        $putData = file_get_contents("php://input", true);
        $putVars = array();      
        parse_str($putData, $putVars);
        $input = array_replace($input, $putVars);

        // Store the input for future use
        $this->input = $input;

        // There's probably a better way to map the incoming requests to the target methods
        $rawParams = ServerRequestFactory::fromGlobals();
        $results = $this->routeByMethod($rawParams->getMethod());

        $payloadStatus = Payload::OK;
        if (!empty($results['error_msg'])) {
            $payloadStatus = Payload::ERROR;
        }

        return (new Payload)
            ->withStatus($payloadStatus)
            ->withOutput($results);
    }

    // Internal Route by HTTP Method - Optionally override in subclass or override the called methods
    protected function routeByMethod($httpMethod) {

        switch($httpMethod) {
            case 'DELETE':
              if (!PermissionService::hasWritePermission($this->input)) {
                return array('result' => 'failure', 'error_msg' => "Permission Denied");
              }
              $results = $this->delete($this->input);
              break;

            case 'PUT':
            case 'POST':
              if (!PermissionService::hasWritePermission($this->input)) {
                return array('result' => 'failure', 'error_msg' => "Permission Denied");
              }
              $results = $this->save($this->input);
              break;

            // GET
            default:
              if (!$this->hasSearchPermission($this->input)) {
                return array('result' => 'failure', 'error_msg' => "Permission Denied");
              }
              $results = $this->search($this->input);
              break;
        }
        return $results;
    }

    // Do we have search permission - Opportunity to override by subclass
    public function hasSearchPermission($input) {
      return true;
    }

    // Init this class
    public function __construct() {
      // Subclass should override this method to set $this->_props
    } 

    // Utilize utilize magic methods for getting / setting our properties
    public function __get($prop) {
      if (isset($this->_props[$prop])) {
        return $this->_props[$prop];
      }

      return null;
    }

    public function __set($prop, $value) {
      if (isset($this->_props[$prop])) {
        $this->_props[$prop]['value'] = $value;
      } else {
        return false;
      }
      return true;
    }

    // Initialize
    public function initWithData($params = array()) {

      // If there's already an ID, lookup the existing values
      if (isset($params['id']) && !empty($params['id'])) {
         $results = $this->search(array('id' => $params['id']));
         if (!empty($results)) {
           $result = array_shift($results);
           foreach ($result as $field => $value) {
             $this->$field = $value;
           }
           // Conver the mysql times to rfc2822
           $this->convertTimesToRFC2822();
         }
      }

      // echo print_r($params, true) . "\n";

      // Apply the values from the request
      foreach ($params as $field => $value) {
        $this->$field = $value;
      }
    }

    // Key Values
    public function keyValues() {
      $keyValues = array();
      foreach ($this->_props as $key => $value) {
        $keyValues[$key] = $value['value'];
      }
      return $keyValues;
    }

    // Create / Update
    public function save($params = array()) {

        // Initialized with provided params
        $this->initWithData($params);

        // Opportunity for subclasses to override initialized values prior to save
        if ($this->postInitCallback() === false) {
          return array('result' => 'failure', 'error_msg' => "Generic Error");
        }

        // Make sure that the current values are valid; Subclasses can override / append
        if ($this->validateValues() === false) {
          $invalidValues = array();
          foreach ($this->_props as $prop => $setting) {
            if ($setting['valid'] == false) {
              $invalidValues[] = array($prop => $setting['value']);
            }
          }
          return array('result' => 'failure', 'error_msg' => "Input did not satisfy validation", 'invalid_fields' => $invalidValues);
        }

        // Convert to MySQL timestamp format
        $this->convertTimesToMySql();       

        // Save 
        $result = DbService::save(static::$_table, $this->keyValues());
        if ($result === false) {
          return array('result' => 'false', 'error_msg' => 'Generic Save Error');
        }

        // Assign the resulting ID as necessary
        $id = $this->id;
        if (empty($this->id)) {
          $this->id = DbService::lastInsertId();
        }

        // Convert back from mysql time
        $this->convertTimesToRFC2822();

        // Return the properties
        return array($this->keyValues());
    }

    // Opportunity for subclasses to override values after init prior to save
    protected function postInitCallback() {
      return true;
    }

    // Convert times to mysql
    protected function convertTimesToMySql() {
      foreach ($this->_props as $prop => $settings) {
        if ($settings['type'] == 'date') {
          $this->_props[$prop]['value'] = Time::toMysqlTime($settings['value']);
        }
      }
    }

    // Convert times to RFC2822
    protected function convertTimesToRFC2822() {
      foreach ($this->_props as $prop => $settings) {
        if ($settings['type'] == 'date') {
          $this->_props[$prop]['value'] = Time::toRFC2822($settings['value']);
        }
      }
    }

    // Delete
    public function delete($params = array()) {
      if (isset($params['id'])) {
        $result = DbService::delete(static::$_table, $params['id']);
      }
      if ($result) {
        $response = array('result' => 'success');
      } else {
        $response = array('result' => 'failure', 'error_msg' => "Generic Save Error");
      }

      return $response;
    }

    // Search
    public function search($params = array()) {
      $searchParams = array();
      foreach ($params as $key => $value) {
        if (($key == '_custom') || in_array($key, array_keys($this->_props))) {
          $searchParams[$key] = $value;
        }
      }

      return $this->searchConvertTimes(DbService::search(static::$_table, $searchParams));
    }

    // Convert Search Result times to RFC2822
    public function searchConvertTimes($rows) {
      foreach ($rows as $index => $row) {
        foreach ($row as $field => $value) {
          if ($this->_props[$field]['type'] == 'date') {
            $rows[$index][$field] = Time::toRFC2822($value);
          }
        }
      }
      return $rows;
    }

    // Validate Values
    protected function validateValues() {
      $valid = true;
      foreach ($this->_props as $prop => $data) {

        if (
          (!empty($data['value']) && !Validate::typeValue($data['type'], $data['value'])) ||
          (empty($data['value']) && isset($data['required']) && $data['required'])
        )
        {
          $this->_props[$prop]['valid'] = false;
          $valid = false;
          continue;
        } 

        $this->_props[$prop]['valid'] = true;

      }

      return $valid;
    }
    
}

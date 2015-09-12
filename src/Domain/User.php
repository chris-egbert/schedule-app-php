<?php

namespace Spark\Project\Domain;

use Spark\Adr\DomainInterface;
use Spark\Payload;
use Zend\Diactoros\ServerRequestFactory;
use Spark\Project\Service\DbService;
use Spark\Project\Model\DomainAbstract;

class User extends DomainAbstract
{
    // Use this table - user
    static protected $_table = 'user';

    // Init this class
    public function __construct() {

      // Properties for a user
      $this->_props = array(
        'id' => array('type' => 'id', 'value' => ''),
        'name' => array('type' => 'string', 'value' => ''),
        'role' => array('type' => 'string', 'value' => ''),
        'email' => array('type' => 'string', 'value' => ''),
        'phone' => array('type' => 'string', 'value' => ''),
        'created_at' => array('type' => 'date', 'value' => ''),
        'updated_at' => array('type' => 'date', 'value' => ''),
      );

    } 

    // Extra validation for this class
    protected function validateValues() {

      // Run basic type validation
      $valid = parent::validateValues();

      // If it's already not valid, no use in continuing
      if (!$valid) {
        return $valid;
      }

      // Role must be employee or manager
      $role = $this->role;
      if (!in_array($role, array('employee', 'manager'))) {
        $valid = false;
        $this->_props['role']['valid'] = false;
      }

      // At least one piece of contact info is required
      $phone = $this->phone;
      $email = $this->email;
      if (empty($email) && empty($phone)) {
        $valid = false;
        $this->_props['email']['valid'] = false;
        $this->_props['phone']['valid'] = false;
      }

      return $valid;
    }
    
}

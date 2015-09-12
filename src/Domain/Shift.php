<?php

namespace Spark\Project\Domain;

use Spark\Adr\DomainInterface;
use Spark\Payload;
use Zend\Diactoros\ServerRequestFactory;
use Spark\Project\Service\DbService;
use Spark\Project\Model\DomainAbstract;

class Shift extends DomainAbstract
{
    // Use this table - shift
    static protected $_table = 'shift';

    // Init this class
    public function __construct() {

      // Properties for a shift
      $this->_props = array(
        'id' => array('type' => 'id', 'value' => ''),
        'manager_id' => array('type' => 'fk', 'value' => ''),
        'employee_id' => array('type' => 'fk', 'value' => ''),
        'break' => array('type' => 'float', 'value' => ''),
        'start_time' => array('type' => 'date', 'value' => '', 'required' => true),
        'end_time' => array('type' => 'date', 'value' => '', 'required' => true),
        'created_at' => array('type' => 'date', 'value' => ''),
        'updated_at' => array('type' => 'date', 'value' => ''),
      );

    }

    // Update the created_at / updated_at times
    protected function postInitCallback() {

      // Update the created and updated times
      $now = new \DateTime();
      $createdAt = $this->created_at;
      if (empty($createdAt)) {
        $this->created_at = $now->format(\DateTime::RFC2822);
      }
      $this->updated_at = $now->format(\DateTime::RFC2822);

      // Check for a manager_id
      $managerId = $this->manager_id;
      if (empty($managerId)) {
        $this->manager_id = $this->input['auth_id'];
      }

      return true;
    }
    
}

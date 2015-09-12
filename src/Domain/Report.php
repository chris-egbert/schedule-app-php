<?php

namespace Spark\Project\Domain;

use Spark\Adr\DomainInterface;
use Spark\Payload;
use Zend\Diactoros\ServerRequestFactory;
use Spark\Project\Service\DbService;
use Spark\Project\Service\PermissionService;
use Spark\Project\Utility\Time;
use Spark\Project\Domain\Shift;

class Report implements DomainInterface {

    // Provided Input
    protected $input;

    /**
     * Route entry point
     */
    public function __invoke(array $input)
    {
        $this->input = $input;


        $results = array('error_msg' => "You do not have permission");
        switch ($this->input['report']) {

            case 'concurrent-employees':
                if (PermissionService::isSelfRequest($input, $input['employee_id']) || PermissionService::isManager($input)) {
                  $results = $this->concurrentEmployees();
                }
                break;

            case 'hours-worked-by-week':
                if (PermissionService::isSelfRequest($input, $input['employee_id']) || PermissionService::isManager($input)) {
                  $results = $this->hoursWorkedByWeek();
                }
                break;

            case 'manager-contact-info':
                if (PermissionService::isSelfRequest($input, $input['employee_id']) || PermissionService::isManager($input)) {
                  $results = $this->managerContactInfo();
                }
                break;

            case 'shifts-within-timeframe':
                if (PermissionService::isManager($input)) {
                  $results = $this->shiftsWithinTimeframe();
                }
                break;

            default:
                break;
        }

        $payloadStatus = Payload::OK;
	if (!empty($results['error_msg'])) {
            $payloadStatus = Payload::ERROR;
        }

        return (new Payload)
            ->withStatus($payloadStatus)
            ->withOutput($results);
    }

    /**
     * Get Employees working same time period as Me
     */
    protected function concurrentEmployees() {

      if (empty($this->input['start_time'])) {
        $response = array('error_msg' => 'No Start Time Provided');
        return $response;
      }      

      if (empty($this->input['end_time'])) {
        $response = array('error_msg' => 'No End Time Provided');
        return $response;
      }

      if (empty($this->input['employee_id'])) {
        $response = array('error_msg' => 'No Employee ID Provided');
        return $response;
      }

      // Get shifts for this employee
      $db = DbService::getDb();
      $startTime = $db->quote(Time::toMysqlTime($this->input['start_time']));
      $endTime = $db->quote(Time::toMysqlTime($this->input['end_time']));
      $criteria = array('employee_id' => $this->input['employee_id'], '_custom' => "(start_time BETWEEN {$startTime} AND {$endTime}) OR (end_time BETWEEN {$startTime} AND {$endTime})");

      $shift = new Shift();
      $shiftData = $shift->search($criteria);

      $employeeIds = array();
      foreach ($shiftData as $data) {
        $otherShiftData = $this->shiftsWithinTimeframe($data['start_time'], $data['end_time']);
        foreach ($otherShiftData as $odata) {
          if (!in_array($odata['employee_id'], $employeeIds) && ($odata['employee_id'] != $this->input['employee_id'])) {
            $employeeIds[] = $odata['employee_id'];
          }
        }
      }

      $sql = "SELECT name, email, phone FROM user WHERE id IN (" . DbService::quote($employeeIds) . ") GROUP BY id";
      $db = DbService::getDb();
      return $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Hours per week for this employee
     */
    protected function hoursWorkedByWeek() {
      if (empty($this->input['employee_id'])) {
        $response = array('error_msg' => 'No Employee ID Provided');
        return $response;
      }
      $db = DbService::getDb();
      $sql = "SELECT WEEK(start_time) as 'week_of_the_year', SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time)))/3600 as hours FROM shift WHERE employee_id = " . $db->quote($this->input['employee_id']) . " GROUP BY 1";
      return $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Manager Contact Info for this employee
     */
    protected function managerContactInfo() {
      if (empty($this->input['employee_id'])) {
        $response = array('error_msg' => 'No Employee ID Provided');
        return $response;
      }
      $db = DbService::getDb();
      $sql = "SELECT mgr.name, mgr.email, mgr.phone FROM shift LEFT JOIN user mgr ON (mgr.id = shift.manager_id) WHERE employee_id = " . $db->quote($this->input['employee_id']) . " GROUP BY mgr.id";
      return $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Shifts within this timeframe
     */
    protected function shiftsWithinTimeframe($overrideStart = null, $overrideEnd = null) {
      $inputStartTime = (isset($overrideStart) ? Time::toMysqlTime($overrideStart) : Time::toMysqlTime($this->input['start_time']));
      $inputEndTime = (isset($overrideEnd) ? Time::toMysqlTime($overrideEnd) : Time::toMysqlTime($this->input['end_time']));

      if (empty($inputStartTime)) {
        $response = array('error_msg' => 'No Start Time Provided');
        return $response;
      }      

      if (empty($inputEndTime)) {
        $response = array('error_msg' => 'No End Time Provided');
        return $response;
      }

      $db = DbService::getDb();
      $startTime = $db->quote($inputStartTime);
      $endTime = $db->quote($inputEndTime);
      $criteria = array('_custom' => "(start_time BETWEEN {$startTime} AND {$endTime}) OR (end_time BETWEEN {$startTime} AND {$endTime})");
      $shift = new Shift();
      return $shift->search($criteria);
    }

}
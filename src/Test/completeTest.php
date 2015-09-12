<?php

// Default to EST
date_default_timezone_set('America/New_York');

// Run cases
$runCases = array(
  // 1, // Employee - Shift assigned to me
  // 2, // Employee - Employees working same shift as me
  // 3, // Summary of hours worked
  // 4, // Manager Contact Info
  // 5, // Create a Shift
  // 6, // Get Shifts by timeframe
  // 7, // Put Shift times
  // 8, // Put Shift Employee
  // 9, // Manager - Get Employee  
);

// Test Cases
$tc = array();
$i = 0;

// Base params for all API requests
$baseParams = array(
  'url' => 'localhost:8000/',
  'auth_role' => '',
  'auth_id' => '',
  'method' => '',
  'data' => '',
);

// -- Employee
$employeeParams = $baseParams;
$employeeParams['auth_role'] = 'employee';
$employeeParams['auth_id'] = '2';

// Get Shifts assigned to me
$i++;
$tc[$i] = array();
$tc[$i]['description'] = "Employee - Get Shifts Assigned to Me";
$tc[$i]['params'] = $employeeParams;
$tc[$i]['params']['method'] = 'get';
$tc[$i]['params']['url'] .= 'shift';
$tc[$i]['params']['data'] = array(
  'employee_id' => $employeeParams['auth_id'],
);

// Get Employees working during the same time period as me
$i++;
$tc[$i] = array();
$tc[$i]['description'] = "Employee - Get Employees working same time period as Me";
$tc[$i]['params'] = $employeeParams;
$tc[$i]['params']['method'] = 'get';
$tc[$i]['params']['url'] .= 'report/concurrent-employees';
$tc[$i]['params']['data'] = array(
  'employee_id' => $employeeParams['auth_id'],
  'start_time' => dateHelper("2015-10-01 00:00:00"),
  'end_time' => dateHelper("2015-10-31 23:59:59")
);

// Get Summary of hours worked by week
$i++;
$tc[$i] = array();
$tc[$i]['description'] = "Employee - Get Summary of hours worked by week";
$tc[$i]['params'] = $employeeParams;
$tc[$i]['params']['method'] = 'get';
$tc[$i]['params']['url'] .= 'report/hours-worked-by-week';
$tc[$i]['params']['data'] = array(
  'employee_id' => $employeeParams['auth_id'],
);

// Get Manager contact info for my shifts
$i++;
$tc[$i] = array();
$tc[$i]['description'] = "Employee - Get Manager contact info for my shifts";
$tc[$i]['params'] = $employeeParams;
$tc[$i]['params']['method'] = 'get';
$tc[$i]['params']['url'] .= 'report/manager-contact-info';
$tc[$i]['params']['data'] = array(
  'employee_id' => $employeeParams['auth_id'],
);


// -- Manager
$managerParams = $baseParams;
$managerParams['auth_role'] = 'manager';
$managerParams['auth_id'] = '1';

// Post shift for employee
$i++;
$tc[$i] = array();
$tc[$i]['description'] = "Manager - Post Shift for employee";
$tc[$i]['params'] = $managerParams;
$tc[$i]['params']['method'] = 'post';
$tc[$i]['params']['url'] .= 'shift';
$tc[$i]['params']['data'] = array(
  'manager_id' => $managerParams['auth_id'],
  'employee_id' => $employeeParams['auth_id'],
  'break' => '5.0',
  'start_time' => dateHelper("2015-10-15 09:00:00"),
  'end_time' => dateHelper("2015-10-15 17:00:00")
);

// Get Shifts by time frame
$i++;
$tc[$i] = array();
$tc[$i]['description'] = "Manager - Shifts by timeframe";
$tc[$i]['params'] = $managerParams;
// $tc[$i]['params'] = $employeeParams;
$tc[$i]['params']['method'] = 'get';
$tc[$i]['params']['url'] .= 'report/shifts-within-timeframe';
$tc[$i]['params']['data'] = array(
  'start_time' => dateHelper("2015-10-01 00:00:00"),
  'end_time' => dateHelper("2015-10-31 23:59:59")
);

// Put Shift with time start / stop change
$i++;
$tc[$i] = array();
$tc[$i]['description'] = "Manager - Put shift with time start / stop";
$tc[$i]['params'] = $managerParams;
$tc[$i]['params']['method'] = 'put';
$tc[$i]['params']['url'] .= 'shift/1';
$tc[$i]['params']['data'] = array(
   'start_time' => dateHelper("2015-10-15 11:00:00"),
   'end_time' => dateHelper("2015-10-15 15:00:00")
);


// Put Shift change employee
$i++;
$tc[$i] = array();
$tc[$i]['description'] = "Manager - Put shift change employee";
$tc[$i]['params'] = $managerParams;
$tc[$i]['params']['method'] = 'put';
$tc[$i]['params']['url'] .= 'shift/1';
$tc[$i]['params']['data'] = array(
  'employee_id' => '3',
);

// Get Employee
$i++;
$tc[$i] = array();
$tc[$i]['description'] = "Manager - Get Employee";
$tc[$i]['params'] = $managerParams;
$tc[$i]['params']['method'] = 'get';
$tc[$i]['params']['url'] .= 'user/1';
$tc[$i]['params']['data'] = array();

// Run test cases
foreach ($tc as $index => $settings) {
  if (empty($runCases) || in_array($index, $runCases)) {
    echo "Running test case: " . $settings['description'] . "...\n";
    echo print_r(apiRequest($settings['params']), true) . "\n";
  }
}


// Convert a mysql style date to RFC2822 per for the format request
function dateHelper($mysqlDate) {
  $date = date_create_from_format('Y-m-d H:i:s', $mysqlDate);
  return date_format($date, DateTime::RFC2822);
}

// Submit an API request with CURL
function apiRequest($params = array()) {

  $authRole = $params['auth_role'];
  $authId = $params['auth_id'];

  $method = $params['method'];
  $data = $params['data']; // Object / App Data

  $postData = $data;

  $url = $params['url'];
  $url .= "?auth_role=" . $authRole . "&auth_id=" . $authId;

  $ch = curl_init();

  // Method specific settings
  switch ($method) {

    case 'get':
      if (!empty($data)) {
        $url .= '&' . http_build_query($data);
      }
      break;

    case 'delete':
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
      break;

    case 'put':
      // curl_setopt($ch, CURLOPT_PUT, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
      // curl_setopt($ch, CURLOPT_HEADER, false);
      // curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json',"OAuth-Token: $token"));
      break;

    case 'post':
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
      break;
  }

  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  $data = curl_exec($ch);
  return $data;

}
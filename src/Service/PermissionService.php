<?php

namespace Spark\Project\Service;

class PermissionService {

  // Extremely trivial permission class

  // Does this request / user have write permission?
  static public function hasWritePermission($input) {
    if (isset($input['auth_role']) && $input['auth_role'] == 'manager') {
      return true;
    }
    return false;
  }

  // Is this user a manager
  static public function isManager($input) {
    if (isset($input['auth_role']) && $input['auth_role'] == 'manager') {
      return true;
    }
    return false;
  }

  // Is this an employee who is requesting info about themselves?
  static public function isSelfRequest($input, $employeeId) {
    if (isset($input['auth_role']) && ($input['auth_role'] == 'employee') && ($input['auth_id'] == $employeeId)) {
      return true;
    }
    return false;
  }

}
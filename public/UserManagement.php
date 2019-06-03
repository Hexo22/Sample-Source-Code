<?php
// Redirect Exceptions rather than displaying the error message here because this is a non-single page app and so there may have already been echos to the page.
try {
    require_once('../userManagement/init.php');
    $app = new UserManagementApp;
} catch (UserManagementAccessException $e) {
    header("Location: ".getSiteURL()."UserManagementError.php?ErrorCode=2");
} catch (Exception $e) {
    log_exception($e, 'UserManagement.php');
    header("Location: ".getSiteURL()."UserManagementError.php?ErrorCode=1");
}
?>
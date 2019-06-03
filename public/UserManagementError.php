<?php
require_once('../config/getGlobalVariables.php');
// We don't provide details of the error to the user to prevent hackers.
if($_GET['ErrorCode'] == '1') {
    echo "An error has occured. We apologise for the inconvenience. We will begin looking into this immediately and contact you when this issue is resolved.";
} elseif($_GET['ErrorCode'] == '2') {
    echo "There has been an error. Your link may have expired.<br><a href='".getSiteURL()."'>Click here</a> to return to the login page and try again.<br>Contact support@originapplication.com for further help.";
}
?>
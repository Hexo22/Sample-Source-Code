<?php

header('Content-Type: text/html; charset=utf-8');

require_once('misc/loggedInUserClass.php'); // This must go before the below as the below call 'session_start' and this class must be declared before any 'session_start's.

require_once(__DIR__.'../../config/init.php');

require_once('core/UserManagementApp.php');
require_once('core/UserManagementController.php');
require_once('misc/IsUserLoggedIn.php');
require_once('misc/UsefulFunctions.php');

require_once(__DIR__.'../../systemTools/ValidatePermissionsDummyForSystemToolsThatProvidesFullAccess.php');
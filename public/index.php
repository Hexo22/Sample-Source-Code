<?php

require_once('../userManagement/misc/IsUserLoggedIn.php');
require_once('../config/getGlobalVariables.php');
if(!isUserLoggedIn('Yes')) { 
    header("Location: ".getSiteURL()."UserManagement.php?Controller=LogInAndOut&Method=login");
} else {
    header("Location: ".getSiteURL()."UserManagement.php?Controller=PostLoginMenu&Method=postLoginMenu");
}
die();
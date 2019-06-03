<?php
require_once('loggedInUserClass.php'); // This must go before the below as the below call 'session_start' and this class must be declared before any 'session_start's.

require_once(__DIR__.'../../../config/init.php');
function isUserLoggedIn($UpdateDateTimeLastActiveYesOrNo) {
    if(!isset($_SESSION)) {
        session_start();
    }
    
    if($UpdateDateTimeLastActiveYesOrNo != 'Yes' && $UpdateDateTimeLastActiveYesOrNo != 'No') {
        throw new Exception('$UpdateDateTimeLastActiveYesOrNo not set.');   
    }
    
    if(isset($_SESSION["LoggedInUser"]) && is_object($_SESSION["LoggedInUser"])) {
        $LoggedInUser = $_SESSION["LoggedInUser"];
    }

    if(!isset($LoggedInUser) || $LoggedInUser == NULL) {
        return false;
    } else {
        // Automatically log the user out if they have been inactive for one hour.
        if(date("Y-m-d H:i:s") < date('Y-m-d H:i:s', strtotime($LoggedInUser->DateTimeLastActive . ' +1 hour'))) {
            
            // Check that the logged in user is in fact a valid user and that they are still active (because if have just been removed from the system then want to immidiately block them).

            $InteractWithPersistentStorage = InteractWithPersistentStorageFactoryForMySQL::create();
            
            $Columns[0] = 'ID';
            $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'ID';
            $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
            $Where['Groups'][0]['Conditions'][0]['Value'] = $LoggedInUser->UserDetails['ID'];
            $Where['Groups'][0]['Conditions'][1]['FieldName'] = 'Password';
            $Where['Groups'][0]['Conditions'][1]['Comparison'] = '=';
            $Where['Groups'][0]['Conditions'][1]['Value'] = $LoggedInUser->UserDetails['Password'];
            $Where['Groups'][0]['Conditions'][2]['FieldName'] = 'Active';
            $Where['Groups'][0]['Conditions'][2]['Comparison'] = '=';
            $Where['Groups'][0]['Conditions'][2]['Value'] = '1';
            $Results = $InteractWithPersistentStorage->row_read('Master.Users', $Columns, $Where);

            if(sizeof($Results) > 0) {
                if($UpdateDateTimeLastActiveYesOrNo == 'Yes') {
                    $LoggedInUser->updateDateTimeLastActive();
                }
                return true;
            } else {
                $LoggedInUser->logUserOut();
                return false;
            }
        } else {
            // Automatically log the user out if they have been inactive for one hour.
            $LoggedInUser->logUserOut();
            return false;
        }
    }
}
?>
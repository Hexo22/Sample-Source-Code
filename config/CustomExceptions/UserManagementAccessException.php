<?php

// Used for example when somebody tries to request a new forgotten password but passed through the wrong activation key, therefore it's not a coding error (and is probably hackers or bots) and so there is no need to log the exception as a coding exception.

class UserManagementAccessException extends Exception {
    public function __construct($AccessMessage, $code = 0, Exception $previous = null) {
        $this->log($AccessMessage);
        parent::__construct($AccessMessage, $code, $previous);
    }
    
    private function log($AccessMessage) {
        $log_path = getLogPath();
        $log_file = 'user_management_access_exception.txt';
        $log_msg = $AccessMessage . " at " . date('Y-m-d H:i:s') . ".\n\n";

        logMessageIfProductionOrPrintIfDevelopment($log_path, $log_file, $log_msg, 'No');
    }
}
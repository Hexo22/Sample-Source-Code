<?php

// As a last line of defence, I catch all uncaught exceptions and simply log them. This should never happen though (as all exceptions should be caught and dealt with properly) so if I ever catch an exception here then I should re-write the code to fix the issue, i.e. it should be caught somewhere.
set_exception_handler('uncaught_exception_handler');

// 'set_error_handler' turns non-fatal PHP errors into exceptions so that they can then be passed back into the scripts and handled appropropriately (i.e. display message to the user and log them) as I do for all crictical exceptions.
set_error_handler('error_handler');

// 'set_error_handler' does not catch fatal PHP errors so I use 'register_shutdown_function' to catch those fatal PHP errors in order to log them and send an email alert to me. I do not display an alert to the user as fatal errors will kill the app entirely anyway.
register_shutdown_function('logFatalErrorsOnShutdown');

function uncaught_exception_handler($exception) {
    log_exception($exception, 'uncaught_exception_handler');
}

function error_handler($errno, $errstr, $errfile, $errline)  { 
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline); 
}

function logFatalErrorsOnShutdown() {
    $error = error_get_last();
    // All errors not caught by set_error_handler() as defined here http://php.net/manual/en/function.set-error-handler.php
    if($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_CORE_WARNING || $error['type'] === E_COMPILE_ERROR || $error['type'] === E_COMPILE_WARNING || $error['type'] === E_STRICT) {
        $log_path = getLogPath();
        $log_file = 'fatal_errors.txt';
        $log_msg = date('Y-m-d H:i:s') . "\nMessage : " . $error['message'] . "\nFile : " . $error['file'] . "\nLine : " . $error['line'] . "\n\n";
        logMessageIfProductionOrPrintIfDevelopment($log_path, $log_file, $log_msg, 'Yes');
    }
}

function log_exception($e, $LocationOfExceptionCatch) {    
    $log_msg = getExceptionLogMessage($e, $LocationOfExceptionCatch);
    
    $log_path = getLogPath();
    $log_file = 'exceptions.txt';
    
    logMessageIfProductionOrPrintIfDevelopment($log_path, $log_file, $log_msg, 'Yes');
}

function getExceptionLogMessage($e, $LocationOfExceptionCatch) {
    // Need to fill in the user id below.
    $log_msg = date("[Y-m-d H:i:s]") . "\n" ;
        
    if(isset($_SESSION["LoggedInUser"])) {
        $log_msg .= "User ID: " . $_SESSION["LoggedInUser"]->UserDetails['ID'] . "\n";
    }
    
    if(isset($_POST)) {
        $log_msg .= "Posted Data: " . print_r($_POST, true) . "\n";
    }
    
    if(isset($_SERVER['HTTP_HOST'])) { // Incase from a CRON job. I check 'HTTP_HOST' isset in public/index.php for the website so I don't have to worry about it not being set from there.
        $log_msg .= "URL Of Exception Catch: " . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "\n";
    }
        
    $log_msg .= "Location Of Exception Catch: " . $LocationOfExceptionCatch . "\n" .
        "Message: " . $e->getMessage() . "\n" .
        "Code: " . $e->getCode() . "\n" .
        "File: " . $e->getFile() . "\n" .
        "Line: " . $e->getLine() . "\n\n";

    // For the 'ErrorException' we do not display the trace. This is because there is an a fifth argument passed to the 'error_handler' function called 'errcontext' that is displayed in the trace and causes a recursive/circular trace and so it doesn't display properly.
    $ExceptionType = get_class($e);
    if($ExceptionType != 'ErrorException') {
        $Environment = getEnvironment();
        if($Environment == 'Development') {
            // Use this for in development for non-single page apps as it makes it easier to read.
            $log_msg .= "Trace: <pre>" . print_r($e->getTrace(), true) . "</pre>\n<br>\n<br>";
        } elseif($Environment == 'Production') {
            $log_msg .= "Trace: " . var_export($e->getTrace(), true) . "\n\n";
        }
    }
    
    return $log_msg;
}

function getLogPath() {
    global $ServerVariables;
    return $ServerVariables['LogPath'];
}

function getEnvironment() {
    global $ServerVariables;
    return $ServerVariables['ProductionOrDevelopment'];
}

function logMessageIfProductionOrPrintIfDevelopment($log_path, $log_file, $log_msg, $emailAlert) {
    $Environment = getEnvironment();
        
    if($Environment == 'Development') {
        print_message($log_path, $log_file, $log_msg);
    } elseif($Environment == 'Production') {
        log_message($log_path, $log_file, $log_msg, $emailAlert);
    }
}

function print_message($log_path, $log_file, $log_msg) {
    echo "<br><br>".$log_path.$log_file."<br>";
    echo $log_msg;
}

function log_message($log_path, $log_file, $log_msg, $emailAlert) {
    try {
        $File = new File;
        $File->appendTextToFile($log_path, $log_file, $log_msg);
    }
    catch (Exception $e) {
        $log_msg = 'File class is not working\n\nLog Path : ' . $log_path . '\nLog File : ' . $log_file . '\nLog Message : ' . $log_msg;
        $Subject = 'Origin - DateTime : '.date('Y-m-d H:i:s');
        new Email('support@originapplication.com', $log_msg, $Subject);
    }

    if($emailAlert == 'Yes') {
        $Subject = 'Origin - DateTime : '.date('Y-m-d H:i:s');
        new Email('support@originapplication.com', 'Message added to '.$log_file.' file.', $Subject);
    }
}
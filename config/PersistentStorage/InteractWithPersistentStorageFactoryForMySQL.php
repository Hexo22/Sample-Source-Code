<?php

if(!isset($_SESSION)) {
   session_start();
}

$_SESSION["Action_ID"] = produceAnAction_ID();

require_once(__DIR__.'../../../../../ServerVariables.php');

function produceAnAction_ID()
{
    return date('YmdHis').rand();
}

class AppBuildersToInteractWithPersistentStorageFactoryForMySQL
{
    public static function create($Account_ID)
    {
        $InteractWithPersistentStorage = InteractWithPersistentStorageFactoryForMySQL::create();
        return new AppBuildersToInteractWithPersistentStorage($InteractWithPersistentStorage, $Account_ID);
    }
}

class AppToInteractWithPersistentStorageAndValidatePermissionsFactoryForMySQL
{
    public static function create($AppName)
    {
        // I have to use a single $InteractWithPersistentStorage for both $ValidatePermissions and $AppToInteractWithPersistentStorage because otherwise if the entire process is wrapped in a transaction then the checks in $ValidatePermissions will not be accurate as they will not be accounting for changes made via a different $InteractWithPersistentStorage in $AppToInteractWithPersistentStorage.
        
        $InteractWithPersistentStorage = InteractWithPersistentStorageFactoryForMySQL::create();
        $ValidatePermissions = new ValidatePermissions($InteractWithPersistentStorage, $AppName);
        $AppToInteractWithPersistentStorage = new AppToInteractWithPersistentStorage($InteractWithPersistentStorage, $ValidatePermissions, $AppName);
        
        $Response['ValidatePermissions'] = $ValidatePermissions;
        $Response['AppToInteractWithPersistentStorage'] = $AppToInteractWithPersistentStorage;
        return $Response;
    }
}

class InteractWithPersistentStorageFactoryForMySQL
{
    public static function create()
    {
        $MySQLFunctions = MySQLFunctionsFactory::create();
        return new InteractWithPersistentStorage($MySQLFunctions);
    }
}

class MySQLFunctionsFactory
{
    public static function create($AppendGlobalDatabasePrefix = 'Yes')
    {
        global $ServerVariables;

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Tell MySQLi to throw Exceptions
        try {
            $MySQLi = new MySQLi($ServerVariables['host'], $ServerVariables['user'], $ServerVariables['pass'], '');
        } catch (Exception $e) {
            throw new Exception('Could not connect to database.\n\nError Code : ' . $e->getCode() . ' Error Message : ' . $e->getMessage());
        }
        $MySQLi->set_charset('utf8');
        return new MySQLFunctions($MySQLi, $AppendGlobalDatabasePrefix);
    }
}
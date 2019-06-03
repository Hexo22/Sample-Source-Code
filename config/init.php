<?php

header("Cache-Control: public");
error_reporting(E_ALL);
date_default_timezone_set('Europe/London');

require_once('getGlobalVariables.php');
require_once('ExceptionHandling.php');

require_once('PersistentStorage/InteractWithPersistentStorage.php');
require_once('PersistentStorage/InteractWithPersistentStorageFactoryForMySQL.php');
require_once('PersistentStorage/PersistentStorageFunctions.php');
require_once('PersistentStorage/PersistentStorageFunctions/MySQLFunctions.php');

require_once('UsefulPHPClassesAndFunctions/EmailClass.php');
require_once('UsefulPHPClassesAndFunctions/FileClass.php');
require_once('UsefulPHPClassesAndFunctions/InputValidationFunctions.php');
require_once('UsefulPHPClassesAndFunctions/UsefulRenderingFunctions.php');

require_once('CustomExceptions/AppStructureIntegrityException.php');
require_once('CustomExceptions/EmailException.php');
require_once('CustomExceptions/InputValidationException.php');
require_once('CustomExceptions/InvalidParamException.php');
require_once('CustomExceptions/UserManagementAccessException.php');
require_once('CustomExceptions/UserValidationException.php');
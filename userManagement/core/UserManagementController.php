<?php

class UserManagementController {
    protected $InteractWithPersistentStorage;
    protected $UsersModel; // I only set this for methods that require it so as to save resources.
    
    public function __construct(InteractWithPersistentStorage $InteractWithPersistentStorage) {
        $this->InteractWithPersistentStorage = $InteractWithPersistentStorage;
    }
    
    public function model($model, $Variables) {
        $model = $model . '_model';
        require_once(__DIR__.'/../models/'. $model .'.php');
        return new $model($this->InteractWithPersistentStorage, $Variables);
    }
    
    public function view($viewName, $data, $errors) {        
        require(__DIR__.'/../views/template.php'); // Must be require not require_once as we often call multiple views multiple times for one page.
    }
    
    protected function getUserIDAndSecureKey($Type) {
        if(!isset($_GET["User_ID"]) || !isset($_GET["SecureKey"])) {
            throw new UserManagementAccessException('No User_ID or Secure Key passed through.');
        }
        $UserIDAndSecureKey['User_ID'] = $_GET["User_ID"];
        $UserIDAndSecureKey['SecureKey'] = $_GET["SecureKey"];
        $this->checkIsValidUserIDAndSecureKey($UserIDAndSecureKey, $Type);
        return $UserIDAndSecureKey;
    }
    
    protected function checkIsValidUserIDAndSecureKey($UserIDAndSecureKey, $Type) {            
        $this->validateUserID($UserIDAndSecureKey['User_ID']);
        $this->validateActionStillLive($UserIDAndSecureKey['User_ID'], $Type);
        $this->validateSecureKey($UserIDAndSecureKey, $Type);
    }
    
    protected function validateUserID($User_ID) {
        if($User_ID == '' || $User_ID == '0') {
            throw new UserManagementAccessException('Incorrect User ID.');
        }
        $this->UsersModel->checkThisUserExists_AndThrowUserManagementAccessExceptionIfNot($User_ID);
    }
    
    protected function validateActionStillLive($User_ID, $Type) {
        $this->ifCheckingAnAccountActivationSecureKeyButUserIsAlreadyActivatedThenRedirectToAccountActivatedPage($User_ID, $Type);
        $this->ifCheckingAnAccountBlockedSecureKeyButUserIsAlreadyUnblockedThenRedirectToLoginPage($User_ID, $Type);
    }
    
    protected function ifCheckingAnAccountActivationSecureKeyButUserIsAlreadyActivatedThenRedirectToAccountActivatedPage($User_ID, $Type) {
        if($Type == 'AccountActivation') {
            $User = $this->UsersModel->getUserRecordForThisUserID($User_ID);
            if($User['Active'] == 1) {
                header("Location: ".getSiteURL()."");
                die();
            }
        }
    }
    
    protected function ifCheckingAnAccountBlockedSecureKeyButUserIsAlreadyUnblockedThenRedirectToLoginPage($User_ID, $Type) {
        if($Type == 'AccountBlocked') {
            $User = $this->UsersModel->getUserRecordForThisUserID($User_ID);
            if($User['NumberOfFailedSignInAttempts'] == 0) {
                header("Location: ".getSiteURL()."");
                die();
            }
        }
    }
    
    protected function validateSecureKey($UserIDAndSecureKey, $Type) {
        if($UserIDAndSecureKey['SecureKey'] == '' || $UserIDAndSecureKey['SecureKey'] == '0') {
            throw new UserManagementAccessException('Incorrect Secure Key.');
        }
        
        $SecureKeyRecord = $this->UsersModel->getSecureKeyRecord($UserIDAndSecureKey['User_ID'], $Type);
        
        if($SecureKeyRecord == 'NoRecord') {
            throw new UserManagementAccessException('Incorrect Secure Key.');
        }
        
        if($UserIDAndSecureKey['SecureKey'] != $SecureKeyRecord['SecureKey']) {
            $this->UsersModel->incrementNumberOfFailedSecureKeyAttemptsByOne($SecureKeyRecord['ID']);
            throw new UserManagementAccessException('Incorrect Secure Key.');
        }
    }
    
    protected function validatePasswordAndReturnHashedPassword() {
        if(!isset($_POST["password"]) || $_POST["password"] == '') {
            array_push($this->Errors, 'No password set.');
        }
        if(!isset($_POST["passwordc"]) || $_POST["passwordc"] == '') {
            array_push($this->Errors, 'No confirmed password set.');
        }
            
        $Password = trim($_POST["password"]);
        $ConfirmPassword = trim($_POST["passwordc"]);
        
        if(minMaxRange(8, 50, $Password) && minMaxRange(8, 50, $ConfirmPassword)) {
            array_push($this->Errors, 'Your password must be no fewer than 8 characters or greater than 50.');
        } elseif($Password != $ConfirmPassword) {
            array_push($this->Errors, 'Passwords must match.');
        } else {
            return generateHash($Password);
        }
    }
}
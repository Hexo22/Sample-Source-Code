<?php

class ForgotPassword_controller extends UserManagementController {     
    protected $Errors = array();
    
    public function requestForgotPassword($Errors) {
        $this->view('ForgotPassword/requestForgotPassword', '', $Errors);
    }
    
    public function requestForgotPasswordSubmit() {
        $this->UsersModel = $this->model('Users', '');
        $User_ID = $this->getUserIDForThisEmail();
        if(empty($this->Errors)) {
            $SecureKey = $this->UsersModel->createSecureKey($User_ID, 'ForgottenPassword');
            $this->sendRequestForgotPasswordEmail($User_ID, $SecureKey);
            $this->view('ForgotPassword/requestForgotPasswordSubmit', '', '');
        } else {
            $this->requestForgotPassword($this->Errors);
        }
    }
    
    public function forgotPassword($Errors) {
        $this->UsersModel = $this->model('Users', '');
        $UserIDAndSecureKey = $this->getUserIDAndSecureKey('ForgottenPassword');
        
        $data['Email'] = $this->UsersModel->getEmailAddressForThisUserID($UserIDAndSecureKey['User_ID']);
        $data['User_ID'] = $UserIDAndSecureKey['User_ID'];
        $data['SecureKey'] = $UserIDAndSecureKey['SecureKey'];
        $this->view('ForgotPassword/forgotPassword', $data, $Errors);
    }

    public function forgotPasswordSubmit() {
        $this->UsersModel = $this->model('Users', '');
        $UserIDAndSecureKey = $this->getUserIDAndSecureKey('ForgottenPassword');
        $this->updatePassword($UserIDAndSecureKey);
    }
    
    private function getUserIDForThisEmail() {
        if(!isset($_POST["email"])) {
            throw new UserManagementAccessException('No email passed through.');
        }
        
        $Email = $_POST["email"];
        
        $Users = $this->UsersModel->getUsersWithThisEmailAddress($Email);
        
        if(sizeof($Users) == 0) {            
            array_push($this->Errors, 'Incorrect Email Address.');
        } elseif(sizeof($Users) == 1) {
            return $Users[0]['ID'];
        } elseif(sizeof($Users) > 1) {
            throw new Exception('There should only be one user account per email address.');
        }
    }
    
    private function sendRequestForgotPasswordEmail($User_ID, $SecureKey) {
        $User = $this->UsersModel->getUserRecordForThisUserID($User_ID);
        $TemplateData['FirstName'] = $User['FirstName'];
        $TemplateData['LastName'] = $User['LastName'];
        $TemplateData['User_ID'] = $User_ID;
        $TemplateData['SecureKey'] = $SecureKey;
        sendUserManagementEmail($User['Email'], 'RequestForgotPassword', $TemplateData, 'Forgotten Password');
    }
    
    private function updatePassword($UserIDAndSecureKey) {
        $HashedPassword = $this->validatePasswordAndReturnHashedPassword();
        if(empty($this->Errors)) {
            $this->UsersModel->updatePassword($UserIDAndSecureKey['User_ID'], $HashedPassword);
            $this->UsersModel->deleteSecureKey($UserIDAndSecureKey['User_ID'], 'ForgottenPassword');
            $this->view('ForgotPassword/forgotPasswordCompleted', '', '');
        } else {
            $this->forgotPassword($this->Errors);
        }
    }
}
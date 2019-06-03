<?php

// A user can never register there own user account. A user is invited to an existing account OR created by a system user via AddUserToAccount.php, so that cant go via this MVC to create a user (as it comes from within another MVC). The below will deal with the email that the user then receives to activate their account.

class UserAccountActivation_controller extends UserManagementController { 
    protected $Errors = array();
    
    public function userAccountActivation($Errors) {
        $this->UsersModel = $this->model('Users', '');
        $UserIDAndSecureKey = $this->getUserIDAndSecureKey('AccountActivation');
        
        $data['Email'] = $this->UsersModel->getEmailAddressForThisUserID($UserIDAndSecureKey['User_ID']);
        $data['User_ID'] = $UserIDAndSecureKey['User_ID'];
        $data['SecureKey'] = $UserIDAndSecureKey['SecureKey'];
        $this->view('UserAccountActivation/userAccountActivation', $data, $Errors);
    }
    
    public function userAccountActivationSubmit() {  
        $this->UsersModel = $this->model('Users', '');
        $UserIDAndSecureKey = $this->getUserIDAndSecureKey('AccountActivation');
        $this->updateUserDetails($UserIDAndSecureKey);
    }
    
    private function updateUserDetails($UserIDAndSecureKey) {
        $UserDetails = $this->getUserDetails();
        if(empty($this->Errors)) {
            $this->UsersModel->updateUserDetailsOnAccountActivation($UserIDAndSecureKey['User_ID'], $UserDetails);
            $this->UsersModel->deleteSecureKey($UserIDAndSecureKey['User_ID'], 'AccountActivation');
            $this->view('UserAccountActivation/userAccountActivated', '', '');
        } else {
            $this->userAccountActivation($this->Errors);
        }
    }
           
    private function getUserDetails() {
        $UserDetails['FirstName'] = $this->getName('first');
        $UserDetails['LastName'] = $this->getName('last');
        $UserDetails['HashedPassword'] = $this->validatePasswordAndReturnHashedPassword();
        return $UserDetails;
    }
           
    private function getName($FirstOrLast) {
        if(!isset($_POST[$FirstOrLast."name"]) || trim($_POST[$FirstOrLast."name"]) == '') {            
            array_push($this->Errors, 'No '.$FirstOrLast.'name set.');
        }
        return $_POST[$FirstOrLast."name"];
    }
}
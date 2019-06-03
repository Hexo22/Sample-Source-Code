<?php

class ViewAndAmmendUserAccountDetails_controller extends UserManagementController {
    protected $Errors = array();
    
    public function viewUserDetails() {
        $this->UsersModel = $this->model('Users', '');
        $data['User_ID'] = $this->getLoggedInUserID();
        
        $User = $this->UsersModel->getUserDataForThisUserID($data['User_ID']);
        
        $data['FirstName'] = $User['FirstName'];
        $data['LastName'] = $User['LastName'];
        $data['Email'] = $User['Email'];
        
        $this->view('ViewAndAmmendUserAccountDetails/viewUserDetails', $data, '');
    }
    
    public function updatePassword($Errors) {
        $this->UsersModel = $this->model('Users', '');
        $User_ID = $this->getLoggedInUserID();
        $data['Email'] = $this->UsersModel->getEmailAddressForThisUserID($User_ID);
        $this->view('ViewAndAmmendUserAccountDetails/updatePassword', $data, $Errors);
    }

    public function updatePasswordSubmit() {
        $this->UsersModel = $this->model('Users', '');
        $User_ID = $this->getLoggedInUserID();
        
        if(!isset($_POST["existingpassword"]) || $_POST["existingpassword"] == '') {
            array_push($this->Errors, 'No existing password set.');
        }
        $ExistingPassword = $_POST["existingpassword"];
        $NewPasswordHash = $this->validatePasswordAndReturnHashedPassword();
        
        $ActualExistingPassword = $_SESSION["LoggedInUser"]->UserDetails['Password'];

        if(empty($this->Errors)) {
            $ExistingPasswordHash = generateHash($ExistingPassword, $ActualExistingPassword);

            if($ExistingPasswordHash != $ActualExistingPassword) {
                array_push($this->Errors, "Current password doesn't match the one we have on record.");
            } else {
                $Users_model = $this->model('Users', '');
                $Users_model->updatePassword($User_ID, $NewPasswordHash);
                $_SESSION["LoggedInUser"]->setUserDetails($this->InteractWithPersistentStorage);
            }
        }
        
        if(empty($this->Errors)) {
            $this->viewUserDetails();
        } else {
            $this->updatePassword($this->Errors);   
        }
    }
    
    public function updateEmail($Errors) {
        $this->UsersModel = $this->model('Users', '');
        $User_ID = $this->getLoggedInUserID();
        $data['Email'] = $this->UsersModel->getEmailAddressForThisUserID($User_ID);
        $this->view('ViewAndAmmendUserAccountDetails/updateEmail', $data, $Errors);
    }

    public function updateEmailSubmit() {        
        $this->UsersModel = $this->model('Users', '');
        $User_ID = $this->getLoggedInUserID();
        
        if(!isset($_POST["email"]) || trim($_POST["email"]) == '') {
            array_push($this->Errors, 'No email set.');
        }
            
        $Email = $_POST["email"];

        if(!isValidEmail($Email)) {
            array_push($this->Errors, 'Invalid email address.');
        } elseif($Email == $_SESSION["LoggedInUser"]->UserDetails['Email']) {
            array_push($this->Errors, 'Nothing to update.');
        } elseif($this->isThereAlreadyAnAccountForThisEmailAddress($Email)) {
            array_push($this->Errors, 'This email address is already taken by another user.');
        }
        
        if(empty($this->Errors)) {
            $Users_model = $this->model('Users', '');
            $Users_model->updateEmailForThisUser($User_ID, $Email);
            $_SESSION["LoggedInUser"]->setUserDetails($this->InteractWithPersistentStorage);
            $this->viewUserDetails();
        } else {
            $this->updateEmail($this->Errors);   
        }
    }
    
    public function updateUserDetails($Errors) {
        $this->UsersModel = $this->model('Users', '');
        $User_ID = $this->getLoggedInUserID();
        
        $User = $this->UsersModel->getUserDataForThisUserID($User_ID);
        
        $data['FirstName'] = $User['FirstName'];
        $data['LastName'] = $User['LastName'];
        $data['Email'] = $User['Email'];
        
        $this->view('ViewAndAmmendUserAccountDetails/updateUserDetails', $data, $Errors);
    }
    
    public function updateUserDetailsSubmit() {
        $this->UsersModel = $this->model('Users', '');
        $User_ID = $this->getLoggedInUserID();
            
        if(!isset($_POST["firstname"]) || trim($_POST["firstname"]) == '') {
            array_push($this->Errors, 'No firstname set.');
        }
        if(!isset($_POST["lastname"]) || trim($_POST["lastname"]) == '') {
            array_push($this->Errors, 'No lastname set.');
        }
            
        if(empty($this->Errors)) {
            $Users_model = $this->model('Users', '');
            $Users_model->updateFirstAndLastNameForThisUser($User_ID, $_POST["firstname"], $_POST["lastname"]);
            $_SESSION["LoggedInUser"]->setUserDetails($this->InteractWithPersistentStorage);
            $this->viewUserDetails();
        } else {
            $this->updatePassword($this->Errors);   
        }
    }
    
    private function getLoggedInUserID() {
        return $_SESSION["LoggedInUser"]->UserDetails['ID'];
    }
    
    private function isThereAlreadyAnAccountForThisEmailAddress($EmailAddress) {
        $Users = $this->UsersModel->getUsersWithThisEmailAddress($EmailAddress);
        if(sizeof($Users) != 0) {
            return true;
        } else {
            return false;   
        }
    }
}
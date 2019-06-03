<?php

class LogInAndOut_controller extends UserManagementController { 
    public function login($Errors = []) {
        if(isset($_GET['AutomaticallyLoggedOut']) && $_GET['AutomaticallyLoggedOut'] == 'Yes') {
            $data['Message'] = 'You have been logged out due to inactivity.<br>Please login again.';
        }
        if(isset($_GET['AppName']) && $_GET['AppName'] != '') {
            // App to go straight to after login submission.
            $data['AppName'] = $_GET['AppName'];
        } else {
            $data['AppName'] = '';
        }
        $this->view('LogInAndOut/login', $data, $Errors);
    }

    public function loginSubmit() {
        $this->UsersModel = $this->model('Users', '');

        try {
            if(empty($_POST)) {
                throw new UserManagementAccessException('No data submitted');
            }
            
            $User_ID = $this->validatePostedEmailAndPassword($this->UsersModel, $_POST);
        
            $_SESSION["LoggedInUser"] = new loggedInUser($User_ID);
            
            if(isset($_GET['AppName']) && $_GET['AppName'] != '') {
                // Go straight to the app.
                header("Location: ".getSiteURL()."AppIndex.php?AppName=".$_GET['AppName']);
            } else {
                header("Location: ".getSiteURL()."UserManagement.php?Controller=PostLoginMenu&Method=postLoginMenu");
            }
        } catch (UserValidationException $e) {
            $Errors[0] = $e->getMessage();
            return $this->login($Errors);
        }
    }
    
    public function validatePostedEmailAndPassword($UsersModel, $Data) {
        $Email = trim($Data["email"]);
        $Password = trim($Data["password"]);
        
        if($Email == '') {
            throw new UserValidationException("Please enter your username");
        }
        if($Password == '') {
            throw new UserValidationException("Please enter your password");
        }

        // We never tell the user which credential was incorrect incase of someone bruteforcing.        
        $UserData = $UsersModel->getUserDataForThisEmailAddress($Email);
        if($UserData == 'NoAccount') {
            throw new UserValidationException("username or password is invalid");
        }
        if($UserData['Active'] == 0) {
            throw new UserValidationException("Your account is in-active. Check your emails / junk folder for account activation instructions");
        }
        
        $NumberOfFailedSignInAttempts = $UsersModel->getNumberOfFailedSignInAttempts($UserData['ID']);
        if($NumberOfFailedSignInAttempts > 5) {
            $this->sendEmailToNotifyThatAccountHasBeenBlocked($UserData['ID']);
            throw new UserValidationException("Your account has been blocked due to repeated failed login attempts. Please check your emails to unlock your account.");
        }
        
        $entered_pass = generateHash($Password, $UserData['Password']); // Use the salt from the database to compare the password.
        if($entered_pass != $UserData['Password']) {
            $UsersModel->incrementNumberOfFailedSignInAttempts($UserData['ID']);
            throw new UserValidationException("username or password is invalid");
        }
        
        $UsersModel->resetNumberOfFailedSignInAttempts($UserData['ID']);

        return $UserData['ID'];
    }

    public function logout() {
        $_SESSION["LoggedInUser"]->logUserOut();
        header("Location: ".getSiteURL()."");
        die();
    }
           
    private function sendEmailToNotifyThatAccountHasBeenBlocked($User_ID) {            
        $EmailThatAccountHasBeenBlockedSent = $this->UsersModel->getValue_EmailThatAccountHasBeenBlockedSent($User_ID);
        // We don't want to keep bombarding the user with emails if somebody is trying to hack into their account, therefore just send the email once.
        if($EmailThatAccountHasBeenBlockedSent == '' || $EmailThatAccountHasBeenBlockedSent == 'No') {
            $this->sendEmailToNotifyThatAccountHasBeenBlocked2($User_ID);
            $this->UsersModel->update_EmailThatAccountHasBeenBlockedSent($User_ID, 'Yes');
        }
    }
           
    private function sendEmailToNotifyThatAccountHasBeenBlocked2($User_ID) {
        $User = $this->UsersModel->getUserRecordForThisUserID($User_ID);
        $TemplateData['User_ID'] = $User['ID'];
        $TemplateData['FirstName'] = $User['FirstName'];
        $TemplateData['LastName'] = $User['LastName'];
        $TemplateData['SecureKey'] = $this->UsersModel->createSecureKey($User_ID, 'AccountBlocked');
        sendUserManagementEmail($User['Email'], 'UserNotificationEmailThatThatAccountHasBeenBlocked', $TemplateData, 'Account Blocked');
    }
           
    public function unlockAccount() {
        $this->UsersModel = $this->model('Users', '');
        $UserIDAndSecureKey = $this->getUserIDAndSecureKey('AccountBlocked');
        $this->UsersModel->resetNumberOfFailedSignInAttempts($UserIDAndSecureKey['User_ID']);
        $this->UsersModel->deleteSecureKey($UserIDAndSecureKey['User_ID'], 'AccountBlocked');
        $this->UsersModel->update_EmailThatAccountHasBeenBlockedSent($UserIDAndSecureKey['User_ID'], 'No');
        $this->login();
    }
}
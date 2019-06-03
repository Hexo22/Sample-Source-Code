<?php

require_once('../models/Users_model.php');
require_once('UsefulFunctions.php');

class AddUserToAccount {
    protected $UsersModel;
    
    // I pass '$InteractWithPersistentStorage' in to here so that I can wrap this and the calling script all under one transaction.
    public function __construct(InteractWithPersistentStorage $InteractWithPersistentStorage) {
        $this->UsersModel = new Users_model($InteractWithPersistentStorage);
    }
    
    public function addUserToAccount2($Account_ID, $EmailAddress, $MasterUser) {
        $EmailAddress = sanitize($EmailAddress);
        $Users = $this->UsersModel->getUsersWithThisEmailAddress($EmailAddress);
        if(sizeof($Users) == 0) {
            return $this->addNewUserAndAddThemToThisAccount($Account_ID, $EmailAddress, $MasterUser);
        } elseif(sizeof($Users) == 1) {            
            $this->UsersModel->addThisUserToThisAccount($Users[0]['ID'], $Account_ID, $MasterUser);
            $this->sendUserNotificationEmailThatTheyHaveBeenAddedToThisAccount($Users[0]['ID'], $Account_ID);
            return $Users[0]['ID'];
        } else {
            throw new Exception('There should only be one user account per email address.');
        }
    }
    
    public function deleteUserToAccount($Account_ID, $User_ID) {
        $this->UsersModel->deleteThisUsersToAccountsRecord($Account_ID, $User_ID);
        $AccountType = $this->UsersModel->getThisAccountType($Account_ID);
        $this->UsersModel->removeAllPermissionsForThisUserForThisAccount($Account_ID, $User_ID, $AccountType); // This will then immediately force the user off of the app.
    }
    
    public function restoreUserToAccount($Account_ID, $User_ID) {
        $this->UsersModel->restoreThisUsersToAccountsRecord($Account_ID, $User_ID);
        $this->sendUserNotificationEmailThatTheyHaveBeenAddedToThisAccount($User_ID, $Account_ID);
        $MasterUser = $this->UsersModel->isThisUserAMasterUser($Account_ID, $User_ID);
        $this->UsersModel->setDefaultPermissionsForUserToAccount($User_ID, $Account_ID, $MasterUser);
    }
    
    private function addNewUserAndAddThemToThisAccount($Account_ID, $EmailAddress, $MasterUser) {
        $User_ID = $this->UsersModel->addNewUser($EmailAddress, $Account_ID);
        $this->UsersModel->addUserToAccount($User_ID, $Account_ID, $MasterUser);
        $SecureKey = $this->UsersModel->createSecureKey($User_ID, 'AccountActivation');
        $this->sendUserAccountActivationEmail($User_ID, $Account_ID, $SecureKey);
        return $User_ID;
    }

    private function sendUserAccountActivationEmail($User_ID, $Account_ID, $SecureKey) {            
        $User = $this->UsersModel->getUserRecordForThisUserID($User_ID);
        if($User['Active'] == 1) {
            throw new AppStructureIntegrityException('This user account has already been activated.');
        } else {
            $this->sendUserAccountActivationEmail2($User, $Account_ID, $SecureKey);  
        }
    }
    
    private function sendUserAccountActivationEmail2($User, $Account_ID, $SecureKey) {
        $TemplateData['AccountName'] = $this->UsersModel->getAccountNameForThisAccountID($Account_ID);
        $TemplateData['User_ID'] = $User['ID'];
        $TemplateData['SecureKey'] = $SecureKey;
        sendUserManagementEmail($User['Email'], 'AccountActivation', $TemplateData, 'Account Activation');
    }

    private function sendUserNotificationEmailThatTheyHaveBeenAddedToThisAccount($User_ID, $Account_ID) {
        $User = $this->UsersModel->getUserRecordForThisUserID($User_ID);
        $TemplateData['FirstName'] = $User['FirstName'];
        $TemplateData['LastName'] = $User['LastName'];
        $TemplateData['AccountName'] = $this->UsersModel->getAccountNameForThisAccountID($Account_ID);
        sendUserManagementEmail($User['Email'], 'UserNotificationEmailThatTheyHaveBeenAddedToThisAccount', $TemplateData, 'New Account');
    }
    
    public function resendUserAccountActivationEmail($User_ID) {
        $User = $this->UsersModel->getUserRecordForThisUserID($User_ID);
        if($User['Active'] == 1) {
            throw new AppStructureIntegrityException('This user account has already been activated.');
        } else {
            // Reset the SecureKey for AccountActivation for security purposes.
            $SecureKey = $this->UsersModel->resetAccountActivationSecureKey($User_ID);
            
            $Accounts = $this->UsersModel->getAccountsThisUserHasAccessTo($User_ID);
            if(sizeof($Accounts) == 0) {
                throw new AppStructureIntegrityException('This user is attached to no accounts therefore the UserAccountActivationEmail can not be resent.');   
            }
            $Account_ID = $Accounts[0]['Account_ID'];
            $this->sendUserAccountActivationEmail($User_ID, $Account_ID, $SecureKey);
        }
    }
}
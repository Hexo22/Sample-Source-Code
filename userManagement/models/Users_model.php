<?php

require_once(__DIR__.'../../../app/init.php');
require_once(__DIR__.'../../../systemTools/ValidatePermissionsDummyForSystemToolsThatProvidesFullAccess.php');

class Users_model {
    protected $InteractWithPersistentStorage;
    
    public function __construct(InteractWithPersistentStorage $InteractWithPersistentStorage) {
        $this->InteractWithPersistentStorage = $InteractWithPersistentStorage;
    }
    
    public function getUsersWithThisEmailAddress($Email) {
        $Columns[0] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'Email';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $Email;
        return $this->InteractWithPersistentStorage->row_read('Master.Users', $Columns, $Where);
    }
    
    public function getUserDataForThisEmailAddress($Email) {
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'Email';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $Email;
        $Results = $this->InteractWithPersistentStorage->row_read('Master.Users', 'All', $Where);
        
        if(sizeof($Results) == 0) {
            return 'NoAccount';
        } else {
            return $Results[0];   
        }
    }
    
    public function checkThisUserExists_AndThrowUserManagementAccessExceptionIfNot($User_ID) {
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $User_ID;
        $Results = $this->InteractWithPersistentStorage->row_read('Master.Users', 'All', $Where);
        if(sizeof($Results) != 1) {
            throw new UserManagementAccessException('This user does not exist.');   
        }
        return $Results[0]; 
    }
    
    public function getUserDataForThisUserID($User_ID) {
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $User_ID;
        return $this->InteractWithPersistentStorage->row_read('Master.Users', 'All', $Where)[0];
    }
    
    public function getUserRecordForThisUserID($User_ID) {
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $User_ID;
        $Results = $this->InteractWithPersistentStorage->row_read('Master.Users', 'All', $Where);
        if(sizeof($Results) != 1) {
            throw new Exception('This user does not exist.');   
        }
        return $Results[0];   
    }
    
    public function getSpecificFieldForThisUserID($User_ID, $FieldName) {
        $Columns[0] = $FieldName;
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $User_ID;
        return $this->InteractWithPersistentStorage->row_read('Master.Users', $Columns, $Where)[0][$FieldName];
    }
    
    public function getEmailAddressForThisUserID($User_ID) {
        return $this->getSpecificFieldForThisUserID($User_ID, 'Email');
    }
    
    public function getNumberOfFailedSignInAttempts($User_ID) {
        return $this->getSpecificFieldForThisUserID($User_ID, 'NumberOfFailedSignInAttempts');
    }
    
    public function getValue_EmailThatAccountHasBeenBlockedSent($User_ID) {
        return $this->getSpecificFieldForThisUserID($User_ID, 'EmailThatAccountHasBeenBlockedSent');
    }
    
    public function getAccountNameForThisAccountID($Account_ID) {
        $Columns[0] = 'Account_Name';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $Account_ID;
        $Results = $this->InteractWithPersistentStorage->row_read('Master.Accounts', $Columns, $Where);
        
        if(sizeof($Results) != 1) {
            throw new Exception('This account does not exist.');
        }
        
        return $Results[0]['Account_Name'];
    }
    
    public function getAccountsThisUserHasAccessTo($User_ID) {
        $Columns[0] = 'Account_ID';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'User_ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $User_ID;
        $Results = $this->InteractWithPersistentStorage->row_read('Master.UsersToAccounts', $Columns, $Where);
        for($i=0; $i<sizeof($Results); $i++) {
            $Results[$i]['AccountName'] = $this->getAccountNameForThisAccountID($Results[$i]['Account_ID']);
        }
        return $Results;
    }
    
    public function addNewUser($EmailAddress, $Account_ID) {
        $EmailAddress = sanitize($EmailAddress);
        if(!isValidEmail($EmailAddress)) {
            throw new AppStructureIntegrityException('Invalid email address.');
        }
        
        $Columns[0] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'Email';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $EmailAddress;
        $Results = $this->InteractWithPersistentStorage->row_read('Master.Users', $Columns, $Where);
        
        if(sizeof($Results) == 0) {
            return $this->addNewUser2($EmailAddress, $Account_ID);
        } else {
            throw new AppStructureIntegrityException('A user account already exists with this email address.');
        }
    }

    private function addNewUser2($EmailAddress, $Account_ID) {
        $Columns[0] = 'Email';
        $Columns[1] = 'Active';
        $Values[0] = $EmailAddress;
        $Values[1] = '0';
        return $this->InteractWithPersistentStorage->row_add('Master.Users', $Columns, $Values);
    }
    
    public function addThisUserToThisAccount($User_ID, $Account_ID, $MasterUser) {
        $Columns[0] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'User_ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $User_ID;
        $Where['Groups'][0]['Conditions'][1]['FieldName'] = 'Account_ID';
        $Where['Groups'][0]['Conditions'][1]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][1]['Value'] = $Account_ID;
        $Results = $this->InteractWithPersistentStorage->row_read('Master.UsersToAccounts', $Columns, $Where);
        
        if(sizeof($Results) == 0) {
            $Where['Groups'][0]['Conditions'][2]['FieldName'] = 'Deleted';
            $Where['Groups'][0]['Conditions'][2]['Comparison'] = '=';
            $Where['Groups'][0]['Conditions'][2]['Value'] = 'Yes';
            $ArchivesResults = $this->InteractWithPersistentStorage->row_read('Master.UsersToAccountsArchives', $Columns, $Where);
            
            if(sizeof($ArchivesResults) == 0) {
                $this->addUserToAccount($User_ID, $Account_ID, $MasterUser);
            } elseif(sizeof($Results) == 1) {
                // The calling function should reactivate via restoring the UsersToAccounts record.
                throw new AppStructureIntegrityException('This user previously had access to this account but it has been deactivated, you must reactivate it.'); // There can only ever be one UsersToAccounts record because I add a record to the People table in the Account's App for this user and so I can't have that there is more than one of them per user_id.
            } elseif(sizeof($Results) > 1) {
                throw new Exception('There should only be one UsersToAccounts record for each user for each account.');   
            }    
        } elseif(sizeof($Results) == 1) {
            throw new AppStructureIntegrityException('This user already has access to this account.');
        } elseif(sizeof($Results) > 1) {
            throw new Exception('There should only be one UsersToAccounts record for each user for each account.');   
        }
    }
    
    public function isThisUserASystemUser($User_ID) {
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'User_ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $User_ID;
        $Results = $this->InteractWithPersistentStorage->row_read('Master.SystemUsers', 'All', $Where);
        if(sizeof($Results) == 0) {
            return 'No';
        } elseif(sizeof($Results) == 1) {
            return 'Yes';
        } else {
            throw new Exception('There should only be one SystemUsers record per user.');   
        }
    }
    
    public function addUserToAccount($User_ID, $Account_ID, $MasterUser) {
        if($MasterUser != 'Yes' && $MasterUser != 'No') {
            throw new Exception('Invalid MasterUser value.');
        }
        if($this->isThisUserASystemUser($User_ID) == 'Yes') {
            if($MasterUser != 'Yes') {
                throw new Exception('SystemUsers must be set as a MasterUser for every account they are added to.');   
            }
        }
        
        $Columns[0] = 'User_ID';
        $Columns[1] = 'Account_ID';
        $Columns[2] = 'MasterUser';
        $Values[0] = $User_ID;
        $Values[1] = $Account_ID;
        $Values[2] = $MasterUser;
        $this->InteractWithPersistentStorage->row_add('Master.UsersToAccounts', $Columns, $Values);
        
        $AccountType = $this->getAccountType($Account_ID);
        if($AccountType == 'Business') {
            $this->addUserToAppPeopleTable($User_ID, $Account_ID);
        }
        
        $this->setDefaultPermissionsForUserToAccount($User_ID, $Account_ID, $MasterUser);
    }
    
    private function addUserToAppPeopleTable($User_ID, $Account_ID) {    
        $PersonObject_ID = $this->getPersonObject_IDForThisAccount($Account_ID);
        
        $PeopleTable = $Account_ID.'_App_AppData.Object_'.$PersonObject_ID;

        $Columns[0] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'Origin_User_ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $User_ID;
        $Results = $this->InteractWithPersistentStorage->row_read($PeopleTable, $Columns, $Where);
        if(sizeof($Results) != 0) {
            throw new Exception('There should not already be a person record for this user.');   
        }

        $Columns[0] = 'FirstName';
        $Columns[1] = 'LastName';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $User_ID;
        $User = $this->InteractWithPersistentStorage->row_read('Master.Users', $Columns, $Where)[0];

        if($User['FirstName'] == '') { // Account has not yet been activated so has no First or Last Name set yet.
            $User['FirstName'] = 'New User ID ' . $User_ID;
            $User['LastName'] = 'Name to be set by user';
        }

        // Need to go through the proper model in order to set up ORG IDs etc correctly and go via FieldRules etc etc.
        // Have to use the ValidatePermissionsDummyForSystemToolsThatProvidesFullAccess because this model can be called from the SystemTools section as well and the SystemUser probably wont have Permission to this app.
        $ValidatePermissionsDummy = new ValidatePermissionsDummyForSystemToolsThatProvidesFullAccess;            
        $AppToInteractWithPersistentStorage = new AppToInteractWithPersistentStorage($this->InteractWithPersistentStorage, $ValidatePermissionsDummy, $Account_ID.'_'.'App');

        $Variables[0] = $PersonObject_ID;
        $Object_model = new Object_model($AppToInteractWithPersistentStorage, $ValidatePermissionsDummy, $Variables);
        $Columns[0] = 'First_Name';
        $Columns[1] = 'Last_Name';
        $Columns[2] = 'Employer';
        $Columns[3] = 'Origin_User_ID';
        $Values[0] = $User['FirstName'];
        $Values[1] = $User['LastName'];
        $Values[2] = '1'; // Default to this Organisation, they can always then change that as required.
        $Values[3] = $User_ID;
        $Object_model->Instance_add($Columns, $Values, '', 'No');
    }
    
    public function deleteUserFromAppPeopleTable($User_ID, $Account_ID) {    
        $PersonObject_ID = $this->getPersonObject_IDForThisAccount($Account_ID);
        
        $PeopleTable = $Account_ID.'_App_AppData.Object_'.$PersonObject_ID;

        $Columns[0] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'Origin_User_ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $User_ID;
        $Results = $this->InteractWithPersistentStorage->row_read($PeopleTable, $Columns, $Where);
        if(sizeof($Results) != 1) {
            throw new Exception('The person record for this user should exist as it should have been blocked from being deleted.');   
        }

        // Need to go through the proper model in order to set up ORG IDs etc correctly and go via FieldRules etc etc.
        // Have to use the ValidatePermissionsDummyForSystemToolsThatProvidesFullAccess because this model can be called from the SystemTools section as well and the SystemUser probably wont have Permission to this app.
        $ValidatePermissionsDummy = new ValidatePermissionsDummyForSystemToolsThatProvidesFullAccess;            
        $AppToInteractWithPersistentStorage = new AppToInteractWithPersistentStorage($this->InteractWithPersistentStorage, $ValidatePermissionsDummy, $Account_ID.'_'.'App');

        $Variables[0] = $PersonObject_ID;
        $Object_model = new Object_model($AppToInteractWithPersistentStorage, $ValidatePermissionsDummy, $Variables);
        $Object_model->Instance_delete($Results[0]['ID'], '');
    }
    
    public function setDefaultPermissionsForUserToAccount($User_ID, $Account_ID, $MasterUser) {
        $AccountType = $this->getAccountType($Account_ID);
        
        // Remove all permissions for this user for this account first as I often use this function to reset up the permissions (for example if swapping from MasterUser to Non-MasterUser).
        $this->removeAllPermissionsForThisUserForThisAccount($Account_ID, $User_ID, $AccountType);

        if($MasterUser == 'Yes') {
            if($AccountType == 'Business') {                
                $this->giveThisUserAccessToTheABFB($User_ID, $Account_ID);
            }

            // Can't add a 'FullAccess' record because of how they can never have FullAccess to the Notes&Tasks elements.
            $this->addPermissionToEveryElementInAppExceptForNotesAndTasks($User_ID, $Account_ID, 'ReadAndWrite');
        } else {
            $this->addPermissionToEveryElementInAppExceptForNotesAndTasks($User_ID, $Account_ID, 'NoAccess');
        }

        if($AccountType == 'Business') {
            $this->addPermissionsToNotesAndTasks($User_ID, $Account_ID, $MasterUser);
        }
    }
    
    private function giveThisUserAccessToTheABFB($User_ID, $Account_ID) {
        // Can't add a 'FullAccess' record because of how they can never have Write access to the 'DefaultElements' Object.
        $this->addPermissionToEveryElementInTheABFBExceptForDefaultElementsObject($User_ID, $Account_ID);
    }
    
    public function removeAllPermissionsForThisUserForThisAccount($Account_ID, $User_ID, $AccountType) {
        if($AccountType == 'Business') {
            $this->removeAllPermissionsForThisUserForThisAccount_TBFOrApp($Account_ID, $User_ID, 'AppBuilderForBusiness');
        } 
        $this->removeAllPermissionsForThisUserForThisAccount_TBFOrApp($Account_ID, $User_ID, 'App');
    }
    
    private function removeAllPermissionsForThisUserForThisAccount_TBFOrApp($Account_ID, $User_ID, $AppBuilderForBusinessOrApp) {
        $Permissions = $this->getAllPermissionsForThisUserToThisAccount($User_ID, $Account_ID, $AppBuilderForBusinessOrApp, 'UserPermissions');
        if(sizeof($Permissions) > 0) {
            foreach($Permissions as $Permission) {
                $this->InteractWithPersistentStorage->row_delete('Master.UserPermissions', $Permission['ID']);
            }
        }
        
        $Permissions = $this->getAllPermissionsForThisUserToThisAccount($User_ID, $Account_ID, $AppBuilderForBusinessOrApp, 'UserPermissions_Subgroups');
        if(sizeof($Permissions) > 0) {
            foreach($Permissions as $Permission) {
                $this->InteractWithPersistentStorage->row_delete('Master.UserPermissions_Subgroups', $Permission['ID']);
            }
        }
    }
    
    private function getAllPermissionsForThisUserToThisAccount($User_ID, $Account_ID, $AppBuilderForBusinessOrApp, $UserPermissionsOrUserPermissions_Subgroup) {        
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'User_ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $User_ID;
        $Where['Groups'][0]['Conditions'][1]['FieldName'] = 'AppName';
        $Where['Groups'][0]['Conditions'][1]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][1]['Value'] = $Account_ID.'_'.$AppBuilderForBusinessOrApp;
        return $this->InteractWithPersistentStorage->row_read('Master.'.$UserPermissionsOrUserPermissions_Subgroup, 'All', $Where);
    }
    
    private function getAccountType($Account_ID) {
        $Columns[0] = 'Account_Type';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $Account_ID;
        $Results = $this->InteractWithPersistentStorage->row_read('Master.Accounts', $Columns, $Where);
        if(sizeof($Results) != 1) {
            throw new Exception('Invalid Account ID : '.$Account_ID.'.');   
        }
        return $Results[0]['Account_Type'];
    }
    
    private function addPermissionToEveryElementInTheABFBExceptForDefaultElementsObject($User_ID, $Account_ID) {
        $Elements = $this->getAllElementsInTheABFBExceptForDefaultElementsObject($Account_ID);
        foreach($Elements as $Element) {
            $this->addUserPermission($User_ID, $Account_ID, 'AppBuilderForBusiness', $Element['Type'], $Element['ID'], 'ReadAndWrite');   
        }
    }
    
    private function getAllElementsInTheABFBExceptForDefaultElementsObject($Account_ID) {
        $i = 0;
        $Columns[0] = 'ID';
        $Columns[1] = 'PluralName';
        $Objects = $this->InteractWithPersistentStorage->row_read($Account_ID.'_AppBuilderForBusiness_AppDefinition.Objects', $Columns, '');
        foreach($Objects as $Object) {
            if($Object['PluralName'] != 'DefaultElements') {
                $Elements[$i]['Type'] = 'Object';
                $Elements[$i]['ID'] = $Object['ID'];
                $i++;
            }
        }
        $Relationships = $this->InteractWithPersistentStorage->row_read($Account_ID.'_AppBuilderForBusiness_AppDefinition.Relationships', $Columns, '');
        foreach($Relationships as $Relationship) {
            $Elements[$i]['Type'] = 'Relationship';
            $Elements[$i]['ID'] = $Relationship['ID'];
            $i++;
        }
        return $Elements;
    }
    
    private function addPermissionToEveryElementInAppExceptForNotesAndTasks($User_ID, $Account_ID, $PermissionType) {
        $Elements = $this->getAllElementsInAppExceptForNotesAndTasks($Account_ID);
        if(isset($Elements)) {
            foreach($Elements as $Element) {
                $this->addUserPermission($User_ID, $Account_ID, 'App', $Element['Type'], $Element['ID'], $PermissionType);   
            }
        }
        
        $this->addPermissionToEveryElementInAppExceptForNotesAndTasks_Reports($User_ID, $Account_ID, $PermissionType);
    }
    
    private function addPermissionToEveryElementInAppExceptForNotesAndTasks_Reports($User_ID, $Account_ID, $PermissionType) {
        if($PermissionType == 'ReadAndWrite') {
            $this->addUserPermission($User_ID, $Account_ID, 'App', 'Reports', '', 'FullAccess');
        } elseif($PermissionType == 'NoAccess') {
            $this->addUserPermission($User_ID, $Account_ID, 'App', 'Reports', '', 'NoAccess');
        } else {
            throw new Exception('');
        }
    }
    
    private function getAllElementsInAppExceptForNotesAndTasks($Account_ID) {            
        $i = 0;
        $Columns[0] = 'ID';
        $Columns[1] = 'PluralName';
        $Objects = $this->InteractWithPersistentStorage->row_read($Account_ID.'_App_AppDefinition.Objects', $Columns, '');
        foreach($Objects as $Object) {
            if($Object['PluralName'] != 'Notes' && $Object['PluralName'] != 'Tasks') {
                $Elements[$i]['Type'] = 'Object';
                $Elements[$i]['ID'] = $Object['ID'];
                $i++;
            }
        }
        $Relationships = $this->InteractWithPersistentStorage->row_read($Account_ID.'_App_AppDefinition.Relationships', $Columns, '');
        foreach($Relationships as $Relationship) {
            if($Relationship['PluralName'] != 'Tags To Notes and Tasks') {
                $Elements[$i]['Type'] = 'Relationship';
                $Elements[$i]['ID'] = $Relationship['ID'];
                $i++;
            }
        }
        if($i > 0) {
            return $Elements;
        }
    }
    
    private function addPermissionsToNotesAndTasks($User_ID, $Account_ID, $MasterUser) {
        $this->addPermissionsToNotesAndTasks_Each($User_ID, $Account_ID, 'Notes');
        $this->addPermissionsToNotesAndTasks_Each($User_ID, $Account_ID, 'Tasks');
        
        // We don't need to add a record for the 'My Subgroups' because we only add subgroups records for groups we don't want the user to be able to access, they have access to the entire element bar these subgroups.
        $this->addPermissionsToNotesAndTasks_EachSubgroup($User_ID, $Account_ID, 'Notes', 'Public');
        $this->addPermissionsToNotesAndTasks_EachSubgroup($User_ID, $Account_ID, 'Tasks', 'Public');
        $this->addPermissionsToNotesAndTasks_EachSubgroup($User_ID, $Account_ID, 'Notes', 'PrivateToOthers');
        $this->addPermissionsToNotesAndTasks_EachSubgroup($User_ID, $Account_ID, 'Tasks', 'PrivateToOthers');
        
        $this->addPermissionsToNotesAndTasks_AllowPermissionToUsersAtAllTimes($User_ID, $Account_ID, $MasterUser);
    }
    
    private function addPermissionsToNotesAndTasks_Each($User_ID, $Account_ID, $NotesOrTasks) {
        $Object_ID = $this->getTheNotesOrTasksObjectID($Account_ID, $NotesOrTasks);
        $this->addUserPermission($User_ID, $Account_ID, 'App', 'Object', $Object_ID, 'DefinedBySubgroups');
    }
    
    private function addPermissionsToNotesAndTasks_EachSubgroup($User_ID, $Account_ID, $NotesOrTasks, $PublicOrPrivateToOthers) {
        $Response = $this->addPermissionsToNotesAndTasks_EachSubgroup_getObjectIDAndSubgroupID($Account_ID, $NotesOrTasks, $PublicOrPrivateToOthers);
        
        if($PublicOrPrivateToOthers == 'Public') {
            $Access = 'ReadOnly';
        } elseif($PublicOrPrivateToOthers == 'PrivateToOthers') {
            $Access = 'NoAccess';
        }
        $this->addUserPermissionToSubgroup($User_ID, $Account_ID, 'App', 'Object', $Response['Object_ID'], $Response['Subgroup_ID'], $Access);
    }
    
    private function addPermissionsToNotesAndTasks_AllowPermissionToUsersAtAllTimes($User_ID, $Account_ID, $MasterUser) {
        // I have to do all of this because Non-MasterUsers have permissions for all elements bar Notes & Tasks set to NoAccess by default, but with this they can not add a new Note or Task because they have no Write access to People and so can’t choose any people for the Created_By Field. So I have to provide write access to the Users subgroup at all times.
        
        // If MasterUser then will already have full ReadAndWrite access to the People Object so there is nothing to do.
        if($MasterUser == 'No') {              
            $PersonObject_ID = $this->getPersonObject_IDForThisAccount($Account_ID);
              
            $Columns[0] = 'ID';
            $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'User_ID';
            $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
            $Where['Groups'][0]['Conditions'][0]['Value'] = $User_ID;
            $Where['Groups'][0]['Conditions'][1]['FieldName'] = 'AppName';
            $Where['Groups'][0]['Conditions'][1]['Comparison'] = '=';
            $Where['Groups'][0]['Conditions'][1]['Value'] = $Account_ID.'_App';
            $Where['Groups'][0]['Conditions'][2]['FieldName'] = 'Element_Type';
            $Where['Groups'][0]['Conditions'][2]['Comparison'] = '=';
            $Where['Groups'][0]['Conditions'][2]['Value'] = 'Object';
            $Where['Groups'][0]['Conditions'][3]['FieldName'] = 'Element_ID';
            $Where['Groups'][0]['Conditions'][3]['Comparison'] = '=';
            $Where['Groups'][0]['Conditions'][3]['Value'] = $PersonObject_ID;
            $Permission_ID = $this->InteractWithPersistentStorage->row_read('Master.UserPermissions', $Columns, $Where)[0]['ID'];
              
            $Columns[0] = 'Permission_Type';
            $Values[0] = 'DefinedBySubgroups';
            $this->InteractWithPersistentStorage->row_edit('Master.UserPermissions', $Columns, $Values, $Permission_ID);
            
            $NonUsersSubgroup_ID = $this->getNonUsersSubgroup_IDForThisAccount($Account_ID, $PersonObject_ID);
            $this->addUserPermissionToSubgroup($User_ID, $Account_ID, 'App', 'Object', $PersonObject_ID, $NonUsersSubgroup_ID, 'NoAccess');
        }
    }
    
    private function addUserPermission($User_ID, $Account_ID, $AppOrAppBuilderForBusiness, $Element_Type, $Element_ID, $PermissionType) {
        $Columns[0] = 'User_ID';
        $Columns[1] = 'AppName';
        $Columns[2] = 'Element_Type';
        $Columns[3] = 'Element_ID';
        $Columns[4] = 'Permission_Type';
        $Values[0] = $User_ID;
        $Values[1] = $Account_ID.'_'.$AppOrAppBuilderForBusiness;
        $Values[2] = $Element_Type;
        $Values[3] = $Element_ID;
        $Values[4] = $PermissionType;
        $this->InteractWithPersistentStorage->row_add('Master.UserPermissions', $Columns, $Values);
    }
    
    private function addUserPermissionToSubgroup($User_ID, $Account_ID, $AppOrAppBuilderForBusiness, $Element_Type, $Element_ID, $Subgroup_ID, $PermissionType) {
        if($PermissionType != 'NoAccess' && $PermissionType != 'ReadOnly') {
            throw new Exception('Incorrect Subgroup PermissionType.');   
        }
        $Columns[0] = 'User_ID';
        $Columns[1] = 'AppName';
        $Columns[2] = 'Element_Type';
        $Columns[3] = 'Element_ID';
        $Columns[4] = 'Subgroup_ID';
        $Columns[5] = 'Permission_Type';
        $Values[0] = $User_ID;
        $Values[1] = $Account_ID.'_'.$AppOrAppBuilderForBusiness;
        $Values[2] = $Element_Type;
        $Values[3] = $Element_ID;
        $Values[4] = $Subgroup_ID;
        $Values[5] = $PermissionType;
        $this->InteractWithPersistentStorage->row_add('Master.UserPermissions_Subgroups', $Columns, $Values);
    }
    
    private function addPermissionsToNotesAndTasks_EachSubgroup_getObjectIDAndSubgroupID($Account_ID, $NotesOrTasks, $PublicOrPrivateToOthers) {
        $Response['Object_ID'] = $this->getTheNotesOrTasksObjectID($Account_ID, $NotesOrTasks);

        if($PublicOrPrivateToOthers == 'Public') {
            $PluralName = 'Public ' . $NotesOrTasks;
        } elseif($PublicOrPrivateToOthers == 'PrivateToOthers') {
            $PluralName = 'Private '.$NotesOrTasks.' You Do Not Have Access To';
        }

        $Columns[0] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'Element_Type';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = 'Object';
        $Where['Groups'][0]['Conditions'][1]['FieldName'] = 'Element_ID';
        $Where['Groups'][0]['Conditions'][1]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][1]['Value'] = $Response['Object_ID'];
        $Where['Groups'][0]['Conditions'][2]['FieldName'] = 'PluralName';
        $Where['Groups'][0]['Conditions'][2]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][2]['Value'] = $PluralName;
        $Results = $this->InteractWithPersistentStorage->row_read($Account_ID.'_App_AppDefinition.Subgroups', $Columns, $Where);
        $Response['Subgroup_ID'] = $Results[0]['ID'];
        
        return $Response;
    }
    
    private function getTheNotesOrTasksObjectID($Account_ID, $NotesOrTasks) {
        $Columns[0] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'PluralName';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $NotesOrTasks;
        $Results = $this->InteractWithPersistentStorage->row_read($Account_ID.'_App_AppDefinition.Objects', $Columns, $Where);
        if(sizeof($Results) != 1) {
            throw new Exception('Incorrect PluralName.');   
        }
        return $Results[0]['ID'];
    }
                                   
    public function updateUserDetailsOnAccountActivation($User_ID, $UserDetails) {
        $Columns[0] = 'FirstName';
        $Columns[1] = 'LastName';
        $Columns[2] = 'Password';
        $Columns[3] = 'Active';
        $Values[0] = $UserDetails['FirstName'];
        $Values[1] = $UserDetails['LastName'];
        $Values[2] = $UserDetails['HashedPassword'];
        $Values[3] = '1';
        $this->InteractWithPersistentStorage->row_edit('Master.Users', $Columns, $Values, $User_ID);
        
        $this->updateFirstAndLastNameForThisUserInAllAccountsPeopleTables($User_ID, $UserDetails['FirstName'], $UserDetails['LastName']);
    }
    
    public function updateSpecificFieldForThisUserID($User_ID, $FieldName, $Value) {
        $Columns[0] = $FieldName;
        $Values[0] = $Value;
        $this->InteractWithPersistentStorage->row_edit('Master.Users', $Columns, $Values, $User_ID);
    }
    
    public function updateLastSignInDateTimeForThisUser($User_ID) {
        $this->updateSpecificFieldForThisUserID($User_ID, 'LastSignInDateTime', date("Y-m-d H:i:s"));
	}
	
	public function updateEmailForThisUser($User_ID, $Email) {
        $this->updateSpecificFieldForThisUserID($User_ID, 'Email', $Email);
	}
    
    public function updateFirstAndLastNameForThisUser($User_ID, $FirstName, $LastName) {
		$Columns[0] = 'FirstName';
        $Columns[1] = 'LastName';
        $Values[0] = $FirstName;
        $Values[1] = $LastName;
        $this->InteractWithPersistentStorage->row_edit('Master.Users', $Columns, $Values, $User_ID);
        
        $this->updateFirstAndLastNameForThisUserInAllAccountsPeopleTables($User_ID, $FirstName, $LastName);
	}
    
    public function updateFirstAndLastNameForThisUserInAllAccountsPeopleTables($User_ID, $FirstName, $LastName) {
        $Columns[0] = 'Account_ID';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'User_ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $User_ID;
        $Accounts = $this->InteractWithPersistentStorage->row_read('Master.UsersToAccounts', $Columns, $Where);
        foreach($Accounts as $Account) {
            $AccountType = $this->getAccountType($Account['Account_ID']);
            if($AccountType == 'Business') {
                $this->updateFirstAndLastNameForThisUserInThisAccountsPeopleTable($Account['Account_ID'], $User_ID, $FirstName, $LastName);
            }
        }
        
        // Update for Accounts that the user previoulsy had access to as well as the Person's record will of course exist in them still as well so we want to keep them updated also.
        $Where['Groups'][0]['Conditions'][1]['FieldName'] = 'Deleted';
        $Where['Groups'][0]['Conditions'][1]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][1]['Value'] = 'Yes';
        $Accounts2 = $this->InteractWithPersistentStorage->row_read('Master.UsersToAccountsArchives', $Columns, $Where);
        foreach($Accounts2 as $Account) {
            $AccountType = $this->getAccountType($Account['Account_ID']);
            if($AccountType == 'Business') {
                $this->updateFirstAndLastNameForThisUserInThisAccountsPeopleTable($Account['Account_ID'], $User_ID, $FirstName, $LastName);
            }
        }
    }
    
    public function updateFirstAndLastNameForThisUserInThisAccountsPeopleTable($Account_ID, $User_ID, $FirstName, $LastName) {
        $PersonObject_ID = $this->getPersonObject_IDForThisAccount($Account_ID);
        
        $DatabasePrefix = $Account_ID.'_'.'App_';
        $Columns[0] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'Origin_User_ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $User_ID;
        $PersonIDForThisUser = $this->InteractWithPersistentStorage->row_read($DatabasePrefix.'AppData.Object_'.$PersonObject_ID, $Columns, $Where)[0]['ID'];

        // Need to go through the proper model in order to set up ORG IDs etc correctly and go via FieldRules etc etc.
        // Have to use the ValidatePermissionsDummyForSystemToolsThatProvidesFullAccess because this model can be called from the SystemTools section as well and the SystemUser probably wont have Permission to this app.
        $ValidatePermissionsDummy = new ValidatePermissionsDummyForSystemToolsThatProvidesFullAccess;            
        $AppToInteractWithPersistentStorage = new AppToInteractWithPersistentStorage($this->InteractWithPersistentStorage, $ValidatePermissionsDummy, $Account_ID.'_'.'App');

        $Variables[0] = $PersonObject_ID;
        $Object_model = new Object_model($AppToInteractWithPersistentStorage, $ValidatePermissionsDummy, $Variables);
        $Columns[0] = 'First_Name';
        $Columns[1] = 'Last_Name';
        $Values[0] = $FirstName;
        $Values[1] = $LastName;
        $Object_model->Instance_edit($PersonIDForThisUser, $Columns, $Values, '', 'No');
    }
                                   
    private function getPersonObject_IDForThisAccount($Account_ID) {
        $Columns[0] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'SingularName';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = 'Person';
        $Results = $this->InteractWithPersistentStorage->row_read($Account_ID.'_App_AppDefinition.Objects', $Columns, $Where);
        if(sizeof($Results) == 0) {
            throw new Exception('');
        } else {
            return $Results[0]['ID'];   
        }
    }
    
    private function getNonUsersSubgroup_IDForThisAccount($Account_ID, $PersonObject_ID) {
        $Columns[0] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'Element_Type';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = 'Object';
        $Where['Groups'][0]['Conditions'][1]['FieldName'] = 'Element_ID';
        $Where['Groups'][0]['Conditions'][1]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][1]['Value'] = $PersonObject_ID;
        $Where['Groups'][0]['Conditions'][2]['FieldName'] = 'PluralName';
        $Where['Groups'][0]['Conditions'][2]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][2]['Value'] = 'NonUsers';
        $Results = $this->InteractWithPersistentStorage->row_read($Account_ID.'_App_AppDefinition.Subgroups', $Columns, $Where);
        if(sizeof($Results) != 1) {
            throw new Exception('The NonUsers Subgroup should exist.');
        } else {
            return $Results[0]['ID'];   
        }
    }
    
    public function updatePassword($User_ID, $HashedPassword) {
        $this->updateSpecificFieldForThisUserID($User_ID, 'Password', $HashedPassword);
	}
    
    public function update_EmailThatAccountHasBeenBlockedSent($User_ID, $YesOrNo) {
        $this->updateSpecificFieldForThisUserID($User_ID, 'EmailThatAccountHasBeenBlockedSent', $YesOrNo);
    }
    
    public function incrementNumberOfFailedSignInAttempts($User_ID) {
        $NumberOfFailedSignInAttempts = $this->getNumberOfFailedSignInAttempts($User_ID);
        $NumberOfFailedSignInAttempts++;
        
        $this->updateSpecificFieldForThisUserID($User_ID, 'NumberOfFailedSignInAttempts', $NumberOfFailedSignInAttempts);
    }
    
    public function resetNumberOfFailedSignInAttempts($User_ID) {
        $this->updateSpecificFieldForThisUserID($User_ID, 'NumberOfFailedSignInAttempts', '0');
    }
    
    public function createSecureKey($User_ID, $Type) {
        $SecureKeyRecord = $this->getSecureKeyRecord($User_ID, $Type);
        if($SecureKeyRecord == 'NoRecord') {
            return $this->createSecureKey2($User_ID, $Type);
        } else {
            return $SecureKeyRecord['SecureKey'];
        }
        
    }
    
    public function createSecureKey2($User_ID, $Type) {
        $SecureKey = $this->generateSecureKey();
        $this->saveThisSecureKey($User_ID, $Type, $SecureKey);
        return $SecureKey;
    }
    
    private function generateSecureKey() {
        do {
            $SecureKey = md5(uniqid(mt_rand(), false));
        }
        while($this->isThisSecureKeyAlreadyBeingUsed($SecureKey));
        return $SecureKey;
    }

    private function isThisSecureKeyAlreadyBeingUsed($SecureKey) {
        $Columns[0] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'SecureKey';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $SecureKey;
        $Results = $this->InteractWithPersistentStorage->row_read('Master.SecureKeys', $Columns, $Where);
        if(sizeof($Results) == 0) {
            return false;
        } elseif(sizeof($Results) == 1) {
            return true;
        } else {
            throw new Exception('Duplicate Secure Keys are not allowed.');
        }
    }
    
    private function saveThisSecureKey($User_ID, $Type, $SecureKey) {
        $Columns[0] = 'User_ID';
        $Columns[1] = 'Type';
        $Columns[2] = 'SecureKey';
        $Values[0] = $User_ID;
        $Values[1] = $Type;
        $Values[2] = $SecureKey;
        $this->InteractWithPersistentStorage->row_add('Master.SecureKeys', $Columns, $Values);
    }
    
    public function deleteSecureKey($User_ID, $Type) {
        $SecureKeyRecord = $this->getSecureKeyRecord($User_ID, $Type);
        if($SecureKeyRecord != 'NoRecord') { // May have already been deleted by another request so check first.
            $this->InteractWithPersistentStorage->row_delete('Master.SecureKeys', $SecureKeyRecord['ID']);
        }
    }
    
    public function getSecureKeyRecord($User_ID, $Type) {
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'User_ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $User_ID;
        $Where['Groups'][0]['Conditions'][1]['FieldName'] = 'Type';
        $Where['Groups'][0]['Conditions'][1]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][1]['Value'] = $Type;
        $Results = $this->InteractWithPersistentStorage->row_read('Master.SecureKeys', 'All', $Where);
        
        if(sizeof($Results) == 0) {
            return 'NoRecord';
        } elseif(sizeof($Results) == 1) {
            if(date("Y-m-d H:i:s") < date('Y-m-d H:i:s', strtotime($Results[0]['Date_Created'] . ' +1 day'))) {
                if($Results[0]['NumberOfAttempts'] > 3) {
                    // Do not delete the SecureKeyRecord as if we did then it would allow a hacker to request a new secure key (e.g. for forgotton password) and start attempting bruteforcing immidiately again, by keeping the record for 24 hours it enables us to block if for 24 hours.
                    throw new UserManagementAccessException('This action has been blocked for 24 hours due to repeated failed attempts.');
                } else {
                    return $Results[0];
                }
            } else {
                // Can't call deleteSecureKey() as will create an infinite loop.
                $this->InteractWithPersistentStorage->row_delete('Master.SecureKeys', $Results[0]['ID']);
                return 'NoRecord';
            }
        } elseif(sizeof($Results) > 1) {
            throw new Exception('There should only be one secure key per user per type.');
        }
    }
    
    // Only use this function for 'AccountActivation' because AccountActivation requests can not come from externally because they can only come from a Master User or System User, therefore we don't need to worry about bruteforcing new requests for them (but of course we still need to prevent for bruteforcing once the actual request has been sent, therefore I still leave all the rest of the stuff for SecureKeys the same for AccountActivation).
    public function resetAccountActivationSecureKey($User_ID) {
        // Don't use deleteSecureKey() because that will throw an exception if NumberOfAttempts>3 (to prevent bruteforcing) but we don't need to worry about bruteforcing new requests for AccountActivation as detailed above. 
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'User_ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $User_ID;
        $Where['Groups'][0]['Conditions'][1]['FieldName'] = 'Type';
        $Where['Groups'][0]['Conditions'][1]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][1]['Value'] = 'AccountActivation';
        $Results = $this->InteractWithPersistentStorage->row_read('Master.SecureKeys', 'All', $Where);
        if(sizeof($Results) == 1) {
            $this->InteractWithPersistentStorage->row_delete('Master.SecureKeys', $Results[0]['ID']);
        }
        
        return $this->createSecureKey($User_ID, 'AccountActivation');
    }
    
    public function incrementNumberOfFailedSecureKeyAttemptsByOne($SecureKeyRecord_ID) {
        // Anybody can keep requesting a forgotten password for an email address to keep creating a new secure key for it therefore checking against the DateTimeCreated field (i.e. a 24 hour expiry) isn’t much use against this and so I need to use this NumberOfAttempts check to prevent brute forcing.
        
        $Columns[0] = 'NumberOfAttempts';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $SecureKeyRecord_ID;
        $NumberOfAttempts = $this->InteractWithPersistentStorage->row_read('Master.SecureKeys', 'All', $Where)[0]['NumberOfAttempts'];
            
        $NumberOfAttempts++;
        
        $Columns[0] = 'NumberOfAttempts';
        $Values[0] = $NumberOfAttempts;
        $this->InteractWithPersistentStorage->row_edit('Master.SecureKeys', $Columns, $Values, $SecureKeyRecord_ID);
    }
    
    public function deleteThisUsersToAccountsRecord($Account_ID, $User_ID) {
        $ID = $this->getUsersToAccountsRecordIDForThisUserAndAccount($Account_ID, $User_ID);
        $this->InteractWithPersistentStorage->row_delete('Master.UsersToAccounts', $ID);
    }
    
    public function restoreThisUsersToAccountsRecord($Account_ID, $User_ID) {
        $ID = $this->getDeletedUsersToAccountsRecordIDForThisUserAndAccount($Account_ID, $User_ID);
        $this->InteractWithPersistentStorage->row_restore('Master.UsersToAccounts', $ID);
    }
    
    private function getUsersToAccountsRecordIDForThisUserAndAccount($Account_ID, $User_ID) {
        $Columns[0] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'User_ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $User_ID;
        $Where['Groups'][0]['Conditions'][1]['FieldName'] = 'Account_ID';
        $Where['Groups'][0]['Conditions'][1]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][1]['Value'] = $Account_ID;
        $Results = $this->InteractWithPersistentStorage->row_read('Master.UsersToAccounts', $Columns, $Where);
        if(sizeof($Results) != 1) {
            throw new Exception('This UsersToAccounts record does not exist.');
        }
        return $Results[0]['ID'];
    }
    
    private function getDeletedUsersToAccountsRecordIDForThisUserAndAccount($Account_ID, $User_ID) {
        $Columns[0] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'User_ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $User_ID;
        $Where['Groups'][0]['Conditions'][1]['FieldName'] = 'Account_ID';
        $Where['Groups'][0]['Conditions'][1]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][1]['Value'] = $Account_ID;
        $Where['Groups'][0]['Conditions'][2]['FieldName'] = 'Deleted';
        $Where['Groups'][0]['Conditions'][2]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][2]['Value'] = 'Yes';
        $Results = $this->InteractWithPersistentStorage->row_read('Master.UsersToAccountsArchives', $Columns, $Where);
        if(sizeof($Results) != 1) {
            throw new Exception('This Deleted UsersToAccounts record does not exist.');
        }
        return $Results[0]['ID'];
    }
    
    public function isThisUserAMasterUser($Account_ID, $User_ID) {
        $Columns[0] = 'MasterUser';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'User_ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $User_ID;
        $Where['Groups'][0]['Conditions'][1]['FieldName'] = 'Account_ID';
        $Where['Groups'][0]['Conditions'][1]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][1]['Value'] = $Account_ID;
        $Results = $this->InteractWithPersistentStorage->row_read('Master.UsersToAccounts', $Columns, $Where);
        if(sizeof($Results) != 1) {
            throw new Exception('This UsersToAccounts record does not exist.');
        }
        return $Results[0]['MasterUser'];
    }
    
    public function getThisAccountType($Account_ID) {
        $Columns[0] = 'Account_Type';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $Account_ID;
        $Results = $this->InteractWithPersistentStorage->row_read('Master.Accounts', $Columns, $Where);
        if(sizeof($Results) != 1) {
            throw new Exception('This Account does not exist.');
        }
        return $Results[0]['Account_Type'];
    }
}
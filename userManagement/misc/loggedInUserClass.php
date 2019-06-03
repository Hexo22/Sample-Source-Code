<?php
require_once('UsefulFunctions.php'); // This must go before the below as the below call 'session_start' and this class must be declared before any 'session_start's.
class loggedInUser {    
    public $User_ID;
    public $UserDetails;
    public $DateTimeLastActive;
    public $MasterUser;
    public $SystemUser;
    
    public function __construct($User_ID) {
        $InteractWithPersistentStorage = InteractWithPersistentStorageFactoryForMySQL::create();
        
        $this->User_ID = $User_ID;
        $Users_model = new Users_model($InteractWithPersistentStorage);
        $this->setUserDetails($InteractWithPersistentStorage);
        $this->setMasterUser($InteractWithPersistentStorage);
        $this->setSystemUser($InteractWithPersistentStorage);
        $Users_model->updateLastSignInDateTimeForThisUser($this->User_ID);
        
        $this->updateDateTimeLastActive();
    }
    
    public function setUserDetails($InteractWithPersistentStorage) {
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $this->User_ID;
        $this->UserDetails = $InteractWithPersistentStorage->row_read('Master.Users', 'All', $Where)[0];
        $this->UserDetails['DisplayName'] = $this->UserDetails['FirstName'] . ' ' . $this->UserDetails['LastName'];   
    }
    
    public function setMasterUser($InteractWithPersistentStorage) {
        $Columns[0] = 'Account_ID';
        $Columns[1] = 'MasterUser';
        $Columns[2] = 'Default_Division_For_Business_Accounts';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'User_ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $this->User_ID;
        $Results = $InteractWithPersistentStorage->row_read('Master.UsersToAccounts', $Columns, $Where);
        foreach($Results as $Result) {
            $this->MasterUser[$Result['Account_ID']] = $Result['MasterUser'];
            $this->UserDetails['Default_Division_For_Business_Accounts'] = $Result['Default_Division_For_Business_Accounts'];
        }
    }
    
    public function setSystemUser($InteractWithPersistentStorage) {
        $Columns[0] = 'ID';
        $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'User_ID';
        $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
        $Where['Groups'][0]['Conditions'][0]['Value'] = $this->User_ID;
        $Results = $InteractWithPersistentStorage->row_read('Master.SystemUsers', $Columns, $Where);
        if(sizeof($Results) == 0) {
            $this->SystemUser = 'No';
        } elseif(sizeof($Results) == 1) {
            $this->SystemUser = 'Yes';
        } else {
            throw new Exception('There should only be one SystemUsers record per user.');   
        }
    }
    
    public function updateDateTimeLastActive() {
        $this->DateTimeLastActive = date("Y-m-d H:i:s");
    }
	
	public function logUserOut() {
		destorySession("LoggedInUser");
	}
}
?>
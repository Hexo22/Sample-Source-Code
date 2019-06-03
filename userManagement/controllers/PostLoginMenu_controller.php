<?php

class PostLoginMenu_controller extends UserManagementController {
    public function postLoginMenu() {
        $this->UsersModel = $this->model('Users', '');
        
        $LoggedInUser = $_SESSION["LoggedInUser"];
        $User_ID = $LoggedInUser->UserDetails['ID'];
        
        $data['Email'] = $LoggedInUser->UserDetails['Email'];
        $data['Accounts'] = $this->UsersModel->getAccountsThisUserHasAccessTo($User_ID);
        $data['IsThisUserASystemUser'] = isThisUserASystemUser($this->InteractWithPersistentStorage, $User_ID);
        
        if(sizeof($data['Accounts']) == 1 && $data['IsThisUserASystemUser'] == 'No') {
            header("Location: ".getSiteURL()."AppIndex.php?AppName=".$data['Accounts'][0]['Account_ID']."_App");
        } else {
            $this->view('PostLoginMenu/postLoginMenu', $data, '');
        }
    }
}
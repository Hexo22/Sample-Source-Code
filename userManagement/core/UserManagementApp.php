<?php

class UserManagementApp {
    private $controller = 'LogInAndOut';
    private $method = 'login';
    private $params = [];
        
    public function __construct() {  
        if(!isset($_SESSION)) {
            session_start();
        }
        
        $this->setCMP();
        
        // Have to create this $InteractWithPersistentStorage here and pass the same $InteractWithPersistentStorage to all the Controllers so that transactions aren't cut off when create a new Controller.
        $InteractWithPersistentStorage = InteractWithPersistentStorageFactoryForMySQL::create();
        
        $WrapInTransaction = $this->shouldIWrapThisCallInATransaction();
        if($WrapInTransaction == 'Yes') {
            $InteractWithPersistentStorage->transaction_begin();
            try {
                $this->callControllerMethod($InteractWithPersistentStorage);
                $InteractWithPersistentStorage->transaction_commit();
            } catch (Exception $e) {
                $InteractWithPersistentStorage->transaction_rollback();
                throw $e; // rethrow.
            }
        } else {
            $this->callControllerMethod($InteractWithPersistentStorage);
        }
    }
    
    private function setCMP() {
        $Data = $_GET;
        
        if(isset($Data['Controller']) && $Data['Controller'] != '') {
            $this->controller = $Data['Controller'];
        }
        if(isset($Data['Method']) && $Data['Method'] != '') {
            $this->method = $Data['Method'];
        }
        if(isset($Data['Params']) && $Data['Params'] != '') { 
            $this->params = $Data['Params'];
        }
    }
        
    private function shouldIWrapThisCallInATransaction() {        
        if($this->controller == 'ForgotPassword') {
            if($this->method == 'requestForgotPassword' || $this->method == 'forgotPassword') {
                return 'No';
            } elseif($this->method == 'requestForgotPasswordSubmit' || $this->method == 'forgotPasswordSubmit') {
                return 'Yes';
            }
        } elseif($this->controller == 'LogInAndOut') {
            if($this->method == 'login' || $this->method == 'logout') {
                return 'No';
            } elseif($this->method == 'loginSubmit' || $this->method == 'unlockAccount') {
                return 'No';
            }
        } elseif($this->controller == 'UserAccountActivation') {
            if($this->method == 'userAccountActivation') {
                return 'No';
            } elseif($this->method == 'userAccountActivationSubmit') {
                return 'Yes';
            }
        } elseif($this->controller == 'ViewAndAmmendUserAccountDetails') {
            if($this->method == 'viewUserDetails' || $this->method == 'updatePassword' || $this->method == 'updateEmail' || $this->method == 'updateUserDetails') {
                return 'No';
            } elseif($this->method == 'updatePasswordSubmit' || $this->method == 'updateEmailSubmit' || $this->method == 'updateUserDetailsSubmit') {
                return 'Yes';
            }
        } elseif($this->controller == 'PostLoginMenu') {
            if($this->method == 'postLoginMenu') {
                return 'No';
            }
        }
        
        if(getEnvironment() == 'Development') {
            throw new Exception('Need to set a check here for this controller and method.');
        } else {
            // Probably robots so just die.
            die();
        }
    }
    
    private function callControllerMethod($InteractWithPersistentStorage) {
        $ControllerScriptName = $this->controller . '_controller';
        
        if(!file_exists(__DIR__.'/../controllers/' . $ControllerScriptName . '.php')) {
            throw new Exception('This Controller ('.$this->controller.') does not exist.');
        }
        
        $this->validateIfUserShouldBeLoggedIn($this->controller, $this->method);
        
        require_once(__DIR__.'/../controllers/' . $ControllerScriptName . '.php');
        $this->actualController = new $ControllerScriptName($InteractWithPersistentStorage);
        
        if(!method_exists($this->actualController, $this->method)) {
            throw new Exception('This Method ('.$this->method.') does not exist in this Controller ('.$this->controller.').');
        }
        
        // I previously tried call_user_func_array instead of call_user_func but it would not work, see this link for why - http://stackoverflow.com/questions/2553114/pass-associative-arrays-in-call-user-func-array.
        call_user_func([$this->actualController, $this->method], $this->params);
    }
    
    private function validateIfUserShouldBeLoggedIn($Controller, $Method) {
        if($Controller == 'ForgotPassword') {
            $this->userShouldNotBeLoggedIn();
        } elseif($Controller == 'LogInAndOut') {
            if($Method == 'login' || $Method == 'loginSubmit' || $Method == 'unlockAccount') {
                $this->userShouldNotBeLoggedIn();
            } elseif($Method == 'logout') {
                $this->userShouldBeLoggedIn();
            } else {
                throw new Exception('Need to set a check here for this controller and method.');
            }
        } elseif($Controller == 'UserAccountActivation') {
            $this->userShouldNotBeLoggedIn();
        } elseif($Controller == 'ViewAndAmmendUserAccountDetails') {
            $this->userShouldBeLoggedIn();
        } elseif($Controller == 'PostLoginMenu') {
            $this->userShouldBeLoggedIn();
        } else {
            throw new Exception('Need to set a check here for this controller and method.');
        }
    }
    
    private function userShouldNotBeLoggedIn() {
        if(isUserLoggedIn('Yes')) {
            header("Location: ".getSiteURL()."");
            die();
        }
    }
    
    private function userShouldBeLoggedIn() {
        if(!isUserLoggedIn('Yes')) {
            header("Location: ".getSiteURL()."");
            die();
        }
    }
}
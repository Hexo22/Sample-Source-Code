<?php
$Header = <<<EOD
<h3>Origin Account Activation</h3>
<h4>{$data['Email']}</h4>
EOD;

$FormOrMessage = 'Form';

$FormSubmitURL = $_SERVER['PHP_SELF'].'?Controller=UserAccountActivation&Method=userAccountActivationSubmit&User_ID='.$data['User_ID'].'&SecureKey='.$data['SecureKey'];

$FormInputs[0] = '<input type="text" name="firstname" placeholder="First Name" />';
$FormInputs[1] = '<input type="text" name="lastname" placeholder="Last Name" />';
$FormInputs[2] = '<input type="password" name="password" placeholder="Password" />';
$FormInputs[3] = '<input type="password" name="passwordc" placeholder="Re-type Password" />';

$FormSubmitString = 'Register';

$DisplayHomePageLink = 'Yes';
$DisplayForgottenPasswordLink = 'No';
?>
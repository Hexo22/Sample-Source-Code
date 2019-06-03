<?php
$Header = <<<EOD
<h3>Forgotten Password</h3>
<h4>{$data['Email']}</h4>
EOD;

$FormOrMessage = 'Form';

$FormSubmitURL = $_SERVER['PHP_SELF'].'?Controller=ForgotPassword&Method=forgotPasswordSubmit&User_ID='.$data['User_ID'].'&SecureKey='.$data['SecureKey'];

$FormInputs[0] = '<input type="password" name="password" placeholder="Password" />';
$FormInputs[1] = '<input type="password" name="passwordc" placeholder="Re-type Password" />';

$FormSubmitString = 'Submit';

$DisplayHomePageLink = 'Yes';
$DisplayForgottenPasswordLink = 'No';
?>
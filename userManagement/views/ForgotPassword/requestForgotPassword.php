<?php
$Header = "<h3>Request Forgotten Password</h3>";

$FormOrMessage = 'Form';

$FormSubmitURL = $_SERVER['PHP_SELF'].'?Controller=ForgotPassword&Method=requestForgotPasswordSubmit';

$FormInputs[0] = '<input type="text" name="email" placeholder="Email" />';

$FormSubmitString = 'Submit';

$DisplayHomePageLink = 'Yes';
$DisplayForgottenPasswordLink = 'No';
?>
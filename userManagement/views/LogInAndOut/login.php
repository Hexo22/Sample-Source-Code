<?php
$Header = <<<EOD
<h2>Origin</h2>
<h2>Sign In</h2>
EOD;

$FormOrMessage = 'Form';

if(isset($data['Message']) && $data['Message'] != '') {
    $MessageAboveForm = $data['Message'];
}

$FormSubmitURL = $_SERVER['PHP_SELF'].'?Controller=LogInAndOut&Method=loginSubmit&AppName='.$data['AppName'];

$FormInputs[0] = '<label>Email:</label><input type="text" name="email" />';
$FormInputs[1] = '<label>Password:</label><input type="password" name="password" />';

$FormSubmitString = 'Sign In';

$DisplayHomePageLink = 'No';
$DisplayForgottenPasswordLink = 'Yes';
?>
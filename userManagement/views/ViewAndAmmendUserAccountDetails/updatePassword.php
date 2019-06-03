<?php
$Header = <<<EOD
<h3>Update Password</h3>
<h4>{$data['Email']}</h4>
EOD;

$FormOrMessage = 'Form';

$FormSubmitURL = $_SERVER['PHP_SELF'].'?Controller=ViewAndAmmendUserAccountDetails&Method=updatePasswordSubmit';

$FormInputs[0] = '<label>Current Password :</label><input type="password" name="existingpassword" />';
$FormInputs[1] = '<label>New Password :</label><input type="password" name="password" />';
$FormInputs[2] = '<label>Confirm New Password :</label><input type="password" name="passwordc" />';

$DisplayCancelButtonBackToViewUserDetails = 'Yes';
$FormSubmitString = 'Update';

$DisplayHomePageLink = 'No';
$DisplayForgottenPasswordLink = 'No';
?>
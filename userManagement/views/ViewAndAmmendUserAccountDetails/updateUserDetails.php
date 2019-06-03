<?php
$Header = <<<EOD
<h3>Update User Details</h3>
<h4>{$data['Email']}</h4>
EOD;

$FormOrMessage = 'Form';

$FormSubmitURL = $_SERVER['PHP_SELF'].'?Controller=ViewAndAmmendUserAccountDetails&Method=updateUserDetailsSubmit';

$FormInputs[0] = '<input type="text" name="firstname" value="'.phpToHTMLInputValue($data['FirstName']).'" />';
$FormInputs[1] = '<input type="text" name="lastname" value="'.phpToHTMLInputValue($data['LastName']).'" />';

$DisplayCancelButtonBackToViewUserDetails = 'Yes';
$FormSubmitString = 'Update';

$DisplayHomePageLink = 'No';
$DisplayForgottenPasswordLink = 'No';
?>
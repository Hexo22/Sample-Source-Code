<?php
$Header = <<<EOD
<h3>Update Email Address</h3>
<h4>{$data['Email']}</h4>
EOD;

$FormOrMessage = 'Form';

$FormSubmitURL = $_SERVER['PHP_SELF'].'?Controller=ViewAndAmmendUserAccountDetails&Method=updateEmailSubmit';

$FormInputs[0] = '<label>Email:</label><input type="text" id="email" name="email" value="'.phpToHTMLInputValue($data['Email']).'" onchange="validateEmail(this.value, this.id);" />';

$DisplayCancelButtonBackToViewUserDetails = 'Yes';
$FormSubmitString = 'Update';

$DisplayHomePageLink = 'No';
$DisplayForgottenPasswordLink = 'No';
?>
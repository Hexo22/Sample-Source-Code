<?php
$Header = <<<EOD
<h3>Post Login Menu</h3>
<h4>{$data['Email']}</h4>
EOD;

$FormOrMessage = 'Message';

if(sizeof($data['Accounts']) == 0 && $data['IsThisUserASystemUser'] == 'No') {
    $Message = '<p>You Currently Have Access To No Origin Accounts.<br>Contact support@orginapplication.com if you believe this is an error.</p>';
} else {
    $Message = '<p><b>Click on a link below to access that account</b></p>';
    if($data['IsThisUserASystemUser'] == 'Yes') {    
        $Message .= '<p><a href="'.getSiteURL().'SystemTools.php">System Tools</a></p>';
    }
    foreach($data['Accounts'] as $Account) {
        $Message .= '<p><a href="'.getSiteURL().'AppIndex.php?AppName='.$Account['Account_ID'].'_App">'.$Account['AccountName'].'</a></p>';
    }
}

$Message .= '<br><p><a style="color:black;" href="'.getSiteURL().'UserManagement.php?Controller=ViewAndAmmendUserAccountDetails&Method=viewUserDetails">Click here to view and edit your user account details</a></p>';
$Message .= '<p><a style="color:black;" href="'.getSiteURL().'UserManagement.php?Controller=LogInAndOut&Method=logout">Click here to log out</a></p>';

$DisplayHomePageLink = 'No';
$DisplayForgottenPasswordLink = 'No';
?>
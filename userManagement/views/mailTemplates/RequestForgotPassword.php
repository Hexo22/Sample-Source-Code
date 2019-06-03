<?php

$SiteURL = getSiteURL();

$EmailContent = <<<EOD
A lost password request has been submitted for your user account.
<br><br>
To reset your password <a href='{$SiteURL}UserManagement.php?Controller=ForgotPassword&Method=forgotPassword&User_ID={$TemplateData['User_ID']}&SecureKey={$TemplateData['SecureKey']}'>click here</a>.
EOD;
?>
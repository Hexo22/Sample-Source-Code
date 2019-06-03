<?php

$SiteURL = getSiteURL();

$EmailContent = <<<EOD
You have been invited to join the Origin Account '{$TemplateData['AccountName']}'.
<br><br>
To set up your user account <a href='{$SiteURL}UserManagement.php?Controller=UserAccountActivation&Method=userAccountActivation&User_ID={$TemplateData['User_ID']}&SecureKey={$TemplateData['SecureKey']}'>click here</a>.
EOD;
?>
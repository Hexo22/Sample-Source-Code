<?php

$SiteURL = getSiteURL();

$EmailContent = <<<EOD
Your Origin account has been blocked due to repeated failed login attempts.
<br><br>
If this was not you then please contact support@OriginApplication.com.
<br><br>
If this was you then <a href='{$SiteURL}UserManagement.php?Controller=LogInAndOut&Method=unlockAccount&User_ID={$TemplateData['User_ID']}&SecureKey={$TemplateData['SecureKey']}'>click here</a> to unlock your account.
<br>
(On the login page you can click 'Forgot Password' to reset your password if you have forgotten it.)
EOD;
?>
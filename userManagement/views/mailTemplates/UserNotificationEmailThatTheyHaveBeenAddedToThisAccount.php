<?php

$SiteURL = getSiteURL();

$EmailContent = <<<EOD
You have been added to the Origin Account '{$TemplateData['AccountName']}'.
<br><br>
Login at <a href='OriginApplication.com' >OriginApplication.com</a> to access your accounts.
EOD;
?>
<?php
if(isset($TemplateData['FirstName']) && $TemplateData['FirstName'] != '' && isset($TemplateData['LastName']) && $TemplateData['LastName'] != '') {
    $Header = 'Hello '.$TemplateData['FirstName'] . ' ' . $TemplateData['LastName'] . ',';
} else {
    $Header = 'Hello,';
}
$Header .= '<br><br>';
?>
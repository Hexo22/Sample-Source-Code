<?php
$Header = <<<EOD
<h2>User Details</h2>
EOD;

$FormOrMessage = 'Message';

$URL = $_SERVER['PHP_SELF'].'?Controller=ViewAndAmmendUserAccountDetails&Method=';

$Message = <<<EOD
    <table style='margin:0 auto;' class='border centertd centerth padding thD8D8D8'>
        <tr>
            <th colspan='2'>User Details</th>
        </tr>
        <tr>
            <td>User ID</td>
            <td>{$data['User_ID']}</td>
        </tr>
        <tr>
            <td>Email Address</td>
            <td>{$data['Email']}</td>
        </tr>
        <tr>
            <td>First Name</td>
            <td>{$data['FirstName']}</td>
        </tr>
        <tr>
            <td>Last Name</td>
            <td>{$data['LastName']}</td>
        </tr>
    </table>
    <br>
    <p><a href="{$URL}updateEmail">Click here to update your email address.</a></p>
    <p><a href="{$URL}updateUserDetails">Click here to update your first or last name.</a></p>
    <p><a href="{$URL}updatePassword">Click here to update your password.</a></p>
</p>
EOD;

$DisplayHomePageLink = 'No';
$DisplayForgottenPasswordLink = 'No';
?>
<?php
/* Based on UserPie Version: 1.0 : http://userpie.com */

function sendUserManagementEmail($EmailAddress, $TemplateName, $TemplateData, $Subject) {       
    $Header; $EmailContent; $Footer;
    include(__DIR__."/views/mailTemplates/Header.php");
    include(__DIR__."/views/mailTemplates/" . $TemplateName . ".php");
    include(__DIR__."/views/mailTemplates/Footer.php");
    if(!$EmailContent || empty($EmailContent) || $EmailContent == '') {
        throw new Exception('Empty email.');
    }
    $EmailContent = $Header.$EmailContent.$Footer;
    new Email($EmailAddress, $EmailContent, $Subject);
}

function sanitize($str) {
    return strtolower(strip_tags(trim(($str))));
}

function isValidEmail($email) {
    return preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",trim($email));
}

function minMaxRange($min, $max, $what) {
    if(strlen(trim($what)) < $min) {
       return true;
    } elseif(strlen(trim($what)) > $max) {
       return true;
    } else {
       return false;
    }
}

//@ Thanks to - http://phpsec.org
function generateHash($plainText, $salt = null) {
    $plainText = trim($plainText);
    if($salt === null) {
        $salt = substr(md5(uniqid(rand(), true)), 0, 25);
    } else {
        $salt = substr($salt, 0, 25);
    }
    return $salt . sha1($salt . $plainText);
}

function getUniqueCode($length = "") {	
    $code = md5(uniqid(rand(), true));
    if($length != "") {
        return substr($code, 0, $length);
    } else {
        return $code;
    }
}

function errorBlock($errors) {
    if(!count($errors) > 0) {
        return false;
    } else {
        echo "<ul style='margin-left:0px;'>";
        foreach($errors as $error) {
            echo "<li style='text-align:center;'>".$error."</li>";
        }
        echo "</ul>";
    }
}

function destorySession($name) {
    if(isset($_SESSION[$name])) {
        $_SESSION[$name] = NULL;
        unset($_SESSION[$name]);
    } 
}
?>
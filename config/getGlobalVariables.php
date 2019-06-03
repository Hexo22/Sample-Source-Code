<?php

if(!isset($_SESSION)) {
    session_start();
}

function getSiteURL() {
    return "https://".$_SERVER['HTTP_HOST']."/";
}

function getGlobalDatabasePrefix() {
    return 'Origin_';
}

function isThisUserASystemUser($InteractWithPersistentStorage, $User_ID) {
    $Columns[0] = 'ID';
    $Where['Groups'][0]['Conditions'][0]['FieldName'] = 'User_ID';
    $Where['Groups'][0]['Conditions'][0]['Comparison'] = '=';
    $Where['Groups'][0]['Conditions'][0]['Value'] = $User_ID;
    $SystemUsers = $InteractWithPersistentStorage->row_read('Master.SystemUsers', $Columns, $Where);
    if(sizeof($SystemUsers) == 0) {
        return 'No';
    } elseif(sizeof($SystemUsers) == 1) {
        return 'Yes';
    } else {
        throw new Exception('There should only be one SystemUsers record per user.');
    }
}
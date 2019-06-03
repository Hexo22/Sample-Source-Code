<?php
function createJSForOnLoadFunctions($JSFunctionCallsOnLoad) {
    return "$(function() { " . $JSFunctionCallsOnLoad . " });";
}

function returnAjaxData($Data) {
    return phpVariablesToJSVariables($Data);
}

function phpVariablesToJSVariables($String) {
    return json_encode($String);
}

function phpToHTMLInputValue($String) { // i.e. when directly echoing out php into the value='' attribute of a form input
    return htmlspecialchars($String, ENT_QUOTES, "UTF-8");
}
?>
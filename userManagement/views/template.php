<?php require_once($viewName.'.php'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title><?php echo swapUnderscoresForSpaces($viewName); ?> | Origin </title>
        
        <?php
        // I don't want the app code viewable when not logged in.
        if(isUserLoggedIn('Yes')) {
            echo getAllAssetsOfAppAndType('App', 'css');
            echo getAllAssetsOfAppAndType('UserManagement', 'css');
            echo getAllAssetsOfAppAndType('App', 'js');
            echo getAllAssetsOfAppAndType('UserManagement', 'js');
        } else {
            echo getAllAssetsOfAppAndType('UserManagement', 'css');
            echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>';
            echo getAllAssetsOfAppAndType('UserManagement', 'js');
        }
        ?>
        
        <script> window.onbeforeunload = null; // Remove the "Are you sure you want to leave this page?" pop up. </script>
    </head>
    <body>
        <div class="modal-ish">
            <div class="modal-header">
                <?php echo $Header; ?>
            </div>
            <?php
            if($FormOrMessage == 'Form') {
                ?>
                <div class="modal-body">
                    <?php
                    if(isset($MessageAboveForm) && $MessageAboveForm != '') {
                        echo '<b>'.$MessageAboveForm.'</b><br><br>';
                    }
                    if(isset($errors) && count($errors) > 0) {
                        echo '<div id="errors">';
                        errorBlock($errors);
                        echo '</div>';
                    }
                    ?>
                    <!--- Add the 'onsubmit' to prevent multiple quick clicks. --->
                    <form action="<?php echo $FormSubmitURL; ?>" method="post" onsubmit="document.getElementById('formSubmitButton').disabled = 1;">
                        <?php
                        foreach($FormInputs as $Input) {
                            echo '<p>'.$Input.'</p>';
                        }
                        if($DisplayForgottenPasswordLink == 'Yes') { 
                            ?>
                            <a href="<?php echo $_SERVER['PHP_SELF'].'?Controller=ForgotPassword&Method=requestForgotPassword' ?>">Forgot Password?</a>
                            <?php
                        }
                        ?>
                </div>
                <div class="modal-footer">
                    <?php
                    if(isset($DisplayCancelButtonBackToViewUserDetails) && $DisplayCancelButtonBackToViewUserDetails == 'Yes') {
                        ?>
                        <input onclick="window.location.assign('<?php echo $_SERVER['PHP_SELF']; ?>?Controller=ViewAndAmmendUserAccountDetails&Method=viewUserDetails');" type="button" class="btn btn-primary" value="Cancel" />
                        <?php
                    }
                    ?>
                    <input id="formSubmitButton" type="submit" class="btn btn-primary" value="<?php echo $FormSubmitString; ?>" />
                </div>
                        </form>
                <?php
            } elseif($FormOrMessage == 'Message') {
                ?>
                <div class="modal-body">
                    <?php echo $Message; ?>
                </div>
                <div class="modal-footer" style='text-align:center; color:#4297D7;'>
                    <b><a href="<?php echo getSiteURL(); ?>">Origin</a></b>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
        if($DisplayHomePageLink == 'Yes') {
            ?>
            <div class="clear"></div>
            <p style="margin-top:30px; text-align:center;">
                <a href="<?php echo getSiteURL(); ?>">Home Page</a>
            </p>
            <?php
        }
        ?>
    </body>
</html>
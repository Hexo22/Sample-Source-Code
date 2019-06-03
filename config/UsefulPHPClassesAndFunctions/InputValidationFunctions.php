<?php

function validateIsLessThan250Characters($value)
{
    if($value != '')
    {
        if(strlen($value) > 250) {
            throw new InputValidationException('Invalid String, must be less than 250 characters');
        }
    }
}

function validateDate($value)
{
    if($value != '')
    {
        $spliter = explode('-', $value);
        if(sizeof($spliter) != 3)
        {
            throw new InputValidationException('Invalid Date');
        }
    
        // return 0 if fine or 1 if error.
        // https://www.w3schools.com/php/func_date_checkdate.asp
        if(checkdate($spliter[1], $spliter[0], $spliter[2]))
        {
            throw new InputValidationException('Invalid Date');
        }
    }
}

function validateTelephoneNumber($value)
{
    if($value != '')
    {
        if(!is_numeric($value))
        {
            throw new InputValidationException('Invalid Telephone Number');
        }

        if(strlen($value) < 10 || strlen($value) > 12)
        {
            throw new InputValidationException('Invalid Telephone Number');
        }
    }
}

function validateEmail($value)
{
    if($value != '')
    {
        if(!preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$value)) 
        { 
            throw new InputValidationException('Invalid Email');
        }
    }
}

function validateMobileNumber($value)
{
    if($value != '')
    {
        if(!is_numeric($value))
        {
            throw new InputValidationException('Invalid Telephone Number');
        }

        if(strlen($value) != 11)
        {
            throw new InputValidationException('Invalid Telephone Number');
        }
    }
}

function validateIsNumeric($value)
{
    if($value != '')
    {
        if(!is_numeric($value))
        {
            throw new InputValidationException('Invalid Number');
        }
    }
}

function validateInteger($value)
{
    if($value != '') {
        if(!is_numeric($value))
        {
            throw new InputValidationException('Invalid Integer');
        }
        if(!is_int($value))
        {
            // For if a string, e.g. '1'. https://stackoverflow.com/questions/2012187/how-to-check-that-a-string-is-an-int-but-not-a-double-etc
            if(!ctype_digit($value))
            {
                throw new InputValidationException('Invalid Integer');
            }
        }
    }
}

function validateDecimal($value)
{
    validateIsNumeric($value);
}

function validateTime($value)
{
    if($value != '')
    {
        $spliter = explode(':', $value);
        if(sizeof($spliter) != 3)
        {
            throw new InputValidationException('Invalid Time');
        }
    
        if(strlen($value) != 8 || strlen($spliter[0]) != 2 || strlen($spliter[1]) != 2 || strlen($spliter[2]) != 2 || $spliter[0] > 23 || $spliter[0] < 0 || $spliter[1] > 59 || $spliter[1] < 0 || $spliter[2] > 59 || $spliter[2] < 0 || !is_numeric($spliter[0]) || !is_numeric($spliter[1]) || !is_numeric($spliter[2]))
        {
            throw new InputValidationException('Invalid Time');
        }
    }
}

function validateURL($value)
{
    if($value != '')
    {
        // Just leave this empty for now.
    }
}

function validatePrice($value)
{
    if($value != '')
    {
        if(!is_numeric($value))
        {
            throw new InputValidationException('Invalid Price');
        }
        
        // Can not have a negative price.
        if($value < 0) {
            throw new InputValidationException('Invalid Price');
        }
    }
}

function validateBoolean($value)
{
    if($value != '')
    {
        if($value !== '0' && $value !== '1') {
            throw new InputValidationException('Boolean required.');
        }
    }
}

function validateDateTime($value)
{
    if($value != '')
    {
        $spliter = explode(' ', $value);
        
        if(sizeof($spliter) != 2)
        {
            throw new InputValidationException('Invalid DateTime');
        }
        
        validateDate($spliter[0]);
        validateTime($spliter[1]);
    }
}

function validateTimeStamp($value)
{
    if($value != '')
    {
        // Just leave this empty for now.
    }
}

function validateValueIsMoreThanZero($value)
{
    if($value <= 0)
    {
        throw new Exception('This value must be more than 0.');
    }
}
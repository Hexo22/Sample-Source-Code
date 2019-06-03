<?php

// Used for returning messages to the user.
// This will still cause MySQL transactions to rollback as an error has occured for App structure integrity.
// E.g; throw new AppStructureIntegrityException($MessageForUser);

class AppStructureIntegrityException extends Exception {
    public function __construct($MessageForUser, $code = 0, Exception $previous = null) {
        parent::__construct($MessageForUser, $code, $previous);
    }
}
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',

    // Shown ONLY when the account really is disabled — see
    // LoginController::sendFailedLoginResponse(). Every other login
    // failure (wrong password, wrong tenant) uses 'failed' above.
    'inactive' => 'You are not an active person, please contact Admin!',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

];

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

    'failed' => 'Your account is currently inactive. Please contact the administrator to activate it.',
    // 'failed' => 'These credentials do not match our records.',
    'password'          => 'The provided password is incorrect.',
    'throttle'          => 'Too many login attempts. Please try again in :seconds seconds.',
    'token_refresh'     => 'Tokens Refresh',
    'token_delete'      => 'Tokens Revoked',
    'signin_msg'        => 'Signin successfully!',
    'profile_msg'       => 'Profile successfully!',
    'credentials_msg'   => 'Credentials not match',
    'resend_otp_msg'    => 'Resend OTP',
    'invalid_otp'       =>  'Invalid OTP',
    'error_msg'         => 'Something went wrong.',
    'password_reset_link'   => 'Password Reset Link',
    'password_reset'   => 'Password Reset',
    'update_password'   => 'Update Password',
    'password_update'   => 'Password updated successfully',
    'password_old'      => 'Old password not match!',
    'profile_update'    => 'Profile updated successfully.',

    // Two-step login (features.login_otp)
    'login_otp_subject'      => 'Your sign-in code',
    'login_otp_greeting'     => 'Hi :name,',
    'login_otp_intro'        => 'Use the following code to finish signing in.',
    'login_otp_expiry'       => 'This code expires in :minutes minutes. If it expires, request a new one from the sign-in page.',
    'login_otp_ignore'       => "If you didn't try to sign in, you can ignore this email — no changes were made to your account.",
    'login_otp_sent'         => "We've emailed you a 6-digit code. Enter it below to finish signing in.",
    'login_otp_invalid'      => 'That code is incorrect. Please try again.',
    'login_otp_expired'      => 'That code has expired. We\'ve sent you a new one.',
    'login_otp_session_lost' => 'Your sign-in session expired. Please sign in again.',
    'login_otp_resent'       => 'A new code has been sent.',
    'login_otp_throttle'     => 'Too many attempts. Try again in :seconds seconds.',

];

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

    'failed' => 'Ces identifiants ne correspondent pas à nos enregistrements.',

    // Shown ONLY when the account really is disabled — see
    // LoginController::sendFailedLoginResponse(). Every other login
    // failure (wrong password, wrong tenant) uses 'failed' above.
    'inactive' => 'Vous n\'êtes pas une personne active, veuillez contacter l\'administrateur !',
    'password'          => 'Le mot de passe fourni est incorrect.',
    'throttle'          => 'Trop de tentatives de connexion. Veuillez réessayer dans :seconds secondes.',
    'token_refresh'     => 'Actualisation des jetons',
    'token_delete'      => 'Révocation des jetons',
    'signin_msg'        => 'Connexion réussie !',
    'profile_msg'       => 'Profil réussi !',
    'credentials_msg'   => 'Les informations d\'identification ne correspondent pas',
    'resend_otp_msg'    => 'Renvoyer le OTP',
    'invalid_otp'       =>  'OTP invalide',
    'error_msg'         => 'Quelque chose a mal tourné.',
    'password_reset_link'   => 'Lien de réinitialisation du mot de passe',
    'password_reset'   => 'Réinitialisation du mot de passe',
    'update_password'   => 'UMettre à jour le mot de passe',
    'password_update'   => 'Mot de passe mis à jour avec succès',
    'password_old'      => 'L\'ancien mot de passe ne correspond pas !',
    'profile_update'    => 'Profil mis à jour avec succès.',


];

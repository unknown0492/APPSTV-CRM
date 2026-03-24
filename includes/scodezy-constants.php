<?php

/**************************** //
// Database Constants - Begin //
/******************************/

// Table user_meta umeta_key Constants
define( "_NEW_ACCOUNT_ACTIVATION_STATUS", "_new_account_activation_status" );
define( "_NEW_ACCOUNT_ACTIVATION_STATUS_ACTIVE", "active" );
define( "_NEW_ACCOUNT_ACTIVATION_STATUS_PENDING_ACTIVATION", "pending_activation" );
define( "_NEW_ACCOUNT_ACTIVATION_CODE", "_new_account_activation_code" );
define( "_NEW_ACCOUNT_ACTIVATION_CODE_GENERATION_TIMESTAMP", "_new_account_activation_code_generation_timestamp" );
define( "_NEW_ACCOUNT_ACTIVATION_TIMESTAMP", "_new_account_activation_timestamp" );
define( "_NEW_ACCOUNT_ACTIVATION_METHOD", "_new_account_activation_method" );                                                   // The way in which the email account was verified.

define( "_EMAIL_UPDATE_VERIFICATION_CODE", "_email_update_verification_code" );
define( "_EMAIL_UPDATE_VERIFICATION_CODE_GENERATION_TIMESTAMP", "_email_update_verification_code_generation_timestamp" );       // The timestamp when the email activation code was generated
define( "_EMAIL_UPDATE_VERIFICATION_CODE_EXPIRY_DURATION", "_email_update_verification_code_expiry_duration" );                 // The email update verification code expiry key
define( "_EMAIL_UPDATE_VERIFICATION_CODE_EXPIRY_DURATION_DEFAULT", 24 * 60 * 60 * 1000 );                                       // The email update verification code expires in 24 hours
define( "_EMAIL_UPDATE_NEW_EMAIL", "_email_update_new_email" );                                                                 // The new email id the user attempted to change to is stored until the user verifies it and it is replaced inside the users table
define( "_EMAIL_UPDATE_VERIFICATION_STATUS", "_email_update_verification_status" );
define( "_EMAIL_UPDATE_VERIFICATION_STATUS_DEFAULT", "0" );                                                                     // 0 -> Updated email not been verified
define( "_EMAIL_UPDATE_VERIFICATION_STATUS_NOT_VERIFIED", "0" );                                                                // 0 -> Not Verified
define( "_EMAIL_UPDATE_VERIFICATION_STATUS_VERIFIED", "1" );                                                                    // 1 -> Verified

define( "_PHONE_UPDATE_OTP", "_phone_update_otp" );                                                     // The OTP key
define( "_PHONE_UPDATE_OTP_GENERATION_TIMESTAMP", "_phone_update_otp_generation_timestamp" );           // The key of the timestamp when OTP is generated
define( "_PHONE_UPDATE_OTP_EXPIRY_DURATION", "_phone_update_otp_expiry_duration" );                     // The key of the duration of OTP expiry
define( "_PHONE_UPDATE_OTP_EXPIRY_DURATION_DEFAULT", 10 * 60 * 1000 );                                  // The default OTP expiry duration
define( "_PHONE_UPDATE_NEW_PHONE_COUNTRY_CODE", "_phone_update_new_phone_country_code" );               // The key for updated phone country code
define( "_PHONE_UPDATE_NEW_PHONE", "_phone_update_new_phone" );                                         // The key for update phone
define( "_PHONE_UPDATE_VERIFICATION_STATUS", "_phone_update_verification_status" );                     // The key for status of phone verification
define( "_PHONE_UPDATE_VERIFICATION_STATUS_DEFAULT", "0" );                                             // 0 -> Updated phone not been verified
define( "_PHONE_UPDATE_VERIFICATION_STATUS_NOT_VERIFIED", "0" );                                        // 0 -> Not verified
define( "_PHONE_UPDATE_VERIFICATION_STATUS_VERIFIED", "1" );                                            // 1 -> Verified
define( "_PHONE_UPDATE_TIMESTAMP", "_phone_update_timestamp" );                                         // The timestamp when the phone number was successfully updated
define( "_PHONE_UPDATE_FREQUENCY", "_phone_update_frequency" );                                         // The frequency of days for every consecutive phone number update
define( "_PHONE_UPDATE_FREQUENCY_VALUE", 15 * 24 * 60 * 1000 );                                         // The frequency of 15 days for every update of contact number
define( "_PHONE_UPDATE_OTP_REQUEST_COUNT", "_phone_update_otp_request_count" );                         // The key to keep track of number of times the OTP has been requested by the user
define( "_PHONE_UPDATE_OTP_REQUEST_COUNT_MAX_LIMIT", 3 );                                               // Maximum number of OTPs that can be requested in a day
define( "_PHONE_UPDATE_OTP_REQUEST_COUNT_MAX_LIMIT_RESET_DURATION", 24 * 60 * 60 * 1000 );              // User can start requesting OTPs once again after 24 hours
define( "_PHONE_UPDATE_OTP_VALIDITY", 5 * 60 * 1000 );                                                  // The duration for which the OTP is valid since its generation
//define( "_PHONE_UPDATE_OTP_REQUEST_COUNT_MAX_LIMIT_RESET_DURATION", 30000 );                            // Temporary Value for testing

define( "_PASSWORD_UPDATE_TIMESTAMP", "_password_update_timestamp" );                                   // The timestamp of the instance when the user changed/updated its own password from its profile


// Table configurations config_key Constants
define( "_NEW_ACCOUNT_ACTIVATION_REQUIRED", "_new_account_activation_required" );                   // The system feature, to enable or disable the account activation process on new user sign up
define( "_NEW_ACCOUNT_ACTIVATION_REQUIRED_DEFAULT", "0" );                                            // By default, account activation using verification link is disabled
define( "_NEW_ACCOUNT_ACTIVATION_CODE_VALIDITY", "_new_account_activation_code_validity" );         // The amount of milliseconds that the activation code should remain valid since its generation time
define( "_NEW_ACCOUNT_ACTIVATION_CODE_VALIDITY_DEFAULT", 24*60*60*1000 );                           // The default value in milliseconds for the activation code should remain valid since its generation time

define( "_FORGOT_PASSWORD_FEATURE", "_forgot_password_feature" );                                   // This will show/hide the Forgot Password option on the Login page
define( "_FORGOT_PASSWORD_FEATURE_DEFAULT", "0" );                                                  // By Default, this would be OFF



// App Verification Failure
define( '_APP_VERIFICATION_FAILURE_ATTEMPT', "_app_verification_failure_attempt" );
define( '_APP_VERIFICATION_FAILURE_SUSPENSION_TIMESTAMP', "_app_verification_failure_suspension_timestamp" );

define( '_APP_VERIFICATION_FAILURE_MAX_ATTEMPTS', 5 );
define( '_APP_VERIFICATION_FAILURE_SUSPENSION_DURATION', 320 * 60 * 1000 );     // 320 minutes
define( '_APP_ACCESS_TOKEN_EXPIRY_DURATION', 90 * 24 * 60 * 60 * 1000 );     // 15 minutes (It should be 3 months). CRON feature to send an email to the app admin to generate a new access token
define( '_APP_REFRESH_TOKEN_EXPIRY_DURATION', 365 * 24 * 60 * 60 * 1000 );     // 1 Year (When refresh token expires, the app needs to be re authenticated to generate a new token pair)
//define( '_APP_REFRESH_TOKEN_EXPIRY_DURATION', 3 * 60 * 1000 );     // 1 Year (When refresh token expires, the app needs to be re authenticated to generate a new token pair)


/**************************** //
// Database Constants - End   //
/******************************/


/**************************** //
// Session Constants - Begin  //
/******************************/

define( "SESSION_FIRST_NAME", "fname" );
define( "SESSION_LAST_NAME", "lname" );
define( "SESSION_NICK_NAME", "nickname" );
define( "SESSION_ID_GENERATE_TIMESTAMP", "session_id_generate_timestamp" );
define( "SESSION_ID_REGENERATE_INTERVAL_DEFAULT", 5 * 60 * 1000 );                                             // 5 Minutes
//define( "SESSION_ID_REGENERATE_INTERVAL_DEFAULT", 30000 );                                                   // 30 seconds
define( "SESSION_EXPIRY_DURATION", 60 * 60 * 1000 );                                                           // 1 Hour
//define( "SESSION_EXPIRY_DURATION", 5 * 60 * 1000 );                                                          // 5 minutes
//define( "SESSION_EXPIRY_DURATION", 180000 );                                                                 // 180 seconds
//define( "SESSION_EXPIRY_DURATION", 10000 );                                                                  // 10 seconds
define( 'SESSION_AUTHORIZATION', 'authorization' );
define( 'SESSION_ROLE_NAME', 'role_name' );
define( 'SESSION_ROLE_ID', 'role_id' );
define( 'SESSION_USER_ID', 'user_id' );
define( 'SESSION_EMAIL', 'email' );

define( 'SESSION_ADMIN', 'admin' );
define( 'SESSION_MODERATOR', 'moderator' );
define( 'SESSION_USER', 'user' );
define( 'SESSION_STAFF', 'staff' );

define( 'TOKEN_NAME', 'xAwBo5Re9a' );
define( 'REFRESH_TOKEN_NAME', 'y8p9B3LqE8' );
define( 'REFRESH_TOKEN_EXPIRY', 30 * 24 * 60 * 60 * 1000 );                                                                             // 30 days
define( 'TOKEN_EXPIRY_DURATION', 30 * 60 * 1000 );                                                                             // 30 Minutes


/**************************** //
// Session Constants - End    //
/******************************/
?>
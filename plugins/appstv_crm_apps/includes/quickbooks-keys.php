<?php

return array(
    'authorizationRequestUrl' => 'https://appcenter.intuit.com/connect/oauth2',
    'tokenEndPointUrl' => 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer',
    //'client_id' => 'ABEXwuVgaZg2eLUPf7c7kjjgPp2mBiFksm0vQMVRbxNtBaC9P0',          // Development
    //'client_secret' => 'qBAwQodGwy3RSeodY1CLcCZXpZe6gouRFzDwNqNe',                // Development
    'client_id' => 'ABiv0OYiPM89O5g2CtzMGfiaGUDMsnJK1HnNwRqGHyNhngZhaZ',            // Production
    'client_secret' => 'n8gT0dSnm2xm5C3quwbE8Gu7t7T3471Ps3RwoIB1',                  // Production
    'oauth_scope' => 'com.intuit.quickbooks.accounting openid profile email phone address',         // Scopes Document: https://developer.intuit.com/app/developer/qbo/docs/learn/scopes
    //'oauth_redirect_uri' => 'http://localhost/sc_framework_june2024/webservice.php?what_do_you_want=callback_quickbooks_api',
    'oauth_redirect_uri' => 'https://myappstv.com/webservice.php?what_do_you_want=generate_quickbooks_tokens',
);

?>
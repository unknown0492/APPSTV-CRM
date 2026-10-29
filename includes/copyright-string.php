<?php
// Copyright line: Administrator > Website Configuration (1.0.56); the defaults are the text used before
$copyright_year  = (string) getConfigurationValue( 'copyright_year', '2024' );
$copyright_owner = (string) getConfigurationValue( 'copyright_owner', 'APPSTV' );
$copyright_url   = (string) getConfigurationValue( 'copyright_url', 'https://appstv.com.sg' );
?><?= htmlspecialchars( $copyright_year ) ?> © <?php if( $copyright_url !== '' ){ ?><a href="<?= htmlspecialchars( $copyright_url ) ?>" target="_blank"><?= htmlspecialchars( $copyright_owner ) ?></a><?php } else { ?><?= htmlspecialchars( $copyright_owner ) ?><?php } ?>. All rights reserved.

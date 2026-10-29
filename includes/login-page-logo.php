<?php // Sign-in pages pass their own logo ($signin, 1.0.58); else the common login logo ?>
<img alt="Logo" src="<?= htmlspecialchars( isset( $signin[ 'logo' ] ) ? $signin[ 'logo' ] : (string) getConfigurationValue( 'login_page_logo' ) ) ?>" />

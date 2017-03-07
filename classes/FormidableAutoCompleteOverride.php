<?php
// Create a helper function for easy SDK access.
function fac_fs() {
	global $fac_fs;
	
	if ( ! isset( $fac_fs ) ) {
		// Include Freemius SDK.
		require_once dirname( __FILE__ ) . '/include/freemius/start.php';
		
		$fac_fs = fs_dynamic_init( array(
			'id'                  => '846',
			'slug'                => 'formidable-autocomplete',
			'type'                => 'plugin',
			'public_key'          => 'pk_75fcfc0463639947aa91b0c11e0c0',
			'is_premium'          => true,
			'is_premium_only'  => true,
			'has_addons'          => false,
			'has_paid_plans'      => true,
			'is_org_compliant'    => false,
			'menu'                => array(
				'slug'           => 'formidable-autocomplete',
				'first-path'     => 'admin.php?page=formidable-autocomplete',
				'support'        => false,
			),
			// Set the SDK to work in a sandbox mode (for development & testing).
			// IMPORTANT: MAKE SURE TO REMOVE SECRET KEY BEFORE DEPLOYMENT.
			'secret_key'          => 'sk_S&oQ@<cgrATjI%~J4w8$V2C6U%4kV',
		) );
	}
	
	return $fac_fs;
}
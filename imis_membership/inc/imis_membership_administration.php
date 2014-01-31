<div class="wrap">
    <div id="icon-edit" class="icon32 icon32-base-template"><br></div>
    <h2><?php _e( 'iMIS Membership', 'imis_membership' ); ?></h2>
	<ul>
		<li class="clear"><h3>Getting Started</h3>
			<p><strong>1. </strong> Setup iMIS</p>
			<p><strong>3. </strong> Use the short code [member-login] to render the login widget on a page</p>
			<p><strong>4. </strong> Enter your iMIS credentials in the settings below</p>			
		</li>
	</ul>
	<p></p>	
    <form id="imis_membership-form" action="options.php" method="POST">

        <?php settings_fields( 'imis_membership_setting' ) ?>
        <?php do_settings_sections( 'imis_membership_setting' ) ?>

        <input type="submit" value="<?php _e( 'Save', 'imis_membership_settings' ); ?>" />
    </form>
</div>
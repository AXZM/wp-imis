<div class="wrap">
    <div id="icon-edit" class="icon32 icon32-base-template"><br></div>
    <h2><?php _e( 'iMIS Membership', 'imis_membership' ); ?></h2>	
    <form id="imis_membership-form" action="options.php" method="POST">
        <?php settings_fields( 'imis_membership_setting' ) ?>
        <?php do_settings_sections( 'imis_membership_setting' ) ?>
        <input type="submit" class="button button-primary button-large" value="<?php _e( 'Save', 'imis_membership_settings' ); ?>" />
    </form>
</div>
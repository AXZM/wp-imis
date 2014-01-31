<?php
session_start();
/**
 * Plugin Name: WP-iMIS Membership
 * Plugin URI: http://axzm.com
 * Author: AXZM
 * Description: Restrict pages of your site to members only.  Works with iMIS.  Restrict to different types/roles/groups of members.
 * Author URI: http://www.axzm.com
 * Version: 1.0
 */

/**
 * Get some constants ready for paths when your plugin grows
 *
 */

define( 'IMIS_MEMBERSHIP_VERSION', '2.0' );
define( 'IMIS_MEMBERSHIP_PATH', dirname( __FILE__ ) );
define( 'IMIS_MEMBERSHIP_PATH_INCLUDES', dirname( __FILE__ ) . '/inc' );
define( 'IMIS_MEMBERSHIP_FOLDER', basename( IMIS_MEMBERSHIP_PATH ) );
define( 'IMIS_MEMBERSHIP_URL', plugins_url() . '/' . IMIS_MEMBERSHIP_FOLDER );
define( 'IMIS_MEMBERSHIP_URL_INCLUDES', IMIS_MEMBERSHIP_URL . '/inc' );

require(IMIS_MEMBERSHIP_PATH . '/core.php');


/**
 *
 * The plugin base class - the root of all WP goods!
 *
 * @author axzm
 *
 */
class IMIS_MEMBERSHIP {
    /**
     *
     * Assign everything as a call from within the constructor
     */
    function __construct() {
        
        add_action( 'wp_enqueue_scripts', array( $this, 'imis_membership_add_JS' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'imis_membership_add_CSS' ) );

        // register admin pages for the plugin
        add_action( 'admin_menu', array( $this, 'imis_membership_admin_pages_callback' ) );

        // register meta boxes for Pages (could be replicated for posts and custom post types)
        add_action( 'add_meta_boxes', array( $this, 'imis_membership_meta_boxes_callback' ) );

        // Register activation and deactivation hooks
        register_activation_hook( __FILE__, 'imis_membership_on_activate_callback' );
        register_deactivation_hook( __FILE__, 'imis_membership_on_deactivate_callback' );

        // Add earlier execution as it needs to occur before admin page display
        add_action( 'admin_init', array( $this, 'imis_membership_register_settings' ), 5 );

        // Add a login shortcode
        add_action( 'init', array( $this, 'imis_membership_login_shortcode' ) );

        //add redirect if not members only
        add_action('template_redirect', array($this, 'redirectIfNotMember'));

        add_action( 'save_post',  array( $this, 'imis_membership_save_box_callback'), 10, 2 );
    }

    function redirectIfNotMember()
    {
        $settings       = $this->imis_membership_register_settings();
        $m              = new WordPressMembership($settings->imis_membership_setting['imis_login_type'], $settings->imis_membership_setting['imis_login_url'], $settings->imis_membership_setting['imis_login_apikey']);
        $loginPageId    = $settings->imis_membership_setting['imis_login_page'];

        if (isset($_GET['imis_logout'])) 
        {
            $m->logoutUser();
            wp_redirect(home_url());
            return;
        }

        if (is_singular() || is_home()) 
        {
            global $post;

            $restriction    = get_post_meta($post->ID, 'restrict_members_only', true);
            $selectedRoles  = explode(',', get_post_meta( $post->ID, 'restricted_roles', true ));

            if ($restriction == 'no' || $restriction == null) 
            {
                return;
            }

            if ($restriction == 'member') 
            {
                if (!$m->isUserLoggedIn()) 
                {
                    wp_redirect(get_permalink($loginPageId).'?redirectToPost='.$post->ID); exit;
                }
                return;
            }

            if ($restriction == 'member-roles')
            {
                if (!$m->isUserLoggedIn()) 
                {
                    wp_redirect(get_permalink($loginPageId).'?redirectToPost='.$post->ID); exit;
                }

                if (!$m->userInRole($selectedRoles)) 
                {
                    wp_redirect(get_permalink($loginPageId).'?unauthorized=true'); exit;
                }
                return;
            }

            echo("error in member redirect"); die();
        }
    }

            /**
     *
     * Adding JavaScript scripts
     *
     * Loading existing scripts from wp-includes or adding custom ones
     *
     */
    function imis_membership_add_JS() {
        wp_enqueue_script( 'jquery' );
        // load custom JSes and put them in footer
        //wp_register_script( 'samplescript', plugins_url( '/js/samplescript.js' , __FILE__ ), array('jquery'), '1.0', true );
        //wp_enqueue_script( 'samplescript' );
    }


    /**
     *
     * Add CSS styles
     *
     */
    function imis_membership_add_CSS() {
        //wp_register_style( 'samplestyle', plugins_url( '/css/samplestyle.css', __FILE__ ), array(), '1.0', 'screen' );
        //wp_enqueue_style( 'samplestyle' );
    }



    /**
     *
     * Callback for registering pages
     *
     * This demo registers a custom page for the plugin and a subpage
     *
     */
    function imis_membership_admin_pages_callback() {
        add_menu_page('iMIS', 'iMIS', 'edit_themes', 'imis_membership', array( $this, 'imis_membership'));
    }

    /**
     *
     * The content of the base page
     *
     */
    function imis_membership() {
        include_once( IMIS_MEMBERSHIP_PATH_INCLUDES . '/imis_membership_administration.php' );
    }


    /**
     *
     *  Adding right and bottom meta boxes to Pages
     *
     */
    function imis_membership_meta_boxes_callback() {
        // register side box
        add_meta_box(
            'imis_membership_meta_box',
            __( 'Restrict Access', 'imis_membership' ),
            array( $this, 'imis_membership_meta_box' ),
            '', // leave empty quotes as '' if you want it on all custom post add/edit screens
            'side',
            'high'
        );
    }

    function imis_membership_meta_box( $post, $metabox )
    {
        $settings = $this->imis_membership_register_settings();
        $m = new WordPressMembership($settings->imis_membership_setting['imis_login_type'], $settings->imis_membership_setting['imis_login_url'], $settings->imis_membership_setting['imis_login_apikey']);
        //print_r($m->authenticate("jessica", "ascouncil"));
        $allRoles = $m->getAllRoles();

        $restrict_members_only = get_post_meta( $post->ID, 'restrict_members_only', true);
        $selectedRoles = explode(',', get_post_meta( $post->ID, 'restricted_roles', true ));
        $chkRestrict = '';
        $chkDoNotRestrict = '';
        $chkSpecificRoles = '';
        if ($restrict_members_only == 'no' || $restrict_members_only == null) {
            $chkDoNotRestrict = 'checked';
        }
        else if ($restrict_members_only == 'member') {
            $chkRestrict = 'checked';
        }
        else if ($restrict_members_only == 'member-roles') {
            $chkSpecificRoles = 'checked';
        }
        else {
            echo($restrict_members_only); die();
        }

        $template = '<input type="radio" id="rdo-no" name="restrict-members-only" value="no" '.$chkDoNotRestrict.'>Do not restrict this page</input> <br/>
<input type="radio" id="rdo-member" name="restrict-members-only" value="member" '.$chkRestrict.'>Restrict this page to members only</input> <br/>
<input type="radio" id="rdo-member-roles" name="restrict-members-only" value="member-roles" '.$chkSpecificRoles.'>Restrict this page to certain member types</input> <br/>
<div id="div-roles" class="inside" style=' . ($chkSpecificRoles != 'checked' ? '"display:none"' : '""') . '>';

        if (!$allRoles)
            return;

        foreach($allRoles as $key=>$value) {
            $template.= '<input type="checkbox" name="roles[]" value="'.$key.'" '.$this->isRoleChecked($key, $selectedRoles).'>'.$value.'</input><br/>';
        }

        $template.= '</div>
<script>
jQuery(document).ready(function (){
    //jQuery("#div-roles").hide();
    jQuery("[name=\'restrict-members-only\']").change(function (){
        if (jQuery(this).val() == "member-roles") {
            jQuery("#div-roles").show();
        }
        else {
            jQuery("#div-roles").hide();
        }
    });
    if (jQuery("[name=\'restrict-members-only :checked\']").val() == "member-roles") {
        jQuery("[name=\'restrict-members-only\']").change();
    }
});
</script>';



        echo($template);
    }

    function isRoleChecked($key, $selectedRoles) {
        if (in_array($key, $selectedRoles)) {
            return "checked";
        }
        return '';
    }

    function imis_membership_save_box_callback($post_id, $post) {
        $restrict = ( isset( $_POST['restrict-members-only'] ) ? sanitize_html_class( $_POST['restrict-members-only'] ) : false );
        $meta_key = 'restrict_members_only';
        $this->save_meta_box_data($post_id, $post, $meta_key, $restrict);

        if(!$_POST['roles'])
            return;

        $role_values = implode(',', $_POST['roles']);
        $meta_key = 'restricted_roles';
        $this->save_meta_box_data($post_id, $post, $meta_key, $role_values);
    }

    function save_meta_box_data($post_id, $post, $meta_key, $new_meta_value) {
        /* Get the meta value of the custom field key. */
        $meta_value = get_post_meta( $post_id, $meta_key, true );

        /* If a new meta value was added and there was no previous value, add it. */
        if ( $new_meta_value && '' == $meta_value )
            add_post_meta( $post_id, $meta_key, $new_meta_value, true );

        /* If the new meta value does not match the old value, update it. */
        elseif ( $new_meta_value && $new_meta_value != $meta_value )
            update_post_meta( $post_id, $meta_key, $new_meta_value );

        /* If there is no new meta value but an old value exists, delete it. */
        elseif ( '' == $new_meta_value && $meta_value )
            delete_post_meta( $post_id, $meta_key, $meta_value );
    }
    /**
     * Register activation hook
     *
     */
    function imis_membership_on_activate_callback() {
        // do something on activation
    }

    /**
     * Register deactivation hook
     *
     */
    function imis_membership_on_deactivate_callback() {
        // do something when deactivated
    }

    /**
     * Initialize the Settings class
     *
     * Register a settings section with a field for a secure WordPress admin option creation.
     *
     */
    function imis_membership_register_settings() {
        require_once( IMIS_MEMBERSHIP_PATH . '/imis_membership_Settings.php' );
        $settings = new imis_membership_Settings();
        return $settings;
    }

    /**
     * Register a sample shortcode to be used
     *
     * First parameter is the shortcode name, would be used like: [dxsampcode]
     *
     */
    function imis_membership_login_shortcode() {
        add_shortcode( 'member-login', array( $this, 'imis_membership_login_shortcode_body' ) );
    }

    /**
     * Returns the content of the sample shortcode, like [dxsamplcode]
     * @param array $attr arguments passed to array, like [dxsamcode attr1="one" attr2="two"]
     * @param string $content optional, could be used for a content to be wrapped, such as [dxsamcode]somecontnet[/dxsamcode]
     */
    function imis_membership_login_shortcode_body( $attr, $content = null ) {
        /*
         * Manage the attributes and the content as per your request and return the result
         */
        $settings = $this->imis_membership_register_settings();
        $m = new WordPressMembership($settings->imis_membership_setting['imis_login_type'], $settings->imis_membership_setting['imis_login_url'], $settings->imis_membership_setting['imis_login_apikey']);

        if ($m->isUserLoggedIn()) {
            $success = '<p>You have logged in successfully. <a href="?imis_logout=true">Click here</a> to log out</p>';
            return __( $success, 'imis_membership');
        }

        if (isset($_GET['unauthorized'])) {
            $unauthorized = '<p>You are not authorized to view this page</p>';
            return __( $unauthorized, 'imis_membership');
        }
        global $post;
		$unLabel = 'Username';
		if ($settings->imis_membership_setting['imis_login_type'] == 'MemberCMS') {
			$unLabel = 'Email Address';
		}
        $loginForm = '<form name="imis_login" method="post" action="'.get_permalink($post->ID).'">
                    <div class="control-group">
                        <label class="control-label" for="Username">'.$unLabel.'</label>
                        <div class="controls">
                            <input type="text" name="Username" />
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="Password">Password</label>
                        <div class="controls">
                            <input type="password" name="Password" />
                        </div>
                    </div>
                    <input type="submit" value="Login" />';
                    if (isset($_GET['redirectToPost'])) {
                        $loginForm.='<input type="hidden" name="redirectToPost" value="'.$_GET['redirectToPost'].'" />';
                    }
                    $loginForm.='
                    </form>
        ';


        if (isset($_POST['Username']) && isset($_POST['Password'])) {
            $response = $m->authenticate($_POST['Username'], $_POST['Password']);
            if (!$response) {
                $loginForm = '<p>Your username or password was incorrect</p>'.$loginForm;
            }
            else {
                $id = $post->ID;
                if (isset($_POST['redirectToPost'])) {
                    $id = $_POST['redirectToPost'];
                }

                $urlToDirectTo = $m->doSingleSignOn($_POST['Username'], $_POST['Password'], get_permalink($id));


                $redirectScript = '<script>window.location = "'.get_permalink($id).'";</script>';
                if ($urlToDirectTo)
                {
                    $redirectScript = '<script>window.location = "'.$urlToDirectTo.'";</script>';
                }

                return __($redirectScript, 'imis_membership');
            }
        }

        return __( $loginForm, 'imis_membership');
    }
}

// Initialize everything
$imisMembership = new IMIS_MEMBERSHIP();

<?php


class imis_membership_Settings {

    public $imis_membership_setting;
    /**
     * Construct me
     */
    public function __construct() {
        $this->imis_membership_setting = get_option( 'imis_membership_setting', '' );

        // register the checkbox
        add_action('admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Setup the settings
     *
     * Add a single checkbox setting for Active/Inactive and a text field
     * just for the sake of our demo
     *
     */
    public function register_settings() {
        register_setting( 'imis_membership_setting', 'imis_membership_setting', array( $this, 'imis_membership_validate_settings' ) );

        add_settings_section(
            'imis_membership_settings_section',         // ID used to identify this section and with which to register options
            'Enable nonprofitCMS Membership',                  // Title to be displayed on the administration page
            array($this, 'imis_membership_settings_callback'), // Callback used to render the description of the section
            'imis_membership_setting'                           // Page on which to add this section of options
        );

        add_settings_field(
            'imis_login_page',                      // ID used to identify the field throughout the theme
            __( 'Login Page: ', 'imis_membership' ),                           // The label to the left of the option interface element
            array( $this, 'imis_login_page_callback' ),   // The name of the function responsible for rendering the option interface
            'imis_membership_setting',                          // The page on which this option will be displayed
            'imis_membership_settings_section'         // The name of the section to which this field belongs
        );

        add_settings_field(
            'imis_login_type',                      // ID used to identify the field throughout the theme
            __( 'Connector: ', 'imis_membership' ),                           // The label to the left of the option interface element
            array( $this, 'imis_type_callback' ),   // The name of the function responsible for rendering the option interface
            'imis_membership_setting',                          // The page on which this option will be displayed
            'imis_membership_settings_section'         // The name of the section to which this field belongs
        );

        add_settings_field(
            'imis_login_url',                      // ID used to identify the field throughout the theme
            __( 'API Endpoint: ', 'imis_membership' ),                           // The label to the left of the option interface element
            array( $this, 'imis_url_callback' ),   // The name of the function responsible for rendering the option interface
            'imis_membership_setting',                          // The page on which this option will be displayed
            'imis_membership_settings_section'         // The name of the section to which this field belongs
        );

        add_settings_field(
            'imis_login_apikey',                      // ID used to identify the field throughout the theme
            __( 'API Key: ', 'imis_membership' ),                           // The label to the left of the option interface element
            array( $this, 'imis_apikey_callback' ),   // The name of the function responsible for rendering the option interface
            'imis_membership_setting',                          // The page on which this option will be displayed
            'imis_membership_settings_section'         // The name of the section to which this field belongs
        );




    }

    public function imis_membership_settings_callback()
    {

    }

    public function imis_type_callback() {
        $out = '';
        $val = '';

        if(! empty( $this->imis_membership_setting ) && isset ( $this->imis_membership_setting['imis_login_type'] ) ) {
            $val = $this->imis_membership_setting['imis_login_type'];
        }

        $imisSelected = $val == 'iMIS 15' ? "selected" : "";

        $out = '<select id="imis_login_type" name="imis_membership_setting[imis_login_type]">';
        $out = $out.'<option value="">-- SELECT --</option>';
        $out = $out.'<option ' . $imisSelected . ' value="iMIS 15">iMIS 15</option>';
        $out = $out.'</select>';



        $out .= '<script> jQuery(document).ready(function (){ jQuery("#imis_login_type").change(function (){ if (jQuery("#imis_login_type").val() == "") { jQuery(".imis").closest("tr").hide(); jQuery(".member").closest("tr").hide();  } if (jQuery("#imis_login_type").val() == "MemberCMS") { jQuery(".imis").closest("tr").hide(); jQuery(".member").closest("tr").show();  } if (jQuery("#imis_login_type").val() == "iMIS 15") { jQuery(".member").closest("tr").hide(); jQuery(".imis").closest("tr").show();  } }); jQuery("#imis_login_type").change();}); </script>';

        echo $out;


    }

    public function imis_url_callback() {
        $out = '';
        $val = '';

        if(! empty( $this->imis_membership_setting ) && isset ( $this->imis_membership_setting['imis_login_url'] ) ) {
            $val = $this->imis_membership_setting['imis_login_url'];
        }

        $out = '<input style="width:35%" class="imis member" type="text" id="imis_login_url" name="imis_membership_setting[imis_login_url]" value="' . $val . '"  />';
        echo $out;
    }

    public function imis_apikey_callback() {
        $out = '';
        $val = '';

        if(! empty( $this->imis_membership_setting ) && isset ( $this->imis_membership_setting['imis_login_apikey'] ) ) {
            $val = $this->imis_membership_setting['imis_login_apikey'];
        }

        $out = '<input style="width:35%" class="member" type="text" id="imis_login_apikey" name="imis_membership_setting[imis_login_apikey]" value="' . $val . '"  />';

        echo $out;
    }


    public function imis_login_page_callback() {
        $out = '';
        $val = '';

        global $wpdb;

        $sql = "SELECT p.id as post_id, u.user_nicename as author, p.post_title, p.post_name as post_slug, p.post_date as local_publish_date, p.comment_count FROM " . $wpdb->base_prefix . "posts p, " . $wpdb->base_prefix . "users u WHERE p.post_status='publish' AND p.post_type='page' AND u.id = p.post_author ORDER BY p.post_title DESC LIMIT 1000";

        global $wpdb;
        $posts = $wpdb->get_results($sql);

        $out = '<select id="imis_login_page" name="imis_membership_setting[imis_login_page]">';
        $out = $out.'<option value="0">-- SELECT --</option>';
        foreach ($posts as $post)
        {
            $val = '';
            if(! empty( $this->imis_membership_setting ) && isset ( $this->imis_membership_setting['imis_login_page'] ) ) {
                if ($post->post_id == $this->imis_membership_setting['imis_login_page']) {
                    $val = 'selected';
                }
            }
            $out = $out.'<option '.$val.' value='.$post->post_id.'>'.$post->post_title.'</option>';
        }

        $out = $out.'</select>';

        $out .= "<br/>Unauthenticated users will be redirected to this page.  Be sure to include the short tag [member-login] on this page";

        echo $out;
    }


    /**
     * Validate Settings
     *
     * Filter the submitted data as per your request and return the array
     *
     * @param array $input
     */
    public function imis_membership_validate_settings( $input ) {

        return $input;
    }
}
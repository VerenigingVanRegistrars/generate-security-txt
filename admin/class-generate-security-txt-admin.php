<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://verenigingvanregistrars.nl/
 * @since      1.0.0
 *
 * @package    Generate_Security_Txt
 * @subpackage Generate_Security_Txt/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Generate_Security_Txt
 * @subpackage Generate_Security_Txt/admin
 * @author     Brian de Geus <wordpress@verenigingvanregistrars.nl>
 */
class Generate_Security_Txt_Admin {

    // Define the constants
    const OPTION_FORM_PREFIX = 'gensecform_';
    const OPTION_TXT_PREFIX = 'gensectxt_';

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name = '', $version = '' ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}


    /**
     * Get the plugin version
     *
     * @return mixed
     */
    public function get_plugin_version()
    {
        // Retrieve the version
        return GENERATE_SECURITY_TXT_VERSION;
    }


    /**
     * Return the url for assets in the admin area.
     *
	 * @since    1.0.0
     * @return string
     */
    public function get_admin_assets_url() {
        return plugin_dir_url( __FILE__ );
    }


    /**
     * Add a custom link to the management admin page
     *
     * @param $links
     * @param $file
     * @return mixed
     */
    function add_custom_plugin_link($links, $file)
    {
        if ($file == generate_security_txt_get_basefile()) {
            $links[] = '<a href="' . admin_url('tools.php?page=security_txt_generator') . '">' . __('Go to settings', 'generate-security-txt') . '</a>';
        }

        return $links;
    }


    /**
     * Register all ajax callback for the backend
     *
     * @return void
     */
    public function ajax_callbacks() {

        add_action('wp_ajax_init_actionlist', array($this, 'init_actionlist_callback'));
        add_action('wp_ajax_process_actionlist', array($this, 'process_actionlist_callback'));
        add_action('wp_ajax_post_finish', array($this, 'post_finish_callback'));
        add_action('wp_ajax_status_loader', array($this, 'status_loader_callback'));

        // Register all ajax actionlist actions
        $securitytxt_actions = $this->admin_security_text_generator_actions();
        foreach($securitytxt_actions as $action) {
            add_action('wp_ajax_' . $action['name'], array($this, $action['name']));
        }
    }


	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

        // Get the current admin screen
        $current_screen = get_current_screen();

        // Check if we are on the specific backend template
        if ($current_screen && $current_screen->id === 'tools_page_security_txt_generator') {
            wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/generate-security-txt-admin.css', [], $this->version, 'all' );
            wp_enqueue_style( $this->plugin_name . '-jquery-ui', plugin_dir_url( __FILE__ ) . 'css/jquery-ui.css', [], $this->version, 'all' );
        }
	}


	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

        // Get the current admin screen
        $current_screen = get_current_screen();

        // Check if we are on the specific backend template
        if ($current_screen && $current_screen->id === 'tools_page_security_txt_generator') {

            // Enqueue the scripts and styles only for the specific backend template
            wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/generate-security-txt-admin.js', array('jquery'), $this->version, false);

            $action_list = $this->admin_security_text_generator_actions();
            $first_action = reset($action_list);

            wp_localize_script($this->plugin_name, 'securitytxt',
                [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'homeurl' => trailingslashit(home_url()),
                    'first_action' => $first_action['name'],
                    'first_action_text' => $first_action['text_start'],
                    'spinner_url' => esc_url( trailingslashit( includes_url() ) . 'images/spinner.gif' ),
                    'status_text' => esc_js( __( 'Checking security.txt status', 'generate-security-txt' ) )
                ]
            );

            wp_enqueue_script('jquery-ui-datepicker');
        }
	}


	/**
	 * Add menu pages
	 *
	 * @since    1.0.0
	 */
	public function add_menus() {
        add_submenu_page(
            'tools.php',
            __( 'Generate Security.txt', 'generate-security-txt'),
            __( 'Generate Security.txt', 'generate-security-txt'),
            'manage_options',
            'security_txt_generator',
            array(&$this, 'admin_security_txt_generator_page_callback')
        );
	}


    /**
     * Add notifications
     *
     * @return void
     */
    function show_admin_notification() {
        // Check if the current screen is the plugin admin page
        $screen = get_current_screen();
        if ($screen && $screen->id === 'tools_page_security_txt_generator') {

            // Display your notification if securitytxy doesn't exist
            if(!$this->check_securitytxt()) {
                echo '<div id="securitytxtNoticeNoTxt" class="notice notice-error">
                    <p>' . esc_html__('No security.txt file exists for this website yet. Create one below.', 'generate-security-txt') . '</p>
                </div>';
            }

            // Display your notification if securitytxy doesn't exist
            if(!is_ssl()) {
                echo '<div id="securitytxtNoticeNoTxt" class="notice notice-error">
                    <p>' . esc_html__('This website isn\'t using HTTPS. This is a requirement for any value in security.txt containing a web URI. Resolve this before you generate a security.txt file.', 'generate-security-txt') . '</p>
                </div>';
            }

            $show_deleted_notification = get_option($this::OPTION_FORM_PREFIX . 'notification_delete', false);
            if($show_deleted_notification) {
                echo '<div id="securitytxtNoticeDeleted" class="notice notice-success is-dismissible">
                    <p>All data, files and keys have been deleted.</p>
                </div>';

                update_option($this::OPTION_FORM_PREFIX . 'notification_delete', false);
            }
        }

        // Show notication on dashboard and in plugin admin if expiry is close or passed
        if ($screen && $screen->id === 'dashboard') {

            // Display your notification if securitytxt exists and is expired
            if ($this->check_securitytxt()) {
                // Get expiration date
                $securitytxt_expire = $this->get_expiredate();

                if (!empty($securitytxt_expire) && is_array($securitytxt_expire))
                    $securitytxt_expire = reset($securitytxt_expire);

                // If expiration date is not available or not in the correct format, return
                if (empty($securitytxt_expire) || !is_string($securitytxt_expire))
                    return;

                // Convert expiration date to DateTime object
                $securitytxt_expire_date = DateTime::createFromFormat("Y-m-d\TH:i:s.u\Z", $securitytxt_expire);

                // Calculate the date for today
                $today = new DateTime('now');

                // Check if expiration date is within 1 day or has passed
                if ($securitytxt_expire_date <= $today->modify('+1 day')) {
                    // translators: the admin URL to this plugin's admin page
                    echo '<div id="securitytxtNoticeExpiry" class="notice notice-error"><p>' . sprintf(esc_html__('Regenerate your security.txt, the expirydate is very soon or has passed. <a href="%s">Click here</a> to do so.', 'generate-security-txt'), esc_url(admin_url('tools.php?page=security_txt_generator'))) . '</p></div>';
                }
            }
        }
    }


    /**
     *
     *
     * @return void
     */
    public function status_loader_callback() {
        $status_type = 'no';
        $status_color = 'red';
        $status_text = __('Invalid - Security.txt is missing', 'generate-security-txt');

        $securitytxt_exists = $this->check_securitytxt();
        $securitytxt_expire = $this->get_expiredate();
        $securitytxt_expire = is_array($securitytxt_expire) ? reset($securitytxt_expire) : '';
        $securitytxt_expire = DateTime::createFromFormat("Y-m-d\TH:i:s.u\Z", $securitytxt_expire);

        if($securitytxt_exists) {
            if ($securitytxt_expire !== false) {
                // Create a DateTime object for the current date
                $currentDate = new DateTime();

                // Add one month to the current date
                $oneMonthLater = new DateTime();
                $oneMonthLater = $oneMonthLater->modify('+1 month');

                // Compare the provided date with one month later
                if ($securitytxt_expire < $currentDate) {
                    // File is expired
                    // translators: the formatted date on which the security.txt file expired
                    $status_text = sprintf(__('Security.txt expired on %s. Regenerate the file below.', 'generate-security-txt'), $securitytxt_expire->format('Y-m-d'));
                } elseif ($securitytxt_expire < $oneMonthLater) {
                    // File will expire soon
                    $status_type = 'ellipsis';
                    $status_color = 'yellow';
                    // translators: the formatted date on which the security.txt file will expire
                    $status_text = sprintf(__('Security.txt will expire on %s. Regenerate the file below.', 'generate-security-txt'), $securitytxt_expire->format('Y-m-d'));
                } else {
                    // File will not expire soon
                    $status_type = 'yes';
                    $status_color = 'green';
                    $status_text = __('Security.txt is valid', 'generate-security-txt');
                }
            }
        }

        $html = '<div class="securitytxt-status ' . $status_color . '">
            <div class="dashicons dashicons-' . $status_type . '"></div>
        </div>
        <div class="securitytxt-status-label">' . $status_text . '</div>';

        // Send a JSON response
        $response = array(
            'html' => $html
        );

        wp_send_json($response);
    }


    /**
     * Save the value in a format according to securitytxt standard
     *
     * @return bool
     */
    private function update_securitytxt_value($field, $clean_values) {

        // Check if we have usuable values
        if(!empty($field['name'] || !empty($clean_values))) {

            // Contact field
            if($field['name'] == 'contact') {
                foreach($clean_values as $key => $clean_value) {

                    if(!empty($clean_value)) {
                        if(is_email($clean_value)) {
                            $clean_values[$key] = 'mailto:' . $clean_value;
                        }
                        elseif($this->is_possibly_phonenumber($clean_value)) {
                            $clean_values[$key] = 'tel:' . $clean_value;
                        }
                    }
                }
            }
            // Expires field
            elseif($field['name'] == 'expires') {
                foreach($clean_values as $key => $clean_value) {
                    $clean_values[$key] = $this->inputdate_to_ISOdate($clean_value);
                }
            }
            return update_option($this::OPTION_TXT_PREFIX . $field['name'], $clean_values);
        }

        return false;
    }


    /**
     * Transform a simple date to a ISO 8601 date according to securitytxt standard
     *
     * @param $input_date
     * @return string
     */
    private function inputdate_to_ISOdate($input_date) {
        // Create a DateTime object from the input string
        $date = DateTime::createFromFormat('Y-m-d', $input_date);

        if ($date instanceof DateTime) {
            // Set the time to 00:00:00
            $date->setTime(0, 0, 0, 0);

            // Format the date in ISO 8601 format
            return $date->format('Y-m-d\TH:i:s.u\Z');
        }

        // Unsuccessful
        return false;
    }


	/**
	 * Return the last datetime to show in the debug table
	 *
	 * @return mixed|string
	 */
	public function get_datetime_last_expiry_reminder() {
		// Retrieve the stored date and time option
		$last_reminder = get_option( $this::OPTION_FORM_PREFIX . 'securitytxt_email_date' );

		// Check if the option is set and not empty
		if ( ! empty( $last_reminder ) ) {
			return $last_reminder;
		} else {
			return 'Never';
		}
	}


    /**
     * See if a value is possibly a phonenumber, this is quite a redementary regex which is fine for our purpose
     *
     * @param $value
     * @return bool
     */
    private function is_possibly_phonenumber($value) {

        if(preg_match('/[a-zA-Z]/', $value))
            return false;

        $numericValue = preg_replace('/[^0-9]/', '', $value);

        // Check if the numeric value is within a reasonable length for a phone number
        return (strlen($numericValue) >= 7 && strlen($numericValue) <= 15);
    }


    /**
     * Print the contents
     *
     * @return string
     */
    public function get_securitytxt_contents($with_linebreaks = true) {

        $securitxt_contents = '';

        // Retrieve our fields
        $fields = $this->admin_security_text_generator_fields();

        $first = true;

        // Loop all fields to retrieve the values
        foreach ($fields as $key => $field) {
            $values = get_option($this::OPTION_TXT_PREFIX . $key);

            if(!empty($values) && is_array($values)) {
                foreach($values as $value) {
                    if(!empty($value)) {

                        if(!$first) {
                            // Maybe add linebreaks before
                            $securitxt_contents .= $with_linebreaks ? "\r\n" : '';
                        }
                        else {
                            $first = false;
                        }

                        $securitxt_contents .= $field['title'] . ': ';
                        $securitxt_contents .= $value;
                    }
                }
            }
        }

        $securitxt_contents = Securitytxt_Encryption::normalize_line_endings($securitxt_contents);

        return $securitxt_contents;
    }


    /**
     * Get contents file contents
     */
	public function get_securitytxt_file_contents() {
		global $wp_filesystem;

		// Initialize the WP_Filesystem
		if ( ! WP_Filesystem() ) {
			return false;
		}

		// Check if the file exists
		if ( $this->check_securitytxt() ) {
			$well_known_path = trailingslashit( ABSPATH ) . '.well-known/';
			$file_path       = $well_known_path . 'security.txt';

			// Return the contents of the local file as a string
			return $wp_filesystem->get_contents( $file_path );
		} else {
			return false;
		}
	}


    /**
     * Check if the php-extension gnupg is available
     *
     * @return bool
     */
    public function is_gnupg_available() {

        if (extension_loaded('gnupg'))
            return true;

        return false;
    }


    /**
     * Get the URL to verify security.txt externally
     *
     * @return string
     */
    public function get_internetnl_testurl(): string
    {
        $prefix_url = 'https://internet.nl/site/';

        // Get the home URL
        $home_url = home_url();

        // Parse the URL
        $parsed_url = wp_parse_url($home_url);

        // Extract the base domain
        $base_domain = $parsed_url['host'];

        // Additional trim
        $trimmed_domain = trim($base_domain, '/');

        return trailingslashit($prefix_url) . $trimmed_domain;
    }


    /**
     * Get the URL to verify security.txt externally
     *
     * @return string
     */
    public function get_pubkey_url(): string
    {
        $base_url = home_url();

        // Add the path to security.txt
        // Output or use $securitytxt_url as needed
        return trailingslashit($base_url) . 'pubkey.txt';
    }


    /**
     * Get the URL to verify security.txt externally
     *
     * @return string
     */
    public function get_securitytxt_url(): string
    {
        $base_url = home_url();

        // Add the path to security.txt
        // Output or use $securitytxt_url as needed
        return trailingslashit($base_url) . '.well-known/security.txt';
    }


    /**
     * Get the URL to verify security.txt externally
     *
     * @return string
     */
    public function get_deletedata_url(): string {
	    $delete_url = menu_page_url( 'security_txt_generator', false );
	    $delete_url = add_query_arg( 'action', 'securitytxt_erase', $delete_url );

	    return wp_nonce_url( $delete_url, 'securitytxt_erase' );
    }


    /**
     * Callback to require the HTML file for the main admin page
     *
     * @return void
     */
    public function admin_security_txt_generator_page_callback() {
        // Load HTML
        require plugin_dir_path( __FILE__ ) . 'partials/generate-security-txt-admin-display.php';
    }


	/**
     * Check for possibly existing POST data and sanitize
     *
	 * @return array
	 */
    private function sanitize_form_submit_post() {

        // Initialize an empty array to hold the sanitized data
        $sanitized_form_data = array();

        // Check if form_data is set and not empty
        if (!empty($_POST['form_data'])) {
            // Parse the serialized form data into an associative array
            parse_str($_POST['form_data'], $form_data_array);

            // Sanitize email addresses in the 'contact' array
            if (!empty($form_data_array['contact']) && is_array($form_data_array['contact'])) {
                $sanitized_form_data['contact'] = array_map('sanitize_email', $form_data_array['contact']);
            }

            // Sanitize the 'expires' date fields
            if (!empty($form_data_array['expires']) && is_array($form_data_array['expires'])) {
                $sanitized_form_data['expires'] = array_map('sanitize_text_field', $form_data_array['expires']);
            }

            // Sanitize preferred languages
            if (!empty($form_data_array['preferred_languages']) && is_array($form_data_array['preferred_languages'])) {
                $sanitized_form_data['preferred_languages'] = array_map('sanitize_text_field', $form_data_array['preferred_languages']);
            }

            // Sanitize the 'lang' field
            if (isset($form_data_array['lang'])) {
                $sanitized_form_data['lang'] = sanitize_text_field($form_data_array['lang']);
            }

            // Sanitize URLs in the 'encryption' array
            if (!empty($form_data_array['encryption']) && is_array($form_data_array['encryption'])) {
                $sanitized_form_data['encryption'] = array_map('esc_url_raw', $form_data_array['encryption']);
            }

            // Sanitize the 'acknowledgments' field
            if (!empty($form_data_array['acknowledgments']) && is_array($form_data_array['acknowledgments'])) {
                $sanitized_form_data['acknowledgments'] = array_map('sanitize_text_field', $form_data_array['acknowledgments']);
            }

            // Sanitize URLs in the 'canonical' array
            if (!empty($form_data_array['canonical']) && is_array($form_data_array['canonical'])) {
                $sanitized_form_data['canonical'] = array_map('esc_url_raw', $form_data_array['canonical']);
            }

            // Sanitize the 'policy' field
            if (!empty($form_data_array['policy']) && is_array($form_data_array['policy'])) {
                $sanitized_form_data['policy'] = array_map('esc_url_raw', $form_data_array['policy']);
            }

            // Sanitize the 'hiring' field
            if (!empty($form_data_array['hiring']) && is_array($form_data_array['hiring'])) {
                $sanitized_form_data['hiring'] = array_map('esc_url_raw', $form_data_array['hiring']);
            }

            // Sanitize the 'csaf' field
            if (!empty($form_data_array['csaf']) && is_array($form_data_array['csaf'])) {
                $sanitized_form_data['csaf'] = array_map('esc_url_raw', $form_data_array['csaf']);
            }
        }

        return $sanitized_form_data;
    }



    /**
     * The array of fields used to generate the form fields on the backend page
     *
     * @return array[]
     */
    public function admin_security_text_generator_fields() {

        $securitytxt_fields = [
            'contact' => [
                'name' => 'contact',
                'title' => 'Contact',
                'title_l10n' => __('Contact', 'generate-security-txt'),
                'description' => __('This should be the e-mail address, phone number or web page of the person within your organization that security researchers can contact when they have found a vulnerability on your site.', 'generate-security-txt'),
                'description_url' => '',
                'required' => true,
                'multiple' => true,
                'disabled' => false,
                'advanced' => false,
                'placeholder' => __('security@domain.com', 'generate-security-txt'),
                'prefill' => 'admin_mail',
                'prefix' => '',
                'type' => 'text',
                'class' => '',
                'regex' => '^(?:(?:[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})|(?:[0-9]+)|(?:https:\/\/.*))$',
                'invalid_text' => __('Not a valid emailaddress, phonenumber or web URI starting with https://', 'generate-security-txt')
            ],
            'expires' => [
                'name' => 'expires',
                'title' => 'Expires',
                'title_l10n' => __('Expiration date', 'generate-security-txt'),
                'description' => __('Because data needs to stay current, an expiration date is set within one year so you can check the data at least once a year. We already have this set but feel free to adjust it. ', 'generate-security-txt'),
                'description_url' => '',
                'required' => true,
                'multiple' => false,
                'disabled' => false,
                'advanced' => false,
                'placeholder' => __('YYYY-MM-DD', 'generate-security-txt'),
                'prefill' => 'expire_suggest',
                'prefix' => '',
                'type' => 'text',
                'class' => '',
                'regex' => '^[0-9]{4}-[0-9]{2}-[0-9]{2}$',
                'invalid_text' => __('Not a valid date format, needs YYYY-MM-DD', 'generate-security-txt')
            ],
            'preferred_languages' => [
                'name' => 'preferred_languages',
                'title' => 'Preferred-Languages',
                'title_l10n' => __('Language settings', 'generate-security-txt'),
                'description' => __('Above you can specify the languages in which you can (and want to) receive notifications. We have already set the language of your WordPress environment for your convenience.', 'generate-security-txt'),
                'description_url' => '',
                'required' => false,
                'multiple' => false,
                'disabled' => false,
                'advanced' => false,
                'placeholder' => __('en, es, nl', 'generate-security-txt'),
                'prefill' => 'site_lang',
                'prefix' => '',
                'type' => 'text',
                'class' => '',
                'invalid_text' => __('Not a valid format. Needs comma-seperated language codes or use the dropdown', 'generate-security-txt')
            ],
            'encryption' => [
                'name' => 'encryption',
                'title' => 'Encryption',
                'title_l10n' => __('Encryption', 'generate-security-txt'),
                'description' => __('If your webhost supports encryption, the security.txt will be digitally encrypted. If so, you will find the PGP key above. If not, the security.txt file will be generated without encryption and you can ask your hosting provider about enabling the PHP-extension <code>gnupg</code>.', 'generate-security-txt'),
                'description_url' => '',
                'required' => false,
                'multiple' => false,
                'disabled' => true,
                'advanced' => true,
                'placeholder' => __('This will be filled automatically after generating your security.txt', 'generate-security-txt'),
                'prefill' => '',
                'prefix' => '',
                'type' => 'text',
                'class' => ''
            ],
            'acknowledgments' => [
                'name' => 'acknowledgments',
                'title' => 'Acknowledgments',
                'title_l10n' => __('Acknowledgments', 'generate-security-txt'),
                'description' => __('Here you can enter a page thanking the security researchers for reporting a vulnerability.', 'generate-security-txt'),
                'description_url' => '',
                'required' => false,
                'multiple' => true,
                'disabled' => false,
                'advanced' => true,
                'placeholder' => trailingslashit(home_url()) . 'hall-of-fame',
                'prefill' => false,
                'prefix' => '',
                'type' => 'text',
                'class' => '',
                'regex' => '^https:\\/\\/',
                'invalid_text' => __('Not a valid web URI starting with https://', 'generate-security-txt')
            ],
            'canonical' => [
                'name' => 'canonical',
                'title' => 'Canonical',
                'title_l10n' => __('File & location', 'generate-security-txt'),
                'description' => __('The security.txt file has been placed in the right folder (well-known) and digitally encrypted. Above you can see where the file is located and how it is read by security researchers.', 'generate-security-txt'),
                'description_url' => '',
                'required' => false,
                'multiple' => true,
                'disabled' => false,
                'advanced' => true,
                'placeholder' => trailingslashit(home_url()) . '.well-known/security.txt',
                'prefill' => trailingslashit(home_url()) . '.well-known/security.txt',
                'prefix' => '',
                'type' => 'text',
                'class' => '',
                'regex' => '^https:\\/\\/',
                'invalid_text' => __('Not a valid web URI starting with https://', 'generate-security-txt')
            ],
            'policy' => [
                'name' => 'policy',
                'title' => 'Policy',
                'title_l10n' => __('Policy', 'generate-security-txt'),
                'description' => __('Some organizations have a security policy on how they want to receive notifications. If you have a specific security policy, you can enter it here.', 'generate-security-txt'),
                'description_url' => '',
                'required' => false,
                'multiple' => true,
                'disabled' => false,
                'advanced' => true,
                'placeholder' => trailingslashit(home_url()) . 'security-policy',
                'prefill' => '',
                'prefix' => '',
                'type' => 'text',
                'class' => '',
                'regex' => '^https:\\/\\/',
                'invalid_text' => __('Not a valid web URI starting with https://', 'generate-security-txt')
            ],
            'hiring' => [
                'name' => 'hiring',
                'title' => 'Hiring',
                'title_l10n' => __('Hiring', 'generate-security-txt'),
                'description' => __('If you have job openings for security-related positions in your organization, you can enter them here as well. So, enter the link to your vacancies here.', 'generate-security-txt'),
                'description_url' => '',
                'required' => false,
                'multiple' => true,
                'disabled' => false,
                'advanced' => true,
                'placeholder' => trailingslashit(home_url()) . 'jobs',
                'prefill' => false,
                'prefix' => '',
                'type' => 'text',
                'class' => '',
                'regex' => '^https:\\/\\/',
                'invalid_text' => __('Not a valid web URI starting with https://', 'generate-security-txt')
            ],
            'csaf' => [
                'name' => 'csaf',
                'title' => 'CSAF',
                'title_l10n' => __('Common Security Advisory Framework (CSAF)', 'generate-security-txt'),
                'description' => __('If you use a CSAF to receive automated notifications, for example, you can enter it here.', 'generate-security-txt'),
                'description_url' => '',
                'required' => false,
                'multiple' => true,
                'advanced' => true,
                'placeholder' => trailingslashit(home_url()) . '.well-known/csaf/provider-metadata.json',
                'prefill' => false,
                'prefix' => '',
                'type' => 'text',
                'class' => '',
                'regex' => '^https:\\/\\/',
                'invalid_text' => __('Not a valid web URI starting with https://', 'generate-security-txt')
            ]
        ];

        return $securitytxt_fields;
    }


    /**
     * Echo the HTML for the list of actions
     *
     * @param $actions
     * @return void
     */
    public function create_action_list($actions): void {
        ?>
            <?php foreach($actions as $action) : ?>
            <?php
                $loader = $action['loader'] ? '<img src="' . trailingslashit(includes_url()) . 'images/spinner.gif">' : '';
            ?>
                <li><div <?php echo esc_html(!empty($action['loader']) ? 'style="display: none;"' : ''); ?> class="dashicons dashicons-yes"></div><?php echo esc_html($loader); ?><?php echo esc_html($action['text_start']); ?></li>
            <?php endforeach; ?>
        <?php
    }


    /**
     * Echo the HTML for the form
     *
     * @return void
     */
    public function create_form_html($form_fields) {

        $advanced_open = false;

        // Loop the fields
        foreach($form_fields as $form_field) {

            if(!empty($form_field['advanced']) && !$advanced_open) {
                $advanced_open = true;
                echo '<div id="securitytxtAdvanced" class="securitytxt-advanced-fields" style="display: none;">';
            }

            $this->create_field_html($form_field);
        }

        if($advanced_open) {
            echo '</div>';
            echo '<button id="securitytxtShowAdvanced" class="securitytxt-show-advanced button"><div class="dashicons dashicons-plus"></div> ' . esc_html__('Toggle advanced settings', 'generate-security-txt') . '</button>';
        }
    }


    /**
     * Echo the HTML for a field
     *
     * @return void
     */
    public function create_field_html($field_values) {

        $current_values = get_option($this::OPTION_FORM_PREFIX . $field_values['name']);

        // No current value, we check to prefill field
        if(empty($current_values) || $field_values['prefill'] == 'expire_suggest') {
            $current_values = [];
            $current_values[0] = $this->prefill_value($field_values);
        }

        $field_values = $this->maybe_alter_placeholder($field_values);

        $field_count = !empty($current_values) && is_array($current_values) ? count($current_values) : 1;
        $data_regex = !empty($field_values['regex']) ? $field_values['regex'] : '';
        ?>
            <h4 class="securitytxt-form-heading">
                <label class="title" for="<?php echo esc_attr($field_values['name']); ?>"><?php echo esc_html($field_values['title']); ?></label>
                <?php if (!empty($field_values['required'])) : ?>
                    <span class="badge red"><?php echo esc_html__('Required', 'generate-security-txt'); ?></span>
                <?php else : ?>
                    <span class="badge neutral"><?php echo esc_html__('Optional', 'generate-security-txt'); ?></span>
                <?php endif; ?>
            </h4>
            <div class="securitytxt-form-field">
                <?php for($i = 0; $i < $field_count; $i++) : ?>
                    <div class="securitytxt-form-input <?php echo esc_html($i != 0 ? 'securitytxt-removable' : ''); ?>">
                        <input type="<?php echo esc_attr($field_values['type']); ?>" <?php echo esc_attr(!empty($field_values['required']) ? 'required' : ''); ?> placeholder="<?php echo esc_html($field_values['placeholder']); ?>"
                               class="<?php echo esc_attr($field_values['required'] ? 'required' : ''); ?> <?php echo esc_attr(!empty($field_values['disabled']) ? 'securitytxt-readonly securitytxt-disabled' : ''); ?> <?php echo !empty($field_values['regex']) ? 'validate' : ''; ?> <?php echo esc_attr($field_values['class']); ?>" id="<?php echo esc_attr($field_values['name'] . '_' . $i); ?>" name="<?php echo esc_attr($field_values['name']); ?>[]"
                               value="<?php echo esc_attr($current_values[$i]); ?>" data-regex="<?php echo esc_js($data_regex); ?>" data-count="<?php echo esc_attr($i); ?>">
                        <button class="securitytxt-submit-button button securitytxt-remove" style="display: <?php echo $i != 0 ? 'block' : 'none'; ?>"><div class="dashicons dashicons-no"></div></button>
                    </div>
                <?php endfor; ?>
                <?php if (!empty($field_values['multiple'])) : ?>
                    <button type="button" class="button securitytxt-addfield">
                        <?php echo esc_html__( 'Add another', 'generate-security-txt'); ?>
                    </button>
                <?php endif; ?>
                <?php if (!empty($field_values['name'] == 'preferred_languages')) : ?>
                    <?php
                    $args = array(
                        'languages' => get_available_languages(), // Get all available languages
                        'selected' => get_locale(), // Set the default selected language
                        'name' => 'lang', // Set the name attribute for the <select> element
                        'id' => 'securitytxtAddLang',
                        'class' => 'securitytxt-dropdown', // Add a CSS class for styling (optional)
                        'show_available' => false, // Display only available languages
                        'show_default' => false, // Display the default language
                        'display_names_as' => 'name', // Display language names as 'name' or 'code'
                    );

                    wp_dropdown_languages($args);
                    echo '<small class="securitytxt-description">' . esc_html__('Select a language to add the correct code to the field') . '</small>';
                    ?>
                <?php endif; ?>
                <?php if (!empty($field_values['description'])) : ?>
                    <?php if (!empty($field_values['description_url'])) : // Not currently used ?>
                        <?php // translators: any description can hold a possible URL to direct to the RFC ?>
                        <p><?php //= sprintf(__($field_values['description'], 'generate-security-txt'), $field_values['description_url']); ?></p>
                    <?php else : ?>
                        <p><?php echo esc_html($field_values['description']); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
                <hr>
            </div>
        <?php
    }


    /**
     * Maybe alter the placeholder if nessecary
     *
     * @return array
     */
    private function maybe_alter_placeholder($field_values) {

        // Change the placeholder on the encryption field if gnupg is not available to clarify
        if(!empty($field_values['name']) && $field_values['name'] == 'encryption') {
            if(!$this->is_gnupg_available()) {
                $field_values['placeholder'] = __('Encryption is not available because PHP-extension \'gnupg\' is not available', 'generate-security-txt');
            }
        }

        return $field_values;
    }


    /**
     * Prefill the fields
     *
     * @param $field_name
     * @return string
     */
    public function prefill_value($field_name) {

        // Return an empty string if there's no prefill
        if(empty($field_name['prefill']))
            return '';

        if($field_name['prefill'] == 'admin_mail') {
            // Get the admin e-mailadres
            $admin_email = get_option('admin_email');

            // Doublecheck a valid emailadress
            if(is_email($admin_email))
                return $admin_email;
        }
        elseif($field_name['prefill'] == 'site_lang') {
            // Get the admin e-mailadres
            $site_lang = get_bloginfo('language');
            $lang_parts = explode('-', $site_lang);
            $site_lang = !empty($lang_parts[0]) ? $lang_parts[0] : '';

            // Doublecheck a valid value
            if(!empty($site_lang))
                return $site_lang;
        }
        elseif($field_name['prefill'] == 'expire_suggest') {
            // Get the current date
            $current_date = new DateTime();
            // Add 11 months to the current date
            $future_date = $current_date->modify('+12 months');
            $future_date->modify('-1 day');
            // Format the future date
            $formatted_date = $future_date->format('Y-m-d');

            // Doublecheck a valid value
            if(!empty($formatted_date))
                return $formatted_date;
        }
        elseif($field_name['prefill'] == 'wp_privacypolicy') {
            // Get the privacy policy page, if published
            $privacy_policy_url = esc_url(get_privacy_policy_url());

            // Doublecheck a valid value
            if(!empty($privacy_policy_url))
                return $privacy_policy_url;
        }

        // Fallback
        return $field_name['prefill'];
    }


    // Define a custom function to check expiration date and send email
    public function check_securitytxt_expiration_and_send_email($ignore_checks = false)
    {
        // Get expiration date
        $securitytxt_expire = $this->get_expiredate();

        if(!$ignore_checks) {

            // Update WordPress option to indicate that email has been sent
            $email_sent = get_option($this::OPTION_FORM_PREFIX . 'securitytxt_email_sent', true);

            if($email_sent)
                return;

            // Reformat to string
            if (!empty($securitytxt_expire) && is_array($securitytxt_expire))
                $securitytxt_expire = reset($securitytxt_expire);

            // If expiration date is not available or not in the correct format, return
            if (!$securitytxt_expire || !is_string($securitytxt_expire))
                return;

            // If somehow the file doesn't exist, it can't expire (possibly user deleted all data)
            if(!$this->check_securitytxt())
                return;
        }

        // Convert expiration date to DateTime object
        $securitytxt_expire_date = DateTime::createFromFormat("Y-m-d\TH:i:s.u\Z", $securitytxt_expire);

        // Calculate the date for today
        $today = new DateTime('now');

        // Check if expiration date is within 1 day or has passed
        if ($securitytxt_expire_date <= $today->modify('+1 day')) {

            // Send email to website admin
            $to_email = get_option('admin_email');
            $mail_title = __('Security.txt Expiry Reminder', 'generate-security-txt');

            // Define the variables to be replaced in the mail content
            $website = home_url();
            $generate_url = admin_url('tools.php?page=security_txt_generator');

            // Translate and format the mail content
            // translators: a link to the admin page for this plugins on plugin's website
            $mail_content = sprintf(__('<h2>Security.txt Expiry Notice</h2><p>This is a reminder from your WordPress website on %1$s.</p><p>Your security.txt file will expire on <code>%2$s</code>.</p><p>We recommend regenerating it as soon as possble it on %3$s.</p><hr><p>This message was sent at <code>%4$s</code> by the Wordpress plugin <b>Generate Security.txt</b> by Vereniging van Registrars.</p>', 'generate-security-txt'), $website, $securitytxt_expire_date->format('Y-m-d H:i:s'), $generate_url, $today->format('Y-m-d H:i:s'));;

            // Send the email
            $sent = wp_mail($to_email, $mail_title, $mail_content, ['Content-Type: text/html; charset=UTF-8']);

            if($sent) {
                // Update WordPress option to indicate when email has been sent
                $current_datetime = current_time('mysql');
                update_option($this::OPTION_FORM_PREFIX . 'securitytxt_email_date', $current_datetime);

                // Update WordPress option to indicate that email has been sent
                update_option($this::OPTION_FORM_PREFIX . 'securitytxt_email_sent', true);
            }
        }
    }


    /**
     * Schedule the event to run once a day
     *
     * @return void
     */
    public function schedule_securitytxt_expiration_check()
    {
        // Check if the event is already scheduled
        if (!wp_next_scheduled('check_securitytxt_expiration_event')) {
            // Schedule event to run once a day at midnight
            wp_schedule_event(strtotime('midnight'), 'daily', 'check_securitytxt_expiration_event');
        }
    }


    /**
     * Actions
     * -----------------------
     */


    /**
     * The array of values used to create the list of actions
     *
     * @return array[]
     */
    public function admin_security_text_generator_actions() {

        $securitytxt_actions = [
            'save_fields' => [
                'name' => 'save_fields',
                'text_start' => __('Saving form fields..', 'generate-security-txt'),
                'text_success' => __('Form fields saved.', 'generate-security-txt'),
                'text_fail' => __('Failed to save forms fields.', 'generate-security-txt'),
                'loader' => true,
                'stop_on_fail' => true,
                'action_on_success' => 'check_wellknown_folder',
                'action_on_fail' => false,
                'process_response' => false,
                'class' => ''
            ],
            'check_wellknown_folder' => [
                'name' => 'check_wellknown_folder',
                'text_start' => __('Checking for <code>.well-known</code> folder..', 'generate-security-txt'),
                'text_success' => __('<code>.well-known</code> folder exists.', 'generate-security-txt'),
                'text_fail' => __('<code>.well-known</code> folder doesn\'t exist. Folder must be created.', 'generate-security-txt'),
                'loader' => true,
                'stop_on_fail' => false,
                'action_on_success' => 'check_securitytxt',
                'action_on_fail' => 'create_wellknown_folder',
                'process_response' => false,
                'class' => ''
            ],
            'create_wellknown_folder' => [
                'name' => 'create_wellknown_folder',
                'text_start' => __('Creating <code>.well-known</code> folder..', 'generate-security-txt'),
                'text_success' => __('<code>.well-known</code> folder created succesfully.', 'generate-security-txt'),
                'text_fail' => __('Failed to create <code>.well-known</code> folder. ', 'generate-security-txt'),
                'loader' => true,
                'stop_on_fail' => true,
                'action_on_success' => 'check_securitytxt',
                'action_on_fail' => false,
                'process_response' => false,
                'class' => ''
            ],
            'check_securitytxt' => [
                'name' => 'check_securitytxt',
                'text_start' => __('Checking for old <code>security.txt</code>..', 'generate-security-txt'),
                'text_success' => __('Old <code>security.txt</code> exists.', 'generate-security-txt'),
                'text_fail' => __('Old <code>security.txt</code> doesn\'t exist.', 'generate-security-txt'),
                'loader' => true,
                'stop_on_fail' => false,
                'action_on_success' => 'delete_securitytxt',
                'action_on_fail' => 'create_securitytxt_contents',
                'process_response' => false,
                'class' => ''
            ],
            'delete_securitytxt' => [
                'name' => 'delete_securitytxt',
                'text_start' => __('Deleting old <code>security.txt</code>..', 'generate-security-txt'),
                'text_success' => __('Deleted old <code>security.txt</code> successfully.', 'generate-security-txt'),
                'text_fail' => __('Failed to delete old <code>security.txt</code>.', 'generate-security-txt'),
                'loader' => true,
                'stop_on_fail' => true,
                'action_on_success' => 'create_securitytxt_contents',
                'action_on_fail' => false,
                'process_response' => false,
                'class' => ''
            ],
            'create_securitytxt_contents' => [
                'name' => 'create_securitytxt_contents',
                'text_start' => __('Creating <code>security.txt</code> content..', 'generate-security-txt'),
                'text_success' => __('Content for <code>security.txt</code> successfully created.', 'generate-security-txt'),
                'text_fail' => __('Failed to content for <code>security.txt</code>.', 'generate-security-txt'),
                'loader' => true,
                'stop_on_fail' => true,
                'action_on_success' => 'save_securitytxt', // Skip to save
                'action_on_fail' => false,
                'process_response' => false,
                'class' => ''
            ],
            'save_securitytxt' => [
                'name' => 'save_securitytxt',
                'text_start' => __('Saving <code>security.txt</code> file..', 'generate-security-txt'),
                'text_success' => __('Saved <code>security.txt</code> successfully.', 'generate-security-txt'),
                'text_fail' => __('Failed to save <code>security.txt</code>.', 'generate-security-txt'),
                'loader' => true,
                'stop_on_fail' => true,
                'action_on_success' => 'check_gnupg',
                'action_on_fail' => false,
                'process_response' => false,
                'class' => ''
            ],
            'check_gnupg' => [
                'name' => 'check_gnupg',
                'text_start' => __('Checking for encryption availability..', 'generate-security-txt'),
                'text_success' => __('Encryption is available. <code>PHP-extension \'gnupg\'</code> is installed.', 'generate-security-txt'),
                'text_fail' => __('Encryption not available, skipping encryption. Ask your webhosting about the <code>PHP-extension \'gnupg\'</code>.', 'generate-security-txt'),
                'loader' => true,
                'stop_on_fail' => false,
                'action_on_success' => 'encrypt_securitytxt', // Skip to save
                'action_on_fail' => 'finish',
                'process_response' => false,
                'class' => ''
            ],
            // Moved key generation to one function
//            'generating_keys' => [
//                'name' => 'generating_keys',
//                'text_start' => __('Generating private and public keys..', 'generate-security-txt'),
//                'text_success' => __('Private and public keys successfully generated.', 'generate-security-txt'),
//                'text_fail' => __('Failed to generate private and public keys.', 'generate-security-txt'),
//                'loader' => true,
//                'stop_on_fail' => true,
//                'action_on_success' => 'encrypt_securitytxt',
//                'action_on_fail' => false,
//                'class' => ''
//            ],
            'encrypt_securitytxt' => [
                'name' => 'encrypt_securitytxt',
                'text_start' => __('Generating keys and signing <code>security.txt</code>..', 'generate-security-txt'),
                'text_success' => __('Successfully generated keys and signed <code>security.txt</code>.', 'generate-security-txt'),
                'text_fail' => __('Failed to generate keys and/or couldn\'t sign <code>security.txt</code>.', 'generate-security-txt'),
                'loader' => true,
                'stop_on_fail' => true,
                'action_on_success' => 'finish',
                'action_on_fail' => false,
                'process_response' => true,
                'class' => ''
            ],
            'finish' => [
                'name' => 'finish',
                'text_start' => __('Finishing..', 'generate-security-txt'),
                'text_success' => __('Finished succesfully.', 'generate-security-txt'),
                'text_fail' => '',
                'loader' => false,
                'stop_on_fail' => true,
                'action_on_success' => false,
                'action_on_fail' => false,
                'process_response' => false,
                'class' => ''
            ]
        ];

        return $securitytxt_actions;
    }


	/**
     * Process a series of actions
     *
	 * @return void
	 */
    public function process_actionlist_callback() {

        $postdata = array();

        $status = -1;
        $finished_text = '';
        $next_start_text = '';
        $response = '';
        $continue = true;

        $finished_action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';
        $next_action = isset($_POST['next_action']) ? sanitize_text_field($_POST['next_action']) : '';

        $postdata['action'] = $finished_action;
        $postdata['next_action'] = $next_action;

        $postdata['form_data'] = $this->sanitize_form_submit_post();

	    // Verify the nonce before proceeding
	    if ( check_admin_referer() ) {

		    $finished_text = __( 'Invalid nonce.. Stopping.', 'generate-security-txt' );

		    // Example: Send a JSON response
		    $response = array(
			    'status'          => 0,
			    'finished_action' => 'save_fields',
			    'finished_text'   => $finished_text,
			    'next_action'     => '',
			    'next_start_text' => $next_start_text,
			    'response_data'   => null,
			    'continue'        => false
		    );

		    wp_send_json( $response );

		    return;
	    }

        if(!empty($postdata) && !empty($postdata['next_action'])) {
            $action_list = $this->admin_security_text_generator_actions();
            $action_name = $postdata['next_action'];
            $finished_action = $action_name;
            $action = key_exists($action_name, $action_list) ? $action_list[$action_name] : false;

            if(!empty($action) && is_array($action)) {
                if(method_exists($this, $action['name'])) {

                    $form_data = !empty($postdata['form_data']) ? $postdata['form_data'] : false;

                    if($form_data) {
                        $status = call_user_func([$this, $action['name']], $form_data);
                    }
                    else {
                        $status = call_user_func([$this, $action['name']]);
                    }

                    if($action['process_response']) {
                        // Save response to variable;
                        $response = $status;

                        // Change status to boolean for processing
                        $status = !empty($status);

                    }

                    // Positive
                    if($status) {
                        $finished_text = !empty($action['text_success']) ? $action['text_success'] : '';

                        if(!isset($action['stop_on_success']) || !$action['stop_on_success']) {
                            $next_action = !empty($action['action_on_success']) ? $action_list[$action['action_on_success']]['name'] : false;
                            $next_start_text = !empty($action['action_on_success']) ? $action_list[$action['action_on_success']]['text_start'] : false;
                        }
                        else {
                            $continue = false;
                        }
                    }
                    // Negative
                    else {
                        $finished_text = !empty($action['text_fail']) ? $action['text_fail'] : '';

                        if(!isset($action['stop_on_fail']) || !$action['stop_on_fail']) {
                            $next_action = !empty($action['action_on_fail']) ? $action_list[$action['action_on_fail']]['name'] : false;
                            $next_start_text = !empty($action['action_on_fail']) ? $action_list[$action['action_on_fail']]['text_start'] : false;
                        }
                        else {
                            $continue = false;
                        }
                    }
                }
                else {
                    $status = -2;
                }
            }
        }

        if(empty($next_action)) {
            $continue = false;
        }

        // Example: Send a JSON response
        $response = array(
            'status' => $status,
            'finished_action' => $finished_action,
            'finished_text' => $finished_text,
            'next_action' => $next_action,
            'next_start_text' => $next_start_text,
            'response_data' => $response,
            'continue' => $continue
        );

        wp_send_json($response);
    }


    /**
     * Get post finish data
     *
     * @return void
     */
    public function post_finish_callback() {
        $content = '';

        // File path
        $well_known_path = trailingslashit(ABSPATH) . '.well-known/';
        $file_securitytxt = $well_known_path . 'security.txt';

        $securitytxt = $this->check_securitytxt();
        $pubkey = $this->check_pubkey();

        // Check if the file exists
        if (file_exists($file_securitytxt)) {
            // Read contents from the file
//            $securitytxt_contents = wp_remote_get($file_securitytxt);
            $securitytxt_contents = $this->get_securitytxt_file_contents();

            // Check if reading was successful
            if ($securitytxt_contents !== false) {
                $content = $securitytxt_contents;
            } else {
                // Error reading contents
                $content = 'Error reading security.txt contents';
            }
        } else {
            // File does not exist
            $content = 'Security.txt file does not exist';
        }

        // Example: Send a JSON response
        $response = array(
            'content' => $content,
            'securitytxt' => $securitytxt,
            'pubkey' => $pubkey
        );

        wp_send_json($response);
    }


	/**
	 * Process a POST submit from the main form
	 *
	 * @param $postdata
	 *
	 * @return void
	 */
    public function process_form_submit( $postdata = null ): void {

        $postdata = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $postdata = null;

	    // Check if this request was a post and if postdata is filled
	    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && ! empty( $postdata ) && is_array( $postdata ) ) {

            // Check nonce validity
		    $nonce_check = check_admin_referer( 'securitytxt_nonce' );

            echo '<ul class="securitytxt-actionlist">';

		    if ( $nonce_check ) {

			    $this->save_form_postdata( $postdata );

			    $action_list = $this->admin_security_text_generator_actions();

			    $this->process_actionlist( $action_list, reset( $action_list ) );

		    } else {
			    echo '<li><div class="dashicons dashicons-no"></div>' . esc_html__( 'A problem occured.', 'generate-security-txt' ) . '</li>';
		    }

		    echo '</ul>';
	    }
    }


    /**
     * Process the actionlist recursively
     *
     * @deprecated - Not used in primary form at the moment, only for manual check
     *
     * @param $action_list
     * @param $action
     * @return void
     */
    private function process_actionlist($action_list, $action) {

        $allowed_html = [
            'b' => [],
            'i' => [],
            'strong' => [],
            'em' => [],
            'code' => []
        ];

        if(!empty($action) && is_array($action)) {
            echo '<li><div class="dashicons dashicons-yes"></div>' . wp_kses($action['text_start'], $allowed_html) . '</li>';

            if(method_exists($this, $action['name'])) {

                $result = -1;

                $result = call_user_func( [ $this, $action['name'] ] );

	            // Success
                if($result) {
                    echo !empty($action['text_success']) ? '<li><div class="dashicons dashicons-yes"></div>' . wp_kses($action['text_success'], $allowed_html) . '</li>' : '';
                    $next_action = !empty($action['action_on_success']) ? $action_list[$action['action_on_success']] : false;
                    $this->process_actionlist($action_list, $next_action);
                }
                // Fail
                else {
                    echo '<li><div class="dashicons dashicons-no"></div>' . wp_kses($action['text_fail'], $allowed_html) . '</li>';

                    if(!$action['stop_on_fail']) {
                        $next_action = !empty($action['action_on_fail']) ? $action_list[$action['action_on_fail']] : false;
                        $this->process_actionlist($action_list, $next_action);
                    }
                    else {
                        echo '<li><b>Stopped on failure</b></li>';
                    }
                }
            }
            else {
                echo '<li><b>Stopped. Function <code>' . wp_kses($action['name'], $allowed_html) . '</code> doesn\'t exist.</b></li>';
            }
        }
        else {
            // Moved logic to different area
//            echo '<li><b>Finished</b></li>';
        }
    }


    /**
     * Ajax callback to save fields
     *
     * @return bool
     */
    public function save_fields($post_data = null) {

        if(!empty($post_data)) {
            return $this->save_form_postdata($post_data);
        }

        return false;
    }


    /**
     * Process postdata to saved options
     *
     * @param $postdata
     * @return bool
     */
    public function save_form_postdata($postdata = null) {

        // Retrieve our fields
        $allowed_fields = $this->admin_security_text_generator_fields();

        foreach ($postdata as $key => $values) {

            // Check if the key exists in our fields
            if (array_key_exists($key, $allowed_fields)) {

                // Sanitize and save form data to custom fields
                // Check if $values is an array
                if (is_array($values)) {
                    // Sanitize each string in the array
                    $clean_values = array_map('sanitize_text_field', $values);
                } else {
                    // Sanitize the single string
                    $clean_values = sanitize_text_field($values);
                }

                // We need to add the security.txt URI to the canonical field if it doesn't exist, this is mandatory
                if($key == 'canonical') {
                    $securitytxt_url = $this->get_securitytxt_url();

                    if(empty($clean_values) || !in_array($securitytxt_url, $clean_values))
                        $clean_values = [$securitytxt_url];
                }

                // We need to add the public key URI to the encryption field if it doesn't exist, this is mandatory
                // Should probably not fill this field until later, after pubkey has been saved
                if($key == 'encryption' && $this->is_gnupg_available()) {
                    $pubkey_url = $this->get_pubkey_url();

                    if(empty($clean_values) || !in_array($pubkey_url, $clean_values))
                        $clean_values = [$pubkey_url];
                }

                update_option($this::OPTION_FORM_PREFIX . $key, $clean_values);
                $this->update_securitytxt_value($allowed_fields[$key], $clean_values);
            }
        }

        return true;
    }


    /**
     * Delete or empty all data stored by this plugin
     *
     * @return void
     */
    function action_delete_all()
    {
        // Check if the URL parameter 'your_parameter' is set
        if ( isset($_GET['action']) && $_GET['action'] === 'securitytxt_erase' ) {

            if(check_admin_referer( 'securitytxt_erase' )) {

                // Check if the current screen is your plugin admin page
                $screen = get_current_screen();

                if ( $screen && $screen->id === 'tools_page_security_txt_generator' ) {

                    // Execute the erase functions when the parameter exists on the backend page
                    $this->delete_all_option_data();
                    $this->delete_securitytxt();
                    $this->delete_publickey_file();

                    // Queue the notification
                    update_option($this::OPTION_FORM_PREFIX . 'notification_delete', true);

                    // Optional: Redirect to remove the parameter from the URL
                    wp_safe_redirect(remove_query_arg('action'));
                    exit();
                }
            }
            else {
                // Will produce a 'The link you followed has expired.' screen by default
            }
        }
    }


    /**
     * Get the expiry date of the security.txt file
     *
     * @return bool
     */
    public function get_expiredate() {
        return get_option($this::OPTION_TXT_PREFIX . 'expires', '');
    }


    /**
     * Delete all saved options
     *
     * @return void
     */
    public function delete_all_option_data(): void
    {
        // Retrieve our fields
        $allowed_fields = $this->admin_security_text_generator_fields();

        foreach ($allowed_fields as $field) {
            // Delete saved options
            delete_option($this::OPTION_FORM_PREFIX . $field['name']);
            delete_option($this::OPTION_TXT_PREFIX . $field['name']);
        }
    }


    /**
     * Check if the well-known folder exists
     *
     * @return bool
     */
    public function check_wellknown_folder(): bool
    {
        // TODO Move to own action in later version

        $well_known_path = trailingslashit(ABSPATH) . '.well-known/';

        // Check if the folder exists
        if (is_dir($well_known_path)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Create the well-known folder
     *
     * @return bool
     */
    public function create_wellknown_folder() {
        global $wp_filesystem;

        // Initialize the WP_Filesystem
        if ( ! WP_Filesystem() ) {
            return false;
        }

        $well_known_path = trailingslashit(ABSPATH) . '.well-known/';

        // Check if the folder exists
        if ( $wp_filesystem->is_dir( $well_known_path ) ) {
            // Folder already exists
            return true;
        }

        // Attempt to create the folder
        if ( $wp_filesystem->mkdir( $well_known_path, 0755 ) ) {
            // Folder created successfully
            return true;
        } else {
            // Unable to create the folder
            return false;
        }
    }


    /**
     * Create contents - Dummy function for frontend
     */
    public function create_securitytxt_contents() {
        // This exists inside the options
        return true;
    }


    /**
     * Check if security.txt exists
     *
     * @return bool
     */
    public function check_securitytxt() {
        $well_known_path = trailingslashit(ABSPATH ) . '.well-known/';
        $file_to_detect = $well_known_path . 'security.txt';

        // Check if the file exists
        if (file_exists($file_to_detect)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Delete the security.txt file if it exists
     *
     * @return bool
     */
    public function delete_securitytxt(): bool
    {
        $well_known_path = trailingslashit(ABSPATH ) . '.well-known/';
        $file_to_delete = $well_known_path . 'security.txt';

        // Check if the file exists before attempting to delete
	    if ( file_exists( $file_to_delete ) ) {
		    // Attempt to delete the file
		    wp_delete_file( $file_to_delete );

		    if ( ! file_exists( $file_to_delete ) ) {
			    return true;
		    } else {
			    return false;
		    }
	    } else {
		    // File doesn't exist in the first place
		    return true;
	    }
    }


    /**
     * Check if pubkey exists
     *
     * @return bool
     */
    public function check_pubkey(): bool
    {
        $well_known_path = trailingslashit(ABSPATH );
        $file_to_detect = $well_known_path . 'pubkey.txt';

        // Check if the file exists
        if (file_exists($file_to_detect)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Create security.txt
     *
     * @return bool
     */
    public function save_securitytxt(): bool {
	    global $wp_filesystem;

	    // Initialize the WP Filesystem
	    if ( ! function_exists( 'request_filesystem_credentials' ) ) {
		    require_once( ABSPATH . 'wp-admin/includes/file.php' );
	    }

	    $creds = request_filesystem_credentials( site_url() );

	    // Check if WP_Filesystem is initialized correctly
	    if ( ! WP_Filesystem( $creds ) ) {
		    return false;
	    }

	    // Try to delete (this is redundant)
	    if ( ! $this->delete_securitytxt() ) {
		    return false;
	    }

	    // Continue
	    $well_known_path  = trailingslashit( ABSPATH ) . '.well-known/';
	    $file_securitytxt = $well_known_path . 'security.txt';

	    // Check if the file exists for some reason, we don't want to throw errors
	    if ( ! $wp_filesystem->exists( $file_securitytxt ) ) {
		    $securitytxt_contents = $this->get_securitytxt_contents( true );

		    // Write contents to the file
		    if ( $wp_filesystem->put_contents( $file_securitytxt, $securitytxt_contents, FS_CHMOD_FILE ) ) {
			    // File created successfully
			    return true;
		    } else {
			    // Unable to write contents to the file
			    return false;
		    }
	    }

	    return false;
    }


    /**
     * Action for ajax call to check gnupg availability
     *
     * @return true
     */
    public function check_gnupg() {
        return $this->is_gnupg_available();
    }


    /**
     * Action for ajax call to check gnupg availability
     *
     * @return array|bool
     */
    public function encrypt_securitytxt() {
        global $wp_filesystem;

        // Initialize the WP Filesystem
        if ( ! function_exists( 'request_filesystem_credentials' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }

        $creds = request_filesystem_credentials( site_url() );

        // Check if WP_Filesystem is initialized correctly
        if ( ! WP_Filesystem( $creds ) ) {
            return false;
        }

        // Retrieve our fields
        $fields = $this->admin_security_text_generator_fields();

        // Retrieve contact email address
        $contact_email = get_option($this::OPTION_FORM_PREFIX . $fields['contact']['name']);

        // We can't proceed without an email address
        if (empty($contact_email))
            return false;

        $contact_email = reset($contact_email);

        // There is no name requirement in the security.txt standard, but PGP encryption requires it. We will simply use the email for this again
        $contact_name = $contact_email;

        if ($this->check_securitytxt()) {
            $securitytxt_contents = $this->get_securitytxt_file_contents();

            $Securitytxt_Encryption = new Securitytxt_Encryption();

            $result = $Securitytxt_Encryption->encrypt_securitytxt($contact_name, $contact_email, $securitytxt_contents, '');

            if (!empty($result['signed_message'])) {
                $well_known_path = trailingslashit(ABSPATH) . '.well-known/';
                $file_securitytxt = $well_known_path . 'security.txt';

                // Write contents to the file
                if ($wp_filesystem->put_contents($file_securitytxt, $result['signed_message'], FS_CHMOD_FILE)) {
                    // File created successfully
                } else {
                    // Unable to write contents to the file
                    return false;
                }
            }

            if (!empty($result['public_key'])) {
                $file_pubkey = trailingslashit(ABSPATH) . 'pubkey.txt';

                // Write contents to the file
                if ($wp_filesystem->put_contents($file_pubkey, $result['public_key'], FS_CHMOD_FILE)) {
                    // File created successfully
                } else {
                    // Unable to write contents to the file
                    return false;
                }
            }

            return $result;
        }

        return false;
    }


    /**
     * Finish
     */
    public function finish() {

        // Update WordPress option to indicate that email has to be sent when expiry is close
        update_option($this::OPTION_FORM_PREFIX . 'securitytxt_email_sent', false);

        // Just true for now
        return true;
    }


    /**
     * Delete the pubkey file if it exists
     *
     * @return bool
     */
    public function delete_publickey_file(): bool
    {
        $base_path = trailingslashit(ABSPATH );
        $file_to_delete = $base_path . 'pubkey.txt';

        // Check if the file exists before attempting to delete
        if (file_exists($file_to_delete)) {
            // Attempt to delete the file
            wp_delete_file( $file_to_delete );

            if ( ! file_exists( $file_to_delete ) ) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }
}

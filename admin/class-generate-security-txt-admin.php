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
        if ($file == get_generate_security_txt_basefile()) {
            $links[] = '<a href="' . admin_url('tools.php?page=security_txt_generator') . '">' . __('Go to settings', Generate_Security_Txt_i18n::TEXT_DOMAIN) . '</a>';
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
            wp_enqueue_style('jquery-ui-datepicker-style', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', [], $this->version, 'all' );
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
                    'first_action_text' => $first_action['text_start']
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
            __( 'Generate Security.txt', Generate_Security_Txt_i18n::TEXT_DOMAIN),
            __( 'Generate Security.txt', Generate_Security_Txt_i18n::TEXT_DOMAIN),
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
                    <p>' . __('No security.txt file exists for this website yet. Create one below.', Generate_Security_Txt_i18n::TEXT_DOMAIN) . '</p>
                </div>';
            }

            // Display your notification if securitytxy doesn't exist
            if(!is_ssl()) {
                echo '<div id="securitytxtNoticeNoTxt" class="notice notice-error">
                    <p>' . __('This website isn\'t using HTTPS. This is a requirement for any value in security.txt containing a web URI. Resolve this before you generate a security.txt file.', Generate_Security_Txt_i18n::TEXT_DOMAIN) . '</p>
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
                    echo '<div id="securitytxtNoticeExpiry" class="notice notice-error">
                        <p>' . sprintf(__('Regenerate your security.txt, the expirydate is very soon or has passed. <a href="%s">Click here</a> to do so.', Generate_Security_Txt_i18n::TEXT_DOMAIN), admin_url('tools.php?page=security_txt_generator')) . '</p>
                    </div>';
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
        $status_text = __('Invalid - Security.txt is missing', Generate_Security_Txt_i18n::TEXT_DOMAIN);

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
                    $status_text = sprintf(__('Security.txt expired on %s. Regenerate the file below.', Generate_Security_Txt_i18n::TEXT_DOMAIN), $securitytxt_expire->format('Y-m-d'));
                } elseif ($securitytxt_expire < $oneMonthLater) {
                    // File will expire soon
                    $status_type = 'ellipsis';
                    $status_color = 'yellow';
                    $status_text = sprintf(__('Security.txt will expire on %s. Regenerate the file below.', Generate_Security_Txt_i18n::TEXT_DOMAIN), $securitytxt_expire->format('Y-m-d'));
                } else {
                    // File will not expire soon
                    $status_type = 'yes';
                    $status_color = 'green';
                    $status_text = __('Security.txt is valid', Generate_Security_Txt_i18n::TEXT_DOMAIN);
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

        // Loop all fields to retrieve the values
        foreach ($fields as $key => $field) {
            $values = get_option($this::OPTION_TXT_PREFIX . $key);

            if(!empty($values) && is_array($values)) {
                foreach($values as $value) {
                    if(!empty($value)) {
                        $securitxt_contents .= $field['title'] . ': ';
                        $securitxt_contents .= $value;

                        // Maybe add linebreaks
                        $securitxt_contents .= $with_linebreaks ? "\n" : '';
                    }
                }
            }

            // Maybe add linebreaks
//            $securitxt_contents .= $with_linebreaks ? '\n' : '';
        }

        return $securitxt_contents;
    }


    /**
     * Get contents file contents
     */
    public function get_securitytxt_file_contents()
    {
        // Check if the file exists
        if ($this->check_securitytxt()) {
            $well_known_path = trailingslashit(ABSPATH) . '.well-known/';
            $file_path = $well_known_path . 'security.txt';

            // Return the contents as a string
            return file_get_contents($file_path);
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
    public function get_deletedata_url(): string
    {
        $deletedate_url = menu_page_url('security_txt_generator', false);
        return add_query_arg('securitytxt_erase', true, $deletedate_url);
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
     * The array of fields used to generate the form fields on the backend page
     *
     * @return array[]
     */
    public function admin_security_text_generator_fields() {

        $securitytxt_fields = [
            'contact' => [
                'name' => 'contact',
                'title' => 'Contact',
                'title_l10n' => __('Contact', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'description' => __('This should be the e-mail address, phone number or web page of the person within your organization that security researchers can contact when they have found a vulnerability on your site.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'description_url' => '',
                'required' => true,
                'multiple' => true,
                'disabled' => false,
                'advanced' => false,
                'placeholder' => __('security@domain.com', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'prefill' => 'admin_mail',
                'prefix' => '',
                'type' => 'text',
                'class' => '',
                'regex' => '^(?:(?:[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})|(?:[0-9]+)|(?:https:\/\/.*))$',
                'invalid_text' => __('Not a valid emailaddress, phonenumber or web URI starting with https://')
            ],
            'expires' => [
                'name' => 'expires',
                'title' => 'Expires',
                'title_l10n' => __('Expiration date', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'description' => __('Because data needs to stay current, an expiration date is set within one year so you can check the data at least once a year. We already have this set but feel free to adjust it. ', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'description_url' => '',
                'required' => true,
                'multiple' => false,
                'disabled' => false,
                'advanced' => false,
                'placeholder' => __('YYYY-MM-DD', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'prefill' => 'expire_suggest',
                'prefix' => '',
                'type' => 'text',
                'class' => '',
                'regex' => '^[0-9]{4}-[0-9]{2}-[0-9]{2}$',
                'invalid_text' => __('Not a valid date format, needs YYYY-MM-DD')
            ],
            'preferred_languages' => [
                'name' => 'preferred_languages',
                'title' => 'Preferred-Languages',
                'title_l10n' => __('Language settings', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'description' => __('Above you can specify the languages in which you can (and want to) receive notifications. We have already set the language of your WordPress environment for your convenience.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'description_url' => '',
                'required' => false,
                'multiple' => false,
                'disabled' => false,
                'advanced' => false,
                'placeholder' => __('en, es, nl', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'prefill' => 'site_lang',
                'prefix' => '',
                'type' => 'text',
                'class' => '',
                'invalid_text' => __('Not a valid format. Needs comma-seperated language codes or use the dropdown')
            ],
            'encryption' => [
                'name' => 'encryption',
                'title' => 'Encryption',
                'title_l10n' => __('Encryption', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'description' => __('If your webhost supports encryption, the security.txt will be digitally encrypted. If so, you will find the PGP key above. If not, the security.txt file will be generated without encryption and you can ask your hosting provider about enabling the PHP-extension <code>gnupg</code>.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'description_url' => '',
                'required' => false,
                'multiple' => false,
                'disabled' => true,
                'advanced' => true,
                'placeholder' => __('This will be filled automatically after generating your security.txt', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'prefill' => '',
                'prefix' => '',
                'type' => 'text',
                'class' => ''
            ],
            'acknowledgments' => [
                'name' => 'acknowledgments',
                'title' => 'Acknowledgments',
                'title_l10n' => __('Acknowledgments', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'description' => __('Here you can enter a page thanking the security researchers for reporting a vulnerability.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
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
                'invalid_text' => __('Not a valid web URI starting with https://')
            ],
            'canonical' => [
                'name' => 'canonical',
                'title' => 'Canonical',
                'title_l10n' => __('File & location', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'description' => __('The security.txt file has been placed in the right folder (well-known) and digitally encrypted. Above you can see where the file is located and how it is read by security researchers.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
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
                'invalid_text' => __('Not a valid web URI starting with https://')
            ],
            'policy' => [
                'name' => 'policy',
                'title' => 'Policy',
                'title_l10n' => __('Policy', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'description' => __('Some organizations have a security policy on how they want to receive notifications. If you have a specific security policy, you can enter it here.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
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
                'invalid_text' => __('Not a valid web URI starting with https://')
            ],
            'hiring' => [
                'name' => 'hiring',
                'title' => 'Hiring',
                'title_l10n' => __('Hiring', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'description' => __('If you have job openings for security-related positions in your organization, you can enter them here as well. So, enter the link to your vacancies here.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
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
                'invalid_text' => __('Not a valid web URI starting with https://')
            ],
            'csaf' => [
                'name' => 'csaf',
                'title' => 'CSAF',
                'title_l10n' => __('Common Security Advisory Framework (CSAF)', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'description' => __('If you use a CSAF to receive automated notifications, for example, you can enter it here.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
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
                'invalid_text' => __('Not a valid web URI starting with https://')
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
                <li><div <?= !empty($action['loader']) ? 'style="display: none;"' : ''; ?> class="dashicons dashicons-yes"></div><?= $loader; ?><?= $action['text_start']; ?></li>
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
            echo '<button id="securitytxtShowAdvanced" class="securitytxt-show-advanced button"><div class="dashicons dashicons-plus"></div> ' . __('Toggle advanced settings', Generate_Security_Txt_i18n::TEXT_DOMAIN) . '</button>';
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
        $data_regex = !empty($field_values['regex']) ? 'data-regex="' . $field_values['regex'] . '"' : '';
        ?>
            <h4 class="securitytxt-form-heading">
                <label class="title" for="<?= $field_values['name']; ?>"><?= $field_values['title']; ?></label>
                <?php if (!empty($field_values['required'])) : ?>
                    <span class="badge red"><?= __('Required', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?></span>
                <?php else : ?>
                    <span class="badge neutral"><?= __('Optional', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?></span>
                <?php endif; ?>
            </h4>
            <div class="securitytxt-form-field">
                <?php for($i = 0; $i < $field_count; $i++) : ?>
                    <div class="securitytxt-form-input <?= $i != 0 ? 'securitytxt-removable' : ''; ?>">
                        <input type="<?= $field_values['type']; ?>" <?= !empty($field_values['required']) ? 'required' : ''; ?> placeholder="<?= $field_values['placeholder']; ?>"
                               class="<?= $field_values['required'] ? 'required' : ''; ?> <?= !empty($field_values['disabled']) ? 'securitytxt-readonly securitytxt-disabled' : ''; ?> <?= !empty($field_values['regex']) ? 'validate' : ''; ?> <?= $field_values['class']; ?>" id="<?= $field_values['name'] . '_' . $i; ?>" name="<?= $field_values['name']; ?>[]"
                               value="<?= $current_values[$i]; ?>" <?= $data_regex; ?> data-count="<?= $i; ?>">
                        <button class="securitytxt-submit-button button securitytxt-remove" style="display: <?= $i != 0 ? 'block' : 'none'; ?>"><div class="dashicons dashicons-no"></div></button>
                    </div>
                <?php endfor; ?>
                <?php if (!empty($field_values['multiple'])) : ?>
                    <button type="button" class="button securitytxt-addfield">
                        <?= __( 'Add another', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?>
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
                    echo '<small class="securitytxt-description">' . __('Select a language to add the correct code to the field') . '</small>';
                    ?>
                <?php endif; ?>
                <?php if (!empty($field_values['description'])) : ?>
                    <?php if (!empty($field_values['description_url'])) : ?>
                        <p><?= sprintf(__($field_values['description'], Generate_Security_Txt_i18n::TEXT_DOMAIN), $field_values['description_url']); ?></p>
                    <?php else : ?>
                        <p><?= $field_values['description']; ?></p>
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
                $field_values['placeholder'] = __('Encryption is not available because PHP-extension \'gnupg\' is not available', Generate_Security_Txt_i18n::TEXT_DOMAIN);
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
    public function check_securitytxt_expiration_and_send_email()
    {
        wp_mail(get_option('admin_email'), 'Securitytxt Expiry Reminder TEST', 'Your securitytxt file will expire tomorrow.');


        // Get expiration date
        $securitytxt_expire = $this->get_expiredate();

        // Reformat to string
        if (!empty($securitytxt_expire) && is_array($securitytxt_expire))
            $securitytxt_expire = reset($securitytxt_expire);

        // If expiration date is not available or not in the correct format, return
        if (!$securitytxt_expire || !is_string($securitytxt_expire)) {
            return;
        }

        // If somehow the file doesn't exist, it can't expire (possibly user deleted all data)
        if(!$this->check_securitytxt())
            return;

        // Convert expiration date to DateTime object
        $securitytxt_expire_date = DateTime::createFromFormat("Y-m-d\TH:i:s.u\Z", $securitytxt_expire);

        // Calculate the date for today
        $today = new DateTime('now');

        // Check if expiration date is within 1 day or has passed
        if ($securitytxt_expire_date <= $today->modify('+1 day')) {
            // Send email to website admin
            // Replace 'admin@example.com' with the admin email address
            wp_mail(get_option('admin_email'), 'Securitytxt Expiry Reminder', 'Your securitytxt file will expire tomorrow.');

            // Update WordPress option to indicate that email has been sent
            update_option($this::OPTION_FORM_PREFIX . 'securitytxt_email_sent', true);
        }
    }

// Schedule the event to run once a day
    function schedule_securitytxt_expiration_check()
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
                'text_start' => __('Saving form fields..', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'text_success' => __('Form fields saved.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'text_fail' => __('Failed to save forms fields.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'loader' => true,
                'stop_on_fail' => true,
                'action_on_success' => 'check_wellknown_folder',
                'action_on_fail' => false,
                'process_response' => false,
                'class' => ''
            ],
            'check_wellknown_folder' => [
                'name' => 'check_wellknown_folder',
                'text_start' => __('Checking for <code>.well-known</code> folder..', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'text_success' => __('<code>.well-known</code> folder exists.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'text_fail' => __('<code>.well-known</code> folder doesn\'t exist. Folder must be created.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'loader' => true,
                'stop_on_fail' => false,
                'action_on_success' => 'check_securitytxt',
                'action_on_fail' => 'create_wellknown_folder',
                'process_response' => false,
                'class' => ''
            ],
            'create_wellknown_folder' => [
                'name' => 'create_wellknown_folder',
                'text_start' => __('Creating <code>.well-known</code> folder..', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'text_success' => __('<code>.well-known</code> folder created succesfully.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'text_fail' => __('Failed to create <code>.well-known</code> folder. ', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'loader' => true,
                'stop_on_fail' => true,
                'action_on_success' => 'check_securitytxt',
                'action_on_fail' => false,
                'process_response' => false,
                'class' => ''
            ],
            'check_securitytxt' => [
                'name' => 'check_securitytxt',
                'text_start' => __('Checking for old <code>security.txt</code>..', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'text_success' => __('Old <code>security.txt</code> exists.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'text_fail' => __('Old <code>security.txt</code> doesn\'t exist.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'loader' => true,
                'stop_on_fail' => false,
                'action_on_success' => 'delete_securitytxt',
                'action_on_fail' => 'create_securitytxt_contents',
                'process_response' => false,
                'class' => ''
            ],
            'delete_securitytxt' => [
                'name' => 'delete_securitytxt',
                'text_start' => __('Deleting old <code>security.txt</code>..', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'text_success' => __('Deleted old <code>security.txt</code> successfully.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'text_fail' => __('Failed to delete old <code>security.txt</code>.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'loader' => true,
                'stop_on_fail' => true,
                'action_on_success' => 'create_securitytxt_contents',
                'action_on_fail' => false,
                'process_response' => false,
                'class' => ''
            ],
            'create_securitytxt_contents' => [
                'name' => 'create_securitytxt_contents',
                'text_start' => __('Creating <code>security.txt</code> content..', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'text_success' => __('Content for <code>security.txt</code> successfully created.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'text_fail' => __('Failed to content for <code>security.txt</code>.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'loader' => true,
                'stop_on_fail' => true,
                'action_on_success' => 'save_securitytxt', // Skip to save
                'action_on_fail' => false,
                'process_response' => false,
                'class' => ''
            ],
            'save_securitytxt' => [
                'name' => 'save_securitytxt',
                'text_start' => __('Saving <code>security.txt</code> file..', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'text_success' => __('Saved <code>security.txt</code> successfully.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'text_fail' => __('Failed to save <code>security.txt</code>.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'loader' => true,
                'stop_on_fail' => true,
                'action_on_success' => 'check_gnupg',
                'action_on_fail' => false,
                'process_response' => false,
                'class' => ''
            ],
            'check_gnupg' => [
                'name' => 'check_gnupg',
                'text_start' => __('Checking for encryption availability..', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'text_success' => __('Encryption is available. <code>PHP-extension \'gnupg\'</code> is installed.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'text_fail' => __('Encryption not available, skipping encryption. Ask your webhosting about the <code>PHP-extension \'gnupg\'</code>.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'loader' => true,
                'stop_on_fail' => false,
                'action_on_success' => 'encrypt_securitytxt', // Skip to save
                'action_on_fail' => 'finish',
                'process_response' => false,
                'class' => ''
            ],
//            'generating_keys' => [
//                'name' => 'generating_keys',
//                'text_start' => __('Generating private and public keys..', Generate_Security_Txt_i18n::TEXT_DOMAIN),
//                'text_success' => __('Private and public keys successfully generated.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
//                'text_fail' => __('Failed to generate private and public keys.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
//                'loader' => true,
//                'stop_on_fail' => true,
//                'action_on_success' => 'encrypt_securitytxt',
//                'action_on_fail' => false,
//                'class' => ''
//            ],
            'encrypt_securitytxt' => [
                'name' => 'encrypt_securitytxt',
                'text_start' => __('Generating keys and signing <code>security.txt</code>..', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'text_success' => __('Successfully generated keys and signed <code>security.txt</code>.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'text_fail' => __('Failed to generate keys and/or couldn\'t sign <code>security.txt</code>.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'loader' => true,
                'stop_on_fail' => true,
                'action_on_success' => 'finish',
                'action_on_fail' => false,
                'process_response' => true,
                'class' => ''
            ],
            'finish' => [
                'name' => 'finish',
                'text_start' => __('Finishing..', Generate_Security_Txt_i18n::TEXT_DOMAIN),
                'text_success' => __('Finished succesfully.', Generate_Security_Txt_i18n::TEXT_DOMAIN),
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


    public function process_actionlist_callback() {

        $postdata = $_POST;
        $status = -1;
        $finished_action = '';
        $finished_text = '';
        $next_action = '';
        $next_start_text = '';
        $response = '';
        $continue = true;

        if(!empty($postdata) && !empty($postdata['next_action'])) {
            $action_list = $this->admin_security_text_generator_actions();
            $action_name = $postdata['next_action'];
            $finished_action = $action_name;
            $action = key_exists($action_name, $action_list) ? $action_list[$action_name] : false;

            if(!empty($action) && is_array($action)) {
                if(method_exists($this, $action['name'])) {

                    $form_data = !empty($postdata['form_data']) ? $postdata['form_data'] : false;
//                    $form_data = !empty($_POST) ? $_POST : false;

                    if($form_data) {
//                        $serialized_data = sanitize_text_field($form_data);
                        $serialized_data = $form_data;
                        $unserialized_data = [];
                        parse_str($serialized_data, $unserialized_data);
//                        print_r($unserialized_data);
//                        die();
                        $status = call_user_func([$this, $action['name']], $unserialized_data);
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

                        if(!$action['stop_on_success']) {
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

                        if(!$action['stop_on_fail']) {
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
//            $securitytxt_contents = file_get_contents($file_securitytxt);
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
     * TODO Change to ajax process
     *
     * @param $_POST
     * @return void
     */
    public function process_form_submit($postdata): void
    {
        // Check if this request was a post and if postdata is filled
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($postdata) && is_array($postdata)) {
            $this->save_form_postdata($postdata);

            $action_list = $this->admin_security_text_generator_actions();

            echo '<ul class="securitytxt-actionlist">';

            $this->process_actionlist($action_list, reset($action_list));

            echo '</ul>';
        }
    }


    /**
     * Process the actionlist recursively
     *
     * @param $action_list
     * @param $action
     * @return void
     */
    private function process_actionlist($action_list, $action) {

        if(!empty($action) && is_array($action)) {
            echo '<li><div class="dashicons dashicons-yes"></div>' . $action['text_start'] . '</li>';

            if(method_exists($this, $action['name'])) {

                $result = -1;

                $form_data = !empty($_POST) ? $_POST : false;

                echo '<li>Calling function <code>' . $action['name'] . '</code> with ' . (!empty($form_data) ? 'data' : 'no data') . '</li>';

                if($form_data) {
//                    $serialized_data = sanitize_text_field($form_data);
//                    $unserialized_data = [];
//                    parse_str($serialized_data, $unserialized_data);
//                    print_r($serialized_data);
                    $result = call_user_func([$this, $action['name']], $form_data);
                }
                else {
                    $result = call_user_func([$this, $action['name']]);
                }

//                $args = [$action_list, $action['action_on_success']];
//                $result = call_user_func([$this, $action['name']]);

                // Success
                if($result) {
                    echo !empty($action['text_success']) ? '<li><div class="dashicons dashicons-yes"></div>' . $action['text_success'] . '</li>' : '';
                    $next_action = !empty($action['action_on_success']) ? $action_list[$action['action_on_success']] : false;
                    $this->process_actionlist($action_list, $next_action);
                }
                // Fail
                else {
                    echo '<li><div class="dashicons dashicons-no"></div>' . $action['text_fail'] . '</li>';

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
                echo '<li><b>Stopped. Function <code>' . $action['name'] . '</code> doesn\'t exist yet.</b></li>';
            }
        }
        else {
//            echo '<li><b>Finished</b></li>';
        }
    }


    /**
     * Ajax callback to save fields
     *
     * @return bool
     */
    public function save_fields($post_data) {

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
    public function save_form_postdata($postdata) {

        // Retrieve our fields
        $allowed_fields = $this->admin_security_text_generator_fields();

        foreach ($postdata as $key => $values) {

            // Check if the key exists in our fields
            if (array_key_exists($key, $allowed_fields)) {

                // Sanitize and save form data to custom fields
                // TODO; This breaks the format of the strings but also seems unnessecary
                //$clean_values = sanitize_text_field($values);
                $clean_values = $values;

                // We need to add the security.txt URI to the canonical field if it doesn't exist, this is mandatory
                if($key == 'canonical') {
                    $securitytxt_url = $this->get_securitytxt_url();

                    if(empty($clean_values) || !in_array($securitytxt_url, $clean_values))
                        $clean_values = [$securitytxt_url];
                }

                // We need to add the public key URI to the encryption field if it doesn't exist, this is mandatory
                if($key == 'encryption' && $this->check_pubkey()) {
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
        if (!empty($_GET['securitytxt_erase'])) {

            // Check if the current screen is your plugin admin page
            $screen = get_current_screen();
//            print_r($screen);

            if ($screen && $screen->id === 'tools_page_security_txt_generator') {
//                echo 'deleting';

                // Execute the erase functions when the parameter exists on the backend page
                $this->delete_all_option_data();
                $this->delete_securitytxt();
                $this->delete_publickey_file();

                // Queue the notification
                update_option($this::OPTION_FORM_PREFIX . 'notification_delete', true);

//                echo 'deleted all';

                // Optional: Redirect to remove the parameter from the URL
                wp_safe_redirect(remove_query_arg('securitytxt_erase'));
                exit();
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
        // TODO Move to own action
//        $this->sav

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
        $well_known_path = trailingslashit(ABSPATH) . '.well-known/';

        // Check if the folder exists
        if (is_dir($well_known_path)) {
            // Folder already exists
            return true;
        }

        // Attempt to create the folder
        if (mkdir($well_known_path, 0755, true)) {
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
        if (file_exists($file_to_delete)) {
            // Attempt to delete the file
            if (unlink($file_to_delete)) {
                // File deleted successfully
                return true;
            } else {
                // Unable to delete the file
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
    public function save_securitytxt(): bool
    {
        // Try to delete (this is redundant)
        if(!$this->delete_securitytxt())
            return false;

        // Continue
        $well_known_path = trailingslashit(ABSPATH) . '.well-known/';
        $file_securitytxt = $well_known_path . 'security.txt';


        // Check if the file exists for some reason, we don't want to throw errors
        if (!file_exists($file_securitytxt)) {
            $securitytxt_contents = $this->get_securitytxt_contents();

            // Write contents to the file
            if (file_put_contents($file_securitytxt, $securitytxt_contents) !== false) {
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

        // Retrieve our fields
        $fields = $this->admin_security_text_generator_fields();

        // Retrieve contact emailaddress
        $contact_email = get_option($this::OPTION_FORM_PREFIX . $fields['contact']['name']);

        // We can't proceed without an emailaddress
        if(empty($contact_email))
            return false;

        $contact_email = reset($contact_email);

        // There is no name requirement in the security.txt standard, but pgp encryption requires it. We will simply use the email for this again
        $contact_name = $contact_email;

        if($this->check_securitytxt()) {
            $securitytxt_contents = $this->get_securitytxt_file_contents();

            $Encryption_Securitytxt = new Encryption_Securitytxt();

            $result = $Encryption_Securitytxt->encrypt_securitytxt($contact_name, $contact_email, $securitytxt_contents);

            if(!empty($result['signed_message'])) {

                $well_known_path = trailingslashit(ABSPATH) . '.well-known/';
                $file_securitytxt = $well_known_path . 'security.txt';

                // Write contents to the file
                if (file_put_contents($file_securitytxt, $result['signed_message'], LOCK_EX) !== false) {
                    // File created successfully
                } else {
                    // Unable to write contents to the file
                    return false;
                }
            }

            if(!empty($result['public_key'])) {

                $file_pubkey = trailingslashit(ABSPATH) . 'pubkey.txt';

                // Write contents to the file
                if (file_put_contents($file_pubkey, $result['public_key'], LOCK_EX) !== false) {
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
            if (unlink($file_to_delete)) {
                // File deleted successfully
                return true;
            } else {
                // Unable to delete the file
                return false;
            }
        } else {
            return true;
        }
    }
}

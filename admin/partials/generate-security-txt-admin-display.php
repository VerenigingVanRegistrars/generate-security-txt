<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Provide an admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://verenigingvanregistrars.nl/
 * @since      1.0.0
 *
 * @package    Generate_Security_Txt
 * @subpackage Generate_Security_Txt/admin/partials
 */

$SecurityTxtAdmin = new Generate_Security_Txt_Admin();
$Securitytxt_Encryption = new Securitytxt_Encryption();
$form_fields = $SecurityTxtAdmin->admin_security_text_generator_fields();

//$SecurityTxtAdmin->process_form_submit();

$SecurityTxtAdmin->check_securitytxt_expiration_and_send_email();
?>
<style>
    .extra-log-row {
        display: none;
    }
</style>
<script>
function showMoreRows(event, count) {
	event.preventDefault();

	// Show hidden rows
	const rows = document.querySelectorAll('.extra-log-row');
	rows.forEach(row => row.style.display = 'table-row');

	// Hide the show-more link row
	event.target.closest('tr').style.display = 'none';
}
</script>
<!-- This file should primarily consist of HTML with a bit of PHP. -->
<div class="securitytxt-header">
    <div class="securitytxt-title-section">
        <h1><?php echo esc_html__('Security.txt Status', 'generate-security-txt'); ?></h1>
    </div>

    <div id="securitytxtStatus" class="securitytxt-title-section securitytxt-status-wrapper">
        <div class="securitytxt-status">
            <img src="<?php echo esc_url(trailingslashit(includes_url())) . 'images/spinner.gif'; ?>">
        </div>
        <div class="securitytxt-status-label"><?php echo esc_html__( 'Checking security.txt status', 'generate-security-txt'); ?></div>
    </div>

    <nav class="securitytxt-tabs-wrapper hide-if-no-js tab-count-1" aria-label="Secondary menu">
        <a href="<?php echo esc_url(admin_url('tools.php?page=security_txt_generator')); ?>" class="securitytxt-tab active">Generate</a>
        <a style="display: none;" href="<?php echo esc_url(admin_url('tools.php?page=security_txt_generator&tab=info')); ?>" class="securitytxt-tab ">Info</a>
        <a style="display: none;" href="<?php echo esc_url(admin_url('tools.php?page=security_txt_generator&tab=debug')); ?>" class="securitytxt-tab ">Debug</a>
    </nav>
</div>

<hr class="wp-header-end">

<div class="securitytxt-body securitytxt-status-tab hide-if-no-js">

    <form method="post" action="<?php echo esc_attr(menu_page_url('security_txt_generator', false)); ?>" class="securitytxt-form-wrapper" id="securitytxt-form-main">

        <?php wp_nonce_field( 'securitytxt_nonce' ); ?>

        <h2 class="securitytxt-form-title"><?php echo esc_html__( 'Generate security.txt', 'generate-security-txt'); ?></h2>

        <p><?php echo esc_html__('On this page you can easily generate a security.txt file. This makes it easier for security researchers to contact you when they find a vulnerability on your site. This plugin puts all the information in the right place!', 'generate-security-txt'); ?></p>
        <p><?php echo esc_html__('Below are several fields where you can enter (contact) information to be included in the security.txt file. Some fields are required, and some are optional.', 'generate-security-txt'); ?></p>

        <div id="securitytxt-site-status-critical" class="securitytxt-form">

            <?php $SecurityTxtAdmin->create_form_html($form_fields); ?>

            <div class="securitytxt-submit-wrapper px-15em">
                <div style="display: none;" id="securitytxtNoticeValidationErrors" class="securitytxt-notify notify-error">
                    <p><?php echo esc_html__( 'There are validation errors, fix the fields with a red border in a the form above.', 'generate-security-txt'); ?></p>
                </div>
                <?php if(is_ssl()) : ?>
                    <button id="securityTxtFormSubmit" type="submit" class="securitytxt-ajax-submit securitytxt-submit-button button button-primary" data-text="<?php echo esc_attr__('Save changes and generate security.txt', 'generate-security-txt'); ?>" data-working="<?php echo esc_attr__('Working.. Don\'t refresh the page', 'generate-security-txt'); ?>">
                        <?php echo esc_html__('Save changes and generate security.txt', 'generate-security-txt'); ?>
                    </button>
                <?php else : ?>
                    <div class="securitytxt-notify notify-error">
                        <p><?php echo esc_html__('This website isn\'t using HTTPS. This is a requirement for any value in security.txt containing a web URI. Resolve this before you generate a security.txt file.', 'generate-security-txt'); ?></p>
                    </div>
                    <button class="disabled securitytxt-submit-button button button-primary" data-text="<?php echo esc_attr__('Save changes and generate security.txt', 'generate-security-txt'); ?>" data-working="<?php echo esc_attr__('Working.. Don\'t refresh the page', 'generate-security-txt'); ?>">
                        <?php echo esc_html__('Save changes and generate security.txt', 'generate-security-txt'); ?>
                    </button>
                <?php endif; ?>
            </div>

            <div class="securitytxt-submit-wrapper px-15em">
                <a href="<?php echo esc_url($SecurityTxtAdmin->get_deletedata_url()); ?>" type="submit" class="securitytxt-submit-button button button-"><i class="dashicons dashicons-trash"></i> <?php echo esc_html__('Reset plugin settings', 'generate-security-txt'); ?></a>
            </div>
        </div>
    </form>

    <div id="securityTxtWorkingContainer" style="display: none;" class="securitytxt-container securitytxt-submit-wrapper">
        <h3 class="securitytxt-form-title"><?php echo esc_html__('Working.. Don\'t refresh the page', 'generate-security-txt'); ?></h3>
        <div id="securityTxtWorking" class="securitytxt-code">
            <ul class="securitytxt-actionlist">
                <li class="securitytxt-actionlist-item">
                    <div class="dashicons dashicons-smiley"></div>
                    <?php echo esc_html__('Not working yet..', 'generate-security-txt'); ?>
                </li>
            </ul>
        </div>
        <div id="securityBtnContainerPrivateKey" style="display: none;" class="securitytxt-privatekey-container">
            <button id="securityBtnPrivateKey" class="securitytxt-submit-button button button-show-pkey"><i class="dashicons dashicons-visibility"></i> <?php echo esc_html__('Show private key, save it locally in a secure location', 'generate-security-txt'); ?></button>
            <small><?php echo esc_html__( 'This key is not stored anywhere by this plugin, when you leave this page it is lost.', 'generate-security-txt'); ?> </small>
        </div>
        <div id="securityContainerPrivateKey" style="display: none;" class="">
            <textarea id="securityTxtPrivateKey" class="securitytxt-key" rows="15" readonly></textarea>
            <small><?php echo esc_html__( 'This key was used to sign the security.txt file. Save this key locally, you may need this to communicate securely with reporting security researchers. You can find your public key by clicking the \'View public keyfile\'-button below.', 'generate-security-txt'); ?> </small>
        </div>
    </div>

    <div class="securitytxt-container">
        <a id="securityTxtFileButton" href="<?php echo esc_url($SecurityTxtAdmin->get_securitytxt_url()); ?>" target="_blank" class="securitytxt-topbutton float-right button <?php echo esc_attr(!$SecurityTxtAdmin->check_securitytxt() ? 'disabled' : ''); ?>"><?php echo esc_html__( 'View security.txt', 'generate-security-txt'); ?> <i class="dashicons dashicons-external"></i></a>
        <a id="securityTxtPubkeyButton" href="<?php echo esc_url($SecurityTxtAdmin->get_pubkey_url()); ?>" target="_blank" class="securitytxt-topbutton float-right button <?php echo esc_attr(!$SecurityTxtAdmin->check_pubkey() ? 'disabled' : ''); ?>"><?php echo esc_html__( 'View public keyfile', 'generate-security-txt'); ?> <i class="dashicons dashicons-external"></i></a>

        <h3 class="securitytxt-form-title">
            <?php echo esc_html__( 'Security.txt file contents', 'generate-security-txt'); ?>
        </h3>

        <div class="">
            <textarea id="securityTxtContents" class="securitytxt-contents securitytxt-transition" rows="15" readonly>
<?php echo esc_textarea($SecurityTxtAdmin->get_securitytxt_file_contents()); ?>
            </textarea>
        </div>

        <div class="securitytxt-submit-wrapper">
            <a id="securityTxtExternalCheck" href="<?php echo esc_url($SecurityTxtAdmin->get_internetnl_testurl()); ?>" target="_blank" class="securitytxt-submit-button button button-primary <?php echo !$SecurityTxtAdmin->check_securitytxt() ? 'disabled' : ''; ?>"><?php echo esc_html__('Verify security.txt externally', 'generate-security-txt'); ?> <i class="dashicons dashicons-external"></i></a>
            <small><?php echo esc_html__( 'This button opens a new tab. Please note that internet.nl doesn’t check subroot WordPress installs like example.com/wordpress.', 'generate-security-txt'); ?> </small>
        </div>
    </div>

    <h3 class="securitytxt-section-title">
        <?php echo esc_html__( 'Debug information and status', 'generate-security-txt'); ?>
    </h3>

    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
        <tr>
            <th scope="col">
                <?php echo esc_html__( 'Subject', 'generate-security-txt'); ?>
            </th>
            <th scope="col">
                <?php echo esc_html__( 'Status / Version', 'generate-security-txt'); ?>
            </th>
        </tr>
        </thead>

        <tbody>
        <tr>
            <td>
                WordPress version
            </td>
            <td>
                <?php echo esc_html(get_bloginfo('version')); ?>
            </td>
        </tr>
        <tr>
            <td>
                Plugin version
            </td>
            <td>
                <?php echo esc_html($SecurityTxtAdmin->get_plugin_version()); ?>
            </td>
        </tr>
        <tr>
            <td>
                HTTPS
            </td>
            <td>
                <div class="dashicons dashicons-<?php echo esc_attr(is_ssl() ? 'yes' : 'no'); ?>"></div> <?php echo esc_html(is_ssl() ? 'Yes' : 'No'); ?>
            </td>
        </tr>
        <tr>
            <td>
                PHP version
            </td>
            <td>
                <?php echo esc_html(phpversion()); ?>
            </td>
        </tr>
        <tr>
            <td>
                PHP-extension 'gnupg'
            </td>
            <td>
                <div class="dashicons dashicons-<?php echo esc_attr($SecurityTxtAdmin->is_gnupg_available() ? 'yes' : 'no'); ?>"></div> <?php echo esc_html($SecurityTxtAdmin->is_gnupg_available() ? 'Yes' : 'No'); ?>
            </td>
        </tr>
        <tr>
            <td>
                Last expiry reminder sent
            </td>
            <td>
                <?php echo esc_html($SecurityTxtAdmin->get_datetime_last_expiry_reminder()); ?>
            </td>
        </tr>
        </tbody>
        <tfoot>
        <tr>
            <th scope="col">
                <?php echo esc_html__( 'Subject', 'generate-security-txt'); ?>
            </th>
            <th scope="col">
                <?php echo esc_html__( 'Status / Version', 'generate-security-txt'); ?>
            </th>
        </tr>
        </tfoot>

    </table>
    <small class="table-footer"><?php echo esc_html__( 'When communicating issues with this plugin, please always include a screenshot of this table.', 'generate-security-txt'); ?></small>


    <h3 class="securitytxt-section-title"><?php echo esc_html__('More information about security.txt', 'generate-security-txt'); ?></h3>
    <p><?php echo esc_html__('When vulnerabilities are discovered on a website, by independent security researchers, they often do not have the correct contact information to disclose them. Security.txt is an open standard that helps organizations and security researchers find each other more easily, exchange the right information and thereby resolve a discovered vulnerability quickly.', 'generate-security-txt'); ?></p>

    <h3 class="securitytxt-section-title">
        <?php echo esc_html__( 'Log', 'generate-security-txt'); ?>
    </h3>

    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
        <tr>
            <th scope="col" style="width: 180px;">
                <?php echo esc_html__( 'Timestamp', 'generate-security-txt'); ?>
            </th>
            <th scope="col">
                <?php echo esc_html__( 'Log entry', 'generate-security-txt'); ?>
            </th>
        </tr>
        </thead>

        <tbody>
        <?php
            $x = 10;
            $row_count = 0;
        ?>
	    <?php $log_entries = $SecurityTxtAdmin->log_get_entries(); ?>
	    <?php $log_count = is_array($log_entries) ? count($log_entries) : 0; ?>
	    <?php if ( is_array( $log_entries ) && count( $log_entries ) > 0 ) : ?>
		    <?php foreach ( $log_entries as $log_entry ) : ?>
                <tr class="<?php echo ($row_count > 5) ? 'extra-log-row' : ''; ?>">
                    <td><?php echo $log_entry['time']; ?></td>
                    <td><?php echo $log_entry['message']; ?></td>
                </tr>

                <?php if ( $row_count == 5 ) : ?>
                <tr class="show-more-row">
                    <td colspan="2" style="text-align: center;">
                        <a href="#" onclick="showMoreRows(event, <?php echo $x; ?>)">
                            <?php echo sprintf( esc_html__( 'Show %d more log entries', 'generate-security-txt' ), $log_count - 5 ); ?>
                        </a>
                    </td>
                </tr>
                <?php endif; ?>
                <?php $row_count++; ?>
            <?php
                endforeach;
            else :
            ?>
                <tr>
                    <td colspan="2"><?php echo esc_html__( 'There are no log entries.', 'generate-security-txt' ); ?></td>
                </tr>
            <?php endif; ?>
            </tbody>

        <tfoot>
        <tr>
            <th scope="col">
                <?php echo esc_html__( 'Timestamp', 'generate-security-txt'); ?>
            </th>
            <th scope="col">
                <?php echo esc_html__( 'Log entry', 'generate-security-txt'); ?>
            </th>
        </tr>
        </tfoot>

    </table>
    <small class="table-footer"><?php echo esc_html__( 'This table contains a log of the last 50 actions made by the plugin.', 'generate-security-txt'); ?></small>

</div>

<div class="securitytxt-footer">
    <small class="securitytxt-footer-text"><?php echo esc_html__('This plugin is sponsored by SIDN fund and an initiative of', 'generate-security-txt'); ?></small>
    <a href="https://www.verenigingvanregistrars.nl/" target="_blank">
        <img class="securitytxt-footer-img" src="<?php echo esc_url($SecurityTxtAdmin->get_admin_assets_url() . '/img/logo-vvr.svg'); ?>">
    </a>
</div>
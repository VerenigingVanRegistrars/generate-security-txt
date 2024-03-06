<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://geusmedia.nl/
 * @since      1.0.0
 *
 * @package    Generate_Security_Txt
 * @subpackage Generate_Security_Txt/admin/partials
 */

$SecurityTxtAdmin = new Generate_Security_Txt_Admin();
$Encryption_Securitytxt = new Encryption_Securitytxt();
$form_fields = $SecurityTxtAdmin->admin_security_text_generator_fields();

$SecurityTxtAdmin->process_form_submit($_POST);
?>
<script>
    var securitytxt_spinner_url = '<?php echo trailingslashit(includes_url()) . 'images/spinner.gif'; ?>';
    var securitytxt_status_text = '<?php echo __( "Checking security.txt status", Generate_Security_Txt_i18n::TEXT_DOMAIN); ?>';
</script>
<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="securitytxt-header">
    <div class="securitytxt-title-section">
        <h1><?= __('Security.txt Status', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?></h1>
    </div>

    <div id="securitytxtStatus" class="securitytxt-title-section securitytxt-status-wrapper">
        <div class="securitytxt-status">
            <img src="<?= trailingslashit(includes_url()) . 'images/spinner.gif'; ?>">
        </div>
        <div class="securitytxt-status-label"><?= __( 'Checking security.txt status', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?></div>
    </div>

    <nav class="securitytxt-tabs-wrapper hide-if-no-js tab-count-1" aria-label="Secondary menu">
        <a href="<?= admin_url('tools.php?page=security_txt_generator'); ?>" class="securitytxt-tab active">Generate</a>
        <a style="display: none;" href="<?= admin_url('tools.php?page=security_txt_generator&tab=info'); ?>" class="securitytxt-tab ">Info</a>
        <a style="display: none;" href="<?= admin_url('tools.php?page=security_txt_generator&tab=debug'); ?>" class="securitytxt-tab ">Debug</a>
    </nav>
</div>

<hr class="wp-header-end">

<div class="securitytxt-body securitytxt-status-tab hide-if-no-js">

    <form method="post" action="<?= menu_page_url('security_txt_generator', false); ?>" class="securitytxt-form-wrapper" id="securitytxt-form-main">
        <h2 class="securitytxt-form-title"><?= __( 'Generate security.txt', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?></h2>

        <p><?= __('On this page you can easily generate a security.txt file. This makes it easier for security researchers to contact you when they find a vulnerability on your site. This plugin puts all the information in the right place!', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?></p>
        <p><?= __('Below are several fields where you can enter (contact) information to be included in the security.txt file. Some fields are required, and some are optional.', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?></p>

        <div id="securitytxt-site-status-critical" class="securitytxt-form">

            <?php $SecurityTxtAdmin->create_form_html($form_fields); ?>

            <div class="securitytxt-submit-wrapper px-15em">
                <div style="display: none;" id="securitytxtNoticeValidationErrors" class="securitytxt-notify notify-error">
                    <p><?= __( 'There are validation errors, fix the fields with a red border in a the form above.', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?></p>
                </div>
                <button id="securityTxtFormSubmit" type="submit" class="securitytxt-ajax-submit securitytxt-submit-button button button-primary" data-text="<?= __('Save changes and generate security.txt', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?>" data-working="<?= __('Working.. Don\'t refresh the page', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?>">
                    <?= __('Save changes and generate security.txt', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?>
                </button>
            </div>

            <div class="securitytxt-submit-wrapper px-15em">
                <a href="<?= $SecurityTxtAdmin->get_deletedata_url(); ?>" type="submit" class="securitytxt-submit-button button button-"><i class="dashicons dashicons-trash"></i> <?= __('Delete all data, files and keys', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?></a>
            </div>
        </div>
    </form>

    <div id="securityTxtWorkingContainer" style="display: none;" class="securitytxt-container securitytxt-submit-wrapper">
        <h3 class="securitytxt-form-title"><?= __('Working.. Don\'t refresh the page', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?></h3>
        <div id="securityTxtWorking" class="securitytxt-code">
            <ul class="securitytxt-actionlist">
                <li class="securitytxt-actionlist-item">
                    <div class="dashicons dashicons-smiley"></div>
                    <?= __('Not working yet..', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?>
                </li>
            </ul>
        </div>
        <div id="securityBtnContainerPrivateKey" style="display: none;" class="securitytxt-privatekey-container">
            <button id="securityBtnPrivateKey" class="securitytxt-submit-button button button-show-pkey"><i class="dashicons dashicons-visibility"></i> <?= __('Show private key, save it locally in a secure location', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?></button>
            <small><?= __( 'This key is not stored anywhere by this plugin, when you leave this page it is lost.', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?> </small>
        </div>
        <div id="securityContainerPrivateKey" style="display: none;" class="">
            <textarea id="securityTxtPrivateKey" class="securitytxt-key" rows="15" readonly></textarea>
            <small><?= __( 'This key was used to sign the security.txt file. Save this key locally, you may need this to communicate securely with reporting security researchers. You can find your public key by clicking the \'View public keyfile\'-button below.', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?> </small>
        </div>
    </div>

    <div class="securitytxt-container">
        <a id="securityTxtFileButton" href="<?= $SecurityTxtAdmin->get_securitytxt_url(); ?>" target="_blank" class="securitytxt-topbutton float-right button <?= !$SecurityTxtAdmin->check_securitytxt() ? 'disabled' : ''; ?>"><?= __( 'View security.txt', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?> <i class="dashicons dashicons-external"></i></a>
        <a id="securityTxtPubkeyButton" href="<?= $SecurityTxtAdmin->get_pubkey_url(); ?>" target="_blank" class="securitytxt-topbutton float-right button <?= !$SecurityTxtAdmin->check_pubkey() ? 'disabled' : ''; ?>"><?= __( 'View public keyfile', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?> <i class="dashicons dashicons-external"></i></a>

        <h3 class="securitytxt-form-title">
            <?= __( 'Security.txt file contents', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?>
        </h3>

        <div class="">
            <textarea id="securityTxtContents" class="securitytxt-contents securitytxt-transition" rows="15" readonly>
<?= htmlspecialchars($SecurityTxtAdmin->get_securitytxt_file_contents()); ?>
            </textarea>
        </div>

        <div class="securitytxt-submit-wrapper">
            <a id="securityTxtExternalCheck" href="<?= $SecurityTxtAdmin->get_internetnl_testurl(); ?>" target="_blank" class="securitytxt-submit-button button button-primary <?= !$SecurityTxtAdmin->check_securitytxt() ? 'disabled' : ''; ?>"><?= __('Verify security.txt externally', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?> <i class="dashicons dashicons-external"></i></a>
            <small><?= __( 'This button opens a new tab. Please note that internet.nl doesn’t check subroot WordPress installs like example.com/wordpress.', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?> </small>
        </div>
    </div>

    <h3 class="securitytxt-section-title">
        <?= __( 'Debug information and status', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?>
    </h3>

    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
        <tr>
            <th scope="col">
                <?= __( 'Subject', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?>
            </th>
            <th scope="col">
                <?= __( 'Status / Version', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?>
            </th>
        </tr>
        </thead>

        <tbody>
        <tr>
            <td>
                WordPress version
            </td>
            <td>
                <?= get_bloginfo('version'); ?>
            </td>
        </tr>
        <tr>
            <td>
                Plugin version
            </td>
            <td>
                <?= $SecurityTxtAdmin->get_plugin_version(); ?>
            </td>
        </tr>
        <tr>
            <td>
                HTTPS
            </td>
            <td>
                <div class="dashicons dashicons-<?= is_ssl() ? 'yes' : 'no'; ?>"></div> <?= is_ssl() ? 'Yes' : 'No'; ?>
            </td>
        </tr>
        <tr>
            <td>
                PHP version
            </td>
            <td>
                <?= phpversion(); ?>
            </td>
        </tr>
        <tr>
            <td>
                PHP-extension 'gnupg'
            </td>
            <td>
                <div class="dashicons dashicons-<?= $SecurityTxtAdmin->is_gnupg_available() ? 'yes' : 'no'; ?>"></div> <?= $SecurityTxtAdmin->is_gnupg_available() ? 'Yes' : 'No'; ?>
            </td>
        </tr>
        </tbody>
        <tfoot>
        <tr>
            <th scope="col">
                <?= __( 'Subject', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?>
            </th>
            <th scope="col">
                <?= __( 'Status / Version', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?>
            </th>
        </tr>
        </tfoot>

    </table>
    <small class="table-footer"><?= __( 'When communicating issues with this plugin, please always include a screenshot of this table.', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?></small>

    <h3 class="securitytxt-section-title"><?= __('More information about security.txt', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?></h3>
    <p><?= __('When vulnerabilities are discovered on a website, by independent security researchers, they often do not have the correct contact information to disclose them. Security.txt is an open standard that helps organizations and security researchers find each other more easily, exchange the right information and thereby resolve a discovered vulnerability quickly.', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?></p>

</div>

<div class="securitytxt-footer">
    <small class="securitytxt-footer-text"><?= __('This plugin is sponsored by SIDN fund and an initiative of', Generate_Security_Txt_i18n::TEXT_DOMAIN); ?></small>
    <a href="https://www.verenigingvanregistrars.nl/" target="_blank">
        <img class="securitytxt-footer-img" src="<?= $SecurityTxtAdmin->get_admin_assets_url() . '/img/logo-vvr.svg'; ?>">
    </a>
</div>
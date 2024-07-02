(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
    $( window ).load(function() {
        enable_add_field_buttons();
        enable_remove_buttons();
        enable_regex_validation();
        enable_datetime_picker();
        enable_ajax_submit();
        enable_show_privatekey();
        enable_show_advanced();
        enable_add_language();
        enable_status_loader();
    });


    /**
     * Enable the ajax submit for the main form
     */
    function enable_ajax_submit() {

        $('.securitytxt-ajax-submit').on('click', function (e) {
            // Prevent the default form submission
            e.preventDefault();

            // Iterate over all input fields in the form
            $('#securitytxt-form-main input').each(function () {
                var inputId = $(this).attr('id');
                validateInput(inputId);
            });

            // Check if any input fields have the class 'invalid'
            if ($('#securitytxt-form-main input.invalid').length > 0) {
                // Display your error message or perform other actions
                console.log('Error: One or more fields are invalid.');
                $('#securitytxtNoticeValidationErrors').show();
            } else {
                // All fields are valid, proceed with your logic
                console.log('All fields are valid.');

                var $loadingSrc = securitytxt.homeurl + 'wp-includes/images/spinner.gif';
                var $loadingImg = '<img class="securitytxt-button-loader" src="' + $loadingSrc + '"> ';

                $(this).addClass('disabled');
                $(this).attr('disabled', true);
                $(this).html($loadingImg + $(this).attr('data-working'));

                // Scroll down 300 pixels below the button
                var currentScrollPosition = $(window).scrollTop();
                // var scrollTarget = $('#securityTxtFormSubmit').offset().top + 300;
                $('html, body').animate({
                    scrollTop: currentScrollPosition + 300
                }, 250); // You can adjust the duration as needed

                $('#securityTxtWorking ul.securitytxt-actionlist').empty();
                $('#securityTxtPrivateKey').empty();
                $('#securityBtnContainerPrivateKey').hide();
                $('#securityContainerPrivateKey').hide();

                $('#securityTxtWorkingContainer').show();

                var form_data = $('#securitytxt-form-main').serialize() || '';

                // First ajax action
                ajax_action_call(securitytxt.first_action, securitytxt.first_action_text, form_data);
            }
        });
    }


    /**
     * Enable the show advanced button
     */
    function enable_show_advanced() {
        $('#securitytxtShowAdvanced').on('click', function (e) {
            // Prevent the default
            e.preventDefault();
            $('#securitytxtAdvanced').toggle();
            $(this).find('.dashicons').toggleClass('dashicons-plus dashicons-minus');
        });
    }


    /**
     * Enable show private key field
     */
    function enable_show_privatekey() {
        $('#securityBtnPrivateKey').on('click', function (e) {
            // Prevent the default
            e.preventDefault();
            $('#securityContainerPrivateKey').show();
        });
    }

    /**
     * Enable the ajax submit for the main form
     */
    function enable_add_language() {

        $("#securitytxtAddLang").change(function(){
            // Get the selected value
            var selectedLang = $(this).val();
            if (!selectedLang) {
                selectedLang = $(this).find('option:selected').attr('lang');
            }

            // Get the current value of preferred_languages_0
            var currentLanguages = $("#preferred_languages_0").val();

            // If there is a value, split it into an array
            var currentLanguagesArray = currentLanguages ? currentLanguages.split(/,\s*|\s*,/) : [];

            // Check if the selected language is not already in the array
            if (currentLanguagesArray.indexOf(selectedLang) === -1) {

                // Add the selected language to the array
                currentLanguagesArray.push(selectedLang);

                // Update the value of preferred_languages_0 with the new array
                var joinedLanguagesArray = currentLanguagesArray.join(', ');
                $("#preferred_languages_0").attr('value', joinedLanguagesArray).val(joinedLanguagesArray);
            }
        });
    }


    /**
     * Start the next ajax call
     *
     * @param next_action
     * @param start_text
     * @param form_data
     */
    function ajax_action_call(next_action,  start_text, form_data = null) {

        var $action_list_html = $('#securityTxtWorking ul.securitytxt-actionlist');
        var $action_item = $('<li>', {
            'id' : next_action,
            'class': 'securitytxt-actionlist-item',     // Add a class
            'data-action': next_action                 // Add a data attribute
        });

        var $loadingSrc = securitytxt.homeurl + 'wp-includes/images/spinner.gif';
        var $loadingImg = $('<img>', {
            'src' : $loadingSrc
        });

        $action_item.html($loadingImg.prop('outerHTML') + " " + start_text);
        $action_list_html.append($action_item);

        if(form_data === null) {
            form_data = $(this).serialize() || '';
        }

        var data = {
            action: 'process_actionlist', // Replace with your actual AJAX action
            next_action: next_action,
            form_data: form_data
        };

        $.ajax({
            type: 'POST',
            url: securitytxt.ajaxurl, // WordPress AJAX URL
            data: data,
            success: function (response) {
                // Handle the AJAX response here
                console.log(response);
                
                if(response.finished_action && response.finished_text) {
                    console.log('finished action');

                    var $finishedActionItem = $('#' + response.finished_action);
                    var $checkmark = $('<div>', {
                        'class': 'dashicons dashicons-yes'
                    });
                    $finishedActionItem.html($checkmark.prop('outerHTML') + " " + response.finished_text);
                }

                if(response.continue && response.next_action && response.next_start_text) {
                    ajax_action_call(response.next_action,  response.next_start_text);
                }

                if(response.response_data) {
                    var response_data = response.response_data;
                    $('#securityBtnPrivateKey').show();

                    $('#securityContainerPrivateKey');
                    $('#securityTxtPrivateKey').html(response_data.private_key);
                }

                if(!response.continue) {
                    var $submitBtn = $('#securityTxtFormSubmit');
                    $submitBtn.removeClass('disabled');
                    $submitBtn.attr('disabled', false);
                    $submitBtn.empty().html($submitBtn.attr('data-text'));

                    ajax_post_finish();
                }
            },
            error: function (error) {
                // Handle AJAX errors here
                console.error(error);
            }
        });
    }


    /**
     * Start the status loader ajax call
     */
    function enable_status_loader() {
        // We do the ajax status once on load
        ajax_status_loader();
    }


    /**
     * Ajax call for loader
     */
    function ajax_status_loader() {

        var $statusDiv = $('<div>', {
            'class': 'securitytxt-status'
        });

        // Create the img element
        var $img = $('<img>', {
            'src': securitytxt_spinner_url
        });

        // Append the img to the div
        $statusDiv.append($img);

        // Create the div with class 'securitytxt-status-label'
        var $labelDiv = $('<div>', {
            'class': 'securitytxt-status-label',
            'text': securitytxt_status_text
        });

        $('#securitytxtStatus').empty()
        $('#securitytxtStatus').append($statusDiv);
        $('#securitytxtStatus').append($labelDiv);

        var data = {
            action: 'status_loader',
        };

        $.ajax({
            type: 'POST',
            url: securitytxt.ajaxurl, // WordPress AJAX URL
            data: data,
            success: function (response) {
                // Handle the AJAX response here
                if(response.html) {
                    $('#securitytxtStatus').html(response.html);
                    var $refresh = $('<i>', {
                        'id': 'securitytxtStatusRefresh',
                        'class': 'dashicons dashicons-update securitytxt-refresh'     // Add a class
                    });
                    $refresh.on('click', function () {
                        ajax_status_loader();
                        $refresh.remove(); // Corrected to remove instead of delete
                    });
                    $('#securitytxtStatus').append($refresh);
                }
            },
            error: function (error) {
                // Handle AJAX errors here
                console.error(error);
            }
        });
    }



    /**
     * Start the next ajax call
     */
    function ajax_post_finish() {

        var data = {
            action: 'post_finish',
        };

        $.ajax({
            type: 'POST',
            url: securitytxt.ajaxurl, // WordPress AJAX URL
            data: data,
            success: function (response) {
                // Handle the AJAX response here
                if ($('#securityTxtPrivateKey').val().trim() !== '') {
                    // Show the container if the textarea is not empty
                    $('#securityBtnContainerPrivateKey').show();
                }

                if(response.content) {
                    var $securityTxtContent = $('#securityTxtContents');
                    $securityTxtContent.removeClass('disabled');
                    $securityTxtContent.attr('disabled', false);
                    $securityTxtContent.empty().html(response.content);
                    flash_background($securityTxtContent);
                }

                if(response.securitytxt) {
                    var $securityTxtFileButton = $('#securityTxtFileButton');
                    $securityTxtFileButton.removeClass('disabled');
                    $securityTxtFileButton.attr('disabled', false);
                    $('#securitytxtNoticeNoTxt').hide();
                    flash_background($securityTxtFileButton);


                    var $securityTxtExternalCheck = $('#securityTxtExternalCheck');
                    $securityTxtExternalCheck.removeClass('disabled');
                    $securityTxtExternalCheck.attr('disabled', false);
                }

                if(response.pubkey) {
                    var $securityTxtPubkeyButton = $('#securityTxtPubkeyButton');
                    $securityTxtPubkeyButton.removeClass('disabled');
                    $securityTxtPubkeyButton.attr('disabled', false);
                    flash_background($securityTxtPubkeyButton);
                }

                $('#securitytxtStatusRefresh').trigger('click');
            },
            error: function (error) {
                // Handle AJAX errors here
                console.error(error);
            }
        });
    }


    /**
     * Flash the background of an element shortly
     *
     * @param element
     */
    function flash_background(element) {
        // Store the original background color
        var originalColor = element.css('background-color');

        // Set the background color to orange
        element.css('background-color', '#F9861C');

        // After 250 milliseconds (0.25 seconds), revert to the original color
        setTimeout(function () {
            element.css('background-color', originalColor);
        }, 250);
    }


    /**
     * Enable the datetime picker field jquery ui element
     */
    function enable_datetime_picker() {
        // Initialize the datepicker and timepicker
        $('input[name="expires[]"]').datepicker({
            dateFormat: 'yy-mm-dd',
            minDate: '+1d',  // Tomorrow
            maxDate: '+364d'  // One year minus one day
        });
    }


    /**
     * Add functionality to the add another-buttons
     */
    function enable_add_field_buttons() {
        $('.securitytxt-addfield').each(function () {

            // Attach a click event handler to each element with the class 'securitytxt-addfield'
            $(this).on('click', function () {

                // Clone the field
                var clonedContainer = $(this).closest('.securitytxt-form-field').find('.securitytxt-form-input:last-of-type').clone();
                clonedContainer.removeClass('validated');
                var clonedInput = clonedContainer.find('input');
                clonedInput.val(''); // Clear the value of the cloned input field
                clonedInput.attr('data-count', clonedInput.data('count') + 1);
                var cleanedName = clonedInput.attr('name').replace(/\[\]$/, ''); // Remove '[]' at the end, if present
                clonedInput.attr('id', cleanedName + '_' + (clonedInput.data('count') + 1));
                clonedInput.removeClass('waiting').removeClass('invalid').removeClass('valid');
                clonedContainer.find('.securitytxt-remove').css('display', 'block');
                $(this).before(clonedContainer);

                // Re-do the validation
                enable_remove_buttons();
                enable_regex_validation();
            });
        });
    }


    /**
     * Enable regex validation on fields that have it
     */
    function enable_remove_buttons() {
         // Remove existing event handlers to prevent multiple bindings
        $('.securitytxt-form-field .securitytxt-remove').off('click');

        // Live validation for the fields
        $('.securitytxt-form-field .securitytxt-remove').on('click', function (event) {
            $(this).closest('.securitytxt-form-input').remove();
        });
    }


    /**
     * Enable regex validation on fields that have it
     */
    function enable_regex_validation() {
         // Remove existing event handlers to prevent multiple bindings
        $('.securitytxt-form-field .validate').off('focus blur input');

        // Live validation for the fields
        $('.securitytxt-form-field .validate').on('focus blur input', function (event) {
            if (event.type === 'focus') {
                $(this).addClass('waiting');
                validateInput($(this).attr('id'));
            } else if (event.type === 'blur') {
                $(this).removeClass('waiting');
                validateInput($(this).attr('id'));
            } else if (event.type === 'input') {
                validateInput($(this).attr('id'));
            }
        });
    }


    /**
     * Regex validator
     *
     * @param inputId
     * @returns {boolean}
     */
    function validateInput(inputId) {
        var field = $(`#${inputId}`);
        var input = field.val();

        // Create a regular expression with the pattern
        var regexPattern = field.data('regex');
        var regex = new RegExp(regexPattern);

        // Check if the input value matches the regex pattern
        if (regex.test(input)) {
            // console.log('Valid URL');
            field.removeClass('invalid').addClass('valid');
            field.closest('.securitytxt-form-input').addClass('validated');
            field.closest('.securitytxt-form-input').removeClass('invalidated');
        } else {
            // console.log('Invalid URL');
            field.removeClass('valid');

            if(field.hasClass('required')) {
                field.addClass('invalid');
                field.closest('.securitytxt-form-input').addClass('invalidated');
            }

            field.closest('.securitytxt-form-input').removeClass('validated');
        }
    }

})( jQuery );

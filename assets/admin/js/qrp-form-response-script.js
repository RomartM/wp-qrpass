jQuery(document).ready(function($) {
    var selector = $("#form-list");
    var form = $(".qrp-form-data");
    var loading = $(".qrp_form_view .loading");
    var success = $(".qrp_form_view .success");

    var display_response = $("#qrp_display_response");
    var response_fields = $(".response_fields");
    var response_display_fields = $("#qrp_display_fields");

    var dlg = $('#qrp-dialog');
    var dlg_close;

    display_response.on('change', function (instance) {
        if($(this).is(":checked")){
            response_fields.removeClass("hidden");
        }else {
            response_fields.addClass("hidden");
        }
    })

    selector.on('change', function (instance) {
        var cf_id = instance.currentTarget.selectedOptions[0].value;
        success.addClass('hidden');
        loading.removeClass('hidden');
        submitAction(cf_id, 'get_values', '', function (response) {

            if(response['status'] !== 'SUCCESS'){
                alert("Failed to fetch data. Please try again later.")
                return;
            }

            loading.addClass('hidden');
            success.removeClass('hidden');
            loadFieldValues(response['fields']);

            if(response['data'] !== 'empty'){
                loadData(response['data']);
            }else{
                form.trigger("reset");
            }
            display_response.trigger('change');
        })
    });

    $(".qrp-form-data-save").click(function () {
        dlg.dialog('open');
        $('.qrp_process_view').removeClass("hidden");
        $('.qrp_process_view .loading').removeClass("hidden");
        $('.qrp_process_view .loading h2').text("Saving...");
        submitAction(selector.val(), 'set_values', form.serialize(), function (response) {
            $('.qrp_process_view .loading').addClass("hidden");
            $('.qrp_process_view .loading h2').text("");
            $('.qrp_process_view .success').removeClass("hidden");
            $('.qrp_process_view .success h2').text(toTitleCase(response.status + " Saving"));
        })
    });

    function toTitleCase(str) {
        return str.replace(/(?:^|\s)\w/g, function(match) {
            return match.toUpperCase();
        });
    }

    function loadFieldValues(data) {
        var raw_data = JSON.parse(data);
        var html = "";
        $.each(raw_data, function(index, value){
            html += '<input type="checkbox" id="qrp_' + index + '" name="response_settings[display_fields][value][' + index + ']" data-id="' + index + '" value="'+ value.label +'">' +
                '<label for="qrp_' + index + '">' + value.label + '</label><br>';
        });
        response_display_fields.empty();
        response_display_fields.append(html);
    }

    function loadData(data) {
        var parse_data = JSON.parse(data).response_settings;
        $('input[name="response_settings[is_attach_qr]"]').prop('checked', parse_data.is_attach_qr === 'on');
        $('input[name="response_settings[is_display_qr]"]').prop('checked', parse_data.is_display_qr === 'on');
        $('input[name="response_settings[is_display_response]"]').prop('checked', parse_data.is_display_response === 'on');
        $('input[name="response_settings[is_display_time]"]').prop('checked', parse_data.is_display_time === 'on');
        $('input[name="response_settings[is_resend_response]"]').prop('checked', parse_data.is_resend_response === 'on');
        $('input[name="response_settings[is_duplicate_response]"]').prop('checked', parse_data.is_duplicate_response === 'on');
        $('textarea[name="response_settings[duplicate_response_message]"]').val(parse_data.duplicate_response_message);
    }

    function submitAction(cf_id, param, data, successCallback) {
        $.ajax({
            type: "post",
            dataType: "json",
            url: qrpAjax.ajaxurl,
            data: {action: qrpAjax.action, cf_id: cf_id, param: param, data: data, nonce: qrpAjax.nonce},
            success: function (response) {
                successCallback(response);
            }
        })
    }

    dlg.dialog({
        title: 'Action Manager',
        dialogClass: 'wp-dialog',
        autoOpen: false,
        draggable: true,
        width: 'auto',
        height: 'auto',
        modal: true,
        resizable: false,
        closeOnEscape: true,
        position: {
            my: "center",
            at: "center",
            of: window
        },
        open: function () {
            // close dialog by clicking the overlay behind it
            $('.ui-widget-overlay').bind('click', function () {
                $('#qrp-dialog').dialog('close');
                dlg_close.click();
            })
        },
        create: function () {
            dlg_close = $('.ui-dialog-titlebar-close');
            dlg_close.addClass('ui-button');
            dlg_close.click(function () {
                $('.qrp_process_view').addClass("hidden");
                $('.qrp_process_view .loading').addClass("hidden");
                $('.qrp_process_view .success').addClass("hidden");
            });
        },
    });

    selector.trigger('change');

})
jQuery(document).ready(function($){
    var selector = $("#form-list");
    var form_view = $(".qrp_form_view");
    var group_wrapper = $(".qrp_group_wrapper");
    var group_name_instance = $(".qrp_group_name");
    var process_dialog = $(".qrp_process_view");
    var fields, data = "";
    var group_iterator = 0;
    var iterator = [];
    var group_name = [];

    var dlg = $('#qrp-dialog');
    var dlg_close;

    window.qrp_form_filters = {};

    window.qrp_form_filters.selectedField = function (instance) {
        var data_index = instance.getAttribute('data-index');
        var group_instance = $(instance).parents('.qrp-group-condition');
        var group_id = Number(group_instance.attr('data-group-id'));
        var conditional_choice_wrapper = $("#qrp_group_id_" + group_id + " #qrp_" + data_index + " .qrp_condition_choices_wrapper");
        var html = getFieldOption(fields[instance.selectedOptions[0].value], group_id, data_index);
        conditional_choice_wrapper.empty();
        conditional_choice_wrapper.append(html);
    }

    function getFieldItemOptions(iterator) {
        var html = '';
        $.each(fields, function (index, value) {
            html += '<option value="' + value.ID + '">'+ value.label +'</option>';
        })
        return html;
    }

    function getEqualityOperatorsOptions(iterator) {
        var html = '';
        $.each(qrpAjax.equality_operators, function (index, value) {
            html += '<option value="' + value.value + '">'+ value.label +'</option>';
        })
        return html;
    }

    function getFieldOption(field_object, iterator ,sub_iterator) {
        var html = ''
        switch (field_object.type) {
            case 'email':
            case 'text':
            case 'phone_better':
                html = '<input type="text" id="qrp_' + iterator + '_' + sub_iterator+ '_' + field_object.ID + '_value" name="qrp_group_condition[' + iterator + '][condition][' + sub_iterator + '][value]" placeholder="Value" value="' + field_object.config.default + '"/>';
                break;
            case 'number':
                html = '<input type="number" id="qrp_' + iterator + '_' + sub_iterator+ '_' + field_object.ID + '_value" name="qrp_group_condition[' + iterator + '][condition][' + sub_iterator + '][value]" placeholder="Value" value="' + field_object.config.default + '"/>';
                break;
            case 'checkbox':
            case 'dropdown':
            case 'radio':
                var config_option_id = 'qrp_option_' + iterator + '_' + sub_iterator+ '_' + field_object.ID;
                $.each(field_object.config.option, function(index, value){
                    html += '<input type="checkbox" id="qrp_' + iterator + '_' +sub_iterator+ '_' + index + '" name="qrp_group_condition[' + iterator + '][condition][' + sub_iterator + '][value]['+ index +']" data-id="' + index + '" value="'+ value.label +'">' +
                        '<label for="qrp_' + iterator + '_' +sub_iterator+ '_' + index + '">' + value.label + '</label><br>';
                });
                html += '<div class="qrp_field_option">' +
                    ' <input type="radio" id="one_' + config_option_id + '" name="qrp_group_condition[' + iterator + '][condition][' + sub_iterator + '][value_config]" value="one">\n' +
                    ' <label for="one_' + config_option_id + '">Treat each item as one value</label><br>\n' +
                    ' <input type="radio" id="sum_' + config_option_id + '" name="qrp_group_condition[' + iterator + '][condition][' + sub_iterator + '][value_config]" value="sum" checked>\n' +
                    ' <label for="sum_' + config_option_id + '">Sum each item value</label><br>' +
                    '</div>';
                break;
        }
        return html;
    }

    function getActionButtons() {
        return '<div class="qrp_action_buttons">' +
            '<a href="javascript:void(0);" class="qrp_trigger_action button" data-action="add">Add Condition</a>' +
            '<a href="javascript:void(0);" class="qrp_trigger_action button" data-action="or">OR Condition</a>' +
            '<a href="javascript:void(0);" class="qrp_trigger_action button" data-action="and">AND Condition</a>' +
            '<a href="javascript:void(0);" class="qrp_trigger_action button" data-action="not">NOT Condition</a>' +
            '<a href="javascript:void(0);" class="qrp_trigger_action button button-link-delete" data-action="delete">Delete Condition</a>' +
            '</div>';
    }

    function addGroupCondition(iterator, group_name) {
        return '<div class="qrp-group-condition" id="qrp_group_id_' + iterator +'" data-group-id="'+iterator+'" data-group-label="' + group_name + '">' +
            '<input type="hidden" name="qrp_group_condition[' + iterator + '][cf_id]" value="' + selector.val() + '">' +
            '<input type="hidden" name="qrp_group_condition[' + iterator + '][title]" value="' + group_name + '">' +
            '<div class="group-name-label"><h4>' + group_name + '</h4></div>' +
            '<button class="qrp_trigger_action button" data-action="add">Add Condition</button>' +
            '<div class="qrp_conditional_wrapper"></div>' +
            '<div class="qrp_conditional_total">' +
            '<div class="group-total-label"><h4>Conditional Total Score</h4></div>' +
            '<select name="qrp_group_condition[' + iterator + '][total_equality_operator]">' + getEqualityOperatorsOptions(iterator) + '</select>\n' +
            '<label for="message_' + group_name + iterator + '">Total Score:</label>' +
            '<input type="number" name="qrp_group_condition[' + iterator + '][total_score_value]" id="total_score_' + group_name + iterator + '"/>' +
            '</div> ' +
            '<hr><div class="group-response-label"><h4>Response & Action Settings</h4></div>' +
            '<div class="group-response-wrapper">' +
            '<label for="approve_' + group_name + iterator + '">Approve Entry</label>' +
            '<input type="checkbox" name="qrp_group_condition[' + iterator + '][settings_config][is_approve]" id="approve_' + group_name + iterator + '"/><br/>' +
            '<label for="attach_' + group_name + iterator + '">Attach User Entry QR</label>' +
            '<input type="checkbox" name="qrp_group_condition[' + iterator + '][settings_config][is_attach_qr]" id="attach_' + group_name + iterator + '"/><br/>' +
            '<label for="message_' + group_name + iterator + '">Message</label>' +
            '<textarea type="text" name="qrp_group_condition[' + iterator + '][settings_config][message]" id="message_' + group_name + iterator + '"></textarea><br/>' +
            '<label for="notify_' + group_name + iterator + '">Notify Admin</label>' +
            '<input type="checkbox" name="qrp_group_condition[' + iterator + '][settings_config][is_notify]" id="notify_' + group_name + iterator + '"/><br/>' +
            '<label for="email_' + group_name + iterator + '">Email Address</label>' +
            '<input type="email" name="qrp_group_condition[' + iterator + '][settings_config][email]" id="email_' + group_name + iterator + '"/><br/>' +
            '</div> ' +
            '</div>';
    }

    function conditionalItem(iterator, sub_iterator, conditional_relation) {
        return '<div class="qrp-condition-item-wrapper" id="qrp_' + sub_iterator + '" data-relation="' + conditional_relation + '"><div class="qrp-condition-item">' +
            '<input type="hidden" name="qrp_group_condition[' + iterator + '][condition][' + sub_iterator + '][conditional_relation]" value="' + conditional_relation + '">' +
            '<select data-index="' + sub_iterator + '" name="qrp_group_condition[' + iterator + '][condition][' + sub_iterator + '][field_id]" onchange="window.qrp_form_filters.selectedField(this)">' + getFieldItemOptions(sub_iterator) + '</select>\n' +
            '<select data-index="' + sub_iterator + '" name="qrp_group_condition[' + iterator + '][condition][' + sub_iterator + '][equality_operator]">' + getEqualityOperatorsOptions(sub_iterator) + '</select>\n' +
            '<div class="qrp_condition_choices_wrapper"></div>' +
            '<input data-index="' + sub_iterator + '" type="number" name="qrp_group_condition[' + iterator + '][condition][' + sub_iterator + '][score_value]" placeholder="Score Value">' +
            getActionButtons() +
            '</div></div>';
    }

    function toTitleCase(str) {
        return str.replace(/(?:^|\s)\w/g, function(match) {
            return match.toUpperCase();
        });
    }

    selector.on('change', function () {
        loadFields(this);
    });

    $(form_view).on('click', '.qrp_trigger_action', function(eve){
        eve.preventDefault();

        var target = eve.currentTarget;
        var data_action = target.getAttribute("data-action");
        if(data_action === 'add-group'){
            var group_name_val = group_name_instance.val();
            if($.trim(group_name_val).length === 0){
                alert("Please enter a valid group name");
                return;
            }
            group_iterator++;
            createGroupCondition(group_iterator, group_name_val);
        }else{
            if(data_action === 'delete'){
                $(this).parents('div.qrp-condition-item').remove();
                return;
            }
            var group_instance = $(this).parents('.qrp-group-condition');
            var group_id = Number(group_instance.attr('data-group-id'));
            iterator[group_id]++;
            createItemCondition(group_instance, group_id, iterator[group_id], data_action);
        }
    });

    $(".qrp-form-data-save").click(function (eve) {
        dlg.dialog('open');
        $('.qrp_process_view').removeClass("hidden");
        $('.qrp_process_view .loading').removeClass("hidden");
        $('.qrp_process_view .loading h2').text("Saving...");
        submitAction(selector.val(), jQuery(".qrp-form-data").serialize(), function (response) {
            $('.qrp_process_view .loading').addClass("hidden");
            $('.qrp_process_view .loading h2').text("");
            $('.qrp_process_view .success').removeClass("hidden");
            $('.qrp_process_view .success h2').text(toTitleCase(response.status + " Saving"));
        })
    });

    function createGroupCondition(group_iterator, group_name_val){
        group_wrapper.prepend(addGroupCondition(group_iterator, group_name_val));
        iterator[group_iterator] = 0;
        group_name[group_iterator] = group_name_val;
        group_name_instance.val('');
    }

    function createItemCondition(group_instance, group_id, iterator, data_action){
        return group_instance.find('.qrp_conditional_wrapper').append(conditionalItem(group_id, iterator, data_action));
    }

    function loadData(data) {
        $.each(data.qrp_group_condition, function(index, value){
            createGroupCondition(index, value['title']);
            group_iterator = index;
            $('select[name="qrp_group_condition['+ index +'][total_equality_operator]"]').val(value['total_equality_operator']);
            $('input[name="qrp_group_condition['+ index +'][total_score_value]"]').val(value['total_score_value']);
            $('textarea[name="qrp_group_condition['+ index +'][settings_config][message]"]').val(value['settings_config']['message']);
            $('input[name="qrp_group_condition['+ index +'][settings_config][is_notify]"]').prop('checked', value['settings_config']['is_notify'] === 'on');
            $('input[name="qrp_group_condition['+ index +'][settings_config][is_approve]"]').prop('checked', value['settings_config']['is_approve'] === 'on');
            $('input[name="qrp_group_condition['+ index +'][settings_config][is_attach_qr]"]').prop('checked', value['settings_config']['is_attach_qr'] === 'on');
            $('input[name="qrp_group_condition['+ index +'][settings_config][email]"]').val(value['settings_config']['email']);
            $.each(value.condition, function (sub_index, sub_value) {
                iterator[group_iterator] = sub_index;
                createItemCondition($('#qrp_group_id_'+index), index, sub_index, sub_value['conditional_relation']).append(function () {
                    $('select[name="qrp_group_condition[' + index + '][condition][' + sub_index + '][field_id]"]').val(sub_value['field_id']).trigger('change');
                    $('select[name="qrp_group_condition[' + index + '][condition][' + sub_index + '][equality_operator]"]').val(sub_value['equality_operator']);
                    $('input[name="qrp_group_condition[' + index + '][condition][' + sub_index + '][score_value]"]').val(sub_value['score_value']);
                    if(typeof(sub_value['value']) === "object"){
                        $.each(sub_value['value'], function (option_index, option_value) {
                            $('#qrp_' + index + '_' + sub_index + '_' + option_index).prop('checked', true);
                        });
                    }else{
                        $('input[name="qrp_group_condition[' + index + '][condition][' + sub_index + '][value]"]').val(sub_value['value']);
                    }
                    $('#' + sub_value['value_config'] + '_qrp_option_' + sub_index + '_' + sub_value['field_id']).prop('checked', true);
                });
            });

        })
    }

    window.loadData = loadData;

    function loadFields(instance) {
        group_name_instance.val('');
        $(form_view.selector + " .loading").removeClass("hidden");
        $(form_view.selector + " .loading h2").text("Fetching Fields...");
        submitAction($(instance).val(), '', function (response) {
            $(form_view.selector + " .loading").addClass("hidden");
            $(form_view.selector + " .loading h2").text("");
            $(form_view.selector + " .success").removeClass("hidden");
            fields = JSON.parse(response.fields);
            data = JSON.parse(response.data);
            group_wrapper.empty();
            loadData(data);
        })
    }

    function submitAction(cf_id, param, successCallback){
        $.ajax({
            type : "post",
            dataType : "json",
            url : qrpAjax.ajaxurl,
            data : { action: qrpAjax.action, cf_id: cf_id, param : param, nonce: qrpAjax.nonce},
            success: function(response) {
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
            $('.ui-widget-overlay').bind('click', function(){
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

    loadFields(selector);
});
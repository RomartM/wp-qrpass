jQuery(document).ready(function($){

    var nonce_data;
    var dlg = $('#qrp-dialog');
    var dlg_title = $('#qrp-dialog .dialog-title');
    var dlg_close;

    String.prototype.capitalize = function() {
        return this.charAt(0).toUpperCase() + this.slice(1);
    }

    function onCloseDialog(btn){
        var data_action = btn.getAttribute('data-action');
        $('#qrp-dialog .'+ data_action +'').addClass("hidden");
        $('#qrp-dialog .'+ data_action +' .default').addClass("hidden");
        $('#qrp-dialog .'+ data_action +' .loading').addClass("hidden");
        $('#qrp-dialog .'+ data_action +' .success').addClass("hidden");
    }

    function submitAction(instance, action, id_number, param, successCallback){
        console.log("trigger")
        var nonce = instance.currentTarget.getAttribute('data-nonce');
        var cf_id = instance.currentTarget.getAttribute('data-cf-id');
        $.ajax({
            type : "post",
            dataType : "json",
            url : qrpAjax.ajaxurl,
            data : { action: action, id_number : id_number, param : param, nonce: nonce, cf_id: cf_id},
            success: function(response) {
                instance.currentTarget.setAttribute('data-nonce', response.nonce);
                successCallback(response);
            }
        })
    }

    function toggleRelative(eve, action, display){
        $.each(eve.currentTarget.parentNode.parentNode.children, function(index, value){
            if(value.className === action){
                value.style.display = display;
            }
        });
    }

    function updateStatus(eve, status){
        $.each(eve.currentTarget.parentElement.parentElement.parentElement.parentElement.children, function(index, value){
            if(value.className === "status column-status"){
                value.textContent = status;
            }
        })
    }

    function validateEmail(email_value) {
        return /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(email_value)
    }

    function validateRefID(link_value){
        return /R-\d+$/.test(link_value)
    }

    $(".qrp-action-trigger").click(function(eve){

        console.log(eve);
        eve.preventDefault();

        var identifier = eve.currentTarget.getAttribute('data-id');
        var data_action = eve.currentTarget.getAttribute('data-action');

        var generic_div = $('#qrp-dialog .'+ data_action +'');
        var default_div = $('#qrp-dialog .'+ data_action +' .default');
        var loading_div = $('#qrp-dialog .'+ data_action +' .loading');
        var success_div = $('#qrp-dialog .'+ data_action +' .success');

        $('.ui-dialog-titlebar-close').attr('data-action', eve.currentTarget.getAttribute('data-action'))

        switch (data_action) {
            case "qrp_view":
                dlg.dialog('open');
                dlg_title.text("User Profile");

                generic_div.removeClass("hidden");
                loading_div.removeClass("hidden");

                submitAction(eve, data_action, identifier, '', function (response) {
                    loading_div.addClass("hidden");
                    success_div.empty();

                    if(response.status !== 'SUCCESS'){
                        success_div.append("<h2>Failed to fetch Data</h2>");
                        success_div.removeClass("hidden");
                    }else{
                        success_div.append("<div class=\"form-fields\"></div>");

                        jQuery.each(response.fields, function(index, value){
                            $('#qrp-dialog .qrp_view .success .form-fields').append("<div class=\"field\">" +
                                "<span>" + value.label.capitalize() + "</span>:" +
                                "<span> " + value.value + "</span>" +
                                "</div>");
                        });

                        success_div.removeClass("hidden");
                    }
                });
                break;
            case "qrp_approve":
                dlg.dialog('open');
                dlg_title.text("");

                generic_div.removeClass("hidden");
                loading_div.removeClass("hidden");

                submitAction(eve, data_action, identifier, '', function (response) {
                    loading_div.addClass("hidden");
                    if(response.message.status === 'success'){
                        $(success_div.selector + " h2").text("QR Pass Approved");
                        toggleRelative(eve, "approve_pass", "none");
                        toggleRelative(eve, "revoke_pass", "block");
                        updateStatus(eve, "approve");
                    }else{
                        $(success_div.selector + " h2").text("Unable to approve QR Pass");
                    }
                    success_div.removeClass("hidden");
                });
                break;
            case "qrp_revoke":
                dlg.dialog('open');
                dlg_title.text("");

                generic_div.removeClass("hidden");
                loading_div.removeClass("hidden");

                submitAction(eve, data_action, identifier, '', function (response) {
                    loading_div.addClass("hidden");
                    if(response.message.status === 'success'){
                        $(success_div.selector + " h2").text("QR Pass Revoked");
                        toggleRelative(eve, "revoke_pass", "none");
                        toggleRelative(eve, "approve_pass", "block");
                        updateStatus(eve, "revoke");
                    }else{
                        $(success_div.selector + " h2").text("Unable to revoked QR Pass");
                    }
                    success_div.removeClass("hidden");
                });
                break;
            case "qrp_update_link":
                dlg.dialog('open');
                dlg_title.text("");

                generic_div.removeClass("hidden");
                loading_div.removeClass("hidden");
                $(loading_div.selector + " h2").text("Fetching current Reference ID...");

                submitAction(eve, data_action, identifier, '', function (response) {
                    var email = $("#qrp_reference_id");
                    loading_div.addClass("hidden");
                    if(response.message.status === 'success'){
                        default_div.removeClass("hidden");
                        email.val(response.message.content);
                    }else{
                        default_div.removeClass("hidden");
                    }
                });

                $(default_div.selector + " .update_link_action ").click(function(){
                    var link_value = $("#qrp_reference_id").val();
                    if(validateRefID(link_value)){
                        $(loading_div.selector + " h2").text("Updating Reference ID...");
                        loading_div.removeClass("hidden");
                        default_div.addClass("hidden");
                        submitAction(eve, data_action, identifier, link_value, function (response) {
                            loading_div.addClass("hidden");
                            if(response.message.status === 'success'){
                                success_div.removeClass("hidden");
                                $(success_div.selector + " h2").text("Reference ID Updated");
                            }else{
                                success_div.removeClass("hidden");
                                $(success_div.selector + " h2").text("Unable to Update Reference ID");
                            }
                            success_div.removeClass("hidden");
                            $(default_div.selector + " .update_link_action").off();
                        });
                    }else {
                        alert("Invalid Reference ID");
                    }
                });
                break;
            case "qrp_update_email":
                dlg.dialog('open');
                dlg_title.text("");

                generic_div.removeClass("hidden");
                loading_div.removeClass("hidden");
                $(loading_div.selector + " h2").text("Fetching current Email...");

                submitAction(eve, data_action, identifier, '', function (response) {
                    var email = $("#qrp_email_address");
                    loading_div.addClass("hidden");
                    if(response.message.status === 'success'){
                        default_div.removeClass("hidden");
                        email.val(response.message.content);
                    }else{
                        default_div.removeClass("hidden");
                    }
                });

                $(default_div.selector + " .update_email_action ").click(function(){
                    var email_value = $("#qrp_email_address").val();
                    if(validateEmail(email_value)){
                        $(loading_div.selector + " h2").text("Updating Email...");
                        loading_div.removeClass("hidden");
                        default_div.addClass("hidden");
                        submitAction(eve, data_action, identifier, email_value, function (response) {
                            loading_div.addClass("hidden");
                            if(response.message.status === 'success'){
                                success_div.removeClass("hidden");
                                $(success_div.selector + " h2").text("Email Address Updated");
                            }else{
                                success_div.removeClass("hidden");
                                $(success_div.selector + " h2").text("Unable to Update Email Address");
                            }
                            success_div.removeClass("hidden");
                            $(default_div.selector + " .update_email_action").off();
                        });
                    }else {
                        alert("Invalid Email Address");
                    }
                });
                break;
            case "qrp_send_email":
                dlg.dialog('open');
                dlg_title.text("");
                generic_div.removeClass("hidden");
                loading_div.removeClass("hidden");
                $(loading_div.selector + " h2").text("Fetching current Email...");
                submitAction(eve, data_action, identifier, '', function (response) {
                    var email = $(default_div.selector + " .email_address");
                    loading_div.addClass("hidden");
                    if(response.message.status === 'success'){
                        default_div.removeClass("hidden");
                        email.text(response.message.content);
                    }else{
                        default_div.removeClass("hidden");
                    }
                });

                $(default_div.selector + " .send_email_action").click(function(){
                    var email_value = $(default_div.selector + " .email_address").text();
                    if(validateEmail(email_value)){
                        $(loading_div.selector + " h2").text("Notifying User...");
                        loading_div.removeClass("hidden");
                        default_div.addClass("hidden");
                        console.log("Test");
                        submitAction(eve, data_action, identifier, email_value, function (response) {
                            loading_div.addClass("hidden");
                            if(response.message.status === 'success'){
                                success_div.removeClass("hidden");
                                $(success_div.selector + " h2").text("Notification Sent");
                            }else{
                                success_div.removeClass("hidden");
                                $(success_div.selector + " h2").text("Unable to Notify User");
                            }
                            success_div.removeClass("hidden");
                            $(default_div.selector + " .send_email_action").off();
                        });
                    }else {
                        alert("Invalid Email Address");
                    }
                });
                break;
            default:
                console.log("Unknown Action");
        }
    });

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
                onCloseDialog(this)
            });
        },
    });

});
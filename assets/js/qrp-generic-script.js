jQuery(document).ready(function($) {

    window.qrp_resend_email = function(instance){
        var request_email = prompt("Email Address:");
        var id_number = instance.getAttribute("data-id");
        var nonce = instance.getAttribute("data-nonce");
        var cf_id = instance.getAttribute("data-cfid");
        if (request_email == null || request_email == "") {
            alert("Email prompt box closed");
        } else {
            submitAction(cf_id, request_email, id_number, nonce, function (response) {
                var raw_response = response;
                if(raw_response.status == 'SUCCESS'){
                    if(raw_response.message.status == 'success'){
                        alert('Email Sent');
                    }else{
                        alert('Failed to resend response');
                    }
                }else{
                    alert('Failed to resend response');
                }
            })
        }
    }

    function submitAction(cf_id, email, id_number, nonce, successCallback) {
        $.ajax({
            type: "post",
            dataType: "json",
            url: qrpPublicAjax.ajaxurl,
            data: { action: qrpPublicAjax.action, cf_id: cf_id, email: email, id_number: id_number, nonce: nonce },
            success: function (response) {
                successCallback(response);
            }
        })
    }

});
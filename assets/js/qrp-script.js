jQuery(document).ready(function($){

    const html5QrCode = new Html5Qrcode("reader");
	
    let feedback_container = $(".feedback-container");
    let status_container = $(".status-container");
	let in_display = $(".in-display");

    let request_camera_permission = $(".request-camera-permission");
    let select_cameras = $(".select-cameras");
    let start_scan = $(".start-scan");
    let stop_scan = $(".stop-scan");

    let camera_region = $(".camera-region");
    let file_region = $(".file-region");
    let file_input_image = $(".file-input-image");

    let scan_type_camera = $(".scan-type-camera");
    let scan_type_local = $(".scan-type-local");

	let clone_video = $(".clone-video")[0];
    let beep = document.getElementById("beepSound");

	// Result Information 
	let user_data = {
    	'user-profile': $(".user-profile"),
    	'user-name': $(".user-name"),
    	'user-position': $(".user-position"),
    	'user-id': $(".user-id")
    };

	let response_text = $(".response-text");
	let status_200 = $(".response-container");
	let status_error = $(".status-error");

    // Global Variables
    window.qrpass = {};
    window.qrpass.SCAN_RECENT = '';
    window.qrpass.SCAN_HISTORY = [];
    window.qrpass.RESULT_HISTORY = [];
    window.qrpass.CURRENT_REGION = 'camera';
	window.qrpass.CLASS = html5QrCode;

	$('ul.tabs li').click(function(){
		let tab_id = $(this).attr('data-tab');

		$('ul.tabs li').removeClass('current');
		$('.tab-content').removeClass('current');

		$(this).addClass('current');
		$("#"+tab_id).addClass('current');
	});

	$('.close-modal').click(function(){
    	status_200.fadeOut();
    });

    window.qrpass.setFeedback = function(message) {
        feedback_container.html(`<div class="feedback-message">${message}</div>`);
    }

    window.qrpass.setStatus = function(message) {
        status_container.html(`<div class="status-message">${message}</div>`);
    }

    function retrieveScanHistory() {
        window.qrpass.SCAN_HISTORY = isHistoryEmpty("scanHistory");
        window.qrpass.RESULT_HISTORY = isHistoryEmpty("resultHistory");
    }

    function isHistoryEmpty(key) {
    	// Temp clear for now 
    	localStorage.setItem(key, "[]");
        if(localStorage.getItem(key) === null || localStorage.getItem(key) === ""){
            localStorage.setItem(key, "[]");
            return JSON.parse(localStorage.getItem(key));
        }else{
            return JSON.parse(localStorage.getItem(key));
        }
    }

    function saveScanHistory(newData) {
        let result_history = window.qrpass.RESULT_HISTORY;
		newData = btoa(newData);
        if(window.qrpass.SCAN_RECENT === (newData)) return;

        window.qrpass.SCAN_RECENT = (newData);
        result_history.push(newData);
        localStorage.setItem("resultHistory", JSON.stringify(result_history));
    }

    function qrCodeSuccessCallback(qrHashData) {
      let scan_history = window.qrpass.SCAN_HISTORY;

      if(scan_history[scan_history.length-1] !== qrHashData){
        scan_history.push(qrHashData);
        localStorage.setItem("scanHistory", JSON.stringify(scan_history));
      }

      console.log(qrHashData)
      // Send Hash to Server
      var data = {
        'action': 'validate_qrpass',
        'hashData': qrHashData
      };
      $.post(ajax_object.ajax_url, data, function (response) {
          displayReceiveData(response);
          saveScanHistory(response);
      });
    }

    function displayReceiveData(data) {
	    beep.play();
    	try {
        	var parse_data = JSON.parse(data);
        	response_text.text(parse_data.message);
        	console.log(parse_data.code);
        	if(parse_data.code === 200){
            	    user_data["user-id"].text(parse_data.data["user-id"]);
            		user_data["user-name"].text(parse_data.data["user-name"]);
            		user_data["user-profile"].attr("src", parse_data.data["user-photo"]);
            		response_text.removeClass('code-400');
            		response_text.addClass('code-200');
            		status_200.fadeIn();
            }else{
            		user_data["user-id"].text('');
            		user_data["user-name"].text('Invalid');
            		user_data["user-profile"].attr("src", 'https://cdn4.iconfinder.com/data/icons/users-outline-3/64/Artboard_45-512.png');
            		response_text.removeClass('code-200');
            		response_text.addClass('code-400');
            		status_200.fadeIn();
            }
        } catch(err) {
        	console.log(err);
        	status_200.fadeOut();
        	response_text.text("Failed Parsing Data");
        }
        // TODO: Get (Server Response, User ID, User Full Name)
    }

    function qrCodeErrorCallback() {
        window.qrpass.setStatus(`Scanning.. Please align within the box`)
    }

    function videoErrorCallback(message) {
        window.qrpass.setFeedback(`Video Error, error = ${message}`);
    }

    function onScanTypeSelectionChange(eve) {

        function setupFileOption() {
            window.qrpass.CURRENT_REGION = 'file';
            if (!stop_scan.is(":disabled")) {
                stop_scan.click();
            }
            scan_type_camera.addClass("disabled");
            scan_type_local.removeClass("disabled");
            file_input_image.prop("disabled", false);
            window.qrpass.setFeedback("Select image file to scan QR code.");
        }

        function setupCameraOption() {
            window.qrpass.CURRENT_REGION = 'camera';
            html5QrCode.clear();
            file_input_image.val("");
            file_input_image.prop("disabled", true);

            camera_region.removeClass("disabled");
            file_region.addClass("disabled");
            window.qrpass.setFeedback("Click 'Start Scanning' to <b>start scanning QR Code</b>");
        }

        let val = eve.currentTarget.getAttribute('data')
        if (val === 'file') {
            setupFileOption();
        } else if (val === 'camera') {
            setupCameraOption();
        } else {
            throw `Unsupported scan type ${val}`;
        }
    }

    function requestCameraPermission() {
        if (window.qrpass.CURRENT_REGION !== 'camera') return;
        Html5Qrcode.getCameras().then(function (cameras) {
                request_camera_permission.prop("disabled", true);

                if (cameras.length === 0) {
                    return window.qrpass.setFeedback("Error: Zero cameras found in the device");
                }

                for (let i = 0; i < cameras.length; i++) {
                    let option = document.createElement('option');
                    option.value = cameras[i].id;
                    option.innerHTML = (cameras[i].label === null ? option.value : cameras[i].label);
                    select_cameras.append(option);
                }

                    start_scan.click(function () {
                    if (window.qrpass.CURRENT_REGION !== 'camera') return;
                    	status_container.fadeIn();
                        select_cameras.prop("disabled", true);
                        start_scan.prop("disabled", true);
                        stop_scan.prop("disabled", false);

                        window.qrpass.setStatus("scanning");
                        window.qrpass.setFeedback("");

                        // Start scanning.
                        html5QrCode.start( select_cameras.val(), {  },
                            qrCodeSuccessCallback,
                            qrCodeErrorCallback).catch(videoErrorCallback).then(function(e){
                        		// Stream Clone Video
                    			clone_video.srcObject = window.qrpass.CLASS._localMediaStream;
                    			clone_video.play();
                        	});
                });

                    stop_scan.click(function () {
                    html5QrCode.stop().then(function (ignore) {
						
                    	status_container.fadeOut();
                        select_cameras.prop("disabled", false);
                        start_scan.prop("disabled", false);
                        stop_scan.prop("disabled", true);

                        window.qrpass.setFeedback('Stopped');
                        window.qrpass.setFeedback("Click 'Start Scanning' to <b>start scanning QR Code</b>");

                        
                    }).catch(err => {
                        window.qrpass.setFeedback('Error');
                        window.qrpass.setFeedback("Race condition, unable to close the scan.");
                    });
                });

                select_cameras.prop("disabled", false);
                start_scan.prop("disabled", false);
                localStorage.setItem('app_init_camera', '1' );
                $('.modal').removeClass('is-visible');
            }).catch(err => {
                localStorage.setItem('app_init_camera', '0' );
            request_camera_permission.prop("disabled", false);
            window.qrpass.setFeedback(`<span class="err-label">Error: Unable to query any cameras.</span><span class="rea-label">Reason: ${err}</span>`);
        });
    }

    function fileBasedScanning(eve) {
        if(window.qrpass.CURRENT_REGION !== 'file') return;
        if (eve.target.files.length === 0) {
            return;
        }
        html5QrCode.scanFile(eve.target.files[0], true)
        .then(qrCodeSuccessCallback)
        .catch(err => window.qrpass.setFeedback(`Error scanning file. Reason: ${err}`));
    }

    function allowCameraAccess() {
	    if(localStorage.getItem('app_init_camera') === '1'){
	        let successCallback = function(error) {
                $('.modal').removeClass('is-visible');
                requestCameraPermission();
            };
            let errorCallback = function(error) {
                $('.modal').addClass('is-visible');
            };
            navigator.mediaDevices.getUserMedia({video:true}).then(successCallback, errorCallback);
        }else{
	        $('.modal').addClass('is-visible');
        }
    }

    function init() {
    	status_200.fadeOut();
        $(".tab-link").click(onScanTypeSelectionChange);
        request_camera_permission.click(requestCameraPermission);
        file_input_image.on('change',fileBasedScanning);
        retrieveScanHistory();
        allowCameraAccess();
    }

    init();

})

angular.module("UploadForm", [ "ngFileUpload", "ui.bootstrap" ]);

angular.module("UploadForm").controller("UploadFormController", [ "$scope", "$filter", "Upload", function($scope, $filter, Upload) {
	var formCtrl = this;

	// Alert messages
	formCtrl.alerts = [];

	// Maximum total file size: 24 MB
	formCtrl.maxTotalSize = 25165824;

	// One array element per row on the input form
	formCtrl.fileInputs = [ null ];

	// True when upload is in progress
	formCtrl.isUploading = false;

	// Uploading progress per centage
	formCtrl.progress = 0;

	formCtrl.fileSelected = function($file) {
		// Add file to the model
		formCtrl.fileInputs[formCtrl.fileInputs.length - 1] = $file;

		// If the last available file input field has been used, append
		// a new input field
		if (formCtrl.fileInputs[formCtrl.fileInputs.length - 1] !== null) {
			formCtrl.fileInputs.push(null);
		}
	};

	formCtrl.filesDropped = function($files) {
		// Update the model and view as though each file were selected
		// individually
		$files.forEach(function(file) {
			formCtrl.fileSelected(file);
		});
	};

	formCtrl.removeFile = function($index, $event) {
		// Don't submit the form when link is clicked
		$event.preventDefault();

		// Remove file at specified index from model
		formCtrl.fileInputs.splice($index, 1);
	};

	formCtrl.addAlert = function(type, msg) {
		formCtrl.alerts.push({
			type: type,
			msg: msg
		}); 
	};

	formCtrl.removeAlert = function(index) {
		formCtrl.alerts.splice(index, 1);
	};

	formCtrl.clearAlerts = function() {
		formCtrl.alerts = [];
	};

	formCtrl.upload = function() {
		var totalSize = 0;
		var files = [];

		// Clear outstanding alerts
		formCtrl.clearAlerts();

		// Examine files to be uploaded
		formCtrl.fileInputs.forEach(function(file) {
			// Skip any blank file selections
			if (file !== null) {
				files.push(file);
				// Tally total size of files to be uploaded
				totalSize += file.size;
			}
		});
		// Refuse upload if maximum size exceeded
		if (totalSize > formCtrl.maxTotalSize) {
			alert("Total size of all select files can't be greater than " + $filter("bytes")(formCtrl.maxTotalSize));
			return;
		}

		formCtrl.isUploading = true;
		Upload.upload({
			url: "upload.php",
			method: "POST",
			data: {
				files: files
			}
		}).then(
			function done(response) {
				formCtrl.isUploading = false;
				formCtrl.progress = 0;
				if (!response.data.success && response.data.files.length == 0) {
					formCtrl.addAlert("danger", "All files failed to upload");
				}
				else {
					response.data.files.forEach(function(file) {
						if (file.success) {
							formCtrl.addAlert("success", file.name + ": uploaded successfully");
							// Remove successful files from the queue
							for (var i = 0; i < formCtrl.fileInputs.length; i++) {
								if (formCtrl.fileInputs[i].name == file.name) {
									formCtrl.fileInputs.splice(i, 1);
									break;
								}
							}
						}
						else {
							formCtrl.addAlert("danger", file.name + ": " + file.error)
						}
					});
				}
			},
			function fail(response) {
				formCtrl.isUploading = false;
				formCtrl.progress = 0;
			},
			function progress(evt) {
				formCtrl.progress = parseInt(100.0 * evt.loaded / evt.total) + "%";
			}
		);
	};
}])

angular.module("UploadForm").filter('bytes', function() {
	return function(bytes, precision) {
		if (isNaN(parseFloat(bytes)) || !isFinite(bytes)) {
			return '-';
		}

		if (typeof precision === 'undefined') {
			precision = 1;
		}

		var units = ['bytes', 'kB', 'MB', 'GB', 'TB', 'PB'];
		var number = Math.floor(Math.log(bytes) / Math.log(1024));

		return (bytes / Math.pow(1024, Math.floor(number))).toFixed(precision) +  ' ' + units[number];
	}
});

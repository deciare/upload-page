<?php
if (isset($_FILES["files"])) {
	$response = array(
		"success" => "true",
		"files" => array()
	);
	$dst_dir = "/home/deciare/incoming/";
	$total_files = count($_FILES["files"]["name"]);
	$error_text = "";

	set_error_handler(function($errno, $errstr) {
		if (!(error_reporting() & $errno)) {
			return true;
		}

		switch ($errno) {
			case E_WARNING:
			case E_NOTICE:
				// Ignore non-fatal errors to avoid messing up
				// JSON response
				break;
			default:
				echo "$errstr";
				return false;
		}

		return true;
	});

	foreach ($_FILES["files"]["error"] as $key => $error) {
		switch ($error) {
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				$error_text = "File is too large";
				break;
			case UPLOAD_ERR_PARTIAL:
				$error_text = "Upload was interrupted";
				break;
			case UPLOAD_ERR_NO_FILE:
				$error_text = "No file was uploaded";
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$error_text = "Missing temporary directory";
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$error_text = "Couldn't write to disk";
				break;
			case UPLOAD_ERR_EXTENSION:
				$error_text = "Upload was interrupted by server extension";
				break;
			default:
				$error_text = "Unknown error";
		}

		if ($error != UPLOAD_ERR_OK) {
			$response["success"] = false;
			array_push($response["files"], array(
				"name" => $_FILES["files"]["name"][$key],
				"success" => false,
				"error" => $error_text
			));
			continue;
		}

		$src_file = $_FILES["files"]["tmp_name"][$key];
		$dst_file = $dst_dir . date("Y-m-d_H.i.s") . "_" . basename($_FILES["files"]["name"][$key]);
		if (move_uploaded_file($src_file, $dst_file)) {
			array_push($response["files"], array(
				"name" => $_FILES["files"]["name"][$key],
				"success" => true
			));
		}
		else {
			$response["success"] = false;
			array_push($response["files"], array(
				"name" => $_FILES["files"]["name"][$key],
				"success" => false,
				"error" => "Couldn't move file to final destination"
			));
		}
	}

	die(json_encode($response));
}

?>
<html ng-app="UploadForm">
<head>
	<title>Upload form</title>
	<link rel="stylesheet" href="components/bootstrap/dist/css/bootstrap.css" />
	<script src="components/jquery/dist/jquery.js"></script>
	<script src="components/bootstrap/dist/js/bootstrap.js"></script>
	<script src="components/angular/angular.js"></script>
	<script src="components/angular-bootstrap/ui-bootstrap-tpls.js"></script>
	<script src="components/ng-file-upload/ng-file-upload-shim.js"></script>
	<script src="components/ng-file-upload/ng-file-upload.js"></script>
	<script src="scripts/upload.js"></script>
	<style type="text/css">
		body {
			padding: 20px;
		}
		.alert {
			border-radius: 5px;
			margin-top: 10px;
		}
		.file-drop {
			margin-left: 20px;
		}
		.file-drop .drop-target {
			background-color: #cccccc;
			border: 4px dashed #666666;
			color: #666666;
			display: table-cell;
			font-size: 2em;
			font-weight: bold;
			text-align: center;
			vertical-align: middle;
			transition: all 0.25s;
			width: 500px;
			height: 100px;
		}
		.file-drop .drop-target.dragover {
			background-color: #ccffcc;
		}
		.file-select {
			margin-left: 20px;
		}
		.file-drop + p,
		.file-select + p {
			margin-top: 20px;
		}
	</style>
</head>
<body>
	<noscript>
		<div class="alert alert-warning">
			<span class="glyphicon glyphicon-exclamation-sign"></span>
			This form requires JavaScript. You will not be able to operate this form if JavaScript is disabled in your browser.
		</div>
	</noscript>
	<h1>Upload form</h1>
	<form ng-controller="UploadFormController as formCtrl" ng-submit="formCtrl.upload()">
		<p>Select files to upload:</p>
		<div class="file-select" ng-repeat="input in formCtrl.fileInputs track by $index">
			<input type="file" ngf-select="formCtrl.fileSelected($file)" ng-if="input === null" />
			<span ng-if="input !== null">
				{{input.name}} ({{input.size | bytes}})
				<button class="btn btn-link" ng-click="formCtrl.removeFile($index, $event)">
					<span class="glyphicon glyphicon-remove"></span>
				</button>
			</span>
		</div>
		<p>Or drag and drop files:</p>
		<div class="file-drop">
			<div class="drop-target" ngf-drag-over-class="'dragover'" ngf-drop="formCtrl.filesDropped($files)" ngf-multiple="true">Drop files here</div>
		</div>
		<p>Maximum total size: {{formCtrl.maxTotalSize | bytes}}</p>
		<button class="btn btn-default" type="submit" ng-disabled="formCtrl.isUploading">Upload</button>
		<span ng-show="formCtrl.isUploading">Upload in progress... {{formCtrl.progress}}</span>
		<uib-alert ng-repeat="alert in formCtrl.alerts" type="{{alert.type}}" close="formCtrl.removeAlert($index)">{{alert.msg}}</uib-alert>
	</form>
</body>
</html>

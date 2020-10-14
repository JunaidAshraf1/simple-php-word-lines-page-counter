<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
include "class.doccounter.php";
 
?>

<!DOCTYPE html>
<html>
<head>
	<title> Word Counter - (DOC, DOCX, PDF, TXT) </title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
	<style>
		/*.please_wait{
			//display:none;
		}*/

		*{
			/*color: white !important;*/
			color: black !important;
			font-family: monospace;
			font-size: 17px;
		}

		.btn{
			color: white !important;
		}
		body{
			padding:25px;
			background-color: #FF9A8B;
			background-image: linear-gradient(90deg, #FF9A8B 0%, #FF6A88 55%, #FF99AC 100%);



			}
	</style>
</head>
<body>
<br>
<h6>Simple Doc Word | Line | Page Counter</h6>
<hr>
<form action="index.php" method="post" enctype="multipart/form-data">
  Please select a file: 
  <input type="file" name="fileToUpload" id="fileToUpload" accept=".doc,.docx,.pdf,.txt" required/>
  <input type="submit" class="btn btn-primary" value="Count Now" name="submit">
</form>
<hr>
<!-- <div class="please_wait">Please Wait...</div> -->
</body>
</html>

<?php


if(isset($_POST['submit'])){

		echo "<div class='please_wait'> Please Wait... </div>";

		$target_dir = "files/";

		if (!file_exists($target_dir)) {
		mkdir($target_dir, 0777, true);
		}

		$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
		$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
		$target_file = $target_dir . date("YmdHis").".".$imageFileType;
		$uploadOk = 1;
		$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

		// Check if file already exists
		if (file_exists($target_file)) {
		echo "Sorry, file already exists.";
		$uploadOk = 0;
		}

		// Check if $uploadOk is set to 0 by an error
		if ($uploadOk == 0) {
		echo "Sorry, your file was not uploaded.";
		// if everything is ok, try to upload file
		} else {
		if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {   

		$doc = new DocCounter();
		$doc->setFile($target_file);

		echo "<b>Result :</b> ".json_encode($doc->getInfo());
		//echo "WordCount: ".($doc->getInfo()->wordCount);
		//sleep(1);
		echo "<script>
		var please_wait = document.getElementsByClassName('please_wait')[0];
		please_wait.style.display = 'none';
		</script>";

		unlink($target_file);

		} else {
		echo "Sorry, there was an error uploading your file.";
		}
	}

}

?>
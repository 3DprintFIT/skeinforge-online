<?php
function putForm() {
	//echo "<p>Not in a working state.</p>";
	echo "<p>Upload your STL file (<a href=\"http://fabmetheus.crsndoo.com/wiki/index.php/Skeinforge#File_Formats\" title=\"Supported filetypes\" >or other</a>) and watch the magic.</p>\n";
	echo "<form action=\"/\" method=\"post\" enctype=\"multipart/form-data\">\n";
	echo "	<p><input type=\"file\" name=\"file\" id=\"file\" /></p>\n";
	echo "	<p><select name=\"profile\" id=\"profile\"><option value=\".none\" selected=\"selected\">Choose a profile...</option>";
	if ($handle = opendir('../prefdir/profiles/extrusion')) {
		$profiles = array();
		while ($profiles[] = readdir($handle));
		closedir($handle);
		sort($profiles);
		foreach ($profiles as $profile) {
			if ((substr($profile,0,1) != ".") && ($profile != "")) {
				echo "<option value=\"".$profile."\">".$profile."</option>";
			}
		}
	} else {
		echo "	<p><strong>Error:</strong> Cannot open profiles dir.</p>\n";
	}
	echo "</select></p>\n";
	echo "<p>Multiply: <input type=\"number\" name=\"rows\" id=\"rows\" value=\"1\" min=\"1\" max=\"50\"> &times <input type=\"number\" name=\"columns\" id=\"columns\" value=\"1\" min=\"1\" max=\"50\"></p>\n";
	echo "<p><input type=\"submit\" name=\"submit\" value=\"Upload\" /></p>\n";
	echo "</form>";
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<title>Skeinforge online</title>
	<link rel="shortcut icon" href="favicon.png" type="image/x-icon" />
	<link rel="stylesheet" type="text/css" href="style.css" />
	<script type="text/javascript" src="js.js"></script>
</head>
<body>
	<a href="https://github.com/3DprintFIT/skeinforge-online"><img style="position: absolute; top: 0; left: 0; border: 0;" src="https://s3.amazonaws.com/github/ribbons/forkme_left_orange_ff7600.png" alt="Fork me on GitHub"></a>
	<a href="/" class="brand">
		<img class="brand-image" alt="3D Print Lab logo" src="logo.png">
		<div class="brand-text"><strong>Skeinfogre</strong>online</div>
	</a>
	<h1>Convert your 3D model into G-Code</h1>
	<?php
	if ($_FILES["file"]["tmp_name"] != "") {
		$allowedExts = array("stl", "gts", "obj", "bfb");
		$extension = strtolower(end(explode(".", $_FILES["file"]["name"])));
		$profile = $_POST["profile"];
		if ($_FILES["file"]["error"] > 0) {
			echo "<p><strong>Error:</strong> ".$_FILES["file"]["error"]."</p>";
			putForm();
		} elseif (!in_array($extension, $allowedExts)) {
			echo "<p><strong>Error:</strong> Not a <a href=\"http://fabmetheus.crsndoo.com/wiki/index.php/Skeinforge#File_Formats\" title=\"Supported filetypes\" >supported file</a>.</p>";
		} elseif ($profile == ".none") {
			echo "<p><strong>Error:</strong> Select a profile!</p>";
		} else {
			$counter = 0;
			$basename = str_replace(" ","_",substr($_FILES["file"]["name"],0,-strlen($extension))).$counter;
			$filename = $basename.".".$extension;
			while (file_exists("files/".$filename)) {
				$counter++;
				$basename = str_replace(" ","_",substr($_FILES["file"]["name"],0,-strlen($extension))).$counter;
				$filename = $basename.".".$extension;
			}
			move_uploaded_file($_FILES["file"]["tmp_name"],"files/".$filename);
			$whoami = exec('whoami');
			$fullpath = 'files/';
			exec('cp -ar ../prefdir '.$fullpath.$basename.'.dir');
			exec('echo -e "Profile Selection:\t'.$profile.'" >> '.$fullpath.$basename.'.dir/profiles/extrusion.csv');
			$columns = $_POST["columns"];
			$rows = $_POST["rows"];
			exec('echo -e "Number of Columns (integer):\t'.$columns.'" >> '.$fullpath.$basename.'.dir/profiles/extrusion/'.$profile.'/multiply.csv');
			exec('echo -e "Number of Rows (integer):\t'.$rows.'" >> '.$fullpath.$basename.'.dir/profiles/extrusion/'.$profile.'/multiply.csv');
			exec('../misc/run.sh '.$fullpath.$basename.'.dir '.$fullpath.$filename.' '.$fullpath.$basename.'.log '.$fullpath.$basename.'.pid > '.$fullpath.$basename.'.exit 2>&1 &',$output,$exitcode);
		}
	} elseif($_GET["job"] != "") {
		if(file_exists("files/".$_GET["job"])) {
			$basename = $_GET["job"];
			$extension = strtolower(end(explode(".", $basename)));
			$basename = substr($basename,0,-strlen($extension)-1);
		} else {
			echo "<p><strong>Error:</strong> Bad job.</p>";
		}
	}
	if (isset($basename)) {
		echo "	<p id=\"infobox\"></p>\n";
		echo "	<p id=\"topDwnLink\"><strong>Permalink:</strong> <a href=\"/?job=".$basename.".".$extension."\">".$basename.".".$extension."</a></p>\n";
		echo '	<pre id="terminal">$ skeinforge '.$basename.' '.$extension.'</pre>'."\n";
		echo '	<script type="text/javascript">'."\n";
		echo '	<!--'."\n";
		echo '	loadLog("'.$basename.'","'.$extension.'");'."\n";
		echo '	refreshInterval = setInterval(function(){refresh("'.$basename.'","'.$extension.'")},3000);'."\n";
		echo '	//-->'."\n";
		echo '	</script>'."\n";
		echo "	<p id=\"bottomDwnLink\"><strong>Permalink:</strong> <a href=\"/?job=".$basename.".".$extension."\">".$basename.".".$extension."</a></p>\n";
	} else {
		putForm();
		?>
	<h2>Warning</h2>
	<p>This service is BETA! It is provided as is and without any express or implied warranties, including, without limitation, the implied warranties of merchantability and fitness for a particular purpose. Uploaded files aren't protected, anyone can steel them.</p>
	<h2>TODO</h2>
	<ul>
		<li>List processes</li>
		<li>Support user uploaded profiles</li>
		<li>Security :P</li>
	</ul>
	<?php } ?>
	<p><a href="http://fabmetheus.crsndoo.com/wiki/index.php/Skeinforge">Skeinforge</a> &copy; Enrique Perez (AGPLv3)<br />This service &copy; <a href="http://hroncok.cz/">Miro Hrončok</a>, <a href="https://3dprint.fit.cvut.cz/">3D Print Lab FIT CTU</a></p>
</body>
</html>

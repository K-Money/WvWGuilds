<?php
	// This code was adapted from the Guild Wars 2 Emblem Creator Script by Moturdrn.2837 https://forum-en.guildwars2.com/forum/community/api/API-Suggestion-Guilds/2158388

	/* Guild Wars 2 Emblem Creator Script by Moturdrn.2837
	Special thanks to:
	- Cliff Spradlin and ArenaNet for making available the API and information
	- smiley.1438 and Think.8042 with PHP colour conversion
	- smiley.1438 for the gw2_api_request function
	- Dr Ishmael.9685 for providing information and downloads of the emblem parts and backgrounds, and the idea to split the matrix and colour conversion functions
	- Killer Rhino.6794 for the extra advanced information on working out how to decode the API colour information into something useable
	- The general Guild Wars 2 development community for being a bunch of guys and gals ready and willing to help one another out
	- Apologies if I've left you out, please contact me and I'll add you in here :)
	
	Please feel free to use this code and make modifications. Apologies in advance if the coding seems all over the place.*/
	
	$pathToBackgrounds = './backgrounds/';
	$pathToEmblems = './emblems/';
	
	//Pull guild ID or name from URL
	if(isset($_GET['guild_id'])){
		$guild_id = $_GET['guild_id'];
		$guildInfo = gw2_api_request("guild_details.json?guild_id={$guild_id}");
		if(!$guildInfo)
			displayUnknown();
	}elseif(isset($_GET['guild_name'])){
		$guild_name = $_GET['guild_name'];
		$guild_name = str_replace(' ', '%20', $guild_name);
		$guildInfo = gw2_api_request("guild_details.json?guild_name={$guild_name}");
		if(!$guildInfo)
			displayUnknown();
	}else{
		displayUnknown();
	}
	
	// Create Connection
	$con=mysqli_connect("localhost","USERNAME","PASSWORD","DATABASE");
	
	// Check Connection
	if (mysqli_connect_errno($con))
		{
		echo "Failed to conect to MySQL: " . mysqli_connect_error();
		}
	$con->set_charset("utf8");
	
	// Set Variables
	$guildName= mysqli_real_escape_string($con,$guildInfo['guild_name']);
	$guildID= mysqli_real_escape_string($con,$guildInfo['guild_id']);
	$guildTag= mysqli_real_escape_string($con,$guildInfo['tag']);
	
	// Prepare SQL statement and query it
	$sql =  "SELECT guild_id, guild_emblem FROM guild_table WHERE guild_id='$guildID';";
	$result = mysqli_query($con,$sql) or die(mysqli_error());
	$row1 = mysqli_fetch_array($result);
	
	//Check if emblem already in database
	if($row1['guild_emblem'] != NULL){
		try
		{
			$image = new Imagick();
			$image->readimageblob($row1['guild_emblem']);
			header("Content-type: image/png");
			echo $image;
		}
		catch(Exception $e)
		{
			echo $e->getMessage();
		}
		exit();
	}
	
	if(!$guildInfo['emblem'])
		displayUnknown();
		
	$background_id = $guildInfo['emblem']['background_id'];
	$foreground_id = $guildInfo['emblem']['foreground_id'];
	$background_color_id = $guildInfo['emblem']['background_color_id'];
	$foreground_primary_color_id = $guildInfo['emblem']['foreground_primary_color_id'];
	$foreground_secondary_color_id = $guildInfo['emblem']['foreground_secondary_color_id'];
	$flags = $guildInfo['emblem']['flags'];
	
	
	$colorsArray = gw2_api_request('colors.json');
	if(!$colorsArray)
		displayUnknown();
	
	$colorsArray = $colorsArray['colors'];
	
	$background = $colorsArray[$background_color_id]['cloth'];
	$primary = $colorsArray[$foreground_primary_color_id]['cloth'];
	$secondary = $colorsArray[$foreground_secondary_color_id]['cloth'];
	$background_path = "{$pathToBackgrounds}{$background_id}.png";
	$primary_path = "{$pathToEmblems}{$foreground_id}a.png";
	$secondary_path = "{$pathToEmblems}{$foreground_id}b.png";
	
	// Try and generate the image, fail with message if error
	try
	{
		// Fetch the background image
		$imagebk1 = imagecreatefrompng($background_path);
		
		// Apply transparency information for the background - Base image gives issue when using imagick so used GD for bg part. I believe imagick is faster so used it for the rest.
		$backgroundCol = imagecolorallocate($imagebk1,  255,255,255);
		imagecolortransparent($imagebk1, $backgroundCol);
		imagealphablending($imagebk1, true);
		imagesavealpha($imagebk1, true);
		
		// Re-colour background image
		imagehue($imagebk1, $background);
		imagefilter($imagebk1, IMG_FILTER_CONTRAST, $background['contrast']);
		imagefilter($imagebk1, IMG_FILTER_COLORIZE, $background['rgb'][0], $background['rgb'][1],$background['rgb'][2]);
		
		// Convert from GD to Imagick format
		ob_start();
		imagepng($imagebk1);
		$blob = ob_get_clean();
		imagedestroy($imagebk1);
		$imagebk = new Imagick();
		$imagebk->readImageBlob($blob);

		// Fetch the primary emblem image
		$image1 = new Imagick($primary_path);
		$matrix = getColorMatrix($primary);
		
		// Re-colour primary emblem image
		$it = $image1->getPixelIterator();
		foreach( $it as $row => $pixels )
		{
			foreach ( $pixels as $column => $pixel )
			{
				$alpha = $pixel->getColorValue(imagick::COLOR_ALPHA);
				if($alpha>0){
					$color = $pixel->getColor();
					$baseRGB = array($color['r'],$color['g'],$color['b']);
					list($r,$g,$b) = applyColorTransform($matrix, $baseRGB);
					$pixel->setColor( "rgba($r,$g,$b,$alpha)" );
				}
			}
			$it->syncIterator();
		}
		
		// Fetch the secondary emblem image
		$image2 = new Imagick($secondary_path);
		$matrix = getColorMatrix($secondary);
		
		// Re-colour secondary emblem image
		$it = $image2->getPixelIterator();
		foreach( $it as $row => $pixels )
		{
			foreach ( $pixels as $column => $pixel )
			{
				$alpha = $pixel->getColorValue(imagick::COLOR_ALPHA);
				if($alpha>0){
					$color = $pixel->getColor();
					$baseRGB = array($color['r'],$color['g'],$color['b']);
					list($r,$g,$b) = applyColorTransform($matrix, $baseRGB);
					$pixel->setColor( "rgba($r,$g,$b,$alpha)" );
				}
			}
			$it->syncIterator();
		}
		
		$image1->compositeImage( $image2, $image2->getImageCompose(), 0, 0 );
		
		foreach($flags as $flag){
			switch($flag){
				case 'FlipBackgroundHorizontal':
					$imagebk->flopImage();
					break;
				case 'FlipBackgroundVertical':
					$imagebk->flipImage();
					break;
				case 'FlipForegroundHorizontal':
					$image1->flopImage();
					break;
				case 'FlipForegroundVertical':
					$image1->flipImage();
					break;
			}
		}
		
		//Final image
		$imagebk->compositeImage( $image1, $image1->getImageCompose(), 0, 0 );
		
		//Convert image to blob for mysql storage
		$savedimage = mysqli_real_escape_string($con,$imagebk->getImageBlob());
		
		//Check if there's an entry to add the image to or create a new one.
		if($row1['guild_id'] == NULL){
			if (!mysqli_query($con,"INSERT INTO guild_table(guild_id,guild_name,guild_tag,guild_emblem)VALUES('$guildID','$guildName','$guildTag','$savedimage')")) {
				echo("Error description: " . mysqli_error($con));
				}
		}
		else {
			if (!mysqli_query($con,"UPDATE guild_table SET guild_emblem='$savedimage' WHERE guild_id='$guildID'")) {
				echo("Error description: " . mysqli_error($con));
				}
		}
		
		//Display image
		header("Content-Type: image/png");
		echo $imagebk;
	}
	catch(Exception $e)
	{
		echo $e->getMessage();
	}
	mysqli_close($con);
	
	
	
	
	function imagehue(&$image, $color) {
		$width = imagesx($image);
		$height = imagesy($image);
		$matrix = getColorMatrix($color);
		for($x = 0; $x < $width; $x++) {
			for($y = 0; $y < $height; $y++) {
				$colorIndex = imagecolorat($image, $x, $y);
				$colorTran = imagecolorsforindex($image, $colorIndex);
				$r = $colorTran['red'];
				$g = $colorTran['green'];
				$b = $colorTran['blue'];            
				$alpha = $colorTran['alpha'];
				if($alpha<127){
					$rgb = array($r,$g,$b);
					$rgb = applyColorTransform($matrix, $rgb);
					imagesetpixel($image, $x, $y, imagecolorallocatealpha($image, $rgb[0], $rgb[1], $rgb[2], $colorTran['alpha']));
				}
			}
		}
	}
	
	function image_flip($img, $type=''){
		$width  = imagesx($img);
		$height = imagesy($img);
		$dest   = imagecreatetruecolor($width, $height);
		imagesavealpha($dest, true);
		$trans_colour = imagecolorallocatealpha($dest, 0, 0, 0, 127);
		imagefill($dest, 0, 0, $trans_colour);
		switch($type){
			case 'vert':
				for($i=0;$i<$height;$i++){
					imagecopy($dest, $img, 0, ($height - $i - 1), 0, $i, $width, 1);
				}
				break;
			case 'horiz':
				for($i=0;$i<$width;$i++){
					imagecopy($dest, $img, ($width - $i - 1), 0, $i, 0, 1, $height);
				}
				break;
			default:
				return $img;
		}
		return $dest;
	}
	
	// Function matrix_multiply by smiley: https://gist.github.com/codemasher/b869faa7603e1934c28d
	function matrix_multiply($m1, $m2){
		$r = count($m1);
		$c = count($m2[0]);
		$p = count($m2);
		if(count($m1[0]) != $p){
			return false; //incompatible matrix
		}
		$m3 = array();
		for($i = 0; $i < $r; $i++){
			for($j = 0; $j < $c; $j++){
				$m3[$i][$j] = 0;
				for($k = 0; $k < $p; $k++){
					$m3[$i][$j] += $m1[$i][$k]*$m2[$k][$j];
				}
			}
		}
		return ($m3);
	}
	
	// Function getColorMatrix adapted from smiley's code: https://gist.github.com/codemasher/b869faa7603e1934c28d
	function getColorMatrix($hslbc){
		//colors from the response .json -> material
		$h = ($hslbc['hue']*pi())/180;
		$s = $hslbc['saturation'];
		$l = $hslbc['lightness'];
		$b = $hslbc['brightness']/128;
		$c = $hslbc['contrast'];
	 
		// 4x4 identity matrix
		$matrix = array(
			array(1, 0, 0, 0),
			array(0, 1, 0, 0),
			array(0, 0, 1, 0),
			array(0, 0, 0, 1)
		);
	 
		if($b != 0 || $c != 1){
			// process brightness and contrast
			$t = 128*(2*$b+1-$c);
			$mult = array(
				array($c,  0,  0, $t),
				array( 0, $c,  0, $t),
				array( 0,  0, $c, $t),
				array( 0,  0,  0,  1)
			);
			$matrix = matrix_multiply($mult, $matrix);
		}
	 
		if($h != 0 || $s != 1 || $l != 1){
			// transform to HSL
			$multRgbToHsl = array(
				array( 0.707107, 0,        -0.707107, 0),
				array(-0.408248, 0.816497, -0.408248, 0),
				array( 0.577350, 0.577350,  0.577350, 0),
				array( 0,        0,         0,        1)
			);
			$matrix = matrix_multiply($multRgbToHsl, $matrix);
	 
			// process adjustments
			$cosHue = cos($h);
			$sinHue = sin($h);
			$mult = array(
				array( $cosHue * $s, $sinHue * $s,  0, 0),
				array(-$sinHue * $s, $cosHue * $s,  0, 0),
				array(            0,            0, $l, 0),
				array(            0,            0,  0, 1)
			);
			$matrix = matrix_multiply($mult, $matrix);
	 
			// transform back to RGB
			$multHslToRgb = array(
				array( 0.707107, -0.408248, 0.577350, 0),
				array( 0,         0.816497, 0.577350, 0),
				array(-0.707107, -0.408248, 0.577350, 0),
				array( 0,         0,        0,        1)
			);
			$matrix = matrix_multiply($multHslToRgb, $matrix);
		}
		return $matrix;
	}
	
	// Function applyColorTransform adapted from smiley's code: https://gist.github.com/codemasher/b869faa7603e1934c28d
	function applyColorTransform($matrix, $base){
		// apply the color transformation
		$bgrVector = array(
			array($base[2]),
			array($base[1]),
			array($base[0]),
			array(1)
		);
		$bgrVector =  matrix_multiply($matrix,$bgrVector);
	 
		// clamp the values
		$rgb = array(
			floor(max(0, min(255, $bgrVector[2][0]))),
			floor(max(0, min(255, $bgrVector[1][0]))),
			floor(max(0, min(255, $bgrVector[0][0])))
		);
	 
		return $rgb;
	}
	
	// Function gw2_api_request from https://gist.github.com/codemasher/4d30a47df24195ac509f
	function gw2_api_request($request){
		$url = parse_url('https://api.guildwars2.com/v1/'.$request);
		if(!$fp = @fsockopen('ssl://'.$url['host'], 443, $errno, $errstr, 5)){
			return 'connection error: '.$errno.', '.$errstr;
		}
	 
		$nl = "\r\n";
		$header = 'GET '.$url['path'].'?'.$url['query'].' HTTP/1.1'.$nl.'Host: '.$url['host'].$nl.'Connection: Close'.$nl.$nl;
		fwrite($fp, $header);
		stream_set_timeout($fp, 5);
	 
		$response = '';
		do{
			if(strlen($in = fread($fp,1024))==0){
				break;
			}
			$response.= $in;
		}
		while(true);
	 
		$response = explode($nl,$response);
		
		if(isset($response[0]) && $response[0] == 'HTTP/1.1 200 OK'){
			$response = json_decode($response[count($response)-1],true);
		}
	 
		return $response;
	}
	
	function displayUnknown(){
		global $pathToEmblems;
		try{
			$image = new Imagick("{$pathToEmblems}unknown_guild.png");
			header("Content-Type: image/png");
			echo $image;
		}
		catch(Exception $e)
		{
			echo $e->getMessage();
			
		}
		exit();
	}
?>
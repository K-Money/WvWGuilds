<?php
// Check WvW data for each match and pull it
// Get list of matches in progress
$matches_url_base = "wvw/matches.json";
$matches_info = gw2_api_request($matches_url_base);
$matches_list = $matches_info['wvw_matches'];

// Pull world names list (doesn't need to be inside loop)
$world_names_url_base = "world_names.json";
$world_names_list = gw2_api_request($world_names_url_base);

// Create Connection (doesn't need to be inside loop)
$con=mysqli_connect("localhost","USERNAME","PASSWORD","DATABASE");

// Check Connection
if (mysqli_connect_errno($con))
	{
	echo "Failed to conect to MySQL: " . mysqli_connect_error();
	}
$con->set_charset("utf8");

// Initialize array and counting variables
$cnt=0;
$guilds_claimed_ids = array();
$guilds_claimed_names = array();
$guilds_claimed_worlds = array();
$guilds_claimed_tags = array();

// Cycle through matches and set world ids for participating worlds
foreach ($matches_list as $wvw_match){
	if (strpos($wvw_match['wvw_match_id'],'1-') !== false){
		$tier=$wvw_match['wvw_match_id'];
		$RedID = $wvw_match['red_world_id'];
		$BlueID = $wvw_match['blue_world_id'];
		$GreenID = $wvw_match['green_world_id'];

		// Find world names for participating worlds
		foreach ($world_names_list as $world_name_entry) {
			if($world_name_entry['id']==$RedID){
				$RedName=$world_name_entry['name'];
			}
			elseif($world_name_entry['id']==$BlueID){
				$BlueName=$world_name_entry['name'];
			}
			elseif($world_name_entry['id']==$GreenID){
				$GreenName=$world_name_entry['name'];
			}
		}
		
		// Get details of currently selected match
		$match_details_url_base="wvw/match_details.json?match_id=";
		$match_details_url=$match_details_url_base.$tier;
		$matchInfo = gw2_api_request($match_details_url);
		$mapsInfo = $matchInfo['maps'];
		
		//Store it into local variables
		foreach ($mapsInfo as $holder){
			foreach ($holder as $map_entry){
				foreach ($map_entry as $objective_entry){
					if(isset($objective_entry['owner_guild'])){
						$guilds_claimed_ids[$cnt] = mysqli_real_escape_string($con,$objective_entry['owner_guild']);
						$guilds_claimed_names[$cnt] = mysqli_real_escape_string($con,getNameFromId($objective_entry['owner_guild']));
						$guilds_claimed_tags[$cnt] = mysqli_real_escape_string($con,getTagFromId($objective_entry['owner_guild']));
						if($objective_entry['owner']=='Red'){
							$guilds_claimed_worlds[$cnt]=mysqli_real_escape_string($con,$RedName);
						} elseif($objective_entry['owner']=='Green'){
							$guilds_claimed_worlds[$cnt]=mysqli_real_escape_string($con,$GreenName);
						} elseif($objective_entry['owner']=='Blue') {
							$guilds_claimed_worlds[$cnt]=mysqli_real_escape_string($con,$BlueName);
						}
						$cnt=$cnt+1;
						echo $cnt;
					}
				}
				
			}
		}
	}
}

//Cycle through data and check if all guilds are present in database, insert any guilds not present
for ($x=0; $x<$cnt; $x++) {
	// Prepare SQL statement and query it
	$sql =  "SELECT guild_id,guild_name,guild_tag,guild_world FROM guild_table WHERE guild_id='$guilds_claimed_ids[$x]';";
	$result = mysqli_query($con,$sql) or die(mysqli_error());
	$row1= mysqli_fetch_array($result);
	
	//Check if guild id has entry in database and if not add all data except emblem
	if($row1== NULL){
		if (!mysqli_query($con,"INSERT INTO guild_table(guild_id,guild_name,guild_tag,guild_world)VALUES('$guilds_claimed_ids[$x]','$guilds_claimed_names[$x]','$guilds_claimed_tags[$x]','$guilds_claimed_worlds[$x]')")) {
		echo("Error description: " . mysqli_error($con));
		}
	}
	//If it's already in there it has all four of those fields since there's no way for it to be entered otherwise.
	
	//Check if emblem is already in database
	if($row1['guild_emblem'] == NULL){
		//RUN EMBLEMFORGE/HERALD FUNCTION HERE
		$emblemsavedimage = herald($guilds_claimed_ids[$x]);
		if (!mysqli_query($con,"UPDATE guild_table SET guild_emblem='$emblemsavedimage' WHERE guild_id='$guilds_claimed_ids[$x]'")) {
		echo("Error description: " . mysqli_error($con));
		}
	}
	// The echo is to show progress when running from command line so I can tell if it gets stuck
	echo $x;
}
mysqli_close($con);
// The echo is to show progress when running from command line so I can tell if it gets stuck
echo $cnt;
echo "done";

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

function getNameFromId($ID1){
	$guildStuff1 = gw2_api_request("guild_details.json?guild_id={$ID1}");
	$guildName1 = $guildStuff1['guild_name'];
	$guildReturnValue1 = $guildName1;
	return $guildReturnValue1;
}

function getTagFromId($ID2){
	$guildStuff2 = gw2_api_request("guild_details.json?guild_id={$ID2}");
	$guildTag2 = $guildStuff2['tag'];
	$guildReturnValue2 = $guildTag2;
	return $guildReturnValue2;
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

// Function for creating image
function herald($GID){
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
	
	$guild_id = $GID;
	$guildInfo = gw2_api_request("guild_details.json?guild_id={$guild_id}");
	if(!$guildInfo){
		return NULL;
	}
	if(!$guildInfo['emblem']){
		return NULL;
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
	$guildID = mysqli_real_escape_string($con,$guildInfo['guild_id']);
	
	// Prepare SQL statement and query it
	$sql =  "SELECT guild_id, guild_emblem FROM guild_table WHERE guild_id='$guildID';";
	$result = mysqli_query($con,$sql) or die(mysqli_error());
	$row1 = mysqli_fetch_array($result);
	
	$background_id = $guildInfo['emblem']['background_id'];
	$foreground_id = $guildInfo['emblem']['foreground_id'];
	$background_color_id = $guildInfo['emblem']['background_color_id'];
	$foreground_primary_color_id = $guildInfo['emblem']['foreground_primary_color_id'];
	$foreground_secondary_color_id = $guildInfo['emblem']['foreground_secondary_color_id'];
	$flags = $guildInfo['emblem']['flags'];
	
	
	$colorsArray = gw2_api_request('colors.json');
	if(!$colorsArray)
		return NULL;
	
	$colorsArray = $colorsArray['colors'];
	
	$background = $colorsArray[$background_color_id]['cloth'];
	$primary = $colorsArray[$foreground_primary_color_id]['cloth'];
	$secondary = $colorsArray[$foreground_secondary_color_id]['cloth'];
	$background_path = "{$pathToBackgrounds}{$background_id}.png";
	$primary_path = "{$pathToEmblems}{$foreground_id}a.png";
	$secondary_path = "{$pathToEmblems}{$foreground_id}b.png";
	
	// Fetch the background image
	$imagebk = imagecreatefrompng($background_path);
	
	// Apply transparency information for the background - Used GD since imagick was giving issues with bg and tweaked emblemforge2 code after writing this daemon.
	$backgroundCol = imagecolorallocate($imagebk,  255,255,255);
	imagecolortransparent($imagebk, $backgroundCol);
	imagealphablending($imagebk, true);
	imagesavealpha($imagebk, true);
	
	// Re-colour background image
	imagehue($imagebk, $background);
	imagefilter($imagebk, IMG_FILTER_CONTRAST, $background['contrast']);
	imagefilter($imagebk, IMG_FILTER_COLORIZE, $background['rgb'][0], $background['rgb'][1],$background['rgb'][2]);
	
	// Fetch the primary emblem image
	$image1 = imagecreatefrompng($primary_path);
	
	// Re-colour primary emblem image
	imagehue($image1, $primary);
	
	// Fetch the secondary emblem image
	$image2 = imagecreatefrompng($secondary_path);
	
	// Re-colour secondary emblem image
	imagehue($image2, $secondary);
	
	// Combine the primary and secondary emblem image
	imagecopy($image1, $image2, 0, 0, 0, 0, 256, 256);
	
	foreach($flags as $flag){
		switch($flag){
			case 'FlipBackgroundHorizontal':
				$imagebk = image_flip($imagebk,'horiz');
				break;
			case 'FlipBackgroundVertical':
				$imagebk = image_flip($imagebk,'vert');
				break;
			case 'FlipForegroundHorizontal':
				$image1 = image_flip($image1,'horiz');
				break;
			case 'FlipForegroundVertical':
				$image1 = image_flip($image1,'vert');
				break;
		}
	}
	
	// Combine the emblem and background
	imagecopy($imagebk, $image1, 0, 0, 0, 0, 256, 256);
	
	// Convert completed image to blob for storage
	ob_start();
	imagepng($imagebk);
	$blob = ob_get_clean();
	$savedimage = mysqli_real_escape_string($con,$blob);
	
	// Clean-up
	imagedestroy($imagebk);
	imagedestroy($image1);
	imagedestroy($image2);
	mysqli_close($con);
	
	// Return final blob of image
	return $imagebk;
	
}
?>
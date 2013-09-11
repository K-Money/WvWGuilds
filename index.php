<?php 
 header('Content-type: text/html; charset=utf-8'); 
?>
<!DOCTYPE html>
<html>
	<head>
		<title>WvW Guild Listing</title>
		<meta http-equiv="refresh" content="60" >
	</head>
	<body>
		<h1 align="center">Guilds Currently Holding an Objective in WvW</h1>
				<?php
					$selected_world_name = "Isle of Janthir";
					
					// Find world ID associated with world name
					$world_names_url_base = "world_names.json";
					$world_names_list = gw2_api_request($world_names_url_base);
					foreach ($world_names_list as $world_name_entry) {
						if($world_name_entry['name']==$selected_world_name){
							$selected_world_id=$world_name_entry['id'];
						} 
					}
					// Get list of matches in progress
					$matches_url_base = "wvw/matches.json";
					$matches_info = gw2_api_request($matches_url_base);
					$matches_list = $matches_info['wvw_matches'];
					
					// Find which match selected world is in and set world ids for participating worlds
					foreach ($matches_list as $wvw_match){
						if($wvw_match['red_world_id']==$selected_world_id){
							$tier=$wvw_match['wvw_match_id'];
							$RedID = $selected_world_id;
							$BlueID = $wvw_match['blue_world_id'];
							$GreenID = $wvw_match['green_world_id'];
						} 
						elseif($wvw_match['blue_world_id']==$selected_world_id){
							$tier=$wvw_match['wvw_match_id'];
							$RedID = $wvw_match['red_world_id'];
							$BlueID = $selected_world_id;
							$GreenID = $wvw_match['green_world_id'];
						} 
						elseif($wvw_match['green_world_id']==$selected_world_id){
							$tier=$wvw_match['wvw_match_id'];
							$RedID = $wvw_match['red_world_id'];
							$BlueID = $wvw_match['blue_world_id'];
							$GreenID = $selected_world_id;
						}
					}
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
					
					// Get details of match that selected world is in
					$match_details_url_base="wvw/match_details.json?match_id=";
					$match_details_url=$match_details_url_base.$tier;
					$matchInfo = gw2_api_request($match_details_url);
					$mapsInfo = $matchInfo['maps'];
					
					// Initialize array and counting variables
					$redcnt=0;
					$greencnt=0;
					$bluecnt=0;
					
					$obj_names_array = array(
					"Overlook",
					"Valley",
					"Lowlands",
					"Golanta Clearing",
					"Pangloss Rise",
					"Speldan Clearcut",
					"Danelon Passage",
					"Umberglade Woods",
					"Stonemist Castle",
					"Rogue's Quarry",
					"Aldon's Ledge",
					"Wildcreek Run",
					"Jerrifer's Slough",
					"Klovan Gully",
					"Langor Gulch",
					"Quentin Lake",
					"Mendon's Gap",
					"Anzalias Pass",
					"Ogrewatch Cut",
					"Veloka Slope",
					"Durios Gulch",
					"Bravost Escarpment",
					"Garrison",
					"Chamion's Demense",
					"Redbriar",
					"Greenlake",
					"Ascension Bay",
					"Dawn's Eyrie",
					"The Spiritholme",
					"Woodhaven",
					"Askalion Hills",
					"Etheron Hills",
					"Dreaming Bay",
					"Victor's Lodge",
					"Greenbriar",
					"Bluelake",
					"Garrison",
					"Longview",
					"The Godsword",
					"Cliffside",
					"Shadaran Hills",
					"Redlake",
					"Hero's Lodge",
					"Dreadfall Bay",
					"Bluebriar",
					"Garrison",
					"Sunnyhill",
					"Faithleap",
					"Bluevale Refuge",
					"Bluewater Lowlands",
					"Astralholme",
					"Arah's Hope",
					"Greenvale Refuge",
					"Foghaven",
					"Redwater Lowlands",
					"The Titanpaw",
					"Cragtop",
					"Godslore",
					"Redvale Refuge",
					"Stargrove",
					"Greenwater Lowlands"
					);
					
					$guilds_claimed_red_ids = array();
					$guilds_claimed_red_names = array();
					$guilds_claimed_red_tags = array();
					$guilds_claimed_red_obj_ids = array();
					$guilds_claimed_red_obj_names = array();
					$guilds_claimed_red_map_names = array();
				
					$guilds_claimed_green_ids = array();
					$guilds_claimed_green_names = array();
					$guilds_claimed_green_tags = array();
					$guilds_claimed_green_obj_ids = array();
					$guilds_claimed_green_obj_names = array();
					$guilds_claimed_green_map_names = array();
					
					$guilds_claimed_blue_ids = array();
					$guilds_claimed_blue_names = array();
					$guilds_claimed_blue_tags = array();
					$guilds_claimed_blue_obj_ids = array();
					$guilds_claimed_blue_obj_names = array();
					$guilds_claimed_blue_map_names = array();
					
					// Find which objectives are claimed by guilds and populate arrays
					foreach ($mapsInfo as $holder){
						$ThisMapName = $holder['type'];
						foreach ($holder as $map_entry){
							foreach ($map_entry as $objective_entry){
								if(isset($objective_entry['owner_guild'])){
									if ($objective_entry['owner']=='Red') {
										$guilds_claimed_red_ids[$redcnt] = $objective_entry['owner_guild'];
										$guilds_claimed_red_names[$redcnt] = getNameFromId($objective_entry['owner_guild']);
										$guilds_claimed_red_tags[$redcnt] = getTagFromId($objective_entry['owner_guild']);
										$guilds_claimed_red_obj_ids[$redcnt] = $objective_entry['id'];
										$guilds_claimed_red_map_names[$redcnt] = $ThisMapName;
										$redcnt=$redcnt+1;
									}
									elseif($objective_entry['owner']=='Green'){
										$guilds_claimed_green_ids[$greencnt] = $objective_entry['owner_guild'];
										$guilds_claimed_green_names[$greencnt] = getNameFromId($objective_entry['owner_guild']);
										$guilds_claimed_green_tags[$greencnt] = getTagFromId($objective_entry['owner_guild']);
										$guilds_claimed_green_obj_ids[$greencnt] = $objective_entry['id'];
										$guilds_claimed_green_map_names[$greencnt] = $ThisMapName;
										$greencnt=$greencnt+1;
									}
									elseif($objective_entry['owner']=='Blue'){
										$guilds_claimed_blue_ids[$bluecnt] = $objective_entry['owner_guild'];
										$guilds_claimed_blue_names[$bluecnt] = getNameFromId($objective_entry['owner_guild']);
										$guilds_claimed_blue_tags[$bluecnt] = getTagFromId($objective_entry['owner_guild']);
										$guilds_claimed_blue_obj_ids[$bluecnt] = $objective_entry['id'];
										$guilds_claimed_blue_map_names[$bluecnt] = $ThisMapName;
										$bluecnt=$bluecnt+1;
									}
								}
							}
							
						}
					}
					
					//Convert held objective IDs to names
					for ($x=0; $x<$redcnt; $x++){
						$guilds_claimed_red_obj_names[$x] = $obj_names_array[$guilds_claimed_red_obj_ids[$x]-1];
						if($guilds_claimed_red_map_names[$x]=="RedHome"){
							$guilds_claimed_red_map_names[$x]=$RedName." Borderlands";
						} elseif($guilds_claimed_red_map_names[$x]=="GreenHome"){
							$guilds_claimed_red_map_names[$x]=$GreenName." Borderlands";
						} elseif($guilds_claimed_red_map_names[$x]=="BlueHome"){
							$guilds_claimed_red_map_names[$x]=$BlueName." Borderlands";
						} elseif($guilds_claimed_red_map_names[$x]=="Center"){
							$guilds_claimed_red_map_names[$x]="Eternal Battlegrounds";
						}
					}
					for ($x=0; $x<$greencnt; $x++){
						$guilds_claimed_green_obj_names[$x] = $obj_names_array[$guilds_claimed_green_obj_ids[$x]-1];
						if($guilds_claimed_green_map_names[$x]=="RedHome"){
							$guilds_claimed_green_map_names[$x]=$RedName." Borderlands";
						} elseif($guilds_claimed_green_map_names[$x]=="GreenHome"){
							$guilds_claimed_green_map_names[$x]=$GreenName." Borderlands";
						} elseif($guilds_claimed_green_map_names[$x]=="BlueHome"){
							$guilds_claimed_green_map_names[$x]=$BlueName." Borderlands";
						} elseif($guilds_claimed_green_map_names[$x]=="Center"){
							$guilds_claimed_green_map_names[$x]="Eternal Battlegrounds";
						}
					}
					for ($x=0; $x<$bluecnt; $x++){
						$guilds_claimed_blue_obj_names[$x] = $obj_names_array[$guilds_claimed_blue_obj_ids[$x]-1];
						if($guilds_claimed_blue_map_names[$x]=="RedHome"){
							$guilds_claimed_blue_map_names[$x]=$RedName." Borderlands";
						} elseif($guilds_claimed_blue_map_names[$x]=="GreenHome"){
							$guilds_claimed_blue_map_names[$x]=$GreenName." Borderlands";
						} elseif($guilds_claimed_blue_map_names[$x]=="BlueHome"){
							$guilds_claimed_blue_map_names[$x]=$BlueName." Borderlands";
						} elseif($guilds_claimed_blue_map_names[$x]=="Center"){
							$guilds_claimed_blue_map_names[$x]="Eternal Battlegrounds";
						}
					}
					
					// Create Connection
					$con=mysqli_connect("localhost","USERNAME","PASSWORD","DATABASE");

					// Check Connection
					if (mysqli_connect_errno($con))
						{
						echo "Failed to conect to MySQL: " . mysqli_connect_error();
						}
					
					$con->set_charset("utf8");
					
					//Red list
					for ($x=0; $x<$redcnt; $x++) {
						// Prepare SQL statement and query it
						$sql =  "SELECT guild_id,guild_name,guild_tag,guild_world FROM guild_table WHERE guild_id='$guilds_claimed_red_ids[$x]';";
						$result = mysqli_query($con,$sql) or die(mysqli_error());
						$row1= mysqli_fetch_array($result);
						
						//Check if guild id has entry in database
						if($row1== NULL){
							if (!mysqli_query($con,"INSERT INTO guild_table(guild_id,guild_name,guild_tag)VALUES('$guilds_claimed_red_ids[$x]','$guilds_claimed_red_names[$x]','$guilds_claimed_red_tags[$x]')")) {
							echo("Error description: " . mysqli_error($con));
							}
						}
						
						//Check if world already in database
						if($row1['guild_world'] == NULL){
								$guild_world=mysqli_real_escape_string($con,$RedName);
							if (!mysqli_query($con,"UPDATE guild_table SET guild_world='$guild_world' WHERE guild_id='$guilds_claimed_red_ids[$x]'")) {
								echo("Error description: " . mysqli_error($con));
								}
						}
					}
					
					//Green list
					for ($x=0; $x<$greencnt; $x++) {
						// Prepare SQL statement and query it
						$sql =  "SELECT guild_id,guild_name,guild_tag,guild_world FROM guild_table WHERE guild_id='$guilds_claimed_green_ids[$x]';";
						$result = mysqli_query($con,$sql) or die(mysqli_error());
						$row1= mysqli_fetch_array($result);
						
						//Check if guild id has entry in database
						if($row1== NULL){
							if (!mysqli_query($con,"INSERT INTO guild_table(guild_id,guild_name,guild_tag)VALUES('$guilds_claimed_green_ids[$x]','$guilds_claimed_green_names[$x]','$guilds_claimed_green_tags[$x]')")) {
							echo("Error description: " . mysqli_error($con));
							}
						}
						
						//Check if world already in database
						if($row1['guild_world'] == NULL){
								$guild_world=mysqli_real_escape_string($con,$GreenName);
							if (!mysqli_query($con,"UPDATE guild_table SET guild_world='$guild_world' WHERE guild_id='$guilds_claimed_green_ids[$x]'")) {
								echo("Error description: " . mysqli_error($con));
								}
						}
					}
					
					//Blue list
					for ($x=0; $x<$bluecnt; $x++) {
						// Prepare SQL statement and query it
						$sql =  "SELECT guild_id,guild_name,guild_tag,guild_world FROM guild_table WHERE guild_id='$guilds_claimed_blue_ids[$x]';";
						$result = mysqli_query($con,$sql) or die(mysqli_error());
						$row1= mysqli_fetch_array($result);
						
						//Check if guild id has entry in database
						if($row1== NULL){
							if (!mysqli_query($con,"INSERT INTO guild_table(guild_id,guild_name,guild_tag)VALUES('$guilds_claimed_blue_ids[$x]','$guilds_claimed_blue_names[$x]','$guilds_claimed_blue_tags[$x]')")) {
							echo("Error description: " . mysqli_error($con));
							}
						}
						
						//Check if world already in database
						if($row1['guild_world'] == NULL){
								$guild_world=mysqli_real_escape_string($con,$BlueName);
							if (!mysqli_query($con,"UPDATE guild_table SET guild_world='$guild_world' WHERE guild_id='$guilds_claimed_blue_ids[$x]'")) {
								echo("Error description: " . mysqli_error($con));
								}
						}
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
					?>
		
		
		
		<?php 
		// Find total number of guilds claiming something in our matchup and display
		$totcnt=$redcnt+$greencnt+$bluecnt;
		echo "<center><h3>There are currently ".$totcnt." guilds claiming in our matchup.</h3></center>";
		
		echo "<table align='center'><tr>";
		$redback="#FA323D";
		$greenback="green";
		$blueback="#3C7AFF";
		
		//Red world
		echo "<td valign='top'><table border='1' align='center'><tr><td bgcolor=".$redback." colspan='3' align='center'>".$RedName."</td></tr>";
		if ($redcnt>0){
			for ($x=0; $x<$redcnt; $x++){
			echo '
				<tr>
					<td>
						'.$guilds_claimed_red_names[$x].' ['.$guilds_claimed_red_tags[$x].']
					</td>
					<td bgcolor='.$redback.' width="256" height="256" background="http://www.wvwguilds.com/emblemforge2.php?guild_id='.$guilds_claimed_red_ids[$x].'">
					</td>
					<td>
						'.$guilds_claimed_red_obj_names[$x].' on <br>'.$guilds_claimed_red_map_names[$x].'
					</td>
				</tr>';
			}
		} else {
			echo "<tr><td colspan='3' align='center'>No ".$RedName." guilds have claimed anything.</td></tr>";
		}
		echo "</table></td>";
		
		//Green world
		echo "<td valign='top'><table border='1' align='center'><tr><td bgcolor=".$greenback." colspan='3' align='center'>".$GreenName."</td></tr>";
		if ($greencnt>0){
			for ($x=0; $x<$greencnt; $x++){
			echo '
				<tr>
					<td>
						'.$guilds_claimed_green_names[$x].' ['.$guilds_claimed_green_tags[$x].']
					</td>
					<td bgcolor='.$greenback.' width="256" height="256" background="http://www.wvwguilds.com/emblemforge2.php?guild_id='.$guilds_claimed_green_ids[$x].'">
					</td>
					<td>
						'.$guilds_claimed_green_obj_names[$x].' on <br>'.$guilds_claimed_green_map_names[$x].'
					</td>
				</tr>';
			}
		} else {
			echo "<tr><td colspan='3' align='center'>No ".$GreenName." guilds have claimed anything.</td></tr>";
		}
		echo "</table></td>";
		
		//Blue world
		echo "<td valign='top'><table border='1' align='center'><tr><td bgcolor=".$blueback." colspan='3' align='center'>".$BlueName."</td></tr>";
		if ($bluecnt>0){
			for ($x=0; $x<$bluecnt; $x++){
			echo '
				<tr>
					<td>
						'.$guilds_claimed_blue_names[$x].' ['.$guilds_claimed_blue_tags[$x].']
					</td>
					<td bgcolor='.$blueback.' width="256" height="256" background="http://www.wvwguilds.com/emblemforge2.php?guild_id='.$guilds_claimed_blue_ids[$x].'">
					</td>
					<td>
						'.$guilds_claimed_blue_obj_names[$x].' on <br>'.$guilds_claimed_blue_map_names[$x].'
					</td>
				</tr>';
			}
		} else {
			echo "<tr><td colspan='3' align='center'>No ".$BlueName." guilds have claimed anything.</td></tr>";
		}
		echo "</table></td>";
		echo "</tr></table>";
		 ?>

		<center><a href="http://wvwguilds.com/allguilds.php">Show all guilds in database</a></center>
	</body>
</html>

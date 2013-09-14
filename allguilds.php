<?php 
 header('Content-type: text/html; charset=utf-8'); 
?>
<!DOCTYPE html>
<html>
	<head>
		<title>WvW Guild Listing Database</title>
	</head>
	<body>
		<center><a href="http://wvwguilds.com">Go back to list of guilds currently claiming</a></center>
		<h1 align="center">All Guilds In Database</h1>
		<?php
		$SelectedServer = $_POST["ServerChoice"];
		?>
		
		<?php
		// If page has been submitted to itself run the code
		if(isset($_POST['submit'])){	
			// Initialize array and counting variables
			$cnt=0;
			$guilds_claimed_ids = array();
			$guilds_claimed_names = array();
			$guilds_claimed_colors = array();
			$guilds_claimed_worlds = array();
			$guilds_claimed_tags = array();
			
			// Create Connection
			$con=mysqli_connect("localhost","USERNAME","PASSWORD","DATABASE");

			// Check Connection
			if (mysqli_connect_errno($con))
				{
				echo "Failed to conect to MySQL: " . mysqli_connect_error();
				}
			$con->set_charset("utf8");
			
			$SelectedServerE=mysqli_real_escape_string($con,$SelectedServer);
			// Prepare SQL statement and query it
			$sql =  "SELECT guild_name, guild_id, guild_world, guild_tag FROM guild_table WHERE guild_world LIKE '$SelectedServerE' ORDER BY guild_world, guild_name;";
			$result = mysqli_query($con,$sql) or die(mysqli_error());
			
			// Find the number of entries in the database
			$sql2 = "SELECT COUNT(*) FROM guild_table;";
			$result2 = mysqli_query($con,$sql2) or die(mysqli_error());
			$data2 = mysqli_fetch_row($result2);
			$row2 = $data2[0];
			
			// Populate arrays with guild entries
			while ($row1= mysqli_fetch_array($result,MYSQL_ASSOC)){
				$guilds_claimed_ids[$cnt] = $row1['guild_id'];
				$guilds_claimed_names[$cnt] = $row1['guild_name'];
				$guilds_claimed_worlds[$cnt] = $row1['guild_world'];
				$guilds_claimed_tags[$cnt] = $row1['guild_tag'];
				$cnt=$cnt+1;
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
			
			echo '<center><h3>There are currently '.$row2.' guilds in the database.';
			if($SelectedServer!='%'){
				echo ' '.$cnt.' of those guilds are on '.$SelectedServer.'.';
			}
			echo ' It may take a bit to load. Thank you for your patience.</h3></center>';
			echo '<table border="1" align="center">'; 
			echo '
			<tr>
				<td align="center">Guild Name [TAG]
				</td>
				<td align="center">Guild Emblem
				</td>
				<td align="center">Guild World
				</td>
			</tr>';
			
			// Create list of guilds in table
			for ($x=0; $x<$cnt; $x++) {
				echo '
					<tr>
						<td>
							'.$guilds_claimed_names[$x].' ['.$guilds_claimed_tags[$x].']
						</td>
						<td width="256" height="256" background="http://www.wvwguilds.com/emblemforge2.php?guild_id='.$guilds_claimed_ids[$x].'">
						</td>
						<td>
							'.$guilds_claimed_worlds[$x].'
						</td>
					</tr>';
			}
			echo '</table>';
		} else { // If page has not been submitted to itself then present choices
		?>
		<center>
		<form method="post" action="<?php echo $PHP_SELF;?>">
		Choose a server to view guilds:
		<select name="ServerChoice">
		<option value="%">All</option>
		<option value="Anvil Rock">Anvil Rock</option>
		<option value="Blackgate">Blackgate</option>
		<option value="Borlis Pass">Borlis Pass</option>
		<option value="Crystal Desert">Crystal Desert</option>
		<option value="Darkhaven">Darkhaven</option>
		<option value="Devona's Rest">Devona's Rest</option>
		<option value="Dragonbrand">Dragonbrand</option>
		<option value="Ehmry Bay">Ehmry Bay</option>
		<option value="Eredon Terrace">Eredon Terrace</option>
		<option value="Ferguson's Crossing">Ferguson's Crossing</option>
		<option value="Fort Aspenwood">Fort Aspenwood</option>
		<option value="Gate of Madness">Gate of Madness</option>
		<option value="Henge of Denravi">Henge of Denravi</option>
		<option value="Isle of Janthir">Isle of Janthir</option>
		<option value="Jade Quarry">Jade Quarry</option>
		<option value="Kaineng">Kaineng</option>
		<option value="Maguuma">Maguuma</option>
		<option value="Northern Shiverpeaks">Northern Shiverpeaks</option>
		<option value="Sanctum of Rall">Sanctum of Rall</option>
		<option value="Sea of Sorrows">Sea of Sorrows</option>
		<option value="Sorrow's Furnace">Sorrow's Furnace</option>
		<option value="Stormbluff Isle">Stormbluff Isle</option>
		<option value="Tarnished Coast">Tarnished Coast</option>
		<option value="Yak's Bend">Yak's Bend</option>
		</select>
		<input type="submit" value="submit" name="submit">
		</center>
		</form>
		<?php
		}
		?>

	</body>
</html>

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
					
					// Prepare SQL statement and query it
					$sql =  "SELECT guild_name,guild_id, guild_world, guild_tag FROM guild_table ORDER BY guild_world,guild_name;";
					$result = mysqli_query($con,$sql) or die(mysqli_error());
					
					// Populate arrays with guild entries
					while ($row1= mysqli_fetch_array($result,MYSQL_ASSOC)){
						$guilds_claimed_ids[$cnt] = $row1['guild_id'];
						$guilds_claimed_names[$cnt] = $row1['guild_name'];
						$guilds_claimed_worlds[$cnt] = $row1['guild_world'];
						$guilds_claimed_tags[$cnt] = $row1['guild_tag'];
						$cnt=$cnt+1;
					}				
					
					// Find the number of entries in the database
					$sql2 = "SELECT COUNT(*) FROM guild_table;";
					$result2 = mysqli_query($con,$sql) or die(mysqli_error());
					$row2 = mysqli_num_rows($result2);
					
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
		echo '<center><h3>There are currently '.$row2.' guilds in the database. It may take a bit to load. Thank you for your patience.</h3></center>';
		?>
		<table border="1" align="center">
		
		<?php 
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
		} ?>

		</table>
	</body>
</html>

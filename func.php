<?php

//****************** EASYTCG FM FUNCTION LIBRARY ******************//

// CHANGE SETTINGS BELOW TO MATCH YOUR EASYTCG FM DATABASE SETTINGS
class Config {

	const DB_SERVER = 'localhost', // In most cases, you can leave this as 'localhost'. If you're unsure, check with your host.
		  DB_USER = 'dbuser', // Database user
		  DB_PASSWORD = 'dbpassword', // Database user password
		  DB_DATABASE = 'dbdatabase'; // Database name

}
// DO NOT EDIT PAST THIS LINE UNLESS YOU KNOW WHAT YOU'RE DOING

class Sanitize {
	
	function clean ($data) {
			
		if ( get_magic_quotes_gpc() ) { $data = stripslashes($data); }
		
		$data = trim(htmlentities(strip_tags($data)));
		
		return $data;
	
	}
	
	function for_db ($data) {
		
		Database::connect();
		
		$data = $this->clean($data);
		
		$data = mysql_real_escape_string($data);
		
		return $data;
			
	}
	
}

class Database {
	
	function connect () {
	
		@mysql_connect( Config::DB_SERVER , Config::DB_USER , Config::DB_PASSWORD )
		or die( "Couldn't connect to MYSQL: ".mysql_error() );
		@mysql_select_db( Config::DB_DATABASE )
		or die ( "Couldn't open $db_datase: ".mysql_error() );
		
		return true;
		
	}
	
	function query ($query) {
		
		@mysql_connect( Config::DB_SERVER , Config::DB_USER , Config::DB_PASSWORD )
		or die( "Couldn't connect to MYSQL: ".mysql_error() );
		@mysql_select_db( Config::DB_DATABASE )
		or die ( "Couldn't open $db_datase: ".mysql_error() );
		
		$result = mysql_query($query);
		
		return $result;
		
	}
	
	function get_assoc ($query) {
		
		@mysql_connect( Config::DB_SERVER , Config::DB_USER , Config::DB_PASSWORD )
		or die( "Couldn't connect to MYSQL: ".mysql_error() );
		@mysql_select_db( Config::DB_DATABASE )
		or die ( "Couldn't open $db_datase: ".mysql_error() );
		
		$result = mysql_query($query);
		
		if ( !$result ) { die ( "Couldn't process query: ".mysql_error() ); }
		
		$assoc = mysql_fetch_assoc($result);
		
		return $assoc;
		
	}
	
	function get_array ($query) {
		
		@mysql_connect( Config::DB_SERVER , Config::DB_USER , Config::DB_PASSWORD )
		or die( "Couldn't connect to MYSQL: ".mysql_error() );
		@mysql_select_db( Config::DB_DATABASE )
		or die ( "Couldn't open $db_datase: ".mysql_error() );
		
		$result = mysql_query($query);
		
		if ( !$result ) { die ( "Couldn't process query: ".mysql_error() ); }
		
		$array = mysql_fetch_array($result);
		
		return $array;
		
	}
	
	function num_rows ($query) {
	
		@mysql_connect( Config::DB_SERVER , Config::DB_USER , Config::DB_PASSWORD )
		or die( "Couldn't connect to MYSQL: ".mysql_error() );
		@mysql_select_db( Config::DB_DATABASE )
		or die ( "Couldn't open $db_datase: ".mysql_error() );
		
		$result = mysql_query($query);
		
		if ( !$result ) { die ( "Couldn't process query: ".mysql_error() ); }
		
		$num_rows = mysql_num_rows($result);
		
		return $num_rows;
	
	}
	
}

// Returns contents of the specified log. $tcg = the name of the TCG as defined in the database; $type = activitylog, activitylogarch, tradelog, or tradelogarch.
function get_logs( $tcg, $type ) {
	
	if ( $type == 'activitylog' || $type == 'activitylogarch' || $type == 'tradelog' || $type == 'tradelogarch' ) {
		$database = new Database;
		$sanitize = new Sanitize;
		$tcg = $sanitize->for_db($tcg);
		
		$log = $database->get_assoc("SELECT `$type` FROM `tcgs` WHERE `name`='$tcg' LIMIT 1");
		return $log[$type];
	}
	else {
		return false;	
	}

}

// Returns the value of an additional field. $tcg = the name of the TCG as defined in the database; $fieldname = the name of the additional field.
function get_additional( $tcg, $fieldname ) {

	$database = new Database;
	$sanitize = new Sanitize;
	$tcg = $sanitize->for_db($tcg);
	$fieldname = $sanitize->for_db($fieldname);
	
	$result = $database->get_assoc("SELECT `id` FROM `tcgs` WHERE `name`='$tcg' LIMIT 1");
	$tcgid = $result['id'];
	
	$result = $database->get_assoc("SELECT `value` FROM `additional` WHERE `tcg`='$tcgid' AND `name`='$fieldname'");
	return $result['value'];

}

// Show cards from the given category. $tcg = the name of the TCG as defined in the database; $category = the card category to display.
function show_cards( $tcg, $category, $unique = 0 ) {

	$database = new Database;
	$sanitize = new Sanitize;
	$tcg = $sanitize->for_db($tcg);
	$category = $sanitize->for_db($category);
	$altname = strtolower(str_replace(' ','',$tcg));
	
	$tcginfo = $database->get_assoc("SELECT * FROM `tcgs` WHERE `name`='$tcg' LIMIT 1");
	$tcgid = $tcginfo['id'];
	$cardsurl = $tcginfo['cardsurl'];
	$format = $tcginfo['format'];
	
	$cards = $database->get_assoc("SELECT `cards` FROM `cards` WHERE `tcg`='$tcgid' AND `category`='$category' LIMIT 1");
	
	if ( $cards['cards'] === '' ) { echo '<p><em>There are currently no cards under this category.</em></p>'; }
	else {
		
		$cards = explode(',',$cards['cards']);
		if ( $unique == 1 ) { $cards = array_unique($cards); }
	
		foreach ( $cards as $card ) {
			
			$card = trim($card);
			echo '<img src="'.$cardsurl.''.$card.'.'.$format.'" alt="" title="'.$card.'" /> ';
		
		}
		
	}

}

function show_doubles( $tcg, $category ) {

	$database = new Database;
	$sanitize = new Sanitize;
	$tcg = $sanitize->for_db($tcg);
	$category = $sanitize->for_db($category);
	$altname = strtolower(str_replace(' ','',$tcg));
	
	$tcginfo = $database->get_assoc("SELECT * FROM `tcgs` WHERE `name`='$tcg' LIMIT 1");
	$tcgid = $tcginfo['id'];
	$cardsurl = $tcginfo['cardsurl'];
	$format = $tcginfo['format'];
	
	$cards = $database->get_assoc("SELECT `cards` FROM `cards` WHERE `tcg`='$tcgid' AND `category`='$category' LIMIT 1");
	
	if ( $cards['cards'] === '' ) { echo '<p><em>There are currently no cards under this category.</em></p>'; }
	else {
		
		$cards = explode(',',$cards['cards']);
		$doubles = array_diff_assoc($cards, array_unique($cards));

		if ( !empty($doubles) ) {
			
			foreach ( $doubles as $double ) {
			
				$double = trim($double);
				echo '<img src="'.$cardsurl.''.$double.'.'.$format.'" alt="" title="'.$double.'" /> ';
			
			}
		
		}
		else {
		
			echo '<p><em>There are currently no cards under this category.</em></p>';
		
		}
		
	}

}

function trim_value(&$value) { $value = trim($value); }

// Show all collecting decks, or optionally show collecting decks by worth or deck name. $tcg = the name of the TCG as defined in the database; $worth = card worth; $deckname = name of a collecting deck.
function show_collecting($tcg, $worth = '', $deckname = '') {
	
	$database = new Database;
	$sanitize = new Sanitize;
	$tcg = $sanitize->for_db($tcg);
	
	$tcginfo = $database->get_assoc("SELECT * FROM `tcgs` WHERE `name`='$tcg' LIMIT 1");
	$tcgid = $tcginfo['id'];
	$cardsurl = $tcginfo['cardsurl'];
	$format = $tcginfo['format'];
	
	if ( $worth !== '' ) { $worth = intval($worth); }
	if ( $deckname !== '' ) { $deckname =  $sanitize->for_db($deckname); }
	
	if ( $worth !== '' ) { $result = $database->query("SELECT * FROM `collecting` WHERE `tcg` = '$tcgid' AND `mastered` = '0' AND `worth` = '$worth' ORDER BY `sort`, `deck`"); }
	else if ( $deckname !== '' ) { $result = $database->query("SELECT * FROM `collecting` WHERE `tcg` = '$tcgid' AND `mastered` = '0' AND `deck` = '$deckname' ORDER BY `sort`, `worth`"); }
	else { $result = $database->query("SELECT * FROM `collecting` WHERE `tcg` = '$tcgid' AND `mastered` = '0' ORDER BY `sort`, `worth`, `deck`"); }
	while ( $row = mysql_fetch_assoc($result) ) { 
		$cards = explode(',',$row['cards']); 
			
		array_walk($cards, 'trim_value');
		
		if ( $row['cards'] == '' ) { $count = 0; } else { $count = count($cards); }
		?>
    	
        <br />
		<h2><?php echo $row['deck']; ?> (<?php echo $count; ?>/<?php echo $row['count']; ?>)</h2>
        <p align="center">
        	<?php
				for ( $i = 1; $i <= $row['count']; $i++ ) {
					
					$number = $i;
					if ( $number < 10 ) { $number = "0$number"; }
					$card = "".$row['deck']."$number";
					
					$pending = $database->num_rows("SELECT * FROM `trades` WHERE `tcg`='$tcgid' AND `receiving` LIKE '%$card%'");
					
					if ( in_array($card, $cards) ) echo '<img src="'.$tcginfo['cardsurl'].''.$card.'.'.$tcginfo['format'].'" alt="" title="'.$card.'" />';
					else if ( $pending > 0 ) { echo '<img src="'.$tcginfo['cardsurl'].''.$row['pending'].'.'.$tcginfo['format'].'" alt="" title="'.$card.'" />'; }
					else { echo '<img src="'.$tcginfo['cardsurl'].''.$row['filler'].'.'.$tcginfo['format'].'" alt="" />'; }
					
					if ( $row['puzzle'] == 0 ) { echo ' '; }
					if ( $row['break'] !== '0' && $i % $row['break'] == 0 ) { echo '<br />'; }
					
				}
			?>
        </p>
        
        <?php 
	}
	
}

// Show all mastered decks (as badges), or optionally show mastered decks by worth or deck name. $tcg = the name of the TCG as defined in the database; $worth = card worth; $deckname = name of a mastered deck.
function show_mastered($tcg, $worth = '', $deckname = '') {
	
	$database = new Database;
	$sanitize = new Sanitize;
	$tcg = $sanitize->for_db($tcg);
	
	$tcginfo = $database->get_assoc("SELECT * FROM `tcgs` WHERE `name`='$tcg' LIMIT 1");
	$tcgid = $tcginfo['id'];
	$cardsurl = $tcginfo['cardsurl'];
	$format = $tcginfo['format'];
	
	if ( $worth !== '' ) { $worth = intval($worth); }
	if ( $deckname !== '' ) { $deckname =  $sanitize->for_db($deckname); }
	
	if ( $worth !== '' ) { $result = $database->query("SELECT * FROM `collecting` WHERE `tcg` = '$tcgid' AND `mastered` = '1' AND `worth` = '$worth' ORDER BY `mastereddate`"); }
	else if ( $deckname !== '' ) { $result = $database->query("SELECT * FROM `collecting` WHERE `tcg` = '$tcgid' AND `mastered` = '1' AND `deck` = '$deckname' ORDER BY `mastereddate`"); }
	else { $result = $database->query("SELECT * FROM `collecting` WHERE `tcg` = '$tcgid' AND `mastered` = '1' ORDER BY `mastereddate`"); }
	while ( $row = mysql_fetch_assoc($result) ) { 
		
		$mastered = date('F d, Y', strtotime($row['mastereddate']));
		if ( $row['badge'] !== '' ) { echo '<img src="'.$tcginfo['cardsurl'].''.$row['badge'].'" alt="" title="Mastered '.$mastered.'" /> '; }
		else { echo ''.$row['deck'].' '; }
		
	}
	
}

// Show all pending trades. $tcg = the name of the TCG as defined in the database.
function show_pending($tcg) {

	$database = new Database;
	
	$tcginfo = $database->get_assoc("SELECT * FROM `tcgs` WHERE `name`='$tcg' LIMIT 1");
	$tcgid = $tcginfo['id'];
	$cardsurl = $tcginfo['cardsurl'];
	$format = $tcginfo['format'];
	
	$result = $database->query("SELECT * FROM `trades` WHERE `tcg`='$tcgid' ORDER BY `date`,`trader`");
	while ( $row = mysql_fetch_assoc($result) ) {
		
		$giving = explode(',',str_replace(';',',',$row['giving']));
		$receiving = str_replace(';',',',$row['receiving']);
		
		echo '- <strong>'.$row['trader'].'</strong> (pending since <em>'.date('F d, Y', strtotime($row['date'])).'</em>) <br />';
		if ( $receiving !== '' ) { echo ''.$receiving.' <br />'; }
		
		foreach( $giving as $card ) {
		
			$card = trim($card);
			echo '<img src="'.$cardsurl.''.$card.'.'.$format.'" alt="" title="'.$card.'" /> ';
		
		}
		
		echo '<br /><br />';
	
	}

}


// Generate a total card count/worth of all cards in the collection, or by category. $tcg = the name of the TCG as defined in the database; $type = worth OR empty to ignore worth; $cat = 'CATEGORY NAME' OR 'collecting' OR 'mastered' OR 'pending' OR empty to count all cards in collection
function cardcount ($tcg,$type = '',$cat = '') {

	$database = new Database;
	
	$tcginfo = $database->get_assoc("SELECT * FROM `tcgs` WHERE `name`='$tcg' LIMIT 1");
	$tcgid = $tcginfo['id'];
	
	// Count categories
	$result = $database->query("SELECT `category`,`cards`,`worth` FROM `cards` WHERE `tcg`='$tcgid'");
	while ( $row = mysql_fetch_assoc($result) ) {
	
		$categories[] = $row['category'];
		
		if ( $row['cards'] === '' ) { $$row['category'] = 0; } else { $cards = explode(',',$row['cards']); $$row['category'] = count($cards); }
		if ( $type === 'worth' ) { $$row['category'] = $$row['category'] * $row['worth']; }
		
		$total = $total + $$row['category'];
	
	}
	
	// Count collecting
	$result = $database->query("SELECT `cards`,`worth` FROM `collecting` WHERE `tcg`='$tcgid' AND `mastered`='0'");
	while ( $row = mysql_fetch_assoc($result) ) {
		
		if ( $row['cards'] !== '' ) { 
			$cards = explode(',',$row['cards']); 
			if ( $type === 'worth' ) { $collecting = $collecting + count($cards) * $row['worth']; }
			else { $collecting = $collecting + count($cards); }
		}
	
	}
	
	$total = $total + $collecting;
	
	// Count mastered
	$result = $database->query("SELECT `cards`,`worth` FROM `collecting` WHERE `tcg`='$tcgid' AND `mastered`='1'");
	while ( $row = mysql_fetch_assoc($result) ) {
		
		if ( $row['cards'] !== '' ) { 
			$cards = explode(',',$row['cards']); 
			if ( $type === 'worth' ) { $mastered = $mastered + count($cards) * $row['worth']; }
			else { $mastered = $mastered + count($cards); }
		}
	
	}
	
	$total = $total + $mastered;
	
	// Count pending
	$result = $database->query("SELECT `giving`,`givingcat` FROM `trades` WHERE `tcg`='$tcgid'");
	while ( $row = mysql_fetch_assoc($result) ) {
		
		if ( $row['giving'] !== '' ) { 
			$cardgroups = explode(';',$row['giving']); 
			$cardcats = explode(',',$row['givingcat']); 
			array_walk($cardcats, 'trim_value');
			
			$i = 0;
			foreach ( $cardgroups as $group ) {
			
				$group = explode(',',$group);
				array_walk($group, 'trim_value');
				if ( $cardcats[$i] === 'collecting' ) {		
					$exists = $database->num_rows("SELECT `worth` FROM `collecting` WHERE `tcg`='$tcgid' AND `mastered`='0' AND `deck` LIKE '%".$group[0]."%'");
					if ( $exists > 0 ) {
						$groupworth = $database->get_assoc("SELECT `worth` FROM `collecting` WHERE `tcg`='$tcgid' AND `mastered`='0' AND `deck` LIKE '%".$group[0]."%' LIMIT 1");
						if ( $type === 'worth' ) { $pending = $pending + count($group) * $groupworth['worth']; } else { $pending = $pending + count($group); }
					}
					else {
						$pending = $pending + count($group);
					}
				}
				else {
					$groupworth = $database->get_assoc("SELECT `worth` FROM `cards` WHERE `tcg`='$tcgid' AND `category`='".$cardcats[$i]."'");
					if ( $type === 'worth' ) { $pending = $pending + count($group) * $groupworth['worth']; } else { $pending = $pending + count($group); }
				}
				
				$i++;
			
			}
		}
	
	}
	
	$total = $total + $pending;
	
	if ( $cat === '' ) { return $total; }
	else { return $$cat; }

}

?>
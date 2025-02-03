<?php
// bot: @pp_npuzzle_bot
// name: 🧩 Npuzzle | Пятнашки
// about: 
// 🧩 Npuzzle game
// Made by @Stler
// desc: 
// 🧩 Welcome to Npuzzle!
// Made just for fun by @Stler
// commands: 
// game - Start new game
// help - Show guide
// lang - Change language
// settings - Show settings menu
// links - Show games links
// main - Show main menu

// $dblink = mysqli_connect($dbhost, $dbuser, $dbpswd, $dbname);
// $dbdrop = "DROP TABLE `".$tbname."`";
// if (mysqli_query($dblink, $dbdrop))
// 	echo "<br>Table dropped";
// else echo mysqli_error($dblink);
// $dbcreate = "CREATE TABLE `".$tbname."` (
// `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, 
// `chat_id` TEXT NOT NULL, `msg_id` BIGINT UNSIGNED, 
// `user_name` TEXT, `user_lang` VARCHAR(10), 
// `size` TINYINT, `nline` TEXT, 
// PRIMARY KEY (`id`)) ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;";
// if (mysqli_query($dblink, $dbcreate))
// echo "<br>Table created";
// else echo mysqli_error($dblink);
// mysqli_close($dblink);

// globals
require_once "connect.php";
$dblink = mysqli_connect($dbhost, $dbuser, $dbpswd, $dbname);
$tbname .= $suffix["npz"];
$bottoken = $tokens["npz"];
require_once "api_bd_menu.php";
$lang = json_decode(file_get_contents("languages.json"), true);
$flags = ["en" => "🇬🇧", "ru" => "🇷🇺"];
$menus = ["main" => [["menu-new"], ["menu-hlp", "menu-links", "menu-set"]],
	"chus-size" => [["3", "4", "5", "6", "7", "8"], ["set-back"]],
	"set" => [["menu-size", "menu-lang"], ["main-back"]]];

// game keyboard
function drawTiles($tiles) {
	$tsize = sqrt(count($tiles));
	$pos_space = array_search(0, $tiles);
	$gkbd = []; $t = 0;
	for ($i = 1; $i <= $tsize; $i++) {
		$gstr = [];
		for ($j = 1; $j <= $tsize; $j++) {
			$text = ($tiles[$t] == 0) ? "  " : $tiles[$t];
			$cb = "-"; if (
				($t == ($pos_space - 1) && $t % $tsize != $tsize - 1) || // left border
				($t == ($pos_space + 1) && $t % $tsize != 0) ||          // right border
				($t == ($pos_space - $tsize)) ||                         // top tile
				($t == ($pos_space + $tsize))                            // bottom tile
			) { $cb = $t; }
			$gstr[] = ["text" => $text, "callback_data" => $cb];
			$t++;
		} $gkbd[] = $gstr;
	} return json_encode(["inline_keyboard" => $gkbd]);
}

// get user request
$content = file_get_contents("php://input");
$input = json_decode($content, TRUE);

// user press button
if (isset($input["callback_query"])) {
	$cb_id = $input["callback_query"]["id"];
	$response = trequest("answerCallbackQuery", ["callback_query_id" => $cb_id]);
	$chat_id = $input["callback_query"]["message"]["chat"]["id"];
	$cb_data = $input["callback_query"]["data"];

	if ($cb_data != "-") {
		// get data from db
		$result_usr = select_data($chat_id);
		$row = mysqli_fetch_assoc($result_usr);
		$msg_id = $row["msg_id"]; $user_lang = $row["user_lang"];
		$ul = (array_key_exists($user_lang, $lang)) ? $user_lang : "ru";
		mysqli_free_result($result_usr);
		$tiles = json_decode($row["nline"], true);
		$tsize = sqrt(count($tiles));

		// rebuild game array
		$pos_space = array_search(0, $tiles);
		list($tiles[$cb_data], $tiles[$pos_space]) = array($tiles[$pos_space], $tiles[$cb_data]);
		update_data($chat_id, ["nline" => $tiles]);

		// game over is all numbers in natural order
		$gameover = range(1, count($tiles)-1); $gameover[] = 0;

		// redraw game keyboard
		trequest("editMessageText", ["chat_id" => $chat_id, "message_id" => $msg_id, 
			"text" => ($tiles == $gameover) ? $lang[$ul]["game-win"] : $lang[$ul]["game-15"].$tsize."x".$tsize, 
			"reply_markup" => drawTiles($tiles)]);
	}

// user send msg
} elseif (isset($input["message"])) {
	$chat_id = $input["message"]["chat"]["id"];
	$result_usr = select_data($chat_id);
	// user new -> insert to db
	if (mysqli_num_rows($result_usr) <= 0) {
		$user_lang = $input["message"]["from"]["language_code"];
		$ul = (array_key_exists($user_lang, $lang)) ? $user_lang : "ru";
		$fn = isset($input["message"]["from"]["first_name"]) ? $input["message"]["from"]["first_name"] : "";
		$ln = isset($input["message"]["from"]["last_name"]) ? $input["message"]["from"]["last_name"] : "";
		$user_name = $fn." ".$ln; $size = 3;
		$stmt = $dblink->prepare("INSERT INTO ".$tbname." (chat_id, user_lang, user_name, size) VALUES (?, ?, ?, ?)");
		$stmt->bind_param("sssi", $chat_id, $ul, $user_name, $size); $stmt->execute(); $stmt->close();
		trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["hi1"].$input["message"]["from"]["first_name"].$lang[$ul]["hi2"], 
			"reply_markup" => draw_menu($lang[$ul], "main")]);

	// user exists
	} else {
		$row = mysqli_fetch_assoc($result_usr);
		$user_lang = $row["user_lang"];
		$ul = (array_key_exists($user_lang, $lang)) ? $user_lang : "ru";
		$lkeys = array_keys($lang); $flag_lang = [];
		foreach ($lkeys as $lkey) $flag_lang[] = $flags[$lkey]." ".$lkey;
		
		$user_msg = trim($input["message"]["text"]);
		switch ($user_msg) {
			// ask & save size {
			// settings menu -> size
			case $lang[$ul]["menu-size"]: {
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["chus-size"], 
					"reply_markup" => draw_menu($lang[$ul], "chus-size")]);
				break;
			} // size menu -> 3 / 4 / 5 / 6 / 7 / 8
			case $lang[$ul]["3"]: case $lang[$ul]["4"]: case $lang[$ul]["5"]: 
			case $lang[$ul]["6"]: case $lang[$ul]["7"]: case $lang[$ul]["8"]: {
				$tsize = (int)$user_msg;
				update_data($chat_id, ["size" => $tsize]);
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["size-saved"], 
					"reply_markup" => draw_menu($lang[$ul], "main")]);
				break;
			} // ask & save size }

			// main menu -> new game
			case "/game": case "/game@pp_npuzzle_bot": case $lang[$ul]["menu-new"]: {
				// make new game array
				$tsize = $row["size"];
				$tiles = range(1, $tsize*$tsize);
				shuffle($tiles);

				// counting parity
				$parity = 0;
				for ($t = 2; $t < $tsize*$tsize; $t++) {
					$pos = array_search($t, $tiles);
					for ($p = $pos+1; $p < $tsize*$tsize; $p++)
					if ($tiles[$p] < $t)
					$parity++;
				} // change max to space
				$pos_max = array_search($tsize*$tsize, $tiles);
				$tiles[$pos_max] = 0;
				if ($tsize % 2 == 0) $parity += (floor($pos_max / $tsize) + 1);
				if ($parity % 2 != 0) // swap 2 last tiles if unsolving
					if ($pos_max < 2) { list($tiles[$pos_max+1], $tiles[$pos_max+2]) = array($tiles[$pos_max+2], $tiles[$pos_max+1]); }
					else { list($tiles[0], $tiles[1]) = array($tiles[1], $tiles[0]); }

				// draw game keyboard
				$answer = trequest("sendMessage", ["chat_id" => $chat_id, 
					"text" => $lang[$ul]["game-15"].$tsize."x".$tsize,
					"reply_markup" => drawTiles($tiles)]);
				$tresponse = json_decode($answer, true);
				$msg_id = $tresponse["result"]["message_id"];
				update_data($chat_id, ["nline" => $tiles, "msg_id" => $msg_id]);
				break;
			}

			// main menu -> help
			case "/help": case $lang[$ul]["menu-hlp"]: {
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["help-npuzzle"]
					.$lang[$ul]["contact"].$lang[$ul]["github"], 
					"parse_mode" => "Markdown", "reply_markup" => draw_menu($lang[$ul], "main")]);
				break;
			}
			
			// basic functionality {
			case "/start": {
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["hi1"].$input["message"]["from"]["first_name"].$lang[$ul]["hi2"], 
					"reply_markup" => draw_menu($lang[$ul], "main")]);
				break;
			}

			// main menu
			case "/main": case $lang[$ul]["main-back"]: {
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["main-ttl"], 
					"reply_markup" => draw_menu($lang[$ul], "main")]);
				break;
			}

			// main menu -> game links
			case "/links": case $lang[$ul]["menu-links"]: {
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["game-links"], 
					"reply_markup" => game_links()]);
				break;
			}

			// main menu -> settings
			case "/settings": case $lang[$ul]["menu-set"]: case $lang[$ul]["set-back"]: {
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["set-ttl"], 
					"reply_markup" => draw_menu($lang[$ul], "set")]);
				break;
			}

			// settings menu -> language ask
			case "/lang": case $lang[$ul]["menu-lang"]: {
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["lang-ask"],
					"reply_markup" => json_encode(["keyboard" => [$flag_lang, [$lang[$ul]["set-back"]]], "resize_keyboard" => true])]);
				break;
			} // settings menu -> language set
			case in_array($user_msg, $flag_lang): {
				$parts = explode(" ", $user_msg);
				$flag = $parts[0]; $lang_code = $parts[1];
				if (in_array($flag, $flags) && array_key_exists($lang_code, $flags) && $flags[$lang_code] === $flag) {
					$ul = $lang_code;
					update_data($chat_id, ["user_lang" => $ul]);
					trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["lang-ok"],
						"reply_markup" => draw_menu($lang[$ul], "main")]);
				} break;
			}

			default:
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["default"], 
					"reply_markup" => draw_menu($lang[$ul], "main")]);
			// basic functionality }
		}
	} mysqli_free_result($result_usr);
} mysqli_close($dblink); ?>
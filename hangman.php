<?php
// bot: @pp_hangman_bot 
// name: 😵 Hangman | Виселица
// about: 
// 😵 Hangman game
// Made by @Stler
// desc: 
// 😵 Welcome to Hangman!
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
// `complex` TINYINT, `lives` TINYINT, `word` TEXT, `guess` TEXT, `letters` TEXT, 
// PRIMARY KEY (`id`)) ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;";
// if (mysqli_query($dblink, $dbcreate))
// echo "<br>Table created";
// else echo mysqli_error($dblink);
// mysqli_close($dblink);

// globals
require_once "connect.php";
$dblink = mysqli_connect($dbhost, $dbuser, $dbpswd, $dbname);
$tbname .= $suffix["hng"];
$bottoken = $tokens["hng"];
require_once "api_bd_menu.php";
$lang = json_decode(file_get_contents("languages.json"), true);
$flags = ["en" => "🇬🇧", "ru" => "🇷🇺"];
$menus = ["main" => [["menu-new"], ["menu-hlp", "menu-links", "menu-set"]],
	"chus-cmplx" => [["cmplx-easy", "cmplx-norm", "cmplx-hard"], ["set-back"]],
	"set" => [["menu-cmplx", "menu-lang"], ["main-back"]]];
require_once "words.php";

// hangman
function draw_hang($lives) {
switch ($lives) {
case 0: return "<code>  ________   
  | /    |   
  |/    (_)  
  |     _|_  
  |    / | \ 
  |      |   
  |     / \  
  |    /   \ 
__|__________
|           |</code>";

case 1: return "<code>  ________   
  | /    |   
  |/    (_)  
  |     _|_  
  |    / | \ 
  |      |   
  |     /    
  |    /     
__|__________
|           |</code>";

case 2: return "<code>  ________   
  | /    |   
  |/    (_)  
  |     _|_  
  |    / | \ 
  |      |   
  |          
  |          
__|__________
|           |</code>";

case 3: return "<code>  ________   
  | /    |   
  |/    (_)  
  |     _|   
  |    / |   
  |      |   
  |          
  |          
__|__________
|           |</code>";

case 4: return "<code>  ________   
  | /    |   
  |/    (_)  
  |      |   
  |      |   
  |      |   
  |          
  |          
__|__________
|           |</code>";

case 5: return "<code>  ________   
  | /    |   
  |/    (_)  
  |          
  |          
  |          
  |          
  |          
__|__________
|           |</code>";

case 6: return "<code>  ________   
  | /    |   
  |/         
  |          
  |          
  |          
  |          
  |          
__|__________
|           |</code>";

case 7: return "<code>  ________   
  | /        
  |/         
  |          
  |          
  |          
  |          
  |          
__|__________
|           |</code>";

case 8: return "<code>             
  |          
  |          
  |          
  |          
  |          
  |          
  |          
__|__________
|           |</code>";

case 9: return "<code>             
             
             
             
             
             
             
             
_____________
|           |</code>";

case 10: return "<code>             
             
             
             
             
             
             
             
             
             </code>";
}
}

// game keyboard
function draw_inline_kbd($letters, $isActive, $lang_ul, $rowSize = 8) {
	if (empty($letters)) return json_encode(["inline_keyboard" => []]);
	$keyboard = []; $row = [];
	foreach ($letters as $index => $letter) {
		$cb = ($letter == "*" || !$isActive) ? "-" : "letter-".$letter;
		$row[] = ["text" => $letter, "callback_data" => $cb];
		if (count($row) == $rowSize) {
			$keyboard[] = $row; $row = [];
		}
	} if (!empty($row)) $keyboard[] = $row;
	$keyboard[] = [["text" => $lang_ul["hang-one"], "callback_data" => ($isActive ? "open-one" : "-")]];
	return json_encode(["inline_keyboard" => $keyboard]);
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
		$lives = $row["complex"];
		$word = $row["word"]; $guess = $row["guess"];
		$letters = $row["letters"]; $lives = $row["lives"];
		$word_arr = mb_str_split($word);
		$guess_arr = mb_str_split($guess);

		list($action, $l) = explode("-", $cb_data);
		if ($action === "open") { // user use hint
			$open = array_unique(array_diff($word_arr, $guess_arr));
			$letter = $open[array_rand($open)];
		} else $letter = explode("-", $cb_data)[1]; // user press letter

		$letters = str_replace($letter, "*", $letters); // mark letter as used
		if (in_array($letter, $word_arr)) { // if letter in word
			foreach (array_keys($word_arr, $letter) as $key)
				$guess_arr[$key] = $letter; // show letter in guess string
			$guess = implode($guess_arr);
		} else $lives--;
		switch ($row["complex"]) {
			case 15: $hangman = ceil($lives *2 /3); break;
			case 10: $hangman = $lives; break;
			case 5: $hangman = floor($lives *2); break;
		}

		if ($word == $guess) { // win!
			trequest("editMessageText", ["chat_id" => $chat_id, "message_id" => $msg_id, 
			"text" => $lang[$ul]["game-win"] ."\n<b>".$word."</b>", "parse_mode" => "HTML", 
			"reply_markup" => draw_inline_kbd(mb_str_split($letters), false, $lang[$ul])]);
		} elseif ($lives == 0) { // lose
			trequest("editMessageText", ["chat_id" => $chat_id, "message_id" => $msg_id, 
				"text" => draw_hang($hangman) 
					."\n".$lang[$ul]["game-lose"] ."\n<b>".$word."</b>", "parse_mode" => "HTML", 
				"reply_markup" => draw_inline_kbd(mb_str_split($letters), false, $lang[$ul])]);
		} else { // game continues
			trequest("editMessageText", ["chat_id" => $chat_id, "message_id" => $msg_id, 
				"text" => draw_hang($hangman) 
					."\n".$lang[$ul]["game-hang"] ." | ". $lang[$ul]["hang-lives"].$lives
					."\n".implode(" ", $guess_arr), "parse_mode" => "HTML", 
				"reply_markup" => draw_inline_kbd(mb_str_split($letters), true, $lang[$ul])]);
		} update_data($chat_id, ["lives" => $lives, "guess" => $guess, "letters" => $letters]);
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
		$user_name = $fn." ".$ln; $complex = 10;
		$stmt = $dblink->prepare("INSERT INTO ".$tbname." (chat_id, user_lang, user_name, complex) VALUES (?, ?, ?, ?)");
		$stmt->bind_param("sssi", $chat_id, $ul, $user_name, $complex); $stmt->execute(); $stmt->close();
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
			// ask & save difficulty {
			// settings menu -> difficulty
			case $lang[$ul]["menu-cmplx"]: {
				trequest("sendMessage", ["chat_id" => $chat_id, 
					"text" => $lang[$ul]["chus-cmplx"].$lang[$ul]["hang-cmplx"], 
					"reply_markup" => draw_menu($lang[$ul], "chus-cmplx")]);
				break;
			} // difficulty menu -> easy / normal / hard
			case $lang[$ul]["cmplx-easy"]: {
				update_data($chat_id, ["complex" => 15]);
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["cmplx-saved"], 
					"reply_markup" => draw_menu($lang[$ul], "main")]);
				break;
			} case $lang[$ul]["cmplx-norm"]: {
				update_data($chat_id, ["complex" => 10]);
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["cmplx-saved"], 
					"reply_markup" => draw_menu($lang[$ul], "main")]);
				break;
			} case $lang[$ul]["cmplx-hard"]: {
				update_data($chat_id, ["complex" => 5]);
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["cmplx-saved"], 
					"reply_markup" => draw_menu($lang[$ul], "main")]);
				break;
			} // ask & save difficulty }

			// main menu -> new game
			case "/game": case "/game@pp_hangman_bot": case $lang[$ul]["menu-new"]: {
				$word = $words[$ul][array_rand($words[$ul])];
				$guess = str_repeat("_", mb_strlen($word));
				$lives = $row["complex"];
				switch ($ul) {
					case "en": $letters = range("a", "z"); break;
					case "ru": $letters = preg_split("//u", "абвгдежзийклмнопрстуфхцчшщъыьэюя", -1, PREG_SPLIT_NO_EMPTY); break;
				}

				// draw game keyboard
				$answer = trequest("sendMessage", ["chat_id" => $chat_id, 
					"text" => $lang[$ul]["game-hang"] ." | ". $lang[$ul]["hang-lives"].$lives
						."\n".implode(" ", mb_str_split($guess)), 
					"reply_markup" => draw_inline_kbd($letters, true, $lang[$ul])]);
				$tresponse = json_decode($answer, true);
				$msg_id = $tresponse["result"]["message_id"];
				update_data($chat_id, ["lives" => $lives, "word" => $word, "guess" => $guess, 
					"letters" => implode($letters), "msg_id" => $msg_id]);
				break;
			}

			// main menu -> help
			case "/help": case $lang[$ul]["menu-hlp"]: {
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["help-hang"]
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
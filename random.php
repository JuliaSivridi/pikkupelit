<?php
// bot: @pp_random_bot
// name: 🎲 Random | Случайность
// about: 
// 🎲 Random games
// Made by @Stler
// desc: 
// 🎲 Welcome to Random games!
// Made just for fun by @Stler
// commands: 
// coin - Toss a coin
// card - Make random card
// rand - Guess a number
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
// `random` TINYINT UNSIGNED, 
// PRIMARY KEY (`id`)) ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;";
// if (mysqli_query($dblink, $dbcreate))
// echo "<br>Table created";
// else echo mysqli_error($dblink);
// mysqli_close($dblink);

// globals
require_once "connect.php";
$tbname .= $suffix["rnd"];
$bottoken = $tokens["rnd"];
require_once "api_bd_menu.php";
$lang = json_decode(file_get_contents("languages.json"), true);
$flags = ["en" => "🇬🇧", "ru" => "🇷🇺"];
$menus = ["main" => [["menu-cazino", "menu-dice", "menu-dart", "menu-bowling", "menu-soccer", "menu-basketball"], 
		["menu-coin", "menu-card"], ["menu-rand"], 
		["menu-hlp", "menu-links", "menu-set"]],
		"set" => [["menu-lang"], ["main-back"]]];
$suits = ["♠️", "♣️", "♥️", "♦️"];
$ranks = ["!", "🤴", "👸", "👨‍🦰", "🔟", "9️⃣", "8️⃣", "7️⃣", "6️⃣", "5️⃣", "4️⃣", "3️⃣", "2️⃣"];

// 4 suits, from 2 to king & ace, no joker
function getRandomCard($lang_ul) {
	global $suits, $ranks;
	$rnd_suit = $suits[array_rand($suits, 1)]; $text_suit = $lang_ul[$rnd_suit];
	$rnd_rank = $ranks[array_rand($ranks, 1)]; $text_rank = $lang_ul[$rnd_rank];
	$card = ["suit" => $rnd_suit, "rank" => $rnd_rank, 
		"text_suit" => $text_suit, "text_rank" =>$text_rank];
	return $card;
}

function drawCard($lang_ul, $rank=0, $suit=0) {
	switch ($rank) { // card back
		case "0": $text = "\n🟧🟦🟧🟦🟧"
			."\n🟦🔸🔹🔸🟦"
			."\n🟧🔷🔶🔷🟧"
			."\n🟦🔶🔷🔶🟦"
			."\n🟧🔷🔶🔷🟧"
			."\n🟦🔸🔹🔸🟦"
			."\n🟧🟦🟧🟦🟧"; break;
		case "2️⃣": $text = "\n".$rank."⬜️⬜️⬜️".$suit
			."\n⬜️⬜️⬜️⬜️⬜️"
			."\n⬜️⬜️".$suit."⬜️⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"
			."\n⬜️⬜️".$suit."⬜️⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"; break;
		case "3️⃣": $text = "\n".$rank."⬜️⬜️⬜️".$suit
			."\n⬜️⬜️".$suit."⬜️⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"
			."\n⬜️⬜️".$suit."⬜️⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"
			."\n⬜️⬜️".$suit."⬜️⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"; break;
		case "4️⃣": $text = "\n".$rank."⬜️⬜️⬜️".$suit
			."\n⬜️".$suit."⬜️".$suit."⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"
			."\n⬜️".$suit."⬜️".$suit."⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"; break;
		case "5️⃣": $text = "\n".$rank."⬜️⬜️⬜️".$suit
			."\n⬜️".$suit."⬜️".$suit."⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"
			."\n⬜️⬜️".$suit."⬜️⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"
			."\n⬜️".$suit."⬜️".$suit."⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"; break;
		case "6️⃣": $text = "\n".$rank."⬜️⬜️⬜️".$suit
			."\n⬜️".$suit."⬜️".$suit."⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"
			."\n⬜️".$suit."⬜️".$suit."⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"
			."\n⬜️".$suit."⬜️".$suit."⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"; break;
		case "7️⃣": $text = "\n".$rank."⬜️⬜️⬜️".$suit
			."\n⬜️".$suit."⬜️".$suit."⬜️"
			."\n⬜️⬜️".$suit."⬜️⬜️"
			."\n⬜️".$suit."⬜️".$suit."⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"
			."\n⬜️".$suit."⬜️".$suit."⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"; break;
		case "8️⃣": $text = "\n".$rank."⬜️⬜️⬜️".$suit
			."\n⬜️".$suit."⬜️".$suit."⬜️"
			."\n⬜️⬜️".$suit."⬜️⬜️"
			."\n⬜️".$suit."⬜️".$suit."⬜️"
			."\n⬜️⬜️".$suit."⬜️⬜️"
			."\n⬜️".$suit."⬜️".$suit."⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"; break;
		case "9️⃣": $text = "\n".$rank."".$suit."⬜️".$suit.$suit
			."\n⬜️⬜️⬜️⬜️⬜️"
			."\n⬜️".$suit."⬜️".$suit."⬜️"
			."\n⬜️⬜️".$suit."⬜️⬜️"
			."\n⬜️".$suit."⬜️".$suit."⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"
			."\n⬜️".$suit."⬜️".$suit."⬜️"; break;
		case "🔟": $text = "\n".$rank."".$suit."⬜️".$suit.$suit
			."\n⬜️⬜️".$suit."⬜️⬜️"
			."\n⬜️".$suit."⬜️".$suit."⬜️"
			."\n⬜️⬜️⬜️⬜️⬜️"
			."\n⬜️".$suit."⬜️".$suit."⬜️"
			."\n⬜️⬜️".$suit."⬜️⬜️"
			."\n⬜️".$suit."⬜️".$suit."⬜️"; break;
		case "👨‍🦰": switch ($suit) {
			case "♠️": case "♣️": $text = "\n".$rank."⬜️⬜️⬜️".$suit
				."\n🟪🟪🟪🟪⬜️"
				."\n🟫🟫🟫🟫🟫"
				."\n⬛️🟨⬛️🟨🟫"
				."\n🟨🟨🟨🟨🟫"
				."\n🟨🟨🟨🟨🟫"
				."\n⬜️🟪🟪🟪⬜️"; break;
			case "♥️": case "♦️": $text = "\n".$rank."⬜️⬜️⬜️".$suit
				."\n🟩🟩🟩🟩⬜️"
				."\n🟧🟧🟧🟧🟧"
				."\n⬛️🟨⬛️🟨🟧"
				."\n🟨🟨🟨🟨🟧"
				."\n🟨🟨🟨🟨🟧"
				."\n⬜️🟩🟩🟩⬜️"; break;
		} break;
		case "👸": switch ($suit) {
			case "♠️": case "♣️": $text = "\n".$rank."⬜️⬜️⬜️".$suit
				."\n⬜️👑👑👑⬜️"
				."\n🟫🟫🟫🟫🟫"
				."\n⬛️🟨⬛️🟨🟫"
				."\n🟨🟨🟨🟨🟫"
				."\n🟨🟥🟨🟨🟫"
				."\n⬜️🟪🟪🟪⬜️"; break;
			case "♥️": case "♦️": $text = "\n".$rank."⬜️⬜️⬜️".$suit
				."\n⬜️👑👑👑⬜️"
				."\n🟧🟧🟧🟧🟧"
				."\n⬛️🟨⬛️🟨🟧"
				."\n🟨🟨🟨🟨🟧"
				."\n🟨🟥🟨🟨🟧"
				."\n⬜️🟩🟩🟩⬜️"; break;
		} break;
		case "🤴": switch ($suit) {
			case "♠️": case "♣️": $text = "\n".$rank."⬜️⬜️⬜️".$suit
				."\n👑👑👑👑👑"
				."\n🟫🟫🟫🟫🟫"
				."\n⬛️🟨⬛️🟨🟫"
				."\n🟨🟨🟨🟨🟫"
				."\n🟫🟫🟫🟨🟫"
				."\n⬜️🟪🟪🟪⬜️"; break;
			case "♥️": case "♦️": $text = "\n".$rank."⬜️⬜️⬜️".$suit
				."\n👑👑👑👑👑"
				."\n🟧🟧🟧🟧🟧"
				."\n⬛️🟨⬛️🟨🟧"
				."\n🟨🟨🟨🟨🟧"
				."\n🟧🟧🟧🟨🟧"
				."\n⬜️🟩🟩🟩⬜️"; break;
		} break;
		case "!": switch ($suit) {
			case "♠️": $text = "\n".$suit."⬜️⬛️⬜️".$suit
				."\n⬜️⬛️⬛️⬛️⬜️"
				."\n⬛️⬛️⬛️⬛️⬛️"
				."\n⬛️⬛️⬛️⬛️⬛️"
				."\n⬛️⬜️⬛️⬜️⬛️"
				."\n⬜️⬜️⬛️⬜️⬜️"
				."\n⬜️⬛️⬛️⬛️⬜️"; break;
			case "♣️": $text = "\n".$suit."⬜️⬛️⬜️".$suit
				."\n⬜️⬛️⬛️⬛️⬜️"
				."\n⬛️⬜️⬛️⬜️⬛️"
				."\n⬛️⬛️⬛️⬛️⬛️"
				."\n⬛️⬜️⬛️⬜️⬛️"
				."\n⬜️⬜️⬛️⬜️⬜️"
				."\n⬜️⬛️⬛️⬛️⬜️"; break;
			case "♥️": $text = "\n".$suit."⬜️⬜️⬜️".$suit
				."\n⬜️🟥⬜️🟥⬜️"
				."\n🟥🟥🟥🟥🟥"
				."\n🟥🟥🟥🟥🟥"
				."\n⬜️🟥🟥🟥⬜️"
				."\n⬜️🟥🟥🟥⬜️"
				."\n⬜️⬜️🟥⬜️⬜️"; break;
			case "♦️": $text = "\n".$suit."⬜️🟥⬜️".$suit
				."\n⬜️🟥🟥🟥⬜️"
				."\n⬜️🟥🟥🟥⬜️"
				."\n🟥🟥🟥🟥🟥"
				."\n⬜️🟥🟥🟥⬜️"
				."\n⬜️🟥🟥🟥⬜️"
				."\n⬜️⬜️🟥⬜️⬜️"; break;
		} break;
	} return $text;
}

// get user request
$content = file_get_contents("php://input");
$input = json_decode($content, TRUE);
$dblink = mysqli_connect($dbhost, $dbuser, $dbpswd, $dbname);

// user send msg
if (isset($input["message"])) {
	$chat_id = $input["message"]["chat"]["id"];
	$result_usr = select_data($chat_id);
	// user new -> insert to db
	if (mysqli_num_rows($result_usr) <= 0) {
		$user_lang = $input["message"]["from"]["language_code"];
		$ul = (array_key_exists($user_lang, $lang)) ? $user_lang : "ru";
		$fn = isset($input["message"]["from"]["first_name"]) ? $input["message"]["from"]["first_name"] : "";
		$ln = isset($input["message"]["from"]["last_name"]) ? $input["message"]["from"]["last_name"] : "";
		$user_name = $fn." ".$ln;
		$stmt = $dblink->prepare("INSERT INTO ".$tbname." (chat_id, user_lang, user_name) VALUES (?, ?, ?)");
		$stmt->bind_param("sss", $chat_id, $ul, $user_name); $stmt->execute(); $stmt->close();
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
			// main menu -> emoji games
			case "🎰": case "🎲": case "🎯": case "🎳": case "⚽️": case "🏀": { break; }

			// main menu -> toss a coin
			case "/coin": case "/coin@pp_random_bot": case $lang[$ul]["menu-coin"]: {
				trequest("sendMessage", ["chat_id" => $chat_id, 
					"text" => ((rand(0, 1) == 1) ? $lang[$ul]["coin-head"] : $lang[$ul]["coin-tail"]), 
					"reply_markup" => draw_menu($lang[$ul], "main")]);
				break;
			}

			// main menu -> random card
			case "/card": case "/card@pp_random_bot": case $lang[$ul]["menu-card"]: {
				$card = getRandomCard($lang[$ul]);
				trequest("sendMessage", ["chat_id" => $chat_id, 
					"text" => $lang[$ul]["card-ttl"]
						."\n".$card["text_rank"]." ".$card["text_suit"]."\n"
						.drawCard($lang[$ul], $card["rank"], $card["suit"]), 
					"reply_markup" => draw_menu($lang[$ul], "main")]);
				break;
			}

			// main menu -> random number
			case "/rand": case "/rand@pp_random_bot": case $lang[$ul]["menu-rand"]: {
				$low = rand(1, 10); $top = rand(10, 100);
				$random = rand($low, $top);
				trequest("sendMessage", ["chat_id" => $chat_id, 
					"text" => $lang[$ul]["rand-low"].$low.$lang[$ul]["rand-top"].$top
						."\n".$lang[$ul]["rand-guess"], 
					"reply_markup" => json_encode(['remove_keyboard' => true])]);
				update_data($chat_id, ["random" => $random]);
				break;
			}

			// main menu -> help
			case "/help": case $lang[$ul]["menu-hlp"]: {
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["help"]
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
			// basic functionality }

			default:
				$random = $row["random"];
				if (is_numeric($user_msg)) {
					if ($user_msg < $random) trequest("sendMessage", ["chat_id" => $chat_id, 
						"text" => $lang[$ul]["rand-more"], 
						"reply_markup" => json_encode(['remove_keyboard' => true])]);
					elseif ($user_msg > $random) trequest("sendMessage", ["chat_id" => $chat_id, 
						"text" =>  $lang[$ul]["rand-less"], 
						"reply_markup" => json_encode(['remove_keyboard' => true])]);
					else trequest("sendMessage", ["chat_id" => $chat_id, 
						"text" => $lang[$ul]["rand-equals"], 
						"reply_markup" => draw_menu($lang[$ul], "main")]);
				} else trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["default"], 
					"reply_markup" => draw_menu($lang[$ul], "main")]);
		}
	} mysqli_free_result($result_usr);
} mysqli_close($dblink); ?>
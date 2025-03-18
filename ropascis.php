<?php
// bot: @pp_ropascis_bot
// name: 🪨📄✂️ Rock Paper Scissors | Камень Ножницы Бумага
// about: 
// 🪨📄✂️ Rock Paper Scissors game
// Made by @Stler
// desc: 
// 🪨📄✂️ Welcome to Rock Paper Scissors!
// Made just for fun by @Stler
// commands: 
// help - Show guide
// stat - Show statistics
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
// `statwin` INT UNSIGNED NOT NULL DEFAULT 0, `statlose` INT UNSIGNED NOT NULL DEFAULT 0, `statdraw` INT UNSIGNED NOT NULL DEFAULT 0,
// PRIMARY KEY (`id`)) ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;";
// if (mysqli_query($dblink, $dbcreate))
// echo "<br>Table created";
// else echo mysqli_error($dblink);
// mysqli_close($dblink);

// globals
require_once "connect.php";
$dblink = mysqli_connect($dbhost, $dbuser, $dbpswd, $dbname);
$tbname .= $suffix["rps"];
$bottoken = $tokens["rps"];
require_once "api_bd_menu.php";
$lang = json_decode(file_get_contents("languages.json"), true);
$flags = ["en" => "🇬🇧", "ru" => "🇷🇺"];
$menus = ["main" => [["rsp-stone", "rsp-scissors", "rsp-paper"], 
		["menu-stat"],
		["menu-hlp", "menu-links", "menu-set"]],
		"set" => [["menu-lang"], ["main-back"]]];

// get user request
$content = file_get_contents("php://input");
$input = json_decode($content, TRUE);

// user send msg
if (isset($input["message"])) {
	$chat_id = $input["message"]["chat"]["id"];
	$chat_type = $input["message"]["chat"]["type"] ?? "private";
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
		trequest("sendMessage", ["chat_id" => $chat_id, 
			"text" => $lang[$ul]["hi1"].$input["message"]["from"]["first_name"].$lang[$ul]["hi2"]
				.$lang[$ul]["cmd-hl"], 
			"reply_markup" => draw_menu($lang[$ul], "main", $chat_type)]);

	// user exists
	} else {
		$row = mysqli_fetch_assoc($result_usr);
		$user_lang = $row["user_lang"];
		$ul = (array_key_exists($user_lang, $lang)) ? $user_lang : "ru";
		$lkeys = array_keys($lang); $flag_lang = [];
		foreach ($lkeys as $lkey) $flag_lang[] = $flags[$lkey]." ".$lkey;
		
		$user_msg = trim($input["message"]["text"]);
		switch ($user_msg) {
			// main menu -> new game
			case $lang[$ul]["rsp-stone"]: case $lang[$ul]["rsp-scissors"]: case $lang[$ul]["rsp-paper"]: {
				$swin = $row["statwin"]; $slose = $row["statlose"]; $sdraw = $row["statdraw"];
				$rsp = [$lang[$ul]["rsp-stone"], $lang[$ul]["rsp-scissors"], $lang[$ul]["rsp-paper"]];
				$bot_msg = $rsp[array_rand($rsp, 1)]; // bot random choice
				$answer = ["chat_id" => $chat_id, "text" => $lang[$ul]["rsp-comp"].$bot_msg."\n\n", 
					"reply_markup" => draw_menu($lang[$ul], "main", $chat_type)];
				if ($bot_msg == $user_msg) {
					$sdraw++;
					$answer["text"] .= $lang[$ul]["game-draw"];
				}
				else {
					$user_win = [$lang[$ul]["rsp-stone"] => $lang[$ul]["rsp-scissors"], 
						$lang[$ul]["rsp-scissors"] => $lang[$ul]["rsp-paper"], 
						$lang[$ul]["rsp-paper"] => $lang[$ul]["rsp-stone"]];
					if ($user_win[$user_msg] == $bot_msg) {
						$swin++;
						$answer["text"] .= $lang[$ul]["game-win"];
						$answer["message_effect_id"] = "5046509860389126442"; // 🎉
					} else {
						$slose++;
						$answer["text"] .= $lang[$ul]["game-lose"];
					}
				} trequest("sendMessage", $answer);
				update_data($chat_id, ["statwin" => $swin, "statlose" => $slose, "statdraw" => $sdraw]);
				break;
			}

			// main menu -> stat
			case "/stat": case "/stat@pp_ropascis_bot": case $lang[$ul]["menu-stat"]: {
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["stat-ttl"]
					."`".$lang[$ul]["stat-all"].str_pad((string)($row["statwin"]+$row["statlose"]+$row["statdraw"]), (20 - mb_strlen($lang[$ul]["stat-all"])), " ", STR_PAD_LEFT)."`"
					."`".$lang[$ul]["stat-win"].str_pad((string)($row["statwin"]), (20 - mb_strlen($lang[$ul]["stat-win"])), " ", STR_PAD_LEFT)."`"
					."`".$lang[$ul]["stat-lose"].str_pad((string)($row["statlose"]), (20 - mb_strlen($lang[$ul]["stat-lose"])), " ", STR_PAD_LEFT)."`"
					."`".$lang[$ul]["stat-draw"].str_pad((string)($row["statdraw"]), (21 - mb_strlen($lang[$ul]["stat-draw"])), " ", STR_PAD_LEFT)."`", 
					"parse_mode" => "Markdown", "reply_markup" => draw_menu($lang[$ul], "main", $chat_type)]);
				break;
			}

			// main menu -> help
			case "/help": case "/help@pp_ropascis_bot": case $lang[$ul]["menu-hlp"]: {
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["help-rsp"],
					"parse_mode" => "Markdown", "reply_markup" => draw_inline_menu($lang[$ul], "contact")]);
				break;
			}

			// basic functionality {
			case "/start": case "/start@pp_ropascis_bot": {
				trequest("sendMessage", ["chat_id" => $chat_id, 
					"text" => $lang[$ul]["hi1"].$input["message"]["from"]["first_name"].$lang[$ul]["hi2"]
						.$lang[$ul]["cmd-hl"], 
					"reply_markup" => draw_menu($lang[$ul], "main", $chat_type)]);
				break;
			}

			// main menu
			case "/main": case "/main@pp_ropascis_bot": case $lang[$ul]["main-back"]: {
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["main-ttl"], 
					"reply_markup" => draw_menu($lang[$ul], "main", $chat_type)]);
				break;
			}

			// main menu -> game links
			case "/links": case "/links@pp_ropascis_bot": case $lang[$ul]["menu-links"]: {
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["game-links"], 
					"reply_markup" => draw_inline_menu($lang[$ul], "game_links")]);
				break;
			}

			// main menu -> settings
			case "/settings": case "/settings@pp_ropascis_bot": case $lang[$ul]["menu-set"]: case $lang[$ul]["set-back"]: {
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["set-ttl"], 
					"reply_markup" => draw_menu($lang[$ul], "set", $chat_type)]);
				break;
			}

			// settings menu -> language ask
			case "/lang": case "/lang@pp_ropascis_bot": case $lang[$ul]["menu-lang"]: {
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
						"reply_markup" => draw_menu($lang[$ul], "main", $chat_type)]);
				} break;
			}

			default:
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["default"], 
					"reply_markup" => draw_menu($lang[$ul], "main", $chat_type)]);
			// basic functionality }
		}
	} mysqli_free_result($result_usr);
} mysqli_close($dblink); ?>
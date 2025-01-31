<?php
// bot: @pp_blackjack_bot 
// name: 🃏 Blackjack | Блэкджек
// about: 
// 🃏 Blackjack game
// Made by @Stler
// desc: 
// 🃏 Welcome to Blackjack!
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
// `ccards` TEXT, `ccost` TINYINT, `ucards` TEXT, `ucost` TINYINT, 
// PRIMARY KEY (`id`)) ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;";
// if (mysqli_query($dblink, $dbcreate))
// echo "<br>Table created";
// else echo mysqli_error($dblink);
// mysqli_close($dblink);

// globals
require_once "connect.php";
$dblink = mysqli_connect($dbhost, $dbuser, $dbpswd, $dbname);
$tbname .= $suffix["bjk"];
$bottoken .= $tokens["bjk"];
require_once "api_bd_menu.php";
$lang = json_decode(file_get_contents("languages.json"), true);
$flags = ["en" => "🇬🇧", "ru" => "🇷🇺"];
$menus = ["main" => [["menu-new"], ["menu-hlp", "menu-links", "menu-set"]],
	"set" => [["menu-lang"], ["main-back"]]];
$inline_menus = ["game" => [[["text" => "menu-more", "cb_data" => "cb-more"],
	["text" => "menu-stop", "cb_data" => "cb-stop"]]]];

// 4 suits, from 2 to king & ace, no joker
function getRandomCard($lang_ul) {
	$suits = ["♠️", "♣️", "♥️", "♦️"];
	$ranks = ["!", "🤴", "👸", "👨‍🦰", "🔟", 
		"9️⃣", "8️⃣", "7️⃣", "6️⃣", "5️⃣", "4️⃣", "3️⃣", "2️⃣"];
	$rnd_suit = $suits[array_rand($suits, 1)]; $text_suit = $lang_ul[$rnd_suit];
	$rnd_rank = $ranks[array_rand($ranks, 1)]; $text_rank = $lang_ul[$rnd_rank];
	$card = ["suit" => $rnd_suit, "rank" => $rnd_rank, 
		"text_rank" =>$text_rank, "text_suit" => $text_suit];
	return $card;
}
// 2..10 = numbers, JQK = 10, ace = 11|1 if common cost > 21
function getCardsCost($cards) {
	$costs = ["!" => 11, "🤴" => 10, "👸" => 10, "👨‍🦰" => 10, "🔟" => 10, 
		"9️⃣" => 9, "8️⃣" => 8, "7️⃣" => 7, "6️⃣" => 6, 
		"5️⃣" => 5, "4️⃣" => 4, "3️⃣" => 3, "2️⃣" => 2];
	$cost = 0;
	foreach ($cards as $card)
		if ($card["rank"] != "!")
			$cost += $costs[$card["rank"]];
	foreach ($cards as $card)
		if ($card["rank"] == "!")
			if ($cost+$costs[$card["rank"]] > 21) { $cost += 1; }
			else { $cost += $costs[$card["rank"]]; }
	return $cost;
}

// write each card
function writeCards($cards) {
	$text = "";
	foreach ($cards as $card) {
		$text .= "\n".$card["suit"]
		.($card["rank"] == "!" ? "🅰️" : $card["rank"])
		." ".$card["text_rank"]
		." ".$card["text_suit"];
	} return $text;
}
// write full message
function writeGame($lang_ul, $ccards, $ccost, $ucards, $ucost) {
	$cclos = (count($ccards) == 1) ? $lang_ul["card-closed"] : "";
	return $lang_ul["cards-comp"].$cclos.writeCards($ccards)
		.$lang_ul["cards-user"].writeCards($ucards)
		.$lang_ul["cost-comp"].$ccost.$lang_ul["cost-user"].$ucost;
}

function isBlackjack($cards, $cost) {
	return ($cost == 21 && count($cards) == 2);
}
// write end of the game
function getGameState($lang_ul, $ccards, $ccost, $ucards, $ucost) {
	$state = 0; $bj = false;
	if ($ucost > 21) { // user bust
		$state = 0; $bj = isBlackjack($ccards, $ccost);
	} else {
		if ($ccost > 21) { // comp bust, user ok
			$state = 1; $bj = isBlackjack($ucards, $ucost);
		} else { 
			if (($ucost == 21) && ($ccost < 21)) { // comp low, user 21
				$state = 1; $bj = isBlackjack($ucards, $ucost);
			} elseif (($ucost < 21) && ($ccost == 21)) { // user low, comp 21
				$state = 0; $bj = isBlackjack($ccards, $ccost);
			} elseif (($ucost == 21) && ($ccost == 21)) { // both 21
				if (count($ccards) == 2) {
					if (count($ucards) == 2) { $state = 2; } // both blackjack
					else { $state = 0; $bj = isBlackjack($ccards, $ccost); }
				} else { // blackjack beats 21 on 3+ cards
					if (count($ucards) == 2) { $state = 1; $bj = isBlackjack($ucards, $ucost); }
					else { $state = 2; } // both 21 on 3+ cards
				}
			} else { // both less 21
				if ($ucost == $ccost) { $msg = "Ничья"; $state = 2; }
				elseif ($ucost < $ccost) { $msg = "Вы проиграли"; $state = 0; }
				else { $msg = "Вы выиграли"; $state = 1; }
			}
		}
	}
	switch ($state) {
		case 0: $msg = $lang_ul["game-lose"]; if ($bj) $msg .= $lang_ul["bj-comp"].$lang_ul["bj-blackjack"]; break;
		case 1: $msg = $lang_ul["game-win"]; if ($bj) $msg .= $lang_ul["bj-user"].$lang_ul["bj-blackjack"]; break;
		case 2: $msg = $lang_ul["game-draw"]; break;
	} return "\n".$msg;
}

// |comp | user->  <21      |    21      |  21< 
// |-----|------------------|------------|---------
// | <21 | user<comp user❌ | user ✅   | user ❌
// | <21 | comp<user user✅ | comp ❌   | comp ✅
// | <21 | comp=user draw   |            |
// |-----|------------------|------------|---------
// |  21 | user ❌          | bj=bj draw | user ❌
// |  21 | comp ✅          | 21=21 draw | comp ✅
// |  21 |                  | 21<bj      |
// |-----|------------------|------------|---------
// | 21< | user ✅          | user ✅   | user ❌
// | 21< | comp ❌          | comp ❌   | comp ❌

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
		$ccards = json_decode($row["ccards"], true); $ccost = $row["ccost"];
		$ucards = json_decode($row["ucards"], true); $ucost = $row["ucost"];

		$isGameOver = false;
		if ($cb_data == "cb-more") { // user ask more cards
			$ucards[] = getRandomCard($lang[$ul]);
			$ucost = getCardsCost($ucards);
			if ($ucost >= 21) $isGameOver = true;
			update_data($chat_id, ["ucards" => $ucards, "ucost" => $ucost]);
		} elseif ($cb_data == "cb-stop") { // user want to stop
			$isGameOver = true;
		}

		if ($isGameOver == true) {
			while ($ccost <= 17) { // comp turn at the end of game
				$ccards[] = getRandomCard($lang[$ul]);
				$ccost = getCardsCost($ccards);
			} update_data($chat_id, ["ccards" => $ccards, "ccost" => $ccost]);
			trequest("editMessageText", ["chat_id" => $chat_id, "message_id" => $msg_id, 
				"text" => writeGame($lang[$ul], $ccards, $ccost, $ucards, $ucost)
					.getGameState($lang[$ul], $ccards, $ccost, $ucards, $ucost), 
				"parse_mode" => "Markdown"]);
		} else {
			trequest("editMessageText", ["chat_id" => $chat_id, "message_id" => $msg_id, 
				"text" => writeGame($lang[$ul], $ccards, $ccost, $ucards, $ucost), 
				"parse_mode" => "Markdown", 
				"reply_markup" => draw_inline_menu($lang[$ul], "game")]);
		}
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
			// main menu -> new game
			case "/game": case "/game@pp_blackjack_bot": case $lang[$ul]["menu-new"]: {
				$ccards[] = getRandomCard($lang[$ul]);
				$ccost = getCardsCost($ccards);
				$ucards[] = getRandomCard($lang[$ul]);
				$ucards[] = getRandomCard($lang[$ul]);
				$ucost = getCardsCost($ucards);
				update_data($chat_id, ["ccards" => $ccards, "ccost" => $ccost, 
					"ucards" => $ucards, "ucost" => $ucost]);

				if (isBlackjack($ucards, $ucost)) { // if user get blackjack immediately
					while ($ccost <= 17) { // comp turn at the end of game
						$ccards[] = getRandomCard($lang[$ul]);
						$ccost = getCardsCost($ccards);
					} update_data($chat_id, ["ccards" => $ccards, "ccost" => $ccost]);
					trequest("sendMessage", ["chat_id" => $chat_id, 
						"text" => writeGame($lang[$ul], $ccards, $ccost, $ucards, $ucost)
							.getGameState($lang[$ul], $ccards, $ccost, $ucards, $ucost), 
						"parse_mode" => "Markdown"]);
				} else {
					$answer = trequest("sendMessage", ["chat_id" => $chat_id, 
						"text" => writeGame($lang[$ul], $ccards, $ccost, $ucards, $ucost), 
						"parse_mode" => "Markdown", 
						"reply_markup" => draw_inline_menu($lang[$ul], "game")]);
					$tresponse = json_decode($answer, true);
					$msg_id = $tresponse["result"]["message_id"];
					update_data($chat_id, ["msg_id" => $msg_id]);
				} break;
			}

			// main menu -> help
			case "/help": case $lang[$ul]["menu-hlp"]: {
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["help-bj"], 
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
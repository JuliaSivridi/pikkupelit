<?php
// bot: @pp_fourinrow_bot 
// name: 🔴🟡 4 in row | 4 в ряд
// about: 
// 🔴🟡 4 in row game
// Made by @Stler
// desc: 
// 🔴🟡 Welcome to 4 in row!
// Made just for fun by @Stler
// commands: 
// game - Start new game
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
// `start` BOOLEAN, `board4` TEXT,
// `statwin` INT UNSIGNED NOT NULL DEFAULT 0, `statlose` INT UNSIGNED NOT NULL DEFAULT 0, `statdraw` INT UNSIGNED NOT NULL DEFAULT 0,
// PRIMARY KEY (`id`)) ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;";
// if (mysqli_query($dblink, $dbcreate))
// echo "<br>Table created";
// else echo mysqli_error($dblink);
// mysqli_close($dblink);

// globals
require_once "connect.php";
$dblink = mysqli_connect($dbhost, $dbuser, $dbpswd, $dbname);
$tbname .= $suffix["fir"];
$bottoken = $tokens["fir"];
require_once "api_bd_menu.php";
$lang = json_decode(file_get_contents("languages.json"), true);
$flags = ["en" => "🇬🇧", "ru" => "🇷🇺"];
$menus = ["main" => [["menu-new", "menu-stat"], ["menu-hlp", "menu-links", "menu-set"]],
	"set" => [["menu-lang"], ["main-back"]]];
$symbols = ["⚪", "🔴", "🟡"];

// game keyboard
function drawBoard($board, $isActive, $highlight = []) {
	global $symbols; $gkbd = []; $coords = [];
	foreach ($highlight as [$hr, $hc])
	    $coords["$hr-$hc"] = true;
	foreach ($board as $r => $row) {
		$gstr = [];
		foreach ($row as $c => $cell) {
			$isHigh = isset($coords["$r-$c"]);
			$text = $isHigh ? "(".$symbols[$cell].")" : $symbols[$cell];
			$cb = (($board[0][$c] != 0) || !$isActive) ? "-" : "col-$c";
			$gstr[] = ["text" => $text, "callback_data" => $cb];
		} $gkbd[] = $gstr;
	} return json_encode(["inline_keyboard" => $gkbd]);
}

// dropping sign
function drawDrop($board, $sign, $endr, $nowr, $nowc) {
	global $symbols; $gkbd = [];
	foreach ($board as $r => $row) {
		$gstr = [];
		foreach ($row as $c => $cell) {
			if ($nowr == $r && $nowc == $c) { $text = "(".$symbols[$sign].")"; }
			elseif ($endr == $r && $nowc == $c) { $text = $symbols[0]; }
			else { $text = $symbols[$cell]; }
			$gstr[] = ["text" => $text, "callback_data" => "-"];
		} $gkbd[] = $gstr;
	} return json_encode(["inline_keyboard" => $gkbd]);
} // animate keyboard
function makeDrop($board, $sign, $row, $col, $chat_id, $msg_id, $lang_ul, $isUser) {
	global $symbols;
	$text = $lang_ul["game-four"] 
		."\n\n".$symbols[$sign]
		." ".($isUser ? $lang_ul["turn-user"] : $lang_ul["turn-comp"]);
	for ($r = 0; $r < $row; $r++) {
		usleep(200000);
		trequest("editMessageText", ["chat_id" => $chat_id, "message_id" => $msg_id, 
			"text" => $text, "reply_markup" => drawDrop($board, $sign, $row, $r, $col)]);
	} return;
}

// heuristic method for computer turn
function getSmartMove($board, $comp_sign, $user_sign) {
	for ($col = 0; $col < 7; $col++) // search win
		if ($board[0][$col] == 0) {
			$test_board = $board;
			$move = makeMove($test_board, $comp_sign, $col);
			if (isWin($move["board"], $comp_sign, $move["row"], $col)["win"])
				return $col;
		}
	for ($col = 0; $col < 7; $col++) // block win
		if ($board[0][$col] == 0) {
			$test_board = $board;
			$move = makeMove($test_board, $user_sign, $col);
			if (isWin($move["board"], $user_sign, $move["row"], $col)["win"])
				return $col;
		}
	if ($board[5][3] == 0) return 3; // take start
	if ($board[4][3] == 0) return 3; // continue start up
	if ($board[5][3] == $user_sign) { // closing left/right bottom
		if (($board[5][2] == $user_sign) && ($board[5][1] == 0)) return 1;
		elseif (($board[5][4] == $user_sign) && ($board[5][5] == 0)) return 5;
	}
	do { // any free place
		$comp_col = rand(0, 6);
	} while ($board[0][$comp_col] != 0);
	return $comp_col;
}

// make whole turn
function processTurn($board, $sign, $col, $chat_id, $msg_id, $lang_ul, $isUser) {
	global $symbols;
	$move = makeMove($board, $sign, $col);
	$board = $move["board"]; $row = $move["row"];
	$state = "";
	$checkState = isWin($board, $sign, $row, $col);
	if ($checkState["win"]) { // if win
		makeDrop($board, $sign, $row, $col, $chat_id, $msg_id, $lang_ul, $isUser);
		trequest("editMessageText", ["chat_id" => $chat_id, "message_id" => $msg_id, 
			"text" => $isUser ? $lang_ul["game-win"] : $lang_ul["game-lose"], 
			"reply_markup" => drawBoard($board, false, $checkState["line"])]);
		$state = $isUser ? "win" : "lose";
		return ["board" => $board, "state" => $state, "game_over" => true];
	} elseif (isDraw($board)) { // if no more moves
		makeDrop($board, $sign, $row, $col, $chat_id, $msg_id, $lang_ul, $isUser);
		trequest("editMessageText", ["chat_id" => $chat_id, "message_id" => $msg_id, 
			"text" => $lang_ul["game-draw"], "reply_markup" => drawBoard($board, false)]);
		$state = "draw";
		return ["board" => $board, "state" => $state, "game_over" => true];
	} else { // game continues
		makeDrop($board, $sign, $row, $col, $chat_id, $msg_id, $lang_ul, $isUser);
		trequest("editMessageText", ["chat_id" => $chat_id, "message_id" => $msg_id, 
			"text" => $lang_ul["game-four"] 
				."\n\n".$symbols[(($sign == 1) ? 2 : 1)]
				." ".($isUser ? $lang_ul["turn-comp"] : $lang_ul["turn-user"]), 
			"reply_markup" => drawBoard($board, ($isUser ? false : true), [[$row, $col]])]);
		return ["board" => $board, "state" => $state, "game_over" => false];
	}
}

// make user/computer move 
function makeMove($board, $sign, $col) {
	$row = 0;
	for ($r = 5; $r >= 0; $r--) {
		if ($board[$r][$col] == 0) {
			$board[$r][$col] = $sign;
			$row = $r;
			break;
		}
	} return ["board" => $board, "row" => $row];
}

// someone won?
function isWin($board, $sign, $row, $col) {
	$directions = [[0, 1], // horisontal
		[1, 0],            // vertical
		[1, 1],            // diagonal \
		[1, -1]];          // diagonal /
	$winLine = [];
	foreach ($directions as [$dx, $dy]) {
		$winLine = [[$row, $col]];
		$count = 1;
		for ($i = 1; $i < 4; $i++) {
			$x = $row + $i * $dx; $y = $col + $i * $dy;
			if ($x >= 0 && $x < 6 && $y >= 0 && $y < 7 && $board[$x][$y] == $sign) {
				$count++; $winLine[] = [$x, $y];
			} else { break; }
		}
		for ($i = 1; $i < 4; $i++) {
			$x = $row - $i * $dx; $y = $col - $i * $dy;
			if ($x >= 0 && $x < 6 && $y >= 0 && $y < 7 && $board[$x][$y] == $sign) {
				$count++; $winLine[] = [$x, $y];
			} else { break; }
		}
		if ($count >= 4)
			return ["win" => true, "line" => $winLine];
	} return ["win" => false, "line" => []];
}

// no more moves?
function isDraw($board) {
	foreach ($board as $row)
		if (in_array(0, $row))
			return false;
	return true;
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
		$swin = $row["statwin"]; $slose = $row["statlose"]; $sdraw = $row["statdraw"];
		$board = json_decode($row["board4"], true);
		$who_starts = (bool)$row["start"];
		$user_sign = ($who_starts) ? 1 : 2;
		$comp_sign = (!$who_starts) ? 1 : 2;

		// user turn
		$user_col = explode("-", $cb_data)[1];
		$userTurn = processTurn($board, $user_sign, $user_col, $chat_id, $msg_id, $lang[$ul], true);
		if ($userTurn["game_over"]) {
			switch ($userTurn["state"]) {
				case "lose": $slose++; break;
				case "win": $swin++; break;
				case "draw": $sdraw++; break;
			} update_data($chat_id, ["statwin" => $swin, "statlose" => $slose, "statdraw" => $sdraw]);
			return;
		} $board = $userTurn["board"];

		// computer turn
		sleep(1);
		$comp_col = getSmartMove($board, $comp_sign, $user_sign);
		$compTurn = processTurn($board, $comp_sign, $comp_col, $chat_id, $msg_id, $lang[$ul], false);
		if ($compTurn["game_over"]) {
			switch ($compTurn["state"]) {
				case "lose": $slose++; break;
				case "win": $swin++; break;
				case "draw": $sdraw++; break;
			} update_data($chat_id, ["statwin" => $swin, "statlose" => $slose, "statdraw" => $sdraw]);
			return;
		} $board = $compTurn["board"];

		update_data($chat_id, ["board4" => $board]);
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
			case "/game": case "/game@pp_fourinrow_bot": case $lang[$ul]["menu-new"]: {
				$board = array_fill(0, 6, array_fill(0, 7, 0)); // make blank board
				$who_starts = (bool)rand(0, 1); // 1 - user, 0 - computer
				$user_sign = ($who_starts) ? 1 : 2;
				if (!$who_starts) { 
					$comp_col = getSmartMove($board, 1, 2);
					$move = makeMove($board, 1, $comp_col);
					$board = $move["board"];
					$gkbd = drawBoard($board, true, [[$move["row"], $comp_col]]);
				} else { $gkbd = drawBoard($board, true); }
		
				// draw game keyboard
				$answer = trequest("sendMessage", ["chat_id" => $chat_id, 
					"text" => $lang[$ul]["game-four"] 
					."\n\n".$symbols[$user_sign]." ".$lang[$ul]["turn-user"],
					"reply_markup" => $gkbd]);
				$tresponse = json_decode($answer, true);
				$msg_id = $tresponse["result"]["message_id"];
				update_data($chat_id, ["start" => $who_starts, "board4" => $board, "msg_id" => $msg_id]);
				break;
			}

			// main menu -> stat
			case "/stat": case $lang[$ul]["menu-stat"]: {
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["stat-ttl"]
					."`".$lang[$ul]["stat-all"].str_pad((string)($row["statwin"]+$row["statlose"]+$row["statdraw"]), (20 - mb_strlen($lang[$ul]["stat-all"])), " ", STR_PAD_LEFT)."`"
					."`".$lang[$ul]["stat-win"].str_pad((string)($row["statwin"]), (20 - mb_strlen($lang[$ul]["stat-win"])), " ", STR_PAD_LEFT)."`"
					."`".$lang[$ul]["stat-lose"].str_pad((string)($row["statlose"]), (20 - mb_strlen($lang[$ul]["stat-lose"])), " ", STR_PAD_LEFT)."`"
					."`".$lang[$ul]["stat-draw"].str_pad((string)($row["statdraw"]), (21 - mb_strlen($lang[$ul]["stat-draw"])), " ", STR_PAD_LEFT)."`", 
					"parse_mode" => "Markdown", "reply_markup" => draw_menu($lang[$ul], "main")]);
				break;
			}

			// main menu -> help
			case "/help": case $lang[$ul]["menu-hlp"]: {
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["help-four"],
					"parse_mode" => "Markdown", "reply_markup" => draw_inline_menu($lang[$ul], "contact")]);
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
					"reply_markup" => draw_inline_menu($lang[$ul], "game_links")]);
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
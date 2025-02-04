<?php
// bot: @pp_tictactoe_bot
// name: ❌⭕️ TicTacToe | Крестики-нолики
// about: 
// ❌⭕️ TicTacToe game
// Made by @Stler
// desc: 
// ❌⭕️ Welcome to TicTacToe!
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
// `size` TINYINT, `start` BOOLEAN, `xoline` TEXT, 
// `statwin` INT UNSIGNED NOT NULL DEFAULT 0, `statlose` INT UNSIGNED NOT NULL DEFAULT 0, `statdraw` INT UNSIGNED NOT NULL DEFAULT 0,
// PRIMARY KEY (`id`)) ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;";
// if (mysqli_query($dblink, $dbcreate))
// echo "<br>Table created";
// else echo mysqli_error($dblink);
// mysqli_close($dblink);

// globals
require_once "connect.php";
$dblink = mysqli_connect($dbhost, $dbuser, $dbpswd, $dbname);
$tbname .= $suffix["ttt"];
$bottoken = $tokens["ttt"];
require_once "api_bd_menu.php";
$lang = json_decode(file_get_contents("languages.json"), true);
$flags = ["en" => "🇬🇧", "ru" => "🇷🇺"];
$menus = ["main" => [["menu-new", "menu-stat"], ["menu-hlp", "menu-links", "menu-set"]],
	"chus-size" => [["3", "4", "5", "6", "7", "8"], ["set-back"]],
	"set" => [["menu-size", "menu-lang"], ["main-back"]]];

define("PLAYER_X", "x"); define("PLAYER_O", "o"); define("EMPTY_CELL", ".");
$symbols = [PLAYER_X => "❌", PLAYER_O => "⭕️", EMPTY_CELL => " "];

// game keyboard
function drawBoard($board_str, $isActive, $highlight = []) {
	global $symbols;
	$bsize = sqrt(strlen($board_str)); // размер поля
	$board = str_split($board_str);
	$board = array_map(fn($char) => $symbols[$char] ?? " ", $board);
	$t = 0; $gkbd = []; $coords = [];
	foreach ($highlight as [$hx, $hy])
		$coords[$hx * $bsize + $hy] = true;
	for ($x = 0; $x < $bsize; $x++) {
		$gstr = [];
		for ($y = 0; $y < $bsize; $y++) {
			$isHigh = $coords[$t] ?? false;
			$text = $isHigh ? "[".$board[$t]."]" : $board[$t];
			$cb = (($board[$t] == " ") && ($isActive)) ? ("$t-$x-$y") : "-";
			$gstr[] = ["text" => $text, "callback_data" => $cb];
			$t++;
		} $gkbd[] = $gstr;
	} return json_encode(["inline_keyboard" => $gkbd]);
}

// heuristic method for computer turn
function getSmartMove($board, $bsize, $comp_sign, $user_sign) {
	$free_positions = array_keys(str_split($board), EMPTY_CELL);
	$winLength = getWinLenght($bsize);
	$moveStack = ["block" => [], "nice" => [], "threat" => []];
	foreach ($free_positions as $pos) {
		$x = intdiv($pos, $bsize); $y = $pos % $bsize;
		if (checkLines(substr_replace($board, $comp_sign, $pos, 1), $bsize, $comp_sign, $x, $y, $winLength, true)["win"])
			return $pos; // search win
		if (checkLines(substr_replace($board, $user_sign, $pos, 1), $bsize, $user_sign, $x, $y, $winLength, true)["win"])
			$moveStack["block"][] = $pos; // block win
		if (count(checkLines(substr_replace($board, $comp_sign, $pos, 1), $bsize, $comp_sign, $x, $y, $winLength-1, false)["lines"]) > 0)
			$moveStack["nice"][] = $pos; // search nice
		if (count(checkLines(substr_replace($board, $user_sign, $pos, 1), $bsize, $user_sign, $x, $y, $winLength-1, false)["lines"]) > 0)
			$moveStack["threat"][] = $pos; // block threat
	} foreach (["block", "nice", "threat"] as $priority)
        if (!empty($moveStack[$priority]))
            return $moveStack[$priority][array_rand($moveStack[$priority])];
	$center = ($bsize % 2 == 0) ? (int)($bsize*$bsize/2+$bsize/2-1) : (int)floor($bsize*$bsize/2);
	if ($board[$center] == EMPTY_CELL) return $center; // take center
	$corners = [0, $bsize - 1, $bsize * ($bsize - 1)];
	foreach ($corners as $corner)
		if ($board[$corner] == EMPTY_CELL) return $corner; // take corner
	return $free_positions[array_rand($free_positions)]; // any free place
}

// make whole turn
function processTurn($board, $bsize, $sign, $pos, $chat_id, $msg_id, $lang_ul, $isUser) {
	global $symbols; $winLength = getWinLenght($bsize);
	$move = makeMove($board, $bsize, $sign, $pos);
	$board = $move["board"]; $x = $move["x"]; $y = $move["y"];
	$state = "";
	$winCheck = checkLines($board, $bsize, $sign, $x, $y, $winLength, true);
	if ($winCheck["win"]) { // if win
		trequest("editMessageText", ["chat_id" => $chat_id, "message_id" => $msg_id,
			"text" => $isUser ? $lang_ul["game-win"] : $lang_ul["game-lose"], 
			"reply_markup" => drawBoard($board, false, $winCheck["line"])]);
		$state = $isUser ? "win" : "lose";
		return ["board" => $board, "state" => $state, "game_over" => true];
	} elseif (isDraw($board)) { // if no more moves
		trequest("editMessageText", ["chat_id" => $chat_id, "message_id" => $msg_id,
			"text" => $lang_ul["game-draw"], "reply_markup" => drawBoard($board, false)]);
		$state = "draw";
		return ["board" => $board, "state" => $state, "game_over" => true];
	} else { // game continues
		trequest("editMessageText", ["chat_id" => $chat_id, "message_id" => $msg_id,
			"text" => $lang_ul["game-xo"].$bsize."x".$bsize . $lang_ul["xo-win-need"].$winLength
				."\n".$symbols[(($sign == PLAYER_X) ? PLAYER_O : PLAYER_X)]
				." ".($isUser ? $lang_ul["turn-comp"] : $lang_ul["turn-user"]), 
				"reply_markup" => drawBoard($board, ($isUser ? false : true), [[$x, $y]])]);
		return ["board" => $board, "state" => $state, "game_over" => false];
	}
}

// make user/computer move
function makeMove($board, $bsize, $sign, $pos) {
	$board = substr_replace($board, $sign, $pos, 1);
	$x = intdiv($pos, $bsize); $y = $pos % $bsize;
	return ["board" => $board, "x" => $x, "y" => $y];
}

// how big for win?
function getWinLenght($bsize) {
	return ($bsize > 4) ? ($bsize - 1) : $bsize; // better: 3-3 4-4 5-4 6-5 7-6 8-7
	// return 3 + floor(($bsize - 1) / 3); // magic: 3-3 4-4 5-4 6-4 7-5 8-5
	// return $bsize; // usually impossible - no one win
}

// no more moves?
function isDraw($board) {
	return strpos($board, EMPTY_CELL) === false;
}

// search/block win/threat
function checkLines($board_str, $bsize, $sign, $x, $y, $length, $stop_on_first = false) {
    $board = str_split($board_str);
    $found_lines = [];
    $checkLine = function($line, $indices) use ($sign, $length, &$found_lines, $stop_on_first) {
        $count = 0; $winningCoords = [];
        foreach ($line as $i => $cell) {
            if ($cell === $sign) {
                $count++;
                $winningCoords[] = $indices[$i];
                if ($count >= $length) {
                    $found_lines[] = $winningCoords;
                    if ($stop_on_first) return true; // win return immideately
                }
            } else {
                $count = 0;
                $winningCoords = [];
            }
        } return false;
    };
    $lines = [ // lines horisontal, vertical, deagonals
        ["data" => array_slice($board, $x * $bsize, $bsize), "indices" => array_map(fn($col) => [$x, $col], range(0, $bsize - 1))],
        ["data" => array_map(fn($i) => $board[$i * $bsize + $y], range(0, $bsize - 1)), "indices" => array_map(fn($row) => [$row, $y], range(0, $bsize - 1))],
        ["data" => [], "indices" => []], // main diag
        ["data" => [], "indices" => []], // anti diag
    ];
    $diag_x = $x - min($x, $y); $diag_y = $y - min($x, $y);
    while ($diag_x < $bsize && $diag_y < $bsize) { // main diag
        $lines[2]["data"][] = $board[$diag_x * $bsize + $diag_y];
        $lines[2]["indices"][] = [$diag_x, $diag_y];
        $diag_x++; $diag_y++;
    }
    $diag_x = $x - min($x, $bsize - 1 - $y);
    $diag_y = $y + min($x, $bsize - 1 - $y);
    while ($diag_x < $bsize && $diag_y >= 0) { // anti diag
        $lines[3]["data"][] = $board[$diag_x * $bsize + $diag_y];
        $lines[3]["indices"][] = [$diag_x, $diag_y];
        $diag_x++; $diag_y--;
    }
    foreach ($lines as $line) // check all directions
        if ($checkLine($line["data"], $line["indices"]))
            return ["win" => true, "line" => $found_lines[0]];
    return ["win" => false, "lines" => $found_lines];
}

// get user request
$content = file_get_contents("php://input");
$input = json_decode($content, TRUE);

// user press button
if (isset($input["callback_query"])) {
	$cb_id = $input["callback_query"]["id"];
	$response = trequest("answerCallbackQuery", array("callback_query_id" => $cb_id));
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
		$board = $row["xoline"]; $bsize = $row["size"];
		$who_starts = (bool)$row["start"];
		$user_sign = ($who_starts) ? PLAYER_X : PLAYER_O;
		$comp_sign = (!$who_starts) ? PLAYER_X : PLAYER_O;

		// user turn
		$user_pos = explode("-", $cb_data)[0];
		$userTurn = processTurn($board, $bsize, $user_sign, $user_pos, $chat_id, $msg_id, $lang[$ul], true);
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
		$comp_pos = getSmartMove($board, $bsize, $comp_sign, $user_sign);
		$compTurn = processTurn($board, $bsize, $comp_sign, $comp_pos, $chat_id, $msg_id, $lang[$ul], false);
		if ($compTurn["game_over"]) {
			switch ($compTurn["state"]) {
				case "lose": $slose++; break;
				case "win": $swin++; break;
				case "draw": $sdraw++; break;
			} update_data($chat_id, ["statwin" => $swin, "statlose" => $slose, "statdraw" => $sdraw]);
			return;
		} $board = $compTurn["board"];

		update_data($chat_id, ["xoline" => $board]);
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
				$bsize = (int)$user_msg;
				update_data($chat_id, ["size" => $bsize]);
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["size-saved"], 
					"reply_markup" => draw_menu($lang[$ul], "main")]);
				break;
			} // ask & save size }

			// main menu -> new game
			case "/game": case "/game@pp_tictactoe_bot": case $lang[$ul]["menu-new"]: {
				// make new game string
				$bsize = $row["size"]; $winLength = getWinLenght($bsize);
				$board = str_repeat(EMPTY_CELL, $bsize * $bsize);
				$who_starts = (bool)rand(0, 1); // 1 - user, 0 - computer
				$user_sign = ($who_starts) ? PLAYER_X : PLAYER_O;
				if (!$who_starts) { 
					$comp_pos = getSmartMove($board, $bsize, PLAYER_X, PLAYER_O);
					$board = substr_replace($board, PLAYER_X, $comp_pos, 1); 
				}

				// draw game keyboard
				$answer = trequest("sendMessage", ["chat_id" => $chat_id, 
					"text" => $lang[$ul]["game-xo"].$bsize."x".$bsize 
					.$lang[$ul]["xo-win-need"].$winLength 
					."\n".$symbols[$user_sign]." ".$lang[$ul]["turn-user"], 
					"reply_markup" => drawBoard($board, true)]);
				$tresponse = json_decode($answer, true);
				$msg_id = $tresponse["result"]["message_id"];
				update_data($chat_id, ["start" => $who_starts, "xoline" => $board, "msg_id" => $msg_id]);
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
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["help-xo"]
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
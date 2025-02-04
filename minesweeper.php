<?php
// bot: @pp_minesweeper_bot
// name: 💣 Minesweeper | Сапер
// about: 
// 💣 Minesweeper game
// Made by @Stler
// desc: 
// 💣 Welcome to Minesweeper!
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
// `complex` TINYINT, `isfirst` BOOLEAN, `isdig` BOOLEAN, `minefield` TEXT, `cover` TEXT,
// `statwin` INT UNSIGNED NOT NULL DEFAULT 0, `statlose` INT UNSIGNED NOT NULL DEFAULT 0, 
// PRIMARY KEY (`id`)) ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;";
// if (mysqli_query($dblink, $dbcreate))
// echo "<br>Table created";
// else echo mysqli_error($dblink);
// mysqli_close($dblink);

// globals
require_once "connect.php";
$dblink = mysqli_connect($dbhost, $dbuser, $dbpswd, $dbname);
$tbname .= $suffix["msw"];
$bottoken = $tokens["msw"];
require_once "api_bd_menu.php";
$lang = json_decode(file_get_contents("languages.json"), true);
$flags = ["en" => "🇬🇧", "ru" => "🇷🇺"];
$menus = ["main" => [["menu-new", "menu-stat"], ["menu-hlp", "menu-links", "menu-set"]],
	"chus-cmplx" => [["cmplx-easy", "cmplx-norm", "cmplx-hard"], ["set-back"]],
	"set" => [["menu-cmplx", "menu-lang"], ["main-back"]]];

// placing mines on field
function initField($field, $mines_count, $x, $y) {
	$size = count($field);
	for ($i = 0; $i < $mines_count; $i++) {
		do {
			$mx = rand(0, $size - 1);
			$my = rand(0, $size - 1);
		} while ($field[$mx][$my] === 9 || // if mine - try another place
			abs($mx - $x) <= 1 && abs($my - $y) <= 1); // around the first click
		$field[$mx][$my] = 9;
		// counter on nearest cells
		for ($dx = -1; $dx <= 1; $dx++)
			for ($dy = -1; $dy <= 1; $dy++) {
				$nx = $mx + $dx; $ny = $my + $dy;
				if ($nx >= 0 && $nx < $size && $ny >= 0 && $ny < $size && $field[$nx][$ny] !== 9)
					$field[$nx][$ny]++;
			}
	} return $field;
}

// game message
function getGameMessage($lang_ul, $isDig, $mines_count, $cover) {
	return $lang_ul["game-mines"]
	." | ".$lang_ul["mines-regime"].($isDig ? $lang_ul["mode-dig"] : $lang_ul["mode-flag"])
	."\n".$lang_ul["mines-count"].$mines_count
	." | ".$lang_ul["mines-mark"].countMark($cover);
} // how much marked
function countMark($cover) {
	$markedCells = 0;
	foreach ($cover as $key => $row) {
		if (!is_array($row)) continue;
		foreach ($row as $cell)
			if ($cell === "F") $markedCells++;
	} return $markedCells;
}

// game keyboard
function drawField($lang_ul, $field, $cover, $isDig, $isGameOver, $bx=-1, $by=-1) {
	$symbols = [12 => "🚩", 11 => "❌", 10 => "💥", 9 => "💣", 
		8 => "8️⃣", 7 => "7️⃣", 6 => "6️⃣", 5 => "5️⃣", 4 => "4️⃣", 
		3 => "3️⃣", 2 => "2️⃣", 1 => "1️⃣", 0 => " ", -1 => "⬜️"];
	$size = count($field);
	$gkbd = '{"inline_keyboard":[';
	$gkbd .= '[{"text":"'.$lang_ul["mode-dig"].'","callback_data":"'.($isGameOver ? "-" : "switch-dig") . '"},';
	$gkbd .= '{"text":"'.$lang_ul["mode-flag"].'","callback_data":"'.($isGameOver ? "-" : "switch-flag") . '"}],';
	if (($bx >= 0) && ($by >= 0)) $field[$bx][$by] = 10; // explosion 💥
	for ($x = 0; $x < $size; $x++) {
		$gkbd .= '[';
		for ($y = 0; $y < $size; $y++) {
			$fcell = $field[$x][$y];
			$ccell = $cover[$x][$y];
			if ($isGameOver) $text = ($ccell === "F") 
				? (($fcell === 9) ? $symbols[12] : $symbols[11]) 
				: $symbols[$fcell];
			else $text = ($ccell === true) 
				? $symbols[$fcell] 
				: ($ccell === "F" ? $symbols[12] : $symbols[-1]);
			$callback_data = ($ccell === true || $isGameOver) 
				? "-" 
				: ($isDig ? "dig-" : "flag-") . "$x-$y";
			$gkbd .= '{"text":"' . $text . '","callback_data":"' . $callback_data . '"},';
		} $gkbd = rtrim($gkbd, ",") . '],';
	} $gkbd = rtrim($gkbd, ",") . ']}';
	return $gkbd;
}

// make user move
function handleMove($field, $cover, $x, $y) {
	if ($field[$x][$y] === 9) return ["state" => "game_over"]; // explosion 💥 => game over and lose
	uncover($field, $cover, $x, $y); // open cell
	if ($cover["closedCount"] === 0) // all not-mines are opened => game over & win
		return ["state" => "game_won"];
	return ["state" => "game_play", "cover" => $cover];
}

// open cells recursively
function uncover(&$field, &$cover, $x, $y) {
	$size = count($field);
	if ($x < 0 || $x >= $size || $y < 0 || $y >= $size || $cover[$x][$y]) return;
	$cover[$x][$y] = true; // open cell
	$cover["closedCount"]--;
	if ($field[$x][$y] === 0) // open neihbors
		for ($dx = -1; $dx <= 1; $dx++)
			for ($dy = -1; $dy <= 1; $dy++)
				uncover($field, $cover, $x + $dx, $y + $dy);
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
		$swin = $row["statwin"]; $slose = $row["statlose"];
		$mines = $row["complex"];
		$field = json_decode($row["minefield"], true);
		$cover = json_decode($row["cover"], true);
		$isDig = (bool)$row["isdig"]; $isFirstMove = (bool)$row["isfirst"];

		if ($cb_data === "switch-dig" || $cb_data === "switch-flag") {
			$isDig = $cb_data === "switch-dig" ? true : false;
			update_data($chat_id, ["isdig" => $isDig]);
			trequest("editMessageText", ["chat_id" => $chat_id, "message_id" => $msg_id,
				"text" => getGameMessage($lang[$ul], $isDig, $mines, $cover),	
				"reply_markup" => drawField($lang[$ul], $field, $cover, $isDig, false)]);
			return;
		} else {
			list($action, $x, $y) = explode("-", $cb_data);
			$x = (int)$x; $y = (int)$y;
			if ($action === "dig") {
				if ($isFirstMove) { // make first click safe
					$field = initField($field, $mines, $x, $y);
					update_data($chat_id, ["minefield" => $field, "isfirst" => false]);
				}

				if (isset($cover[$x][$y]) && $cover[$x][$y] === "F") return; // don't dig if flag

				$result = handleMove($field, $cover, $x, $y); // dig
				if ($result["state"] == "game_over") { // explosion 💥
					$slose++; update_data($chat_id, ["statlose" => $slose]);
					trequest("editMessageText", ["chat_id" => $chat_id, "message_id" => $msg_id, 
						"text" => $lang[$ul]["game-lose"],
						"reply_markup" => drawField($lang[$ul], $field, $cover, $isDig, true, $x, $y)]);
				} elseif ($result["state"] == "game_won") { // all not-mines are opened
					$swin++; update_data($chat_id, ["statwin" => $swin]);
					trequest("editMessageText", ["chat_id" => $chat_id, "message_id" => $msg_id, 
						"text" => $lang[$ul]["game-win"],
						"reply_markup" => drawField($lang[$ul], $field, $cover, $isDig, true)]);
				} else { // game play
					update_data($chat_id, ["cover" => $result["cover"]]);
					trequest("editMessageReplyMarkup", ["chat_id" => $chat_id, "message_id" => $msg_id,
						"reply_markup" => drawField($lang[$ul], $field, $result["cover"], $isDig, false)]);
				}
			} elseif ($action === "flag") {
				if ($cover[$x][$y] === false || $cover[$x][$y] === "F") {
					$cover[$x][$y] = ($cover[$x][$y] === "F") ? false : "F"; // flag up/down
					update_data($chat_id, ["cover" => $cover]);
					trequest("editMessageText", ["chat_id" => $chat_id, "message_id" => $msg_id,
						"text" => getGameMessage($lang[$ul], $isDig, $mines, $cover),
						"reply_markup" => drawField($lang[$ul], $field, $cover, $isDig, false)]);
				}
			}
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
		$user_name = $fn." ".$ln; $complex = 12;
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
					"text" => $lang[$ul]["chus-cmplx"].$lang[$ul]["mines-cmplx"], 
					"reply_markup" => draw_menu($lang[$ul], "chus-cmplx")]);
				break;
			} // difficulty menu -> easy / normal / hard
			case $lang[$ul]["cmplx-easy"]: {
				update_data($chat_id, ["complex" => 8]);
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["cmplx-saved"], 
					"reply_markup" => draw_menu($lang[$ul], "main")]);
				break;
			} case $lang[$ul]["cmplx-norm"]: {
				update_data($chat_id, ["complex" => 12]);
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["cmplx-saved"], 
					"reply_markup" => draw_menu($lang[$ul], "main")]);
				break;
			} case $lang[$ul]["cmplx-hard"]: {
				update_data($chat_id, ["complex" => 16]);
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["cmplx-saved"], 
					"reply_markup" => draw_menu($lang[$ul], "main")]);
				break;
			} // ask & save difficulty }

			// main menu -> new game
			case "/game": case "/game@pp_minesweeper_bot": case $lang[$ul]["menu-new"]: {
				$mines = $row["complex"]; $fsize = 8; // don't make bigger
				$field = array_fill(0, $fsize, array_fill(0, $fsize, 0)); // make blank field
				$cover = array_fill(0, $fsize, array_fill(0, $fsize, false)); // all cells closed
				$cover["closedCount"] = $fsize * $fsize - $mines; $isDig = true;
				
				// draw game keyboard
				$answer = trequest("sendMessage", ["chat_id" => $chat_id, 
					"text" => getGameMessage($lang[$ul], $isDig, $mines, $cover), 
					"reply_markup" => drawField($lang[$ul], $field, $cover, $isDig, false)]);
				$tresponse = json_decode($answer, true);
				$msg_id = $tresponse["result"]["message_id"];
				update_data($chat_id, ["minefield" => $field, "cover" => $cover, 
					"isdig" => $isDig, "isfirst" => true, "msg_id" => $msg_id]);
				break;
			}

			// main menu -> stat
			case "/stat": case $lang[$ul]["menu-stat"]: {
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["stat-ttl"]
					."`".$lang[$ul]["stat-all"].str_pad((string)($row["statwin"]+$row["statlose"]), (20 - mb_strlen($lang[$ul]["stat-all"])), " ", STR_PAD_LEFT)."`"
					."`".$lang[$ul]["stat-win"].str_pad((string)($row["statwin"]), (20 - mb_strlen($lang[$ul]["stat-win"])), " ", STR_PAD_LEFT)."`"
					."`".$lang[$ul]["stat-lose"].str_pad((string)($row["statlose"]), (20 - mb_strlen($lang[$ul]["stat-lose"])), " ", STR_PAD_LEFT)."`", 
					"parse_mode" => "Markdown", "reply_markup" => draw_menu($lang[$ul], "main")]);
				break;
			}

			// main menu -> help
			case "/help": case $lang[$ul]["menu-hlp"]: {
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["help-mines"]
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
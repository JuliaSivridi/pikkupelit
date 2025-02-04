<?php
// bot: @pp_battleship_bot
// name: 🚢 Battle Ship | Морской Бой
// about: 
// 🚢 Battle Ship game
// Made by @Stler
// desc: 
// 🚢 Welcome to Battle Ship!
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
// `cstack` TEXT, `turn` BOOLEAN, `ccover` TEXT, `csea` TEXT, `clives` TINYINT, `ucover` TEXT, `usea` TEXT, `ulives` TINYINT, 
// `statwin` INT UNSIGNED NOT NULL DEFAULT 0, `statlose` INT UNSIGNED NOT NULL DEFAULT 0, 
// PRIMARY KEY (`id`)) ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;";
// if (mysqli_query($dblink, $dbcreate))
// echo "<br>Table created";
// else echo mysqli_error($dblink);
// mysqli_close($dblink);

// globals
require_once "connect.php";
$dblink = mysqli_connect($dbhost, $dbuser, $dbpswd, $dbname);
$tbname .= $suffix["bts"];
$bottoken = $tokens["bts"];
require_once "api_bd_menu.php";
$lang = json_decode(file_get_contents("languages.json"), true);
$flags = ["en" => "🇬🇧", "ru" => "🇷🇺"];
$menus = ["main" => [["menu-new", "menu-stat"], ["menu-hlp", "menu-links", "menu-set"]],
	"set" => [["menu-lang"], ["main-back"]]];
$symbols = [4 => "☠️", 3 => "🔥", 2 => "🚢", 1 => "🌀", 0 => "🟦", -1 => "⬜️"];

// placing ships on board
function placeShips($bsize = 7) {
	$board = array_fill(0, $bsize, array_fill(0, $bsize, 0));
	$ships = [4 => 1, 3 => 2, 2 => 3, 1 => 4]; // ship size => count of ships
	foreach ($ships as $ssize => $scount)
		if ($ssize > 1)	
			for ($i = 0; $i < $scount; $i++) {
				$attempts = 0;
				while ($attempts < 100) {
					$d = rand(0, 1); // 0 — hor, 1 — vert
					$x = rand(0, $bsize-1);
					$y = rand(0, $bsize-1);
					if (canPlaceShip($board, $x, $y, $ssize, $d)) {
						placeShip($board, $x, $y, $ssize, $d);
						break;
					}
				} $attempts++;
			}
		else { // for small ships take all possible free space, bcz random mb won't find them
			$freeCells = getAvailableSingleShipCells($board);
			shuffle($freeCells);
			for ($i = 0; $i < $scount && !empty($freeCells); $i++) {
				[$x, $y] = array_pop($freeCells);
				$board[$x][$y] = 2;
				$freeCells = array_values(array_filter($freeCells, fn($cell) => !isTouching($board, $cell[0], $cell[1])));
			}
		}
	return $board;
} function canPlaceShip($board, $x, $y, $size, $d) {
	$bsize = count($board);
	for ($i = 0; $i < $size; $i++) {
		$nx = $x + ($d === 1 ? $i : 0); // vert
		$ny = $y + ($d === 0 ? $i : 0); // hor
		if ($nx >= $bsize || $ny >= $bsize // out of border
			|| $board[$nx][$ny] !== 0 // not free
			|| isTouching($board, $nx, $ny)) // neihgbors on sides
			return false;
	} return true;
} function placeShip(&$board, $x, $y, $size, $d) {
	for ($i = 0; $i < $size; $i++) {
		$nx = $x + ($d === 1 ? $i : 0); // vert
		$ny = $y + ($d === 0 ? $i : 0); // hor
		$board[$nx][$ny] = 2;
	}
} function getAvailableSingleShipCells($board) {
	$bsize = count($board);
	$candidates = [];
	for ($x = 0; $x < $bsize; $x++)
		for ($y = 0; $y < $bsize; $y++)
			if ($board[$x][$y] === 0 && !isTouching($board, $x, $y))
				$candidates[] = [$x, $y];
	return $candidates;
} function isTouching($board, $x, $y) { // check neihgbors only on sides, not diags like in classical game
	$bsize = count($board);
	foreach ([[-1, 0], [1, 0], [0, -1], [0, 1]] as [$dx, $dy]) {
		$tx = $x + $dx; $ty = $y + $dy;
		if ($tx >= 0 && $tx < $bsize && $ty >= 0 && $ty < $bsize && $board[$tx][$ty] === 2)
			return true;
	} return false;
}

// game message
function getGameMessage($lang_ul, $board, $isUser = false, $isGameOver = false, $isWin = false) {
	global $symbols;
	if ($isGameOver) $text = ($isWin ? $lang_ul["game-win"] : $lang_ul["game-lose"])."\n";
	else $text = $lang_ul["game-sea"]."\n";
	foreach ($board as $row) {
		$text .= "\n";
		foreach ($row as $cell)
			$text .= $symbols[$cell];
	} if (!$isGameOver) $text .= "\n\n".($isUser ? "🙂".$lang_ul["turn-user"] : "🤖".$lang_ul["turn-comp"]);
	return $text;
}

// game keyboard
function drawBoard($cover, $board, $isActive, $isGameOver) {
	global $symbols;
	$bsize = count($board);
	if ($isGameOver) // open board at the end of the game
		$cover = array_fill(0, $bsize, array_fill(0, $bsize, true));
	$gkbd = [];
	foreach ($board as $r => $row) {
		$gstr = [];
		foreach ($row as $c => $cell) {
			$isOpen = $cover[$r][$c];
			$text = ($isOpen) ? $symbols[$cell] : $symbols[-1];
			$cb = ($isOpen || !$isActive) ? "-" : "try-$r-$c";
			$gstr[] = ["text" => $text, "callback_data" => $cb];
		} $gkbd[] = $gstr;
	} return json_encode(["inline_keyboard" => $gkbd]);
}

// whole answer
function updateGameMessage($chat_id, $msg_id, $lang_ul, $uboard, $cboard, $ccover, $isUser, $isGameOver, $isWin) {
    trequest("editMessageText", ["chat_id" => $chat_id, "message_id" => $msg_id,
        "text" => getGameMessage($lang_ul, $uboard, $isUser, $isGameOver, $isWin),
        "reply_markup" => drawBoard($ccover, $cboard, $isUser, $isGameOver)]);
}

// comp search ships
function fillStack($ucover, $uboard, $r, $c, $cstack) {
	$bsize = count($uboard);
	$direction = null; // 'h' - horisontal, 'v' - vertical
	foreach ([[-1, 0], [1, 0]] as [$dx, $dy])
		if ($r + $dx >= 0 && $r + $dx < $bsize && $uboard[$r + $dx][$c] == 3) {
			$direction = 'v'; break;
		}
	foreach ([[0, -1], [0, 1]] as [$dx, $dy])
		if ($c + $dy >= 0 && $c + $dy < $bsize && $uboard[$r][$c + $dy] == 3) {
			$direction = 'h'; break;
		}
	if ($direction === 'v') $cstack = array_filter($cstack, fn($coord) => $coord[1] == $c);
	elseif ($direction === 'h') $cstack = array_filter($cstack, fn($coord) => $coord[0] == $r);
	if ($direction === 'v') {
		foreach ([[-1, 0], [1, 0]] as [$dx, $dy]) {
			$nx = $r + $dx;
			if ($nx >= 0 && $nx < $bsize && !$ucover[$nx][$c])
				if (!in_array([$nx, $c], $cstack))
					$cstack[] = [$nx, $c];
		}
	} elseif ($direction === 'h') {
		foreach ([[0, -1], [0, 1]] as [$dx, $dy]) {
			$ny = $c + $dy;
			if ($ny >= 0 && $ny < $bsize && !$ucover[$r][$ny])
				if (!in_array([$r, $ny], $cstack))
					$cstack[] = [$r, $ny];
		}
	} else {
		foreach ([[-1, 0], [1, 0], [0, -1], [0, 1]] as [$dx, $dy]) {
			$nx = $r + $dx; $ny = $c + $dy;
			if ($nx >= 0 && $nx < $bsize && $ny >= 0 && $ny < $bsize && !$ucover[$nx][$ny])
				if (!in_array([$nx, $ny], $cstack))
					$cstack[] = [$nx, $ny];
		}
	} return $cstack;
}

// make user/computer move
function makeMove($chat_id, $cover, $board, $r, $c, $lives, $isUser, $cstack = []) {
	$isGameOver = false;
	$cover[$r][$c] = true; // open cell
	$board[$r][$c]++; // hit (2->3) or miss (0->1)
	if ($board[$r][$c] == 3) { // hit!
		$turn = $isUser;
		$checkDead = isShipDead($board, $r, $c);
		if ($checkDead["dead"]) { // if dead
			if (!$isUser) $cstack = []; // comp memory vanished
			foreach ($checkDead["ship"] as [$dr, $dc])
				if ($board[$dr][$dc] == 3) {
					$board[$dr][$dc] = 4;
					$lives--;
				}
			openSea($cover, $board, $checkDead["ship"]);
			if ($lives <= 0) // game over
				$isGameOver = true;
		} else { if (!$isUser) $cstack = fillStack($cover, $board, $r, $c, $cstack); }
	} else { // miss
		$turn = !$isUser;
	} if (!$isUser) update_data($chat_id, ["cstack" => $cstack]);
	update_data($chat_id, ["turn" => $turn, 
		($isUser ? "csea" : "usea") => $board, 
		($isUser ? "ccover" : "ucover") => $cover, 
		($isUser ? "clives" : "ulives") => $lives]);
	return ["cover" => $cover, "board" => $board, "lives" => $lives, 
		"cstack" => $cstack, "turn" => $turn, "game_over" => $isGameOver];
}

// is dead?
function isShipDead($board, $x, $y) {
	$bsize = count($board);
    $ship = []; $visited = [];
    $stack = [[$x, $y]];
    while (!empty($stack)) {
        [$cx, $cy] = array_pop($stack);
        $visited["$cx-$cy"] = true;
        $ship[] = [$cx, $cy];
        foreach ([[-1, 0], [1, 0], [0, -1], [0, 1]] as [$dx, $dy]) {
            $nx = $cx + $dx; $ny = $cy + $dy; // check neihgbors on sides
            if ($nx >= 0 && $nx < $bsize && $ny >= 0 && $ny < $bsize
                && !isset($visited["$nx-$ny"])
                && ($board[$nx][$ny] === 3 || $board[$nx][$ny] === 2))
                $stack[] = [$nx, $ny]; // not dead yet ship part
        }
    }
	foreach ($ship as [$sx, $sy])
        if ($board[$sx][$sy] === 2) // not dead yet ship part
            return ["dead" => false, "ship" => []];
    return ["dead" => true, "ship" => $ship];
}

// if ship dead - open sea around
function openSea(&$cover, $board, $ship) {
    $bsize = count($board);
    foreach ($ship as [$x, $y])
        foreach ([[-1, 0], [1, 0], [0, -1], [0, 1]] as [$dx, $dy]) {
            $nx = $x + $dx; $ny = $y + $dy;
            if ($nx >= 0 && $nx < $bsize && $ny >= 0 && $ny < $bsize
                && $board[$nx][$ny] === 0 && !$cover[$nx][$ny])
                $cover[$nx][$ny] = true;
        }
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
		$bsize = 7; // don't make bigger
		$ucover = json_decode($row["ucover"], true); $ccover = json_decode($row["ccover"], true);
		$uboard = json_decode($row["usea"], true); $cboard = json_decode($row["csea"], true);
		$ulives = $row["ulives"]; $clives = $row["clives"];
		$cstack = json_decode($row["cstack"], true) ?? [];
		$turn = (bool)$row["turn"];

		// user turn
		list($action, $r, $c) = explode("-", $cb_data);
		$r = (int)$r; $c = (int)$c;
		$userMove = makeMove($chat_id, $ccover, $cboard, $r, $c, $clives, true);
		$ccover = $userMove["cover"]; $cboard = $userMove["board"]; $turn = $userMove["turn"];
		updateGameMessage($chat_id, $msg_id, $lang[$ul], $uboard, $cboard, $ccover, 
			$turn, $userMove["game_over"], $userMove["game_over"]);
		if ($userMove["game_over"]) { // user win
			$swin++; update_data($chat_id, ["statwin" => $swin]);
			return;
		}
		if ($turn) return; // user turn again

		if (!$turn) { // computer turn
			do {
				sleep(1);
				if (!empty($cstack)) [$r, $c] = array_shift($cstack); // if ship not dead yet
				else do { // new search
					$r = rand(0, $bsize - 1);
					$c = rand(0, $bsize - 1);
				} while ($ucover[$r][$c] === true);
				$compMove = makeMove($chat_id, $ucover, $uboard, $r, $c, $ulives, false, $cstack);
				$ucover = $compMove["cover"]; $uboard = $compMove["board"]; $ulives = $compMove["lives"];
				$cstack = $compMove["cstack"]; $turn = $compMove["turn"];
				updateGameMessage($chat_id, $msg_id, $lang[$ul], $uboard, $cboard, $ccover, 
					$turn, $compMove["game_over"], false);
				if ($compMove["game_over"]) { // user lose
					$slose++; update_data($chat_id, ["statlose" => $slose]);
					return;
				}
			} while (!$turn); // computer turn again
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
			case "/game": case "/game@pp_seabattle_bot": case $lang[$ul]["menu-new"]: {
				$bsize = 7; // don't make bigger
				$cstack = []; $turn = (bool)rand(0, 1); // 1 - user, 0 - computer
				$ccover = array_fill(0, $bsize, array_fill(0, $bsize, false)); // all cells closed
				$ucover = array_fill(0, $bsize, array_fill(0, $bsize, false)); // all cells closed
				$cboard = placeShips($bsize); $uboard = placeShips($bsize); // placing ships on boards
				$clives = array_sum(array_map(fn($row) => array_sum(array_map(fn($cell) => $cell === 2 ? 1 : 0, $row)), $cboard));
				$ulives = array_sum(array_map(fn($row) => array_sum(array_map(fn($cell) => $cell === 2 ? 1 : 0, $row)), $uboard));

				$answer = trequest("sendMessage", ["chat_id" => $chat_id, 
					"text" => getGameMessage($lang[$ul], $uboard, $turn, false), 
					"reply_markup" => drawBoard($ccover, $cboard, $turn, false)]);
				$tresponse = json_decode($answer, true);
				$msg_id = $tresponse["result"]["message_id"];
				update_data($chat_id, ["msg_id" => $msg_id, "cstack" => $cstack, "turn" => true, 
				"ccover" => $ccover, "csea" => $cboard, "clives" => $clives, 
				"ucover" => $ucover, "usea" => $uboard, "ulives" => $ulives]);

				if (!$turn) do { // computer turn
					sleep(1);
					if (!empty($cstack)) [$r, $c] = array_shift($cstack); // if ship not dead yet
					else do { // new search
						$r = rand(0, $bsize - 1);
						$c = rand(0, $bsize - 1);
					} while ($ucover[$r][$c] === true);
					$compMove = makeMove($chat_id, $ucover, $uboard, $r, $c, $ulives, false, $cstack);
					$ucover = $compMove["cover"]; $uboard = $compMove["board"]; $ulives = $compMove["lives"];
					$cstack = $compMove["cstack"]; $turn = $compMove["turn"];
					updateGameMessage($chat_id, $msg_id, $lang[$ul], $uboard, $cboard, $ccover, 
						$turn, $compMove["game_over"], false);
					if ($compMove["game_over"]) return; // user lose
				} while (!$turn); // computer turn again
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
				trequest("sendMessage", ["chat_id" => $chat_id, "text" => $lang[$ul]["help-sea"]
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
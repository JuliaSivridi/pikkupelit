<?php
// send telegram request
function trequest($method, $inputarray) {
	global $bottoken;
	$inputstring = http_build_query($inputarray, null, "&", PHP_QUERY_RFC3986);
	$options = ["http" => ["method" => "POST",
		"header" => "Content-type: application/x-www-form-urlencoded\r\n",
		"ignore_errors" => true,
		"content" => $inputstring]];
	$request="https://api.telegram.org/bot".$bottoken."/".$method;
	$context = stream_context_create($options);
	$answer = file_get_contents($request, false, $context);
	return $answer;
}

// get data from db
function select_data($chat_id) {
	global $dblink, $tbname;
	$query = "SELECT * FROM ".$tbname." WHERE chat_id = ?";
	if ($stmt = mysqli_prepare($dblink, $query)) {
		mysqli_stmt_bind_param($stmt, "s", $chat_id);
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);
		mysqli_stmt_close($stmt);
		return $result;
	} else { return false; }
} // update multiple fields in db
function update_data($chat_id, $data) {
	global $dblink, $tbname;
	$set_parts = []; $values = []; $types = "";
	foreach ($data as $field => $value) {
		$set_parts[] = "$field = ?";
		if (is_array($value)) { $values[] = json_encode($value, JSON_UNESCAPED_UNICODE); $types .= "s"; } 
		elseif (is_bool($value)) { $values[] = $value ? 1 : 0; $types .= "i"; }
		elseif (is_int($value)) { $values[] = $value; $types .= "i"; } 
		elseif (is_float($value)) { $values[] = $value; $types .= "d"; } 
		else { $values[] = (string) $value; $types .= "s"; }
	} $values[] = $chat_id; $types .= "s";
	$query = "UPDATE $tbname SET " . implode(", ", $set_parts) . " WHERE chat_id = ?";
	if ($stmt = mysqli_prepare($dblink, $query)) {
		mysqli_stmt_bind_param($stmt, $types, ...$values);
		$success = mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
		return $success;
	} else { return false; }
}

// menu keyboard
function draw_menu($lang_ul, $type) {
	global $menus; $menu = $menus[$type] ?? [];
	$mkbd = array_map(fn($row) => array_map(fn($key) => $lang_ul[$key], $row), $menu);
	return json_encode(["keyboard" => $mkbd, "resize_keyboard" => true]);
} // inline keyboard
function draw_inline_menu($lang_ul, $type) {
	global $inline_menus; $buttons = $inline_menus[$type] ?? [];
	$inl_kbd = array_map(fn($row) => array_map(fn($button) => ["text" => $lang_ul[$button["text"]], 
		"callback_data" => $button["cb_data"]], $row), $buttons);
	return json_encode(["inline_keyboard" => $inl_kbd]);
}

// contact links keyboard
function contact_links($lang_ul) {
	$buttons = [
		[["text" => $lang_ul["contact-dev"], "url" => "tg://resolve?domain=Stler"]],
		[["text" => $lang_ul["source-code"], "url" => "https://github.com/JuliaSivridi/pikkupelit"]]
	];
	$inl_kbd = array_map(fn($row) => array_map(fn($button) => ["text" => $button["text"], 
		"url" => $button["url"]], $row), $buttons);
	return json_encode(["inline_keyboard" => $inl_kbd]);
}

// bot links keyboard
function game_links($lang_ul) {
	$buttons = [
		[["text" => $lang_ul["game-hang"], "url" => "tg://resolve?domain=pp_hangman_bot"],
		 ["text" => $lang_ul["game-rand"], "url" => "tg://resolve?domain=pp_random_bot"]],
		[["text" => $lang_ul["game-rsp"], "url" => "tg://resolve?domain=pp_ropascis_bot"]],
		[["text" => $lang_ul["game-bj"], "url" => "tg://resolve?domain=pp_blackjack_bot"],
		 ["text" => $lang_ul["game-mines"], "url" => "tg://resolve?domain=pp_minesweeper_bot"]],
		[["text" => $lang_ul["game-xo"], "url" => "tg://resolve?domain=pp_tictactoe_bot"],
		 ["text" => $lang_ul["game-sea"], "url" => "tg://resolve?domain=pp_battleship_bot"]],
		[["text" => $lang_ul["game-four"], "url" => "tg://resolve?domain=pp_fourinrow_bot"],
		 ["text" => $lang_ul["game-15"], "url" => "tg://resolve?domain=pp_npuzzle_bot"]]
	];
	$inl_kbd = array_map(fn($row) => array_map(fn($button) => ["text" => $button["text"], 
		"url" => $button["url"]], $row), $buttons);
	return json_encode(["inline_keyboard" => $inl_kbd]);
} ?>
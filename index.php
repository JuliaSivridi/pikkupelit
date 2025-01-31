<!DOCTYPE html>
<html><head>
<link rel="stylesheet" href="../style.css" />
</head><body>
<a href=../index.php><= home</a><br>

<?php
require_once "connect.php";
// $tbname .= $suffix["rnd"];
// $tbname .= $suffix["rps"];
// $tbname .= $suffix["hng"];
// $tbname .= $suffix["bjk"];
// $tbname .= $suffix["npz"];
$tbname .= $suffix["ttt"];
// $tbname .= $suffix["msw"];
// $tbname .= $suffix["fir"];
// $tbname .= $suffix["bts"];

$dblink = mysqli_connect($dbhost, $dbuser, $dbpswd, $dbname);
// $dbdrop = "DROP TABLE `".$tbname."`";
// if (mysqli_query($dblink, $dbdrop))
// 	echo "<br>Table dropped";
// else echo mysqli_error($dblink);
// $dbcreate = "CREATE TABLE `".$tbname."` (
// `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, 
// `chat_id` TEXT NOT NULL, `msg_id` BIGINT UNSIGNED, 
// `user_name` TEXT, `user_lang` VARCHAR(10), 
// PRIMARY KEY (`id`)) ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;";
// if (mysqli_query($dblink, $dbcreate))
// echo "<br>Table created";
// else echo mysqli_error($dblink);

// `random` TINYINT UNSIGNED, 
// `complex` TINYINT, `lives` TINYINT, `word` TEXT, `guess` TEXT, `letters` TEXT, 
// `ccards` TEXT, `ccost` TINYINT, `ucards` TEXT, `ucost` TINYINT, 
// `complex` TINYINT, `isfirst` BOOLEAN, `isdig` BOOLEAN, `minefield` TEXT, `cover` TEXT,
// `size` TINYINT, `nline` TEXT, 
// `size` TINYINT, `start` BOOLEAN, `xoline` TEXT, 
// `start` BOOLEAN, `board4` TEXT,
// `cstack` TEXT, `turn` BOOLEAN, `ccover` TEXT, `csea` TEXT, `clives` TINYINT, `ucover` TEXT, `usea` TEXT, `ulives` TINYINT, 

echo "<br>";
$dbquery = "select * from ".$tbname;
if ($result = mysqli_query($dblink, $dbquery)) {
	echo "<table>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr><td>[".$row["chat_id"]."]"."<td>".$row["msg_id"]
			."<td>".$row["user_name"]."<td>".$row["user_lang"];
		// echo "<td>".$row["random"];
		// echo "<td>".$row["complex"];
		// echo "<td>".$row["size"];
		// echo "<td>".$row["start"];
		// echo "<td>".$row["word"]."<td>".$row["guess"];
		// echo "<td>".$row["lives"]."<td>".$row["letters"];
		// echo "<td>".$row["ccost"]."<td>".$row["ucost"]."<td>".$row["ccards"].$row["ucards"];
		// echo "<td>".$row["isfirst"]."<td>".$row["isdig"]."<td>".$row["minefield"]."<td>".$row["cover"];
		// echo "<td>".$row["nline"];
		echo "<td>".$row["xoline"];
		// echo "<td>".$row["board4"];
		// echo "<td>".$row["turn"];
		// echo "<td>".$row["ulives"]."<td>".$row["usea"]."<td>".$row["ucover"];
		// echo "<td>".$row["cstack"]."<td>"."<td>".$row["csea"]."<td>".$row["clives"];
	} echo "</table>";
	mysqli_free_result($result);
}

// file_put_contents("input.log", print_r($input, true));
// file_put_contents("output.log", "\nCheck:" . json_encode($check, JSON_UNESCAPED_UNICODE), FILE_APPEND);
mysqli_close($dblink); ?></body></html>
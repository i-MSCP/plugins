<?php
$file = "// @generated from modules/*.js\n";
$file .= file_get_contents("modules/jush.js");
foreach (glob("modules/jush-*.js") as $filename) {
	$file .= "\n\n\n" . file_get_contents($filename);
}
file_put_contents('jush.js', $file);

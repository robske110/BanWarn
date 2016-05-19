<?php
$server = proc_open(PHP_BINARY . " ClearSky/src/pocketmine/PocketMine.php --no-wizard --disable-readline", [
	0 => ["pipe", "r"],
	1 => ["pipe", "w"],
	2 => ["pipe", "w"]
], $pipes);

fwrite($pipes[0], "version\nmakeplugin BanWarn\nstop\n\n");

while(!feof($pipes[1])){
	echo fgets($pipes[1]);
}

fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);

echo "\n\nReturn value: ". proc_close($server) ."\n";

if(count(glob("plugins/DevTools/BanWarn*.phar")) === 0){
	echo "Failed to create BanWarn phar!\n";
	exit(1);
}else{
	echo "BanWarn phar created!\n";
	exit(0);
}

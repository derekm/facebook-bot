<?php

$loader = require_once __DIR__ . '/../vendor/autoload.php';

use PHPWorldWide\FacebookBot\Connection;
use PHPWorldWide\FacebookBot\Bot;

$conn = new Connection("gimmewarez...", "...", "1171016216243606");

$bot = new Bot($conn);

$bot->run();

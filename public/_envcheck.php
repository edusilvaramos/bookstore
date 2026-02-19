<?php
header('Content-Type: text/plain');
echo "APP_ENV=" . ($_SERVER['APP_ENV'] ?? getenv('APP_ENV') ?: 'null') . PHP_EOL;
echo "APP_DEBUG=" . ($_SERVER['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: 'null') . PHP_EOL;

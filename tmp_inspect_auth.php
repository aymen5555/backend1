<?php
$file = __DIR__ . '/app/Http/Controllers/Auth/AuthController.php';
$code = file_get_contents($file);
$lines = explode("\n", $code);
$start = 360;
$end = 405;
for ($i = $start; $i <= $end && $i <= count($lines); $i++) {
    $line = $lines[$i - 1];
    printf("%03d: %s\n", $i, $line);
}

echo "\n--- TOKENS ---\n";
$tokens = token_get_all($code);
foreach ($tokens as $idx => $token) {
    if (is_array($token)) {
        list($id, $text, $line) = $token;
        if ($line >= $start && $line <= $end) {
            printf("%03d: %s %s\n", $line, token_name($id), trim($text));
        }
    }
}

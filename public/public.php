<?php

require_once __DIR__ . '/../config/config.php';

$tenant = $_GET['tenant'] ?? null;
$path   = $_GET['path'] ?? '';

if (!$tenant || !$path) {
	http_response_code(403);
	exit;
}

$cfg = cdn_load_tenant($tenant);

$file = "/mnt/ssd/cdn/$tenant/public/$path";
if (!is_file($file)) {
	http_response_code(404);
	exit;
}

header("Cache-Control: public, max-age=31536000, immutable");
header("X-Sendfile: $file");
http_response_code(200);
exit;

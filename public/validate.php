<?php

require_once __DIR__ . '/../config/config.php';

$tenant = $_GET['tenant'] ?? null;
$path   = $_GET['path'] ?? '';
$tid    = $_GET['tid'] ?? '';
$exp    = isset($_GET['exp']) ? intval($_GET['exp']) : 0;
$sig    = $_GET['sig'] ?? '';

if (!$tenant || !$path || !$tid || $exp <= time()) {
	http_response_code(403);
	exit;
}

$cfg = cdn_load_tenant($tenant);

$result = cdn_validate_signed_url($cfg, $tenant, $path, $tid, $exp, $sig);
if (!$result) {
	http_response_code(403);
	exit;
}

$file = "/mnt/ssd/cdn/$tenant/private/$path";
if (!is_file($file)) {
	http_response_code(404);
	exit;
}

header(
	$result['public']
		? "Cache-Control: public, max-age=31536000, immutable"
		: "Cache-Control: private, max-age=0, must-revalidate"
);

header("X-Sendfile: $file");
http_response_code(200);
exit;

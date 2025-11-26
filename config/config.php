<?php

define('CDN_BASE', __DIR__);

function cdn_load_tenant(string $tenant): array
{
	$file = CDN_BASE . "/tenants/{$tenant}.php";
	if (!file_exists($file)) throw new RuntimeException("Unknown tenant");
	return require $file;
}

require_once CDN_BASE . '/libs/helpers.php';
require_once CDN_BASE . '/libs/db.php';
require_once CDN_BASE . '/libs/validate_signed_url.php';

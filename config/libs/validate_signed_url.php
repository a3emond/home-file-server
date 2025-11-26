<?php

function cdn_validate_signed_url(array $cfg, $tenant, $path, $tid, $exp, $sig)
{
	// portfolio / public-only tenant
	if (!empty($cfg['public_only'])) {
		return [
			'file' => "/mnt/ssd/cdn/$tenant/public/$path",
			'public' => true
		];
	}

	// database lookup
	$pdo = cdn_db_connect($cfg);
	$stmt = $pdo->prepare($cfg['sql_token_lookup']);
	$stmt->execute([':tid' => $tid]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$row) return false;
	if ($row['revoked'] || strtotime($row['expires_at']) < time()) return false;
	if ($path !== $row['storage_path']) return false;

	// verify signature
	$canonical = "tenant=$tenant&path=$path&tid=$tid&exp=$exp";
	$calc = hash_hmac('sha256', $canonical, $row['secret'], true);
	if (!hash_equals(base64url_encode($calc), $sig)) return false;

	return [
		'file'   => "/mnt/ssd/cdn/$tenant/$path",
		'public' => $row['is_public'] === 't'
	];
}

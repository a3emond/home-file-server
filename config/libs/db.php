<?php

function cdn_db_connect(array $cfg): PDO
{
	if (empty($cfg['driver']) || empty($cfg['dsn'])) {
		throw new RuntimeException("Tenant DB config missing");
	}

	$pdo = new PDO(
		$cfg['dsn'],
		$cfg['user'] ?? null,
		$cfg['pass'] ?? null,
		[
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		]
	);

	return $pdo;
}

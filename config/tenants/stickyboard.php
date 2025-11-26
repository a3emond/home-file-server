<?php

return [
	'name'   => 'stickyboard',
	'driver' => 'pgsql',
	'dsn'    => 'pgsql:host=127.0.0.1;port=5432;dbname=stickyboard;',
	'user'   => 'stickyadmin',
	'pass'   => 'strongpassword',

	'sql_token_lookup' => "
	    SELECT
        	ft.secret,
        	ft.expires_at,
        	ft.revoked,
        	COALESCE(av.storage_path, a.storage_path) AS storage_path,
        	COALESCE(av.is_public, a.is_public) AS is_public
	FROM file_tokens ft
	    LEFT JOIN attachments a      ON a.id = ft.attachment_id
	    LEFT JOIN attachment_variants av
        	ON av.parent_id = ft.attachment_id
        	AND av.variant = ft.variant
	    WHERE ft.id = :tid
	    LIMIT 1

    ",
];

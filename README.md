AEDev Multi-Tenant CDN – Documentation

Overview

This CDN layer provides secure and public file delivery for multiple AEDev applications. It supports:
	•	Multi-tenant isolation (e.g., stickyboard, portfolio)
	•	Protected files via signed URLs
	•	Public static asset delivery
	•	Postgres-backed token validation
	•	X-Sendfile secure streaming
	•	Extensible tenant configuration

Directory structure (server side):

/var/www/html/cdn.aedev.pro/
  config/
    config.php
    tenants/*.php
    libs/*.php
  public/
    .htaccess
    validate.php
    public.php
  files/            # storage handler entry scripts (optional future)
/srv/cdn/{tenant}/protected/*
/srv/cdn/{tenant}/public/*

Concepts

Protected Files

Served only with valid signed URL:

https://cdn.aedev.pro/{tenant}/protected?path=<encoded>&tid=<id>&exp=<epoch>&sig=<hmac>

CDN PHP validates:
	•	Token exists
	•	Not revoked
	•	Not expired
	•	DB storage path matches request
	•	HMAC signature correct
	•	Streams via X-Sendfile

Public Files

No token required:

https://cdn.aedev.pro/{tenant}/public?path=<encoded>

CDN resolves tenant and sends file with long-term cache headers.

Tenant Config

Located in config/tenants/*.php

Example (StickyBoard):

return [
  'name' => 'stickyboard',
  'driver' => 'pgsql',
  'dsn'    => 'pgsql:host=127.0.0.1;port=5432;dbname=stickyboard;',
  'user'   => 'stickyadmin',
  'pass'   => 'strongpassword',
  'sql_token_lookup' => "
    SELECT
      ft.secret, ft.expires_at, ft.revoked,
      COALESCE(v.storage_path, a.storage_path) AS storage_path,
      COALESCE(v.mime, a.mime) AS mime,
      COALESCE(v.is_public, a.is_public) AS is_public
    FROM file_tokens ft
    JOIN attachments a ON a.id = ft.attachment_id
    LEFT JOIN attachment_variants v
      ON v.parent_id = a.id AND v.variant = ft.variant
    WHERE ft.id = :tid
    LIMIT 1
  ",
];

Portfolio example (public only):

return [
  'name' => 'portfolio',
  'public_only' => true,
];

URL Contract

Protected:

/protected?path=...&tid=...&exp=...&sig=...

Public:

/public?path=...

Canonical string (HMAC)

path=<path>&tid=<id>&exp=<epoch>

Required Query Params

Parameter	Purpose
path	storage path in DB
tid	file token id
exp	expiration epoch
sig	base64url(HMAC)

Storage Paths

Application decides relative paths, e.g.:

boards/<board_id>/att/<attachment_id>/original/<filename>
boards/<board_id>/att/<attachment_id>/thumb_256.webp

CDN prepends tenant root:

/srv/cdn/{tenant}/protected/<path>
/srv/cdn/{tenant}/public/<path>

Validation Logic
	•	Load tenant config
	•	Query DB
	•	Validate token + signature
	•	Verify file exists
	•	Send with proper cache headers

Cache Policies

File Type	Header
Public	Cache-Control: public, max-age=31536000, immutable
Private	Cache-Control: private, max-age=0, must-revalidate

What a Client App Must Do

1. Upload file to app backend

App backend generates attachments row.

2. Request signed URL

Client calls API:

POST /attachments/{id}/tokens

Backend responds:

{
  "url": "https://cdn.aedev.pro/stickyboard/protected?...",
  "expiresAt": "..."
}

3. Use signed URL

Client fetches from CDN directly.
Never persist CDN URLs — request new token when expired.

4. Public Assets

Use:

https://cdn.aedev.pro/stickyboard/public?path=... 

Or for static site tenants:

https://cdn.aedev.pro/portfolio/public/<file>

Security Notes
	•	No direct file serving — always via PHP + DB + secrets
	•	public_only mode bypasses DB for static tenants
	•	Short TTL recommended (5–15 minutes)
	•	Private responses bypass CDN cache

Future Extensions
	•	Token refresh endpoint
	•	Signed upload URLs
	•	Rate-limit public reads
	•	Audit logs
	•	S3 adapter for storage

Summary

The CDN provides:
	•	Multi-tenant file delivery
	•	Strong auth for private data
	•	HMAC URL validation
	•	Public CDN caching for open assets
	•	Clean separation between apps and file storage

Client apps only need to:
	1.	Upload file metadata
	2.	Request token URL
	3.	Fetch via token URL
	4.	Refresh tokens when expired

#!/bin/sh
# VulnForge app entrypoint. Ensure the log directory (which may be a host
# bind-mount owned by a different uid) is writable by the web server user and
# readable from the host, then start Apache. Local lab convenience only.
mkdir -p /var/www/app/logs
chmod 0777 /var/www/app/logs 2>/dev/null || true
exec apache2-foreground

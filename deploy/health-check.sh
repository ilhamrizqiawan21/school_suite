#!/usr/bin/env bash
set -euo pipefail

# Probe the local TLS vhost using its real certificate and hostname.
HEALTH_CHECK_URL="${1:-https://school.sch.id/up}"
HEALTH_CHECK_IP="${HEALTH_CHECK_IP:-127.0.0.1}"
read -r HEALTH_HOST HEALTH_PORT < <(php -r '
$url = parse_url($argv[1]);
if (($url["scheme"] ?? "") !== "https" || empty($url["host"])) { exit(1); }
echo $url["host"]." ".($url["port"] ?? 443).PHP_EOL;
' "$HEALTH_CHECK_URL")
PROBE_FILE=$(mktemp)
trap 'rm -f "$PROBE_FILE"' EXIT
HTTP_STATUS=$(curl --silent --show-error --fail --connect-timeout 5 --max-time 15 \
    --resolve "${HEALTH_HOST}:${HEALTH_PORT}:${HEALTH_CHECK_IP}" \
    --output "$PROBE_FILE" --write-out '%{http_code}' "$HEALTH_CHECK_URL")
[ "$HTTP_STATUS" = "200" ]
php -r '
$payload = json_decode(file_get_contents($argv[1]), true);
exit(is_array($payload) && ($payload["status"] ?? null) === "healthy" ? 0 : 1);
' "$PROBE_FILE"

@php
    $route = function (...$params) {
        return str_replace('link.','', route(...$params));
    };
@endphp#!/bin/bash
set -euo pipefail

export USERAGENT="`echo $USER`@`hostname`:installer"
export DEBIAN_FRONTEND=noninteractive

apt_install_prereqs() {
    apt-get update -y >/dev/null 2>&1 || true
    apt-get install -y curl jq >/dev/null 2>&1 || true
}

machine_id() {
    if [ -f /etc/machine-id ]; then
        cat /etc/machine-id | tr -d '\n'
        return 0
    fi

    if [ -f /var/lib/dbus/machine-id ]; then
        cat /var/lib/dbus/machine-id | tr -d '\n'
        return 0
    fi

    echo ""
}

HOST_DATA() {
    NAME=$(hostname -s | xargs)
    PUBLIC_IP={{ request()->ip() }}
    BOOTED_AT=$(uptime -s 2>/dev/null || true)
    MACHINE_ID=$(machine_id)
    OS_DESC=$(lsb_release -ds 2>/dev/null || cat /etc/os-release 2>/dev/null | head -n 1 | xargs || true)

    cat <<EOF
{
  "machine_id": "$MACHINE_ID",
  "name": "$NAME",
  "ip_address": "$PUBLIC_IP",
  "booted_at": "$BOOTED_AT",
  "os": "$OS_DESC",
  "status": "enrolling",
  "connection_type": "agent"
}
EOF
}

register_host() {
    curl -fsSL \
        -X POST \
        -H "Authentication: Bearer {{ $credential->api_key }}" \
        -H "Accept: application/json" \
        -H "Content-Type: application/json" \
        -d "$(HOST_DATA)" \
        "{{ route('api.infrastructure.hosts.register') }}" \
        --user-agent "$USERAGENT"
}

apt_install_prereqs

if [ -z "$(machine_id)" ]; then
    echo "Could not determine machine-id. Ensure /etc/machine-id exists."
    exit 1
fi

echo "Registering host with Spork..."
RESPONSE=$(register_host)
echo "$RESPONSE" | jq .
echo "Done."



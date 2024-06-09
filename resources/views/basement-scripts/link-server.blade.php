@php
    $route = function (...$params) {
        return str_replace('link.','', route(...$params));
    };
    $url = function (...$params) {
        return str_replace('link.','', url(...$params));
    };
@endphp#!/bin/bash
set -exv
# Steps
#   1. Setup SSH
#   2. Get disk size
#   3. Get available memory
#   4. Get available IPs
#   5. Create the server record via CURL.
#   6. Install node
#   6-1. Curl step update for the server.
#   7. Install Metrics
#   7-1. Curl step update for the server.
#   8. Connect to socket IO and start pumping out live installation output
#   9. Run the post-install steps.

# Set up SSH key (initial generation, and posting to our server)
# Get main disk size
# Get available memory
# Get all available IP configurations (At least need public and private IPs)

export DEBIAN_FRONTEND=noninteractive

apt_install_prereqs() {
    apt-get install -y curl openssh-client jq
}
update_server() {
    curl -X PUT -H "Authentication: Bearer $TOKEN" -H 'Content-type: application/json' -H 'Accept: application/json' -d  "$1" {{ route('server.update', [$credential->id]) }} --user-agent "`echo $USER`@`hostname`:installer"
}

source /etc/lsb-release

apt_install_prereqs

# Creation link
SERVER_DATA()
{
    NAME=$(hostname -s | xargs)
    PUBLIC_IP={{request()->ip()}}
    DISK_SIZE=$(df | grep -v run | grep -v sys | awk '{print $2}' | head -1 | tail -1 | xargs)
    MEMORY_KB=$(cat /proc/meminfo | grep memtotal -i | awk '{print $2}')
    MEMORY=$(echo $(($MEMORY_KB / 1024)) | xargs)
    KERNEL=$(uname -r | xargs)
    CPU_THREADS=$(nproc --all  | xargs)
    source /etc/lsb-release
    cat {!! '<<EOF' !!}
{!! json_encode([
    'name' => '$NAME',
    'ip_address' => '$PUBLIC_IP',
    'memory' => '$MEMORY',
    'disk' => '$DISK_SIZE',
    'host_created_at' => now()->format('Y-m-d H:i:s'),
    'kernel' => '$KERNEL',
    'distro' => '$DISTRIB_ID',
    'threads' => '$CPU_THREADS',
    'status' => 'provisioning',
    'server_id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
], JSON_PRETTY_PRINT) !!}
EOF
}

SERVER=$(curl -X POST -H 'Content-type: application/json' -H "Authentication: bearer {{ $credential->api_key }}" -H 'Accept: application/json' -d  "$(SERVER_DATA)" -X POST "{{ route('server.create') }}" --user-agent "`echo $USER`@`hostname`:installer")

echo ""
echo "$SERVER"
echo ""

MESSAGE=$(echo $SERVER | jq -r ".message")
ERRORS=$(echo "$SERVER" | jq -r ".errors")
if [[ "$MESSAGE" != null ]]; then
    clear
    echo 'Error creating server.'
    echo "$(SERVER_DATA)"
    echo "ERRORS: $MESSAGE"
    exit 1;
fi
if [[ "$ERRORS" != null ]]; then
    echo 'Error creating server.'
    echo $SERVER;
    echo "$(SERVER_DATA)"
    echo "ERRORS: $ERRORS"
    exit 1;
fi
TOKEN=$(echo "$SERVER" | jq -r ".access_token")

mkdir -p ~/.basement/logs

# Is server scoped token, and the server owner's credential_id
echo "{ \"token\": \"$(echo "$TOKEN")\", \"credential_id\": {{$credential->id}} }" > ~/.basement/config.json

SSH_PUBLIC_KEY="{{ $credential->getPublicKey() }}"

mkdir -p /root/.ssh
touch  /root/.ssh/authorized_keys
echo $SSH_PUBLIC_KEY >> /root/.ssh/authorized_keys

update_server {!! escapeshellarg(json_encode(['status' => 'ssh_ready'])) !!}

# We could install other dependencies here, ssh_ready just means accessible.
# We still need to do service discovery, and install

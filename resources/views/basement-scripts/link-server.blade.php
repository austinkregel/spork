@php
    $route = function (...$params) {
        return str_replace('link.','', route(...$params));
    };
    $url = function (...$params) {
        return str_replace('link.','', url(...$params));
    };
@endphp#!/bin/bash
set -ev
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

yum_install_prereqs() {
    curl -sL https://rpm.nodesource.com/setup_12.x | bash -

    yum install curl openssh-client jq
}

apt_install_node() {
    apt-get remove -y nodejs
    curl -sL https://deb.nodesource.com/setup_12.x | bash -
    apt-get update
    apt-get install -y nodejs
}
yum_install_node() {
    yum remove node nodejs
    curl -sL https://rpm.nodesource.com/setup_12.x | bash -
    yum update
    yum install nodejs
}

source /etc/lsb-release

if [[ "$DISTRIB_ID" == "Ubuntu" ]];  then
    apt_install_prereqs
else
    yum_install_prereqs
fi

# Creation link
SERVER_DATA()
{
    NAME=$(hostname -s | xargs)
    PUBLIC_IP=$(curl "https://ipinfo.io/ip" | xargs)
    DISK_SIZE=$(df -h | awk '{print $4}' | head -2 | tail -1 | xargs)
    MEMORY_KB=$(cat /proc/meminfo | grep memtotal -i | awk '{print $2}')
    MEMORY=$(echo $(($MEMORY_KB / 1024)) | xargs)
    KERNEL=$(uname -r | xargs)
    CPU_THREADS=$(nproc --all  | xargs)
    source /etc/lsb-release
    cat <<EOF
{!! json_encode([
    'name' => '$NAME',
    'ip_address' => '$PUBLIC_IP',
    'memory' => '$MEMORY',
    'disk' => '$DISK_SIZE',
    'host_created_at' => now()->format('Y-m-d H:i:s'),
    'kernel' => '$KERNEL',
    'distro' => '$DISTRIB_ID',
    'threads' => '$CPU_THREADS',
], JSON_PRETTY_PRINT) !!}
EOF
}

SERVER=$(curl -X POST -H 'Authentication: Bearer {{ request()->header('') }}' -H 'Content-type: application/json' -H 'Accept: application/json' -d  "$(SERVER_DATA)" -X POST {{ route('create-device') }})

echo ""
echo "$SERVER"
echo ""

MESSAGE=$(echo $SERVER | jq -r ".message")
ERRORS=$(echo "$SERVER" | jq -r ".errors")
if [[ "$MESSAGE" != null ]]; then
    clear
    echo 'Error creating server.'
    echo $SERVER;
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
SERVER=$(echo "$SERVER" | jq -r ".data")

mkdir -p ~/.basement/logs

echo "{ \"token\": \"$(echo "$SERVER" | jq -r ".access_token")\", \"user_id\": \"$(echo "SERVER" | jq -r ".user_id")\" }" > ~/.basement/config.json

SSH_PUBLIC_KEY=$(echo $SERVER | jq -r '.`ssh_key_public`')

mkdir -p /root/.ssh
touch  /root/.ssh/authorized_keys
echo $SSH_PUBLIC_KEY >> /root/.ssh/authorized_keys

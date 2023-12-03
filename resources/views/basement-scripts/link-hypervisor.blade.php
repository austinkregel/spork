#!/bin/bash
set -xe
# Post-create Hypervisor linking script.
# We should have access to the hypervisor variable here to be able to dispatch a put request from the server.

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

apt_install_prereqs() {
apt-get install -y curl openssh-client jq virt-what
}

yum_install_prereqs() {
curl -sL https://rpm.nodesource.com/setup_12.x | bash -

yum install curl openssh-client jq virt-what
}

apt_install_node() {
apt-get remove -y node nodejs
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

source /etc/os-release

if [ -n "$(command -v apt-get)" ];  then
apt_install_prereqs
else
yum_install_prereqs
fi

# The general idea for these bits here is that we should be getting the data from the server
# in a very general way. One that would allow this to  work cross platform.
NAME=$(hostname -s)
PUBLIC_IP=$(curl {{ url('ip') }})
INSTALL_DATE=$(ls -lact  --time-style=long-iso /etc |awk 'END {print $6,$7}')
DISK_SIZE=$(df -h | awk '{print $4}' | head -2 | tail -1)
MEMORY_KB=$(cat /proc/meminfo | grep memtotal -i | awk '{print $2}')
MEMORY=$(($MEMORY_KB / 1024))
KERNEL=$(uname -r)
CPU_THREADS=$(nproc --all)

HYPERVISOR=$(virt-what)

if [[ -z "$HYPERVISOR" ]]; then
echo "##########################################################################################################################"
echo "# ERROR:"
echo "#   You do not have a hypervisor installed. Please install one before trying to run this command."
echo "# Suggestion:"
echo "#    For Ubuntu you can use:"
echo "          sudo apt-get install qemu-kvm libvirt-daemon-system libvirt-clients bridge-utils"
echo "#"
echo "#    For CentOS you can use:"
echo "           yum -y install @virt* dejavu-lgc-* xorg-x11-xauth tigervnc libguestfs-tools policycoreutils-python bridge-utils"
echo "           semanage fcontext -a -t virt_image_t "/vm(/.*)?"; restorecon -R /vm"
echo "           sed -i 's/^\(net.ipv4.ip_forward =\).*/\1 1/' /etc/sysctl.conf; sysctl -p"
echo "           chkconfig libvirtd on; shutdown -r now"
echo "           chkconfig network on"
echo "           service network restart"
echo "           yum -y erase NetworkManager"
echo "           cp -p /etc/sysconfig/network-scripts/ifcfg-{eth0,br0}"
echo "           sed -i -e'/HWADDR/d' -e'/UUID/d' -e's/eth0/br0/' -e's/Ethernet/Bridge/' /etc/sysconfig/network-scripts/ifcfg-br0"
echo "           echo DELAY=0 >> /etc/sysconfig/network-scripts/ifcfg-br0"
echo "           echo 'BOOTPROTO="none"' >> /etc/sysconfig/network-scripts/ifcfg-eth0"
echo "           echo BRIDGE=br0 >> /etc/sysconfig/network-scripts/ifcfg-eth0"
echo "           service network restart"
echo "           brctl show"
echo "#"
echo "#########################################################################################################################w#"
    exit 1;
fi

# Creation link
SERVER=$(curl -X POST -H 'Content-type: application/json' -H 'Accept: application/json' -H 'Authorization: Bearer {!! $token->token !!}' --data "{!! addslashes(json_encode([
    'name' => '$NAME',
    'ip_address' => '$PUBLIC_IP',
    'memory' => '$MEMORY',
    'disk' => '$DISK_SIZE',
    'host_created_at' => '$INSTALL_DATE',
    'kernel' => '$KERNEL',
    'distro' => '$NAME',
    'threads' => '$CPU_THREADS',
])) !!}" {{ route('servers.create-no-credential', [$credential->id]) }})

# The ssh key is generated on the instance of the basement being used.
SSH_PUBLIC_KEY=$(echo $SERVER | jq -r '.ssh_key_public')

mkdir -p /root/.ssh
touch  /root/.ssh/authorized_keys
echo $SSH_PUBLIC_KEY >> /root/.ssh/authorized_keys

@if(request()->has('node') && (bool) request()->get('node'))
    # Install node.

    if [ -n "$(command -v apt-get)" ];  then
    apt_install_node
    else
    yum_install_node
    fi

    @if (request()->has('metrics') && (bool) request()->get('metrics'))
        # Install metrics client
        {{--npm i -g @kbco/monitor--}}
        {{--API_HOST={{ url('') }} API_TOKEN={{ $token->token }} monitor--}}
    @endif
@endif

<?php

declare(strict_types=1);

namespace App\Services\Server;

use App\Contracts\Services\ServerServiceContract;
use Illuminate\Support\Arr;

class LibvirtService implements ServerServiceContract
{
    protected $resource;
    public function __construct()
    {
        $this->resource = libvirt_connect('qemu:///system', false);
    }

    public function createServer(array $config): array
    {
        [
            'name' => 'My VM',
            'memory' => (string) (1024 * 1024 * 1024),
            'vcpu' => '1',
            'disk_name' => 'my-disk.img',
        ];

        // Create the root element
        $domain = new \SimpleXMLElement('<domain/>');
        $domain->addAttribute('type', 'kvm');

        // Add child elements
        $domain->addChild('name', $config['name']);
        $domain->addChild('memory', (string) $config['memory']);
        if (isset($config['vcpu'])) {
            $domain->addChild('vcpu', (string) ($config['vcpu']));
        }

        $os = $domain->addChild('os');
        $type = $os->addChild('type', 'hvm');
        $type->addAttribute('arch', 'x86_64');
        $type->addAttribute('machine', 'pc');

        $os->addChild('boot')
            ->addAttribute('dev', 'cdrom');

        $features = $domain->addChild('features');
        $features->addChild('acpi');
        $features->addChild('apic');

        $cpu = $domain->addChild('cpu');

        $cpu->addAttribute('mode', 'host-passthrough');
        $cpu->addAttribute('check', 'none');
        $cpu->addAttribute('migratable', 'on');

        $clock = $domain->addChild('clock');
        $clock->addAttribute('offset', 'utc');

        $catchUpTimer = $clock->addChild('timer');
        $catchUpTimer->addAttribute('name', 'rtc');
        $catchUpTimer->addAttribute('tickpolicy', 'catchup');

        $delayTimer = $clock->addChild('timer');
        $delayTimer->addAttribute('name', 'pit');
        $delayTimer->addAttribute('tickpolicy', 'delay');

        if (isset($config['cores']) || isset($config['threads'])) {
            $topology = $cpu->addChild('topology');
            $topology->addAttribute('sockets', '1');
            $topology->addAttribute('cores', (string) ($config['cores'] ?? 1));
            $topology->addAttribute('threads', (string) ($config['threads'] ?? 1));
        }

//        $domain->addChild('on_poweroff', 'destroy');
        $domain->addChild('on_reboot', 'restart');
//        $domain->addChild('on_crash', 'destroy');
        $pm = $domain->addChild('pm');
        $pm->addChild('suspend-to-mem')->addAttribute('enabled', 'no');
        $pm->addChild('suspend-to-disk')->addAttribute('enabled', 'no');

        // Devices
        $devices = $domain->addChild('devices');

        // Add our disk
        $disk = $devices->addChild('disk');
        $disk->addAttribute('type', 'file');
        $disk->addAttribute('device', 'disk');

        $driver = $disk->addChild('driver');
        $driver->addAttribute('name', 'qemu');
        $driver->addAttribute('type', 'raw');
        $driver->addAttribute('discard', 'unmap');
        $disk->addChild('source')->addAttribute('file', $config['disk_path']);
        $target = $disk->addChild('target');
        $target->addAttribute('dev', 'vda');
        $target->addAttribute('bus', 'virtio');

        if (isset($config['iso_path'])) {
            // Add our ISO as a CD
            $cdrom = $devices->addChild('disk');
            $cdrom->addAttribute('type', 'file');
            $cdrom->addAttribute('device', 'cdrom');
            $iso = $cdrom->addChild('driver');
            $iso->addAttribute('name', 'qemu');
            $iso->addAttribute('type', 'raw');
            $cdrom->addChild('source')->addAttribute('file', $config['iso_path']);
            $target = $cdrom->addChild('target');
            $target->addAttribute('dev', 'hda');
            $target->addAttribute('bus', 'sata');

            $cdrom->addChild('readonly');
        }

        $interface = $devices->addChild('interface');
        $interface->addAttribute('type', 'network');
        $interface->addChild('source')->addAttribute('network', 'default');

        $graphics = $devices->addChild('graphics');
        $graphics->addAttribute('type', 'vnc');
        $graphics->addAttribute('port', '-1');
        $graphics->addAttribute('autoport', 'yes');

        // Convert SimpleXMLElement object to XML string

        // Define the storage for the VM
        $storagePool = libvirt_storagepool_lookup_by_name($this->resource, $config['storage_pool']);
        $volume = $devices->addChild('volume');
        $volume->addChild('name', $config['disk_name']);
        $volume->addChild('target')->addChild('path', $config['disk_path']);
        $volume->addChild('capacity', (string) $config['disk_capacity']); // In KiB

        // This will provision the drive for us on the storage
        libvirt_storagevolume_create_xml($storagePool, $volume->asXML());

        $virtIoController = $devices->addChild('controller');
        $virtIoController->addAttribute('type', 'virtio-serial');
        $virtIoController->addAttribute('index', '0');

        $interface = $devices->addChild('interface');
        $interface->addAttribute('type', 'network');
        // Dynamically set the mac?
        if (isset($config['network_mac'])) {
            $interface->addChild('mac')->addAttribute('address', $config['network_mac']);
        }
        $interface->addChild('source')->addAttribute('network', 'default');
        $interface->addChild('model')->addAttribute('type', 'virtio');

        $console = $devices->addChild('console');
        $console->addAttribute('type', 'pty');
        $target = $console->addChild('target');
        $target->addAttribute('type', 'serial');
        $target->addAttribute('port', '0');

        $channel = $devices->addChild('channel');
        $channel->addAttribute('type', 'unix');
        $target = $channel->addChild('target');
        $target->addAttribute('type', 'virtio');
        $target->addAttribute('name', 'org.qemu.guest_agent.0');

        $graphics = $devices->addChild('graphics');
        $graphics->addAttribute('type', 'spice');
        $graphics->addAttribute('autoport', 'yes');

        $graphics->addChild('listen')->addAttribute('type', 'address');
        $graphics->addChild('image')->addAttribute('compression', 'off');

        $audio = $devices->addChild('audio');
        $audio->addAttribute('type', 'spice');
        $audio->addAttribute('id', '1');

        $video = $devices->addChild('video');
        $model = $video->addChild('model');
        $model->addAttribute('type', 'qxl');
        $model->addAttribute('ram', '65536');
        $model->addAttribute('vram', '65536');
        $model->addAttribute('vgamem', '16384');
        $model->addAttribute('heads', '1');
        $model->addAttribute('primary', 'yes');

        $devices->addChild('memballoon')->addAttribute('model', 'virtio');

        $rng = $devices->addChild('rng');
        $rng->addAttribute('model', 'virtio');
        $rng->addChild('backend', '/dev/urandom')->addAttribute('model', 'random');

        $keyboard = $devices->addChild('input');
        $keyboard->addAttribute('type', 'keyboard');
        $keyboard->addAttribute('bus', 'usb');

        $mouse = $devices->addChild('input');
        $mouse->addAttribute('type', 'mouse');
        $mouse->addAttribute('bus', 'usb');

        libvirt_domain_define_xml($this->resource, $domain->asXML());
        libvirt_domain_create(libvirt_domain_lookup_by_name($this->resource, $config['name']));
        // libvirt_domain_define_xml will --- Will save VM
        return json_decode(json_encode($domain), true);
    }

    public function findAllRegions(): array
    {
        return [
            'default'
        ];
    }

    public function findAllSizes(): array
    {
        return libvirt_connect_get_machine_types($this->resource);
    }

    public function findAllServers(): array
    {
        $vmNamesThatAreOn = libvirt_list_active_domains($this->resource);

        $vms = libvirt_connect_get_all_domain_stats($this->resource);

        $formattedArray = [];
        foreach ($vms as $key => $vm) {
            foreach ($vm as $k => $v) {
                $formattedArray = Arr::add($formattedArray, $k, $v);
            }
        }

        return [
            'data' => array_map(fn ($data, $key) => array_merge(
                [
                    'id' => libvirt_domain_get_id($resource = libvirt_domain_lookup_by_name($this->resource, $key)),
                    'name' => $key,
                    'on' => in_array($key, $vmNamesThatAreOn),
                    'state' => match ($data['state.state']) {
                        VIR_DOMAIN_NOSTATE => 'no state',
                        VIR_DOMAIN_RUNNING => 'running',
                        VIR_DOMAIN_BLOCKED => 'blocked',
                        VIR_DOMAIN_PAUSED => 'paused',
                        VIR_DOMAIN_SHUTDOWN => 'shutdown',
                        VIR_DOMAIN_SHUTOFF => 'shutoff',
                        VIR_DOMAIN_CRASHED => 'crashed',
                        VIR_DOMAIN_PMSUSPENDED => 'suspended',
                    },
                    // bytes
                    'memory' => ($info = libvirt_domain_get_info($resource))['memory'],
                    'cpu_usage' => $info['cpuUsed'],
                    'cpus' => $info['nrVirtCpu'],
                    'disk' => array_values(array_filter($formattedArray['block'], 'is_array')),
                ],
                isset($formattedArray['balloon']) ? ['balloon' => $formattedArray['balloon']] : [],
                isset($formattedArray['net']) ? ['net' => array_values(array_filter($formattedArray['net'], 'is_array'))] : [],
            ), $vms, array_keys($vms)),
            'hypervisor' => libvirt_connect_get_information($this->resource),
            'storage_pool' => array_map(fn ($storagePoolName) => libvirt_storagepool_get_info(libvirt_storagepool_lookup_by_name($this->resource, $storagePoolName)), libvirt_list_storagepools($this->resource)),
        ];
    }

    public function deleteServer(mixed $identifier): void
    {
        // We want to destroy all associated libvirt resources
        $domain = libvirt_domain_lookup_by_name($this->resource, (string) $identifier);
        // Lookup snapshots, disks, nic, etc and remove or destroy them
        $disks = libvirt_domain_get_disk_devices($domain);

        // Attempt to shutdown the domain
        libvirt_domain_shutdown($domain);

        // Wait for the domain to shut down
        while (($info = libvirt_domain_get_info($domain)) && $info['state'] != VIR_DOMAIN_SHUTOFF) {
            // Sleep for a bit to prevent high CPU usage
            sleep(1);
        }

        foreach ($disks as $disk) {
            libvirt_domain_detach_device($domain, $disk);
            libvirt_domain_disk_remove($domain, $disk);
            sleep(1);
        }

        libvirt_domain_destroy($domain);
        libvirt_domain_undefine($domain);
    }

    public function powerOnServer(int|string $identifier): void
    {
        $domain = libvirt_domain_lookup_by_name($this->resource, (string) $identifier);
        libvirt_domain_create($domain);
    }

    public function powerOffServer(int|string $identifier): void
    {
        $domain = libvirt_domain_lookup_by_name($this->resource, (string) $identifier);
        libvirt_domain_shutdown($domain);
    }

    public function shutdownServer(int|string $identifier): void
    {
        $domain = libvirt_domain_lookup_by_name($this->resource, (string) $identifier);
        libvirt_domain_shutdown($domain);
    }

    public function rebootServer(int|string $identifier): void
    {
        $domain = libvirt_domain_lookup_by_name($this->resource, $identifier);
        libvirt_domain_reboot($domain);
    }

    public function findAllSshkeys(): array
    {
        throw new NotImplementedException('SSH Key management is not supported');
    }

    public function createServerKey(array $config): SshKeylike
    {
        throw new NotImplementedException('SSH Key management is not supported');
    }

    public function removeServerKey($identifier): void
    {
        throw new NotImplementedException('SSH Key management is not supported');
    }
}

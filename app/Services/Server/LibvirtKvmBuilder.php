<?php

declare(strict_types=1);

namespace App\Services\Server;

class LibvirtKvmBuilder
{
    public function __construct(
        protected \SimpleXMLElement $kvm,
    ) {
        $this->kvm = new \SimpleXMLElement('<domain/>');
    }

    public function setMemory(string $memory): self
    {
        $this->kvm->addChild('memory', $memory)
            ->addAttribute('unit', 'KiB');

        return $this;
    }

    public function setVcpu(string $vcpu): self
    {
        $this->kvm->addChild('vcpu', $vcpu)
            ->addAttribute('placement', 'static');

        return $this;
    }

    public function setOs(string $type, string $arch, string $machine = 'pc-q35-6.2'): self
    {
        $os = $this->kvm->addChild('os');
        $type = $os->addChild('type', $type);
        $type->addAttribute('arch', $arch);
        $type->addAttribute('machine', $machine);
        $os->addChild('boot')
            ->addAttribute('dev', 'cdrom');

        return $this;
    }

    public function setFeatures(array $features): self
    {
        $features = $this->kvm->addChild('features');
        $features->addChild('acpi');
        $features->addChild('apic');

        return $this;
    }
}

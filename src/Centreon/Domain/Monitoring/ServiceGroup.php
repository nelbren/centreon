<?php

namespace Centreon\Domain\Monitoring;

use JMS\Serializer\Annotation as Serializer;
use Centreon\Domain\Annotation\EntityDescriptor as Desc;

/**
 * Class ServiceGroup
 * @package Centreon\Domain\Monitoring
 */
class ServiceGroup
{
    /**
     * @Serializer\Groups({"sg_main"})
     * @Desc(column="servicegroup_id", modifier="setId")
     * @var int
     */
    private $id;

    /**
     * @Serializer\Groups({"sg_main"})
     * @var Host[]
     */
    private $hosts = [];

    /**
     * @Serializer\Groups({"sg_main"})
     * @var string|null
     */
    private $name;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ServiceGroup
     */
    public function setId(int $id): ServiceGroup
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param Host $host
     * @return ServiceGroup
     */
    public function addHost(Host $host):ServiceGroup
    {
        $this->hosts[] = $host;
        return $this;
    }


    /**
     * @return Host[]
     */
    public function getHosts(): array
    {
        return $this->hosts;
    }

    /**
     * @param Host[] $hosts
     * @return ServiceGroup
     */
    public function setHosts(array $hosts): ServiceGroup
    {
        $this->hosts = $hosts;
        return $this;
    }

    /**
     * Indicates if a host exists in this service group.
     *
     * @param int $hostId Host id to find
     * @return bool
     */
    public function isHostExists(int $hostId): bool
    {
        foreach ($this->hosts as $host) {
            if ($host->getId() === $hostId) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return ServiceGroup
     */
    public function setName(?string $name): ServiceGroup
    {
        $this->name = $name;
        return $this;
    }
}

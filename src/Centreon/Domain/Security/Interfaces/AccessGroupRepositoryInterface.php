<?php

namespace Centreon\Domain\Security\Interfaces;

use Centreon\Domain\Entity\AccessGroup;
use Centreon\Domain\Contact\Interfaces\ContactInterface;

interface AccessGroupRepositoryInterface
{
    /**
     * Find all access groups according to a contact.
     *
     * @param ContactInterface $contact Contact to use to find access groups.
     * @return AccessGroup[]
     */
    public function findByContact(ContactInterface $contact): array;
}

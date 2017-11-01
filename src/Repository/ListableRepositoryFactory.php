<?php

/*
 * This file is part of staccato listable component
 *
 * (c) Krystian Karaś <dev@karashome.pl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Staccato\Component\Listable\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Staccato\Component\Listable\Repository\AbstractRepository;
use Staccato\Component\Listable\Repository\RepositoryFactoryInterface;

class ListableRepositoryFactory implements RepositoryFactoryInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function create($data): AbstractRepository
    {
        $entityRepository = $this->entityManager->getRepository($data);

        if (method_exists($entityRepository, 'createListableRepository')) {
            $repository = $entityRepository->createListableRepository();
        } else {
            $repository = new ListableRepository($entityRepository);
        }

        return $repository;
    }
}

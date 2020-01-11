<?php

/*
 * This file is part of staccato listable doctrine integration
 *
 * (c) Krystian Karaś <dev@karashome.pl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Staccato\Component\Listable\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Staccato\Component\Listable\ListStateInterface;
use Staccato\Component\Listable\Repository\AbstractRepository;
use Staccato\Component\Listable\Repository\Result;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DoctrineRepository extends AbstractRepository
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getResult(ListStateInterface $state): Result
    {
        $qb = $this->prepareQueryBuilder($state);

        return $this->fetchResult($qb, $state);
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver
            ->setRequired([
                'entity',
            ])
            ->setDefaults([
                'criteria' => null,
                'query_builder' => null,
            ])
            ->setAllowedTypes('entity', 'string')
            ->setAllowedTypes('query_builder', ['closure', 'null'])
            ->setAllowedTypes('criteria', ['closure', 'null'])
        ;
    }

    private function createQueryBuilder(): QueryBuilder
    {
        /** @var EntityRepository $repository */
        $repository = $this->em->getRepository($this->options['entity']);

        $callback = $this->options['query_builder'];

        if ($callback instanceof \Closure) {
            $qb = $callback->call($this, $repository);
        } else {
            $qb = $repository->createQueryBuilder('e');
        }

        return $qb;
    }

    private function prepareQueryBuilder(ListStateInterface $state)
    {
        $qb = $this->createQueryBuilder();

        $callback = $this->options['criteria'];

        if ($callback instanceof \Closure) {
            $callback->call($this, $qb, $state);
        }

        return $qb;
    }

    private function fetchResult(QueryBuilder $qb, ListStateInterface $state)
    {
        if ($state->getLimit() > 0) {
            $qb->setMaxResults($state->getLimit());
        }

        if ($state->getPage() > 0) {
            $qb->setFirstResult($state->getPage() * $state->getLimit());
        }

        $paginator = new Paginator($qb);

        $result = new Result();
        $result->setTotalCount($paginator->count());

        if (!$result->getTotalCount()) {
            return $result;
        }

        /** @var \ArrayIterator $iterator */
        $iterator = $paginator->getIterator();
        $result->setRows($iterator->getArrayCopy());

        return $result;
    }
}

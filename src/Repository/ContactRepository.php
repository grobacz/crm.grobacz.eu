<?php

namespace App\Repository;

use App\Entity\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCustomer(string $customerId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.customer = :customerId')
            ->setParameter('customerId', $customerId)
            ->orderBy('c.isPrimary', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

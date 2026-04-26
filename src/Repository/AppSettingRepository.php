<?php

namespace App\Repository;

use App\Entity\AppSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AppSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppSetting::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.settingKey', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByKey(string $key): ?AppSetting
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.settingKey = :key')
            ->setParameter('key', $key)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function upsert(string $key, string $value, ?string $description = null): AppSetting
    {
        $setting = $this->findOneByKey($key);

        if ($setting === null) {
            $setting = new AppSetting();
            $setting->setSettingKey($key);
        }

        $setting->setSettingValue($value);
        $setting->setUpdatedAt(new \DateTime());

        if ($description !== null) {
            $setting->setDescription($description);
        }

        $this->getEntityManager()->persist($setting);
        $this->getEntityManager()->flush();

        return $setting;
    }

    public function bulkGet(array $keys): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.settingKey IN (:keys)')
            ->setParameter('keys', $keys)
            ->getQuery()
            ->getResult();
    }
}

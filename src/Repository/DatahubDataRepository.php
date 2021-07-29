<?php

namespace App\Repository;

use App\Entity\DatahubData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DatahubData|null find($id, $lockMode = null, $lockVersion = null)
 * @method DatahubData|null findOneBy(array $criteria, array $orderBy = null)
 * @method DatahubData[]    findAll()
 * @method DatahubData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DatahubDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DatahubData::class);
    }

    // /**
    //  * @return DatahubData[] Returns an array of DatahubData objects
    //  */

    public function findById($id)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.id = :val')
            ->setParameter('val', $id)
            ->getQuery()
            ->getResult()
        ;
    }
}

<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Operation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Operation>
 *
 * @method Operation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Operation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Operation[]    findAll()
 * @method Operation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OperationRepository extends ServiceEntityRepository
{
    public const PAGINATOR_PER_PAGE = 10;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Operation::class);
    }

//    /**
//     * @return Operation[] Returns an array of Operation objects
//     */
   public function getOperationPaginatorByDateAndAccount(Account $account, int $page, $start_date, $end_date): Paginator
   {
        $offset = ($page - 1) * self::PAGINATOR_PER_PAGE;
       $query = $this->createQueryBuilder('o')
           ->andWhere('o.account = :acc')
           ->andWhere('o.date >= :startDate')
           ->andWhere('o.date < :endDate')
           ->setParameter('acc', $account)
           ->setParameter('startDate', $start_date)
           ->setParameter('endDate', $end_date)
           ->orderBy('o.date', 'ASC')
           ->setMaxResults(self::PAGINATOR_PER_PAGE)
           ->setFirstResult($offset)
           ->getQuery()
       ;

       return new Paginator($query);

   }

   public function getTotalValueByType(Account $account, $type, $start_date, $end_date)
   {
       return $this->createQueryBuilder('o')
            ->select('SUM(o.value)')
           ->andWhere('o.account = :acc')
           ->andWhere('o.date >= :startDate')
           ->andWhere('o.date < :endDate')
           ->join('o.category', 'c')
           ->andWhere('c.type = :type')
           ->setParameter('acc', $account)
           ->setParameter('type', $type)
           ->setParameter('startDate', $start_date)
           ->setParameter('endDate', $end_date)
           ->orderBy('o.date', 'ASC')
           ->getQuery()
           ->getSingleResult()[1]
       ;
   }

   public function getTotalValueByTypeWithCategory(Account $account, $type, $start_date, $end_date)
   {
        $builder = $this->createQueryBuilder('o')
            ->select('c.name, SUM(o.value) value')
            ->andWhere('o.account = :acc')
            ->Join('o.category', 'c')
            ->andWhere('c.type = :type')
            ->setParameter('acc', $account)
            ->setParameter('type', $type)
            ->groupBy('c.name');

        if($start_date != '' && $end_date != '')
        {
            $builder
                ->andWhere('o.date >= :startDate')
                ->andWhere('o.date < :endDate')
                ->setParameter('startDate', $start_date)
                ->setParameter('endDate', $end_date);
        }

        return $builder->getQuery()->getResult();
    }
//    public function findOneBySomeField($value): ?Operation
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

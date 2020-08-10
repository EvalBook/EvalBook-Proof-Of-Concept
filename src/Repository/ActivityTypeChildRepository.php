<?php

namespace App\Repository;

use App\Entity\ActivityType;
use App\Entity\ActivityTypeChild;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method ActivityTypeChild|null find($id, $lockMode = null, $lockVersion = null)
 * @method ActivityTypeChild|null findOneBy(array $criteria, array $orderBy = null)
 * @method ActivityTypeChild[]    findAll()
 * @method ActivityTypeChild[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityTypeChildRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityTypeChild::class);
    }

    /**
     * Populate database with default activity types children ( most commonly used ).
     * @param TranslatorInterface $translator
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function populate(TranslatorInterface $translator)
    {
        $em = $this->getEntityManager();
        $activityTypesRepository = $em->getRepository(ActivityType::class);

        // Only if activity types already populated with default values.
        if($activityTypesRepository->activityTypesCount() > 0) {
            // Knowledge activity type.
            $french = new ActivityTypeChild();
            $french
                ->setName($translator->trans('French', [], 'templates'))
                ->setActivityType($activityTypesRepository->findOneBy([
                    // Knowledge = 0
                    'weight' => '0'
                ]))
            ;
            $em->persist($french);

            $maths = new ActivityTypeChild();
            $maths
                ->setName($translator->trans('Mathematics', [], 'templates'))
                ->setActivityType($activityTypesRepository->findOneBy([
                    // Knowledge = 0
                    'weight' => '0'
                ]))
            ;

            $em->persist($maths);

            $eveil = new ActivityTypeChild();
            $eveil
                ->setName($translator->trans('History / Geography', [], 'templates'))
                ->setActivityType($activityTypesRepository->findOneBy([
                    // Knowledge = 0
                    'weight' => '0'
                ]))
            ;
            $em->persist($eveil);


            $em->persist($maths);

            $specialClassrooms = new ActivityTypeChild();
            $specialClassrooms
                ->setName($translator->trans('Special classrooms', [], 'templates'))
                ->setActivityType($activityTypesRepository->findOneBy([
                    // Knowledge = 0
                    'weight' => '0'
                ]))
            ;
            $em->persist($specialClassrooms);


            // Behavior activity type.
            $behaviorWithMaster = new ActivityTypeChild();
            $behaviorWithMaster
                ->setName($translator->trans('Behavior with classroom owner', [], 'templates'))
                ->setActivityType($activityTypesRepository->findOneBy([
                    // Behavior = 2
                    'weight' => '2'
                ]))
            ;
            $em->persist($behaviorWithMaster);

            $behaviorWithSpecialMasters = new ActivityTypeChild();
            $behaviorWithSpecialMasters
                ->setName($translator->trans('Behavior with special masters', [], 'templates'))
                ->setActivityType($activityTypesRepository->findOneBy([
                    // Behavior = 2
                    'weight' => '2'
                ]))
            ;
            $em->persist($behaviorWithSpecialMasters);

            $em->flush();
        }
    }


    /**
     * Return available activity types children count.
     * @return int
     */
    public function activityTypesChildCount()
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder
            ->select('count(a.id)')
            ->from(ActivityTypeChild::class, 'a')
        ;

        try {
            $count = $queryBuilder->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
        } catch (NonUniqueResultException $e) {
            return 1;
        }

        return intval($count);

    }
}

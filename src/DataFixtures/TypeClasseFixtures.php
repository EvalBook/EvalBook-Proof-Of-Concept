<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\TypeClasse;


/**
 * Class TypeClasseFixtures
 * @package App\DataFixtures
 */
class TypeClasseFixtures extends Fixture
{
    public const NORMAL_CLASS  = 'normal_class';
    public const SPECIAL_CLASS = 'special_class';

    /**
     * Load fixtures.
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $typeClasse1 = new TypeClasse();
        $typeClasse2 = new TypeClasse();

        $typeClasse1->setName("Classe normale");
        $typeClasse2->setName("Classe maître spécial");

        $this->addReference(self::NORMAL_CLASS, $typeClasse1);
        $this->addReference(self::SPECIAL_CLASS, $typeClasse2);

        $manager->persist($typeClasse1);
        $manager->persist($typeClasse2);
        $manager->flush();
    }
}
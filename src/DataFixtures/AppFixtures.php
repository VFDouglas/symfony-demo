<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $product = new Product();
        $product->setName('Procuct One');
        $product->setDescription('Description One');
        $product->setSize(100);

        $manager->persist($product);

        $product = new Product();
        $product->setName('Procuct Two');
        $product->setDescription('Description Two');
        $product->setSize(200);

        $manager->persist($product);

        $manager->flush();
    }
}

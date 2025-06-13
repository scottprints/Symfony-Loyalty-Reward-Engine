<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Prize;
use App\Entity\SpinResult;
use App\Entity\PointsTransaction;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // Create customers
        $customers = [];
        for ($i = 0; $i < 10; $i++) {
            $customer = new Customer();
            $customer->setEmail($faker->email());
            $customer->setPoints($faker->numberBetween(0, 1000));
            $manager->persist($customer);
            $customers[] = $customer;
        }

        // Create prizes
        $prizes = [];
        $prizeNames = ['Free Pizza', 'Free Drink', 'Free Dessert', 'Free Side', 'Free Appetizer'];
        foreach ($prizeNames as $name) {
            $prize = new Prize();
            $prize->setName($name);
            $prize->setPointCost($faker->numberBetween(50, 200));
            $prize->setPointsAward($faker->numberBetween(10, 50));
            $prize->setIsActive(true);
            $manager->persist($prize);
            $prizes[] = $prize;
        }

        // Create spin results
        for ($i = 0; $i < 20; $i++) {
            $spinResult = new SpinResult();
            $spinResult->setCustomer($faker->randomElement($customers));
            $spinResult->setPrize($faker->randomElement($prizes));
            $spinResult->setPointsAwarded($faker->numberBetween(10, 50));
            $spinResult->setCreatedAt($faker->dateTimeThisMonth());
            $manager->persist($spinResult);
        }

        // Create points transactions
        for ($i = 0; $i < 30; $i++) {
            $transaction = new PointsTransaction();
            $transaction->setCustomer($faker->randomElement($customers));
            $transaction->setAmount($faker->numberBetween(10, 100));
            $transaction->setType($faker->randomElement(['spin', 'redeem']));
            $transaction->setCreatedAt($faker->dateTimeThisMonth());
            $manager->persist($transaction);
        }

        $manager->flush();
    }
} 
<?php
/**
 * Created by PhpStorm.
 * User: Mickael
 * Date: 19/11/2018
 * Time: 16:34
 */

namespace App\DataFixtures;

use App\Entity\Member;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class MemberTestFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $members = [
            ['firstName' => 'Bernard',
                'lastName' => 'Dupont',
                'email' => 'bernard.dupont@gmail.com',
                'password' => 'UG552YD4R9LU'],

            ['firstName' => 'Mirentxu',
                'lastName' => 'Etchegarray',
                'email' => 'mirentxu.etchegarray@gmail.com',
                'password' => 'CFVES25UGH63'],

            ['firstName' => 'Txomin',
                'lastName' => 'Iturria',
                'email' => 'txomin.iturria@gmail.com',
                'password' => '54OHND3UBG5Z'],

            ['firstName' => 'Bixente',
                'lastName' => 'Olagaray',
                'email' => 'bixente.olagaray@gmail.com',
                'password' => 'GBNH52E698AS'],

            ['firstName' => 'Maite',
                'lastName' => 'Bideondo',
                'email' => 'maite.bideondo@gmail.com',
                'password' => 'GN2485SECNU4']];

        foreach ($members as $memberInfo) {
            $member = new Member();
            $member->setFirstName($memberInfo['firstName']);
            $member->setLastName($memberInfo['lastName']);
            $member->setEmail($memberInfo['email']);
            $member->setPassword($memberInfo['password']);
            $manager->persist($member);
        }

        $manager->flush();
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Mickael
 * Date: 19/11/2018
 * Time: 14:50
 */

namespace App\Tests\API;

use App\DataFixtures\MemberTestFixtures;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Component\HttpKernel\KernelInterface;

class DatabaseConfiguration
{
    public function cleanDatabase(KernelInterface $kernel, $tableName) {

        $doctrineManager = $kernel->getContainer()->get('doctrine')->getManager();
        $databaseCleaner = new ORMPurger($doctrineManager);

        /*
         * Reset all content and auto_increment from a SQLite database
         */
        $databaseCleaner->purge();
        $databaseCleaner->getObjectManager()->getConnection()->
        executeUpdate("DELETE FROM sqlite_sequence WHERE name='$tableName'");

        /*
         * Reset all content and auto_increment from a MySQL database
         */
//        $databaseCleaner->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
//        $databaseCleaner->purge();
    }

    public function populateDatabaseWithFixtures(KernelInterface $kernel) {
        $doctrineManager = $kernel->getContainer()->get('doctrine')->getManager();

        $fixture = new MemberTestFixtures();
        $fixture->load($doctrineManager);
    }
}
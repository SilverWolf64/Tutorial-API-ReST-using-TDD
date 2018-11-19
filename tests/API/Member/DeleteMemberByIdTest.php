<?php
/**
 * Created by PhpStorm.
 * User: Mickael
 * Date: 19/11/2018
 * Time: 15:47
 */

namespace App\Tests\API\Member;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\Api\DatabaseConfiguration;

class DeleteMemberByIdTest extends WebTestCase
{
    public static $uri = 'api/members';
    public static $memberId = 3;

    public static function setUpBeforeClass()
    {
        self::bootKernel();

        $database = new DatabaseConfiguration();
        $database->cleanDatabase(self::$kernel, "member");
        $database->populateDatabaseWithFixtures(self::$kernel);
    }

    public function testPutMemberById()
    {
        $expectedStatus = 204;

        $client = self::createClient();

        $client->request(
            'DELETE',
            self::$uri . "/" . self::$memberId . ".jsonld",
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/ld+json'));

        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertEquals($expectedStatus, $client->getResponse()->getStatusCode());
    }
}
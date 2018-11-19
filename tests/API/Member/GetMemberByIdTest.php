<?php
/**
 * Created by PhpStorm.
 * User: Mickael
 * Date: 19/11/2018
 * Time: 15:48
 */

namespace App\Tests\API\Member;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\Api\DatabaseConfiguration;

class GetMemberByIdTest extends WebTestCase
{
    public static $uri = 'api/members';
    public static $memberId = 3;

    public static function setUpBeforeClass() {
        self::bootKernel();

        $database = new DatabaseConfiguration();
        $database->cleanDatabase(self::$kernel, "member");
        $database->populateDatabaseWithFixtures(self::$kernel);
    }

    public function testGetMemberById()
    {
        $expectedStatus = 200;
        $expectedResponse = <<< JSON
                {
                    "@context": "/api/contexts/Member",
                    "@id": "/api/members/3",
                    "@type": "Member",
                    "id": 3,
                    "firstName": "Txomin",
                    "lastName": "Iturria",
                    "email": "txomin.iturria@gmail.com",
                    "password": "54OHND3UBG5Z"
}
JSON;

        $client = self::createClient();

        $client->request(
            'GET',
            self::$uri. "/" . self::$memberId . ".jsonld",
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/ld+json'));

        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertEquals($expectedStatus, $client->getResponse()->getStatusCode());

        $this->assertJsonStringEqualsJsonString($expectedResponse, $client->getResponse()
            ->getContent());
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Mickael
 * Date: 19/11/2018
 * Time: 15:45
 */

namespace App\Tests\API\Member;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\Api\DatabaseConfiguration;

class PutMemberByIdTest extends WebTestCase
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
        $expectedStatus = 200;
        $requestBody = <<< JSON
                {
                    "firstName": "Dominique",
                    "email": "dominique.iturria@gmail.com"
                }
JSON;
        $expectedResponse = <<< JSON
                {
                    "@context": "/api/contexts/Member",
                    "@id": "/api/members/3",
                    "@type": "Member",
                    "id": 3,
                    "firstName": "Dominique",
                    "lastName": "Iturria",
                    "email": "dominique.iturria@gmail.com",
                    "password": "54OHND3UBG5Z"
}
JSON;

        $client = self::createClient();

        $client->request(
            'PUT',
            self::$uri . "/" . self::$memberId . ".jsonld",
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/ld+json'),
            $requestBody);

        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertEquals($expectedStatus, $client->getResponse()->getStatusCode());

        $this->assertJsonStringEqualsJsonString($expectedResponse, $client->getResponse()
            ->getContent());
    }
}
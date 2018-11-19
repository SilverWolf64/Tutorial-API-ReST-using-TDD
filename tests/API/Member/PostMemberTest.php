<?php
/**
 * Created by PhpStorm.
 * User: Mickael
 * Date: 19/11/2018
 * Time: 14:31
 */

namespace App\Tests\API\Member;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\API\DatabaseConfiguration;

class PostMemberTest extends WebTestCase
{
    public static $uri = 'api/members';

    public static function setUpBeforeClass()
    {
        self::bootKernel();
        $database = new DatabaseConfiguration();
        $database->cleanDatabase(self::$kernel, "member");
    }

    public function testPostMember()
    {

        $expectedStatus = 201;
        $requestBody = <<< JSON
                {
                    "firstName": "Bernard",
                    "lastName": "Dupont",
                    "email": "bernard.dupont@gmail.com",
                    "password": "QGFQ45FGQ554FQ755Q2DFGQGG"
                }
JSON;
        $expectedResponse = <<< JSON
                {
                    "@context": "/api/contexts/Member",
                    "@id": "/api/members/1",
                    "@type": "Member",
                    "id": 1,
                    "firstName": "Bernard",
                    "lastName": "Dupont",
                    "email": "bernard.dupont@gmail.com",
                    "password": "QGFQ45FGQ554FQ755Q2DFGQGG"
                }
JSON;

        $client = self::createClient();
        $client->request(
            'POST',
            self::$uri . ".jsonld",
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
<?php
/**
 * Created by PhpStorm.
 * User: Mickael
 * Date: 19/11/2018
 * Time: 11:20
 */

namespace App\Tests\API\Member;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\API\DatabaseConfiguration;

class GetMemberTest extends WebTestCase
{
    public static $uri = 'api/members';

    public static function setUpBeforeClass()
    {
        self::bootKernel();
        $database = new DatabaseConfiguration();
        $database->cleanDatabase(self::$kernel, "member");
    }

    public function testGetMembers()
    {
        $expectedStatus = 200;
        $expectedResponse = <<< JSON
                {
                    "@context": "/api/contexts/Member",
                    "@id": "/api/members",
                    "@type": "hydra:Collection",
                    "hydra:member": [],
                    "hydra:totalItems": 0
                }
JSON;

        $client = self::createClient();
        $client->request(
            'GET',
            self::$uri . ".jsonld",
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/ld+json'));

        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertEquals($expectedStatus, $client->getResponse()->getStatusCode());

        $this->assertJsonStringEqualsJsonString($expectedResponse, $client->getResponse()->getContent());
    }
}
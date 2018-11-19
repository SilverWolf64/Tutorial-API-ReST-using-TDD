# Testing API in Symfony Tutorial

In this tutorial, we are going to learn how to develop and test an API made with Symfony.

## Getting started

First of all, we are going to start a new Symfony project. In this tutorial, I will start with a simple Symfony "*skeleton*" but you can also use the more complete "*website-skeleton*". Open your terminal and run the following commands:

```bash
$ composer create-project symfony/skeleton your-project-name

$ cd your-project-name
$ composer require server --dev
```

You can check that your Symfony project has been correctly installed by running the command:

```bash
$ php bin/console server:run
```

You can then go to the Symfony welcome page by clicking on the link that appeared in your console or by using the URL: http://localhost:8000 in your internet browser. You can then quit the server by using Ctrl+C.

## Setting up a testing environment

### PHPUnit

In this tutorial, I will use PHPUnit  to test the API. To install it, run:

```bash
$ composer require --dev phpunit
$ php bin/phpunit
```

This second command is the one we will always use to run our tests. During the first run, PHPUnit will install some dependences but it won't after. As you can see in your console, since we still haven't created test classes, PHPUnit tells you "*==No tests executed!==*".

Please check that you now have a file named "*phpunit.xml.dist*" at the root of your project, it will be important from now on.

### Test Database

You might already know how to set up a database in Symfony and use it in your development but this database is not made to be used in tests. In order to run fixed tests, you will need to perfectly control the content of your database, which means you're going to sometimes need to empty it or fill it with fixtures (we are going to do both later). That is why the database you use for development which is usually filled with fake data must not be used.

Let's start by installing some dependences to create our database with doctrine:

```bash
$ composer require doctrine
```

You now have doctrine installed and you can set up your database by editing the "*.env*" file:

```
DATABASE_URL=mysql://db_user:db_password@127.0.0.1:3306/db_name
```

But this is for the development database, not the one you will use for tests. To create a different database, you need to add an new environment variable:

```
DATABASE_URL=mysql://db_user:db_password@127.0.0.1:3306/db_name
TEST_DATABASE_URL=sqlite:///%kernel.project_dir%/var/test_db.db
```

Notice that I chose to use SQLite for my test database but you can also choose to use MySQL, later in this tutorial, I will show you how to work with both in your tests.

Unfortunately, this is not the only thing you need to do, we now need to tell doctrine which database it needs to use in the test environment.

Run:

```bash
$ cd config/packages
```

In this directory, you will find the "*doctrine.yaml*" file and a "*test*" directory. Make a copy of the "*doctrine.yaml*" file into the test directory and edit it, it should look like this:

```yaml
parameters:
    # Adds a fallback DATABASE_URL if the env var is not set.
    # This allows you to run cache:warmup even if your
    # environment variables are not available yet.
    # You should not need to change this value.
    env(DATABASE_URL): ''

doctrine:
    dbal:
        # configure these for your database server
        driver: 'pdo_mysql'
        server_version: '5.7'
        charset: utf8mb4
        default_table_options:
            charset: utf8mb4
            collate: utf8mb4_unicode_ci

        url: '%env(resolve:DATABASE_URL)%'
    orm:
        auto_generate_proxy_classes: true
        naming_strategy: doctrine.orm.naming_strategy.underscore
        auto_mapping: true
        mappings:
            App:
                is_bundle: false
                type: annotation
                dir: '%kernel.project_dir%/src/Entity'
                prefix: 'App\Entity'
                alias: App
```

Replace the variable **DATABASE_URL** by **TEST_DATABASE_URL**:

```yaml
parameters:
    # Adds a fallback DATABASE_URL if the env var is not set.
    # This allows you to run cache:warmup even if your
    # environment variables are not available yet.
    # You should not need to change this value.
    env(TEST_DATABASE_URL): ''

doctrine:
    dbal:
        # configure these for your database server
        driver: 'pdo_mysql'
        server_version: '5.7'
        charset: utf8mb4
        default_table_options:
            charset: utf8mb4
            collate: utf8mb4_unicode_ci

        url: '%env(resolve:TEST_DATABASE_URL)%'
    orm:
        auto_generate_proxy_classes: true
        naming_strategy: doctrine.orm.naming_strategy.underscore
        auto_mapping: true
        mappings:
            App:
                is_bundle: false
                type: annotation
                dir: '%kernel.project_dir%/src/Entity'
                prefix: 'App\Entity'
                alias: App
```

Doctrine is now set up and you can create your test database. To do that, go back to the root of your project and run:

```
$ php bin/console doctrine:database:create --env=test
```

If you go to the "*var*" folder of your project, you will now have a "*test_db.db*" file. This is your SQLite test database.

### What about "*phpunit.xml.dist*"? 

Remember, I told you that this file was going to be important. When you run your tests, PHPUnit will not search for environment variables in your "*.env*" file so it will not find your test database. You need to put your environment variables in "*phpunit.xml.dist*". Edit this file and part of it should look like this:

```XML
<php>
    <ini name="error_reporting" value="-1" />
    <env name="APP_ENV" value="test" />
    <env name="SHELL_VERBOSITY" value="-1" />
</php>
```

Add this line:

```xml
<env name="TEST_DATABASE_URL" value="sqlite:///%kernel.project_dir%/var/test_db.db" />
```

Now PHPUnit will know where your test database is.



And we are done, you have now a working test environment. For more information about what I did in this part please use these links:

[Testing in Symfony]: https://symfony.com/doc/current/testing.html
[Using a Test Database]: https://symfonycasts.com/screencast/symfony-rest/test-database



## Example

We are now going to do a small example of how to write tests for your API. I am going to work with only one entity called Member that will have as variables: a first name, a last name, an email and a password.

### The GET method  test

The first thing we are going to test is the GET request of your API (Your API is not set up yet? That's normal, we are going to do it later). Go to the root of your project, you should find a folder called "*tests*", run the following commands:

```bash
$ cd tests
$ mkdir API
$ mkdir Member
```

It will be easier for a big project to organize your tests in multiple folders. This is what I used but feel free to do as you like. Just be advised that from now on, there will be namespaces in my code so depending on your arborescence you might run into some issues and need to change them.

Now let's create a test class, you can use your IDE to do it or run:

```bash
$ touch GetMembersTest.php
```

Important: Your file name must end with "*Test*" in order for PHPUnit to identify this file as a test it needs to run.

Here is the content of your test file:

```php
<?php
/**
 * Created by PhpStorm.
 * User: Mickael
 * Date: 19/11/2018
 * Time: 11:20
 */

namespace App\Tests\API\Member;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GetMemberTest extends WebTestCase
{
    public static $uri = 'api/members';

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

        $this->assertJsonStringEqualsJsonString($expectedResponse, $client->getResponse()
                                                ->getContent());
    }
}
```

"How do you know the API expected response?" I just do, you will be able to see it later when we will install API Platform but for now, just trust me.

In this test, the three assertions I use are to see if I get a response for my request and if the status code and the content are what I expect. You should know that the last assertion is actually a three in one assertion because it will first check that the two strings are JSON strings and then compare them. That is why this test should actually return five assertions and not three.

**Important**: notice that in my request I use this URI: *self::$uri . ".jsonld"* which gives "**api/members.jsonld**"

```
self::$uri . ".jsonld"   //which gives: api/members.jsonld
```

The *.jsonld* is very important and necessary. API Platform needs you to specify what type of data you want to be returned. If you don't, API Platform will return you a 200 response containing an HTML telling you that it needs an extension. This can be confusing because it will not return you a 404 so if you check only the response status, PHPUnit will assert that you have the right 200 status but the content will be wrong. 



I will not go into more details about this code. If you want more information about it, here are some useful links:

[The HTTPFoundation Component]: https://symfony.com/doc/current/components/http_foundation.html
[PHPUnit Assertions]: https://phpunit.de/manual/6.5/en/appendixes.assertions.html



Now let's run our tests:

```bash
$ php bin/phpunit
```

We get errors. Great, let's solve them.



What do we need? PHP Unit should tell you:

```
No route found for "GET /api/members.jsonld"
```

We do not have a route for this data. How do we create it? With a controller? **No**. This route will be created automatically by API Platform so let's install it.

### API Platform

API Platform is a component that help easily set up a REST API for your website. I will not go into details about it in this tutorial but if you want to know more, you can use the documentation on their website:

[API Platform Documentation]: https://api-platform.com/docs/

First we need to install the API package, run:

```bash
$ composer require api
```

Once the installation is finished, you can check that the API works by launching a server with:

```bash
$ php bin/console server:run
```

and go to http://127.0.0.1:8000/api on your web browser. You should see this screen:

![Annotation 2018-11-19 135808](C:\Users\Mickael\Desktop\Screen Tuto\Annotation 2018-11-19 135808.jpg)

This is the API Platform welcome page. It is empty for now but we are going to create an entity API Platform will be able to use.

First let's get some dependences:

```bash
$ composer require maker --dev
```

Now we can create our entity following this model, be careful to choose "**yes**" when the console asks you if you want to make this an API Platform ressource:

```bash
$ php bin/console make:entity
```

```
Class name of the entity to create or update (e.g. OrangePizza):
 > Member

 Mark this class as an API Platform resource (expose a CRUD API for it) (yes/no) [no]:
 > yes

 created: src/Entity/Member.php
 created: src/Repository/MemberRepository.php

 Entity generated! Now let's add some fields!
 You can always add more fields later manually or by re-running this command.

 New property name (press <return> to stop adding fields):
 > firstName

 Field type (enter ? to see all types) [string]:
 >

 Field length [255]:
 >

 Can this field be null in the database (nullable) (yes/no) [no]:
 >

 updated: src/Entity/Member.php

 Add another property? Enter the property name (or press <return> to stop adding fields):
 > lastName

 Field type (enter ? to see all types) [string]:
 >

 Field length [255]:
 >

 Can this field be null in the database (nullable) (yes/no) [no]:
 >

 updated: src/Entity/Member.php

 Add another property? Enter the property name (or press <return> to stop adding fields):
 > email

 Field type (enter ? to see all types) [string]:
 >

 Field length [255]:
 >

 Can this field be null in the database (nullable) (yes/no) [no]:
 >

 updated: src/Entity/Member.php

 Add another property? Enter the property name (or press <return> to stop adding fields):
 > password

 Field type (enter ? to see all types) [string]:
 >

 Field length [255]:
 >

 Can this field be null in the database (nullable) (yes/no) [no]:
 >

 updated: src/Entity/Member.php

 Add another property? Enter the property name (or press <return> to stop adding fields):
 >


           
  Success! 
           

 Next: When you're ready, create a migration with make:migration
```

As said by the console, we now need to migrate the entity to our database. Since this is a test database, you do not need to create a migration, just run:

```bash
$ php bin/console doctrine:schema:create --env=test
```

Now you can run:

```bash
$ php bin/phpunit
```

and you will get:

```
#!/usr/bin/env php
PHPUnit 6.5.13 by Sebastian Bergmann and contributors.

Testing Project Test Suite
.                                                                   1 / 1 (100%)

Time: 6.03 seconds, Memory: 38.00MB

OK (1 test, 5 assertions)
```

Great! The test for GET method passed.

### The  POST method test

Now that you've set up your test environment, created the test database and done your test for the GET method you might worry that you are done for the hard part, but good news, the hard part keeps getting harder.

Let's write our test for the POST method. Just like you did for the GetMemberTest.php file, create a PostMemberTest.php file:

```php
<?php
/**
 * Created by PhpStorm.
 * User: Mickael
 * Date: 17/11/2018
 * Time: 17:14
 */

namespace App\Tests\API\Member;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PostMemberTest extends WebTestCase
{
    public static $uri = 'api/members';

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
```

Once you're done, run your tests using:

```bash
$ php bin/phpunit
```

You should get this response:

```
#!/usr/bin/env php
PHPUnit 6.5.13 by Sebastian Bergmann and contributors.

Testing Project Test Suite
..                                                                  2 / 2 (100%)

Time: 1.81 seconds, Memory: 22.00MB

OK (2 tests, 10 assertions)

```

Great, everything works, that was easy... ... Wait a second.... Yes, I did say it was going to get harder, try to run your tests again.

```bash
$ php bin/phpunit
```

Epic fail right? Now, not did the POST test failed but even your previously working GET test doesn't work anymore. That's because, before, your database was always empty but now that you sent data into it, it's not. PHPUnit doesn't automatically clean your database after tests that's why your test worked the first time but failed on the second.

Now let's fix this, what we are going to do is to ensure that the database will be cleaned before every test. Why before and not after? Because later, we are going to use fixtures to populate the database. This will, of course, be done before a test so you might as well do the same to clean it to simplify your code.

First, you will need some dependences:

```
$ composer require --dev doctrine/doctrine-fixtures-bundle
```

In your "**tests/API**" folder, we are going to create a PHP class that we will call "**DatabaseConfiguration.php**"

```php
<?php
/**
 * Created by PhpStorm.
 * User: Mickael
 * Date: 17/11/2018
 * Time: 17:04
 */

namespace App\Tests\API;

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
}
```

Notice that I put both the SQLite and MySQL ways to purge the database. Use the one you need.

In MySQL, the use of "**PURGE_MODE_TRUNCATE**" will delete the content of the database and also reset the auto-incremented "*id*". Unfortunately, SQLite doesn't have a "**TRUNCATE**" request so we need to delete the "*sqlite_sequence*" of the table we want to purge.

Again, I will not go into details about this code but if you want to know more, use these links:

[Clearing the database]: https://symfonycasts.com/screencast/phpunit/control-database
[ORMPurger]: https://hotexamples.com/examples/doctrine.common.datafixtures.purger/ORMPurger/purge/php-ormpurger-purge-method-examples.html



Now that you are done, add these lines of code to both your GetMemberTest.php and PostMemberTest.php:

```php
use App\Tests\API\DatabaseConfiguration;
```

```php
public static function setUpBeforeClass()
    {
        self::bootKernel();

        $database = new DatabaseConfiguration();
        $database->cleanDatabase(self::$kernel, "member");
    }
```

This function setUpBeforeClass() is a function that will be called automatically by PHPUnit before running the test in this file.

Try running your tests again:

```bash
$ php bin/phpunit
```

```
#!/usr/bin/env php
PHPUnit 6.5.13 by Sebastian Bergmann and contributors.

Testing Project Test Suite
..                                                                  2 / 2 (100%)

Time: 1.95 seconds, Memory: 22.00MB

OK (2 tests, 10 assertions)
```

Great, it works again. You can try a few times just to be sure and will work every time.

### The PUT, DELETE and GET by id methods

Yes, I put those three in the same paragraph and yes, that's because we are nearing the end of this tutorial. Let's write the tests for these methods:

PutMemberByIdTest.php:

```php
<?php
/**
 * Created by PhpStorm.
 * User: Mickael
 * Date: 17/11/2018
 * Time: 18:54
 */

namespace App\Tests\Api\Member;

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
```

DeleteMemberByIdTest.php:

```php
<?php
/**
 * Created by PhpStorm.
 * User: Mickael
 * Date: 17/11/2018
 * Time: 19:09
 */

namespace App\Tests\Api\Member;

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
```

Note that the DELETE method does not return a content.

GetMemberByIdTest.php:

```php
<?php
/**
 * Created by PhpStorm.
 * User: Mickael
 * Date: 17/11/2018
 * Time: 18:29
 */

namespace App\Tests\Api\Member;

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
```

Once you're done, you can run:

```bash
$ php bin/phpunit
```

FAILURES. That's too bad. You might have noticed that in those three tests, I am trying to target member whose id number is 3, but wait!!!! Isn't the database always emptied before a test? Yes, it is. That's why for those tests we are going to need to put some fixed data in the database. To do that, we are going to use a **fixture**.

Go to your folder: **src/DataFixtures** and create a file **MemberTestFixtures.php**.

```php
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
```

Now that we have our fixture, we need to load it before our tests, after the database has been purged. We are going to add a function in the DatabaseConfiguration.php:

```php
use App\DataFixtures\MemberTestFixtures;
```

```php
public function populateDatabaseWithFixtures(KernelInterface $kernel) {
        $doctrineManager = $kernel->getContainer()->get('doctrine')->getManager();

        $fixture = new MemberTestFixtures();
        $fixture->load($doctrineManager);
    }
```

You now need to go back to the three test files we just created and add this line in the setUpBeforeClass() function:

```php
$database->populateDatabaseWithFixtures(self::$kernel);
```

So they now look like this:

```php
public static function setUpBeforeClass()
    {
        self::bootKernel();

        $database = new DatabaseConfiguration();
        $database->cleanDatabase(self::$kernel, "member");
        $database->populateDatabaseWithFixtures(self::$kernel);
    }
```

## Conclusion

You can now run one final test:

```bash
$ php bin/phpunit
```

And you should get:

```
#!/usr/bin/env php
PHPUnit 6.5.13 by Sebastian Bergmann and contributors.

Testing Project Test Suite
.....                                                               5 / 5 (100%)

Time: 10.31 seconds, Memory: 40.00MB

OK (5 tests, 22 assertions)
```

Our five tests passed successfully with 22 assertions.
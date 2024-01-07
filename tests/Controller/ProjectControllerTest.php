<?php 
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProjectControllerTest extends WebTestCase
{
    public function testReadAll()
    {
        $client = static::createClient();
        $client->request("GET","/home");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
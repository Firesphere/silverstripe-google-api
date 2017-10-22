<?php

class AAGoogleClientServiceTest extends SapphireTest
{
    /**
     * @expectedException LogicException
     */
    public function testException()
    {
        new GoogleClientService();
    }

    /**
     * Validate it's constructed
     */
    public function testCreation()
    {
        define('SS_ANALYTICS_KEY', 'google-api/tests/fixtures/test.json');
        $client = new GoogleClientService();
        $this->assertInstanceOf(Google_Client::class, $client->getClient());
    }
}

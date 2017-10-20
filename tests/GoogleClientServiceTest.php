<?php

class GoogleClientServiceTest extends SapphireTest
{
    /**
     * Validate it's constructed
     */
    public function testCreation()
    {
        $client = new GoogleClientService();
        $this->assertInstanceOf(Google_Client::class, $client->getClient());
    }
}

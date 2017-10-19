<?php

class GoogleClientServiceTest extends SapphireTest
{
    public function testCreation()
    {
        $client = new GoogleClientService();
        $this->assertInstanceOf(Google_Client::class, $client->getClient());
    }
}

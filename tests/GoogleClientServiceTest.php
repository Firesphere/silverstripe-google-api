<?php

namespace Firesphere\GoogleAPI\Tests;

use Firesphere\GoogleAPI\Services\GoogleClientService;
use Google_Client;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;

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

    /**
     * @expectedException \LogicException
     */
    public function testNoAnalyticsKey()
    {
        if (!Environment::getEnv('SS_ANALYTICS_KEY')) {
            Environment::setEnv('SS_ANALYTICS_KEY', 'google-api/tests/fixtures/test.json');
        }
        new GoogleClientService();
    }
}

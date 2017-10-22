<?php

class GoogleClientServiceTest extends SapphireTest
{
    /**
     * We want this process to be run in a separate process
     * for constant definition reasons
     * @var bool
     */
    protected $runTestInSeparateProcess = true;
    protected $preserveGlobalState = false;


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

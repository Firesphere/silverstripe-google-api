<?php
namespace Firesphere\GoogleAPI\Services;

use Google_Client;
use Google_Exception;
use Google_Service_Analytics;
use LogicException;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;

class GoogleClientService
{
    /**
     * @var Google_Client
     */
    protected $client;

    /**
     * GoogleClientService constructor.
     * @throws LogicException
     * @throws Google_Exception
     */
    public function __construct()
    {
        if (!Environment::getEnv('SS_ANALYTICS_KEY')) {
            throw new LogicException('No analytics API set up');
        }

        $client = new Google_Client();
        $client->setAuthConfig(Environment::getEnv('SS_ANALYTICS_KEY'));
        $client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);

        $this->setClient($client);
    }

    /**
     * @return Google_Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Google_Client $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }
}

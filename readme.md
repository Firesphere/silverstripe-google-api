# Connect to Google API
[![codecov](https://codecov.io/gh/Firesphere/silverstripe-google-api/branch/master/graph/badge.svg)](https://codecov.io/gh/Firesphere/silverstripe-google-api)
[![Scrutinizer](https://scrutinizer-ci.com/g/Firesphere/silverstripe-google-api/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Firesphere/silverstripe-google-api/)
[![CircleCI](https://circleci.com/gh/Firesphere/silverstripe-google-api/tree/master.svg?style=svg)](https://circleci.com/gh/Firesphere/silverstripe-google-api/tree/master)
[![Maintainability](https://api.codeclimate.com/v1/badges/893fcc84d65664ec64c9/maintainability)](https://codeclimate.com/github/Firesphere/silverstripe-google-api/maintainability)

## Google API
The Google API V4 is pretty powerful and supports many different calls to different applications.

The initial use case implemented in this module is, to get the page views for the a set of pages in a given time period and store this count in the database, so that you can sort pages by popularity.
There are many features of the google api, that could be included in future updates or extensions of this module.

Further functionality is possible with the given API

## Installation

`composer require firesphere/google-api:dev-master`

And run a `dev/build`

## Set up

First, you will have to set up a Google API application, that is connected to Google Analytics.
How to set that up is detailed [In the Google Documentation](https://developers.google.com/analytics/devguides/reporting/core/v4/quickstart/web-php).

Put the downloaded json in the document root (Better solution to be done). And add a key for it to your `_ss_environment` file:
```php
define('SS_ANALYTICS_KEY', 'name-of-my-file');
```

Currently, the OAuth2 integration is not built yet, so for now, add the email address from the downloaded json (key: client_email)
and add this user to your Analytics account. You will need to give it full administrative powers.

Second, copy your `View ID` from the Google Analytics account you want your data from, and put it in the cms under Settings > Google API

## Configuration

Create a yml file, or add to an existing config yml the configuration for your update:
```yaml
GoogleAnalyticsReportService:
  begins_with: '/mypage' # Find everything Google knows about children of this page. Can not be used in combination with other configurations
  ends_with: 'mypage' # Find everything Google knows about pages whose URL end with 'mypage', Can not be used in combination with other configurations
  whitelist: # Array of pagetypes you want to search for. Can be combined with blacklist
    - MyPageType
    - MyOtherPageType
  blacklist: # Exclude these pagetypes from being returned
    - MyBlacklistedPage
    - MyOtherBlacklistedPage
```

With multiple filters, it seems Google limits to only the first 20 filters.

If you have multiple filters, you need to set up the batch functionality. By default, the CronTask will do this for you.

The crontask currently does _not_ support scheduling, but will probably do so in the future.

Filters are what Google calls "DimensionFilters", which are contained in a "DimensionFilterClause". A filter, at this stage, is a specific page URL.

More on [DimensionFilterClauses and DimensionFilters can be found in the Google Documentation](https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet#DimensionFilterClause).

## Did you say Ninja Unicorns?

```
                                                    /
                                                  .7
                                       \       , //
                                       |\.--._/|//
                                      /\ ) ) ).'/
                                     /(  \  // /
                                    /(   J`((_/ \
                                   / ) | _\     /
                                  /|)  \  eJ    L
                                 |  \ L \   L   L
                                /  \  J  `. J   L
                                |  )   L   \/   \
                               /  \    J   (\   /
             _....___         |  \      \   \```
      ,.._.-'        '''--...-||\     -. \   \
    .'.=.'                    `         `.\ [ Y
   /   /                                  \]  J
  Y / Y                                    Y   L
  | | |          \                         |   L
  | | |           Y                        A  J
  |   I           |                       /I\ /
  |    \          I             \        ( |]/|
  J     \         /._           /        -tI/ |
   L     )       /   /'-------'J           `'-:.
   J   .'      ,'  ,' ,     \   `'-.__          \
    \ T      ,'  ,'   )\    /|        ';'---7   /
     \|    ,'L  Y...-' / _.' /         \   /   /
      J   Y  |  J    .'-'   /         ,--.(   /
       L  |  J   L -'     .'         /  |    /\
       |  J.  L  J     .-;.-/       |    \ .' /
       J   L`-J   L____,.-'`        |  _.-'   |
        L  J   L  J                  ``  J    |
        J   L  |   L                     J    |
         L  J  L    \                    L    \
         |   L  ) _.'\                    ) _.'\
         L    \('`    \                  ('`    \
          ) _.'\`-....'                   `-....'
         ('`    \
          `-.___/ 
```

## License

BSD-3 clause
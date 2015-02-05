## swale
This is a tiny MVC framework based on swoole extension.


#### Installation ####

Create a new file composer.json in your project folder with the following content.

```JSON
{
    "repositories": [{
        "type": "package",
        "package": {
            "name": "robinmin/swale",
            "version": "dev-master",
            "source": {
                "url": "git://github.com/robinmin/swale.git",
                "type": "git",
                "reference": "origin/master"
            }
        }
    }],
    "require": {
        "php": ">=5.4.0",
        "aura/sql": "dev-master",
        "aura/session": "dev-master",
        "guzzlehttp/guzzle": "4.1.*",
        "smarty/smarty": "3.1.*@dev",
        "ssdb/phpssdb": "dev-master",
        "maliemin/ssdb-session": "dev-master",
        "robinmin/swale": "dev-master"
    }
}
```

Then run the following command:

```BASH
composer install --dev
```


## swale
Swale is a tiny MVC framework for application server based on [swoole](http://www.swoole.com/) extension.


#### Installation ####

Create a new file composer.json in your project folder with the following content.

```JSON
{
    "require": {
        "php"                  : ">=5.4.0",
        "aura/sql"             : "dev-master",
        "aura/session"         : "dev-master",
        "guzzlehttp/guzzle"    : "4.1.*",
        "smarty/smarty"        : "3.1.*@dev",
        "ssdb/phpssdb"         : "dev-master",
        "robinmin/swale"       : "dev-master"
    }
}
```

Then run the following command to prepare project folder structure:

```BASH
composer install --dev
cp -p vendor/robinmin/swale/*.php .
cp -rp vendor/robinmin/swale/apps ./apps
mkdir test
```

Now, you can start the default web server by:

```BASH
php ./server.php
```

#### Change Log ####

Date | Content
-------- | --------
2015-02-10 | Add unit test support
2015-02-26 | Add authentication with simple acl

#### Planned Feature ####
  - Asynchonized file log

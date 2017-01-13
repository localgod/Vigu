[![Build Status](https://secure.travis-ci.org/localgod/Vigu.png?branch=master)](http://travis-ci.org/localgod/Vigu)
Vigu
[![Maintenance](https://img.shields.io/maintenance/yes/2013.svg)]()
====

*Authors* [Jens Riisom Schultz](mailto:ibber_of_crew42@hotmail.com)(_backend_), [Johannes Skov Frandsen](mailto:localgod@heaven.dk)(_frontend_)

*Since*   2012-03-20

Vigu is a PHP error aggregation system, which collects all possible PHP errors and aggregates them in a Redis database. It includes a frontend to browse the data.

This project is based on [Zaphod](https://github.com/Ibmurai/zaphod) and uses several other projects:

  * [Redis](http://redis.io)
  * [The Frood VC framework](https://github.com/Ibmurai/frood)
  * [FroodTwig](https://github.com/Ibmurai/froodTwig)
  * [PHP-Daemon](https://github.com/shaneharter/PHP-Daemon)
  * [Jquery](http://jquery.com/)
  * [jqGrid](http://www.trirand.com/blog/)
  * [git](http://git-scm.com/)
  

Requirements
------------

  * `git` is required for installation.
  * You need apache `mod_rewrite`.
  * You need the [phpredis](https://github.com/nicolasff/phpredis) PHP extension.
  * You need a Redis server, dedicated to this application.

Optionally you can use a gearman based variant of the daemon, adding the following dependencies:

  * [Gearman](http://gearman.org/)
  * [pecl_gearman](http://pecl.php.net/package/gearman)
  * [php-gearman-admin](https://github.com/Ibmurai/php-gearman-admin)


Documentation
-------------

  * Point your browser to the root of the site, to start browsing errors.


Installing
----------

  * Clone vigu from git, i.e. `git clone http://github.com/localgod/Vigu.git Vigu`
  * Run `php composer.phar install` from command line.
  * Copy `vigu.ini.dist` to `vigu.ini` and edit it.
  * Make a vhost, to point at the root of vigu or the `web/` folder, or however you choose to serve the site.
  * Set the daemon up, using `php handlers/daemon.php -I`. The daemon should be running at all times, but it may ONLY run on the Vigu server.
  * Copy `vigu.ini` to `handlers/vigu.ini`.
  * Include `handlers/shutdown.php` before anything else, preferably through php.ini's `auto_prepend_file` directive. It has no dependencies besides it's configuration file, which must exist next to it, so you can safely deploy it alone, to all your servers.


License
-------

Copyright 2012 Jens Riisom Schultz, Johannes Skov Frandsen

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.

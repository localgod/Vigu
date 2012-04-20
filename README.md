Vigu
====

*Authors* [Jens Riisom Schultz](mailto:ibber_of_crew42@hotmail.com), [Johannes Skov Frandsen](mailto:localgod@heaven.dk)
*Since*   2012-03-20

Vigu is a PHP error aggregation system, which collects all possible PHP errors and aggregates them in a Redis database. It includes a frontend to browse the data.

This application is based on [Redis](http://redis.io) and The [Zaphod distribution](https://github.com/Ibmurai/zaphod) of [The Frood VC framework](https://github.com/Ibmurai/frood).


Requirements
------------

  * You need apache mod_rewrite.
  * You need the `pecl_http` PHP extension.
  * You need the [`phpredis`](https://github.com/nicolasff/phpredis) PHP extension.
  * You need a Redis server, dedicated to this application.


Documentation
-------------

  * Point your browser to the root of the site, to start browsing errors.


Installing
----------

  * Clone vigu from git.
  * Run `install.php` from command line.
  * Copy `vigu.ini.dist` to `vigu.ini` and edit it.
  * Make a vhost, to point at the root of vigu or the `web/` folder, or however you choose to serve the site.
  * Copy `handlers/shutdown.ini.dist` to `handlers/shutdown.ini` and edit it.
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

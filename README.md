m
=

Tools for dumping and reporting, they are a little gruff, but they are smart about being called on a dev machine vs a live-production server.
At first the behavior was different in case a dump was left in code that made it to the production server. Later I realized that I wanted different behavior as well.

If you are on my team, there is a 'dev' branch - please use it on your local dev boxes. I'll be implementing it soon on our staging servers.

```php
m::dump($_SERVER);

```

not really geared for public consumption, but that will be my next step.

rename the sample.ini file to m.ini. make changes therein.
# film2serial API Service Crawler

film2serial-api-service-crawler

Run daily or every hours:

```
php importer.php
```

This crawler is designed to automatically update the database.
But this will require a **DataLife Engine** CMS. Something that is not available for free. (Free trial has limitation)
https://dle-news.com/

#### Download the trial version: Download DataLife Engine

- https://dle-news.com/demo.html
- https://dle-news.com/price.html

If you want to use a crawler without this CMS(DataLife). You can look at the [parser-test.php](parser-test.php) file.

### Cron Jobs

| Minute | Hour | Day | Month | Weekday | Command |
| :---: | :---: | :---: | :---: | :---: | :---: |
| 0 | * |	* |	* |	* |	/usr/local/bin/ea-php74 /home/hostName/public_html/folder-test/importer.php |

```
0	*	*	*	*	/usr/local/bin/php /home/hostName/public_html/folder-test/importer.php
or
0	*	*	*	*	/usr/local/bin/ea-php74 /home/hostName/public_html/folder-test/importer.php
```

---------

# Max Base

My nickname is Max, Programming language developer, Full-stack programmer. I love computer scientists, researchers, and compilers. ([Max Base](https://maxbase.org/))

## Asrez Team

A team includes some programmer, developer, designer, researcher(s) especially Max Base.

[Asrez Team](https://www.asrez.com/)


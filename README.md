## php-election-scraper
#### Guzzle (curl) based election scraper of Guardian(tm) election results presentation sites.

There are some assumptions to the provided bash script.  To install, perform the following:
**relatively low-fuss install:**
first, install [composer](https://getcomposer.org/download/)

```cd ~/```

```git clone https://github.com/mattyhead/php-election-scraper.git```

```cd php-election-scraper```

finally, run:

```composer install```

...and edit settings.json to match your source and target (leave target values blank to move results to ~/public_html/whowon)
I suggest running with `./scrape.sh` or running periodically from cron.

## php-election-scraper
#### Guzzle (curl) based election scraper of Guardian(tm) election results presentation sites.

There are some assumptions to the provided bash script.  To install, perform the following:
**relatively low-fuss install:**
[install composer](https://getcomposer.org/download/)
`cd ~/` (home directory of user you plan to run)
`git clone https://github.com/mattyhead/php-election-scraper.git` (default path will be created)
`cd php-election-scraper` 
`composer install`

...and edit settings.json to match your source and target (leave target values blank to move results to ~/public_html/whowon)
I suggest running with ./scrape.sh or running periodically from cron.

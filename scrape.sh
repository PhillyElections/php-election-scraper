#!/bin/bash

#uncomment to debug
#set -x

#initialize
limit=5
wait=1
count=1

#goto working directory
cd ~/php_election_scraper
date >> log

php -c /etc/php5/cli/php.ini -d debug_errors -f index.php
r=$? #because running a command before this clobbers my $? value

while [ $r -gt 0 ]
do
  sleep $wait

  php -c /etc/php5/cli/php.ini -d debug_errors -f index.php
  r=$? #...
  ((count++))

  if [ $count -eq $limit ]
  then
    echo "ack!  we hit the retry limit..." >> log
    break
  fi
done

exit $r

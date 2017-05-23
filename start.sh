#!/bin/bash
function clean_up {
    if [ ! -f bot.pid ]; then
        kill -9 $pid
    else
        kill -9 `cat bot.pid`
    fi
    echo "Stopping the bot and removing the .pid file"
    exit
}
trap clean_up SIGHUP SIGINT SIGTERM
php bot.php & export pid=$!
echo -n "$pid" > bot.pid
echo "The PID of the bot is $pid. If you run this script in the background, to stop it, just remove the bot.pid file created in this folder"
elapsed=0
revived=0
while true; do
  if [ "$elapsed" -gt "3600" ]; then
    elapsed=0
    if [ -f "bot.pid" ]; then
      kill -9 $(cat bot.pid)
      rm bot.pid
    fi
    php bot.php & export pid=$!
    echo -n "$pid" > bot.pid
  fi
  if [ ! -f "bot.pid" ]; then
    echo "No PID file found"
    if [ ! -z "$pid" ]; then
      kill -9 "$pid"
      exit
    fi
  fi
  alive=$(ps aux | grep "$pid" | grep -v "grep" | wc -l)
  if [ "$alive" == "0" ]; then
    echo "Bot dead. Reviving"
    php bot.php & export pid=$!
    echo -n "$pid" > bot.pid
    if [ "$revived" -gt "3" ]; then
        echo "Bot died 3 times, fix it"
        clean_up
    fi
    revived=$((revived+1))
  fi
  sleep 1
  elapsed=$((elapsed+1))
done
clean_up

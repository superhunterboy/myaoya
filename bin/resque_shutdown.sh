#!/usr/bin/sh

kill -s QUIT `cat /home/website/bin/resque.pid` && rm -f /home/website/bin/resque.pid

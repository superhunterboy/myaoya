#!/bin/bash

step=30 # 间隔的秒数，不能大于60

for (( i = 0; i < 60; i = (i+step) )); do
    $(curl -ks https://51pphzp.com/checkPayOutStatus > /dev/null 2>&1)
    $(curl -ks http://jsmw672.com/checkPayOutStatus > /dev/null 2>&1)
    sleep $step
done

exit 0

#!/bin/bash

# Stop Local PHP Servers

ROOT_DIR=$(pwd)

echo "Stopping Local Servers..."

kill_server() {
    NAME=$1
    if [ -f "$ROOT_DIR/pid_$NAME.txt" ]; then
        PID=$(cat "$ROOT_DIR/pid_$NAME.txt")
        if ps -p $PID > /dev/null; then
            kill $PID
            echo "$NAME stopped (PID $PID)."
        else
            echo "$NAME not running."
        fi
        rm "$ROOT_DIR/pid_$NAME.txt"
    else
        echo "No PID file for $NAME."
    fi
}

kill_server "api"
kill_server "camat"
kill_server "docku"
kill_server "suratqu"

echo "Done."

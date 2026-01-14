#!/bin/bash

# Start Local PHP Servers
# Usage: bash start_local.sh

# Project Root
ROOT_DIR=$(pwd)

echo "Starting Local Servers..."

# 1. API (Port 8000)
echo "Starting API on http://localhost:8000"
nohup php -S localhost:8000 -t "$ROOT_DIR/api" > "$ROOT_DIR/server_api.log" 2>&1 &
echo $! > "$ROOT_DIR/pid_api.txt"

# 2. Camat (Port 8001)
echo "Starting Camat on http://localhost:8001"
nohup php -S localhost:8001 -t "$ROOT_DIR/camat" > "$ROOT_DIR/server_camat.log" 2>&1 &
echo $! > "$ROOT_DIR/pid_camat.txt"

# 3. Docku (Port 8002)
echo "Starting Docku on http://localhost:8002"
nohup php -S localhost:8002 -t "$ROOT_DIR/docku" > "$ROOT_DIR/server_docku.log" 2>&1 &
echo $! > "$ROOT_DIR/pid_docku.txt"

# 4. SuratQu (Port 8003)
echo "Starting SuratQu on http://localhost:8003"
nohup php -S localhost:8003 -t "$ROOT_DIR/suratqu" > "$ROOT_DIR/server_suratqu.log" 2>&1 &
echo $! > "$ROOT_DIR/pid_suratqu.txt"

echo "All servers started in background."
echo "Logs available in server_*.log files."
echo "Use 'bash stop_local.sh' to stop them."

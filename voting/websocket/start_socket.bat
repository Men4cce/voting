@echo off
cd /d %~dp0
start "" /B "C:\xampp\php\php.exe" "websocket-server.php" > ws_output.log 2>&1
Set WshShell = CreateObject("WScript.Shell")
WshShell.Run chr(34) & "C:\xampp\htdocs\voting\websocket\start_socket.bat" & chr(34), 0
Set WshShell = Nothing
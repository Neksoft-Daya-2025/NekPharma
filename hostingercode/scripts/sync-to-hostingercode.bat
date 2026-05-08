@echo off
setlocal
set "ROOT=%~dp0.."
set "DEST=%ROOT%\hostingercode"
robocopy "%ROOT%" "%DEST%" /E /XD vendor node_modules .git hostingercode .cursor /XD user-uploads /XF .env
set RC=%ERRORLEVEL%
if %RC% GEQ 8 exit /b %RC%
exit /b 0

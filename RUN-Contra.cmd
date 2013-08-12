@echo off
setlocal enableextensions
pushd %~dp0
for %%* in (.) do @title %%~n*
popd
endlocal

rem		Contra run script v3.0 by photofroggy
rem		Released under a Creative Commons 3.0 license.
rem		This is used to launch Contra.
rem		It's also a shell for the process.

if not exist phpbin (
	if not exist run.php (
		echo =========================== WARNING: LAUNCH ERROR =============================
		echo.
		echo ** It looks as though you are trying to launch Contra from outside the bot
		echo ** folder. This does not tend to work on Windows. Please cd to the bot's
		echo ** directory before trying again, or double-click RUN-Contra.cmd to run
		echo ** the bot.
		echo.
		echo ===============================================================================
		pause
		goto stop
	)
)

for /f "eol=# delims=" %%g in (phpbin) do set phpbin=%%g
set args=%1
if not exist "%phpbin%" goto nophp
"%phpbin%" run.php %1
goto loop

:nophp
echo You must have PHP installed! Change the file path in ~/phpbin to the correct
echo file path.
pause
goto stop

:loop
if exist "storage\bat\quit.bcd" (
	del storage\bat\quit.bcd
	del storage\lock
	goto stop
)
if exist "storage\bat\restart.bcd" (
	del storage\bat\restart.bcd
	del storage\lock
	goto refresher
)
if exist "storage\bat\update.bcd" (
	del storage\bat\update.bcd
	del storage\lock
	goto update
)
if !%1==! goto stopped
if %1==--bot goto stopped
if %1==--debug goto stopped
goto stop

:refresher
echo ===============================================================================
echo ** Contra is restarting.
echo ** One moment please...
echo ===============================================================================
"%phpbin%" run.php %1
goto loop

:update
echo ===============================================================================
echo ** Contra is updating.
echo ** One moment please...
echo ===============================================================================
"%phpbin%" run.php %1
goto loop

:stopped
echo ===============================================================================
echo ** Contra has stopped.
goto confirm
:confirm
set /p input=** Would you like to reboot? [y/n]
if %input%==y goto continue
if %input%==Y goto continue
if %input%==n goto stop
if %input%==N goto stop
goto confirm

:continue
echo ===============================================================================
"%phpbin%" run.php %1
goto loop

:stop

@echo off
if not exist ./phpbin (
	if not exist ./run.php (
		echo =========================== WARNING: LAUNCH ERROR =============================
		echo.
		echo ** It looks as though you are trying to launch Contra from outside the bot
		echo ** folder. This does not tend to work on Windows. Please cd to the bot's
		echo ** directory before trying again, or double-click RUN-Contra.cmd to run
		echo ** the bot.
		echo.
		echo ===============================================================================
		pause
		exit
	)
)
RUN-Contra.cmd --php
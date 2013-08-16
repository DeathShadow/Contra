#!/usr/bin/env bash
if [ "`whoami`" = "root" ]; then
    echo "Don't run Contra on root user!";
    exit;
fi

if [ -n "$(readlink --version 2>/dev/null | grep "GNU")" ]; then cd "$(dirname "$(readlink -f "$0")")";
elif [ -e "$(which realpath)" ]; then cd "$(dirname "$(realpath "$0")")";
else cd "$(dirname "${BASH_SOURCE[0]}")"; fi

phpbin=$(tail -n1 "./phpbin")

if [ ! -e "$phpbin" ]; then
	echo "warning: php binary given in ./phpbin doesn't exist, searching for one."
	phpbin="$(which php)"
fi

if [ ! -e "$phpbin" ]; then
	echo "error: failed to find a php binary. maybe you don't have it installed."
else

	echo "php binary located."

	rr=2
	ss=0
	input=y

	while [[ $rr = 1 || $rr = 2  ]]; do

		if [ ! -e "$phpbin" ]; then
			phpbin=$(tail -n1 "./phpbin")
		fi

		if [ $rr = 1 ]; then
			echo "==============================================================================="
		fi
		"$phpbin" run.php $@
		rr=0
		if [ -e "./storage/bat/restart.bcd" ]; then
			echo "==============================================================================="
			echo "** Contra is restarting."
			echo "** One moment please..."
			echo "==============================================================================="
			rm -f "./storage/bat/restart.bcd"
			rm -f "./storage/lock"
			rr=2
		elif [ -e "./storage/bat/update.bcd" ]; then
			echo "==============================================================================="
			echo "** Contra is updating."
			echo "** One moment please..."
			echo "==============================================================================="
			rm -f "./storage/bat/update.bcd"
			rm -f "./storage/lock"
			rr=1
			ss=1
		elif [ -e "./storage/bat/quit.bcd" ]; then
			rm -f "./storage/bat/quit.bcd"
			rm -f "./storage/lock"
		else
			if [[ -z $1 || $1 = "--debug" || $1 = "--bot" ]]; then
				echo "==============================================================================="
				echo "** Contra has stopped."
				ss=0
				while [ $ss = 0 ]; do
					echo -n "** Would you like to reboot? [y|n] "
					read REPLY
					if [ -z "$REPLY" ]; then input=y;
					else input="$REPLY"; fi
					if [[ "$input" = "y" || "$input" = "Y" ]]; then
						rr=1
						ss=1
					fi
					if [[ "$input" = "n" || "$input" = "N" ]]; then ss=1; fi
				done
			fi
		fi

	done

fi


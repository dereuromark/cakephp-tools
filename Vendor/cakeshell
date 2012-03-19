#!/bin/bash
TERM=dumb
export TERM
cmd="cake"
while [ $# -ne 0 ]; do
	if [ "$1" = "-cli" ] || [ "$1" = "-console" ]; then
		PATH=$PATH:$2
		shift
	else
		cmd="${cmd} $1"
	fi
	shift
done
$cmd
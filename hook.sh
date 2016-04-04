#!/bin/bash

# Abort at first error
set -e

# Set paths
ME="$PWD/sql/migrator"
THEM="$PWD/sql"

echo "Starting database migration from directory $THEM"

# Get the configuration of the connection/db
export $($THEM/env)
COMMAND="mysql -u $MYSQL_USER -D $MYSQL_DB --host=$MYSQL_HOST -P $MYSQL_PORT -p$MYSQL_PWD"

# Creates a transaction from a file
transaction() {
	cat <(echo "START TRANSACTION;") $1 <(echo "COMMIT;")
}

# Create migration table if neccessary
transaction <("$ME/create.sh") | $COMMAND

# Get the last db version
LAST=$(transaction <("$ME/last.sh") | $COMMAND -ss)
if [ -z $LAST ]; then
	LAST=0
fi
echo "We are on version $LAST"

# Now, lets apply everything from our current version onwards
NEXT=$(($LAST + 1))
while [ -f "$THEM/migrations/$NEXT.sql" ]; do
	echo "Applying version $NEXT"
	transaction "$THEM/migrations/$NEXT.sql" | $COMMAND
	transaction <("$ME/apply.sh" $NEXT) | $COMMAND
	NEXT=$(($NEXT + 1))
done

# We are done
echo "All done"

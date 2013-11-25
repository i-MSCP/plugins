#!/bin/sh

# Copy Horde_iCalendar classes and dependencies to stdout.
# This will create a standalone copy of the classes requried for iCal parsing.

SRCDIR=$1

if [ ! -d "$SRCDIR" ]; then
  echo "Usage: get_horde_icalendar.sh SRCDIR"
  echo "Please enter a valid source directory of the Horde lib"
  exit 1
fi

echo "<?php

/**
 * This is a concatenated copy of the following files:
 *   Horde/String.php, Horde/iCalendar.php, Horde/iCalendar/*.php
 * Pull the latest version of these file from the PEAR channel of the Horde project at http://pear.horde.org
 */

require_once(dirname(__FILE__) . '/Horde_Date.php');"

sed 's/<?php//; s/?>//' $SRCDIR/String.php
echo "\n"
sed 's/<?php//; s/?>//' $SRCDIR/iCalendar.php | sed -E "s/include_once.+//; s/NLS::getCharset\(\)/'UTF-8'/"
echo "\n"

for fn in `ls $SRCDIR/iCalendar/*.php | grep -v 'vcard.php'`; do
	sed 's/<?php//; s/?>//' $fn | sed -E "s/(include|require)_once.+//"
done;

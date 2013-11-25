DecodeShortURLs
===============

This is a plugin for SpamAssassin.

This plugin looks for URLs shortened by a list of URL shortening services and
upon finding a matching URL will connect using to the shortening service and
do an HTTP HEAD lookup and retrieve the location header which points to the 
actual shortened URL, it then adds this URL to the list of URIs extracted by 
SpamAssassin which can then be accessed by other plug-ins, such as URIDNSBL.

This plugin also sets the rule HAS_SHORT_URL if any matching short URLs are 
found.

Regular 'uri' rules can be used to detect and score links disabled by the
shortening service for abuse and URL_BITLY_BLOCKED is supplied as an example.
It should be safe to score this rule highly on a match as experience shows
that bit.ly only blocks access to a URL if it has seen consistent abuse and
problem reports.

As of version 0.3 this plug-in will follow 'chained' shorteners e.g.

short URL -> short URL -> short URL -> real URL

If this form of chaining is found, then the rule 'SHORT_URL_CHAINED' will be
fired.  If a loop is detected then 'SHORT_URL_LOOP' will be fired.
This plug-in limits the number of chained shorteners to a maximim of 10 at 
which point it will fire the rule 'SHORT_URL_MAXCHAIN' and go no further.

If a shortener returns a '404 Not Found' result for the short URL then the 
rule 'SHORT_URL_404' will be fired.

If a shortener does not return an HTTP redirect, then a dynamic rule will
be fired: 'SHORT_\<SHORTENER\>_\<CODE\>' where \<SHORTENER\> is the uppercase
name of the shortener with dots converted to underscores.  e.g.:
'SHORT_T_CO_200' This is to handle the case of t.co which now returns an
HTTP 200 and an abuse page instead of redirecting to an abuse page like
every other shortener does...

NOTES
-----

This plugin runs the parsed_metadata hook with a priority of -1 so that
it may modify the parsed URI list prior to the URIDNSBL plugin which
runs as priority 0.

Currently the plugin queries a maximum of 10 distinct shortened URLs with
a maximum timeout of 5 seconds per lookup.  

ACKNOWLEDGEMENTS
----------------

A lot of this plugin has been hacked together by using other plugins as 
examples.  The author would particularly like to tip his hat to Karsten
Bräckelmann for the _add_uri_detail_list() function that he stole from
GUDO.pm for which this plugin would not be possible due to the SpamAssassin
API making no provision for adding to the base list of extracted URIs and 
the author not knowing enough about Perl to be able to achieve this without 
a good example from someone that does ;-)

## i-MSCP Mailman plugin v0.0.1

Plugin allowing to manage mailing-lists through i-MSCP using Mailman.

### Requirements

 i-MSCP versions >= 1.1.11 (plugin API >= 0.2.10)
 Mailman

Note It's assumed that you are using Mailman as provided by the Debian/Ubuntu mailman package.

### Installation

**1.** Install needed Debian/Ubuntu package if not already done

	# aptitude update && aptitude install mailman

**2.** Create mailman site list if not already done

	# newlist mailman

This is really needed. Without this list, mailman will refuse to start.

**3.** Restart mailman

	# service mailman restart

**5.** Plugin upload and installation

	- Login into the panel as admin and go to the plugin management interface
	- Upload the Mailman plugin archive
	- Activate the plugin

### UPDATE

**1.** Plugin upload and update

	- Login into the panel as admin and go to the plugin management interface
	- Upload the Mailman plugin archive
	- Activate the plugin

### KNOWN BUGS

 - [Debian Related - wrong permissions, causes archiving to fail](http://bugs.debian.org/cgi-bin/bugreport.cgi?bug=603904 "Wrong permissions, causes archiving to fail")

### License

The files in this archive are released under the **GNU LESSER GENERAL PUBLIC LICENSE**. You can find a copy of this
license in **[LICENSE.txt](LICENSE.txt)**.

### Sponsors

Development of this plugin has been sponsored by

 [Retail Service Management](http://www.retailservicesystems.com "Retail Service Management")
 [IP-Projects GmbH & Co. KG](https://www.ip-projects.de/ "IP-Projects GmbH & Co. KG")

### Author

Laurent Declercq <l.declercq@nuxwin.com>

**Thank you for using this plugin.**

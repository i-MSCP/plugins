## i-MSCP Mailman plugin v0.0.3

Plugin allowing to manage mailing-lists through i-MSCP using Mailman.

### Requirements

 - i-MSCP versions >= 1.1.11 (plugin API >= 0.2.10)
 - Mailman (as provided by debian/ubuntu packages)

### Installation

**1.** Install needed Debian/Ubuntu package

	# aptitude update && aptitude install mailman

**2.** Create the mailman site list

	# newlist mailman

This is really needed. Without this list, mailman will refuse to start.

**3.** Restart mailman

	# service mailman restart

**4.** Plugin upload and installation

 - Download the Mailman plugin archive through the plugin store
 - Login into the panel as admin and go to the plugin management interface
 - Upload the Mailman plugin archive
 - Install the plugin

### Update

 - Download the Mailman plugin archive through the plugin store
 - Login into the panel as admin and go to the plugin management interface
 - Upload the Mailman plugin archive

### Known bugs

 - [Debian Related - wrong permissions, causes archiving to fail](http://bugs.debian.org/cgi-bin/bugreport.cgi?bug=603904 "Wrong permissions, causes archiving to fail")

### License

The files in this archive are released under the **GNU LESSER GENERAL PUBLIC LICENSE**. You can find a copy of this
license in **[LICENSE.txt](LICENSE.txt)**.

### Sponsors

The development of this plugin has been sponsored by:

 - [IP-Projects GmbH & Co. KG](https://www.ip-projects.de/ "IP-Projects GmbH & Co. KG")
 - [Retail Service Management](http://www.retailservicesystems.com "Retail Service Management")

### Author

Laurent Declercq <l.declercq@nuxwin.com>

**Thank you for using this plugin.**

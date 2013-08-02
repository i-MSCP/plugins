#!/usr/bin/perl

use strict;
use warnings;

use lib "{IMSCP_PERLLIB_PATH}";

use iMSCP::Debug;
use iMSCP::Boot;

$ENV{'LC_MESSAGES'} = 'C';

umask(027);

newDebug('mailgraph-plugin-cronjob.log');

silent(1);

iMSCP::Boot->getInstance()->boot({ 'nolock' => 'yes', 'config_readonly' => 'yes' });

my $pluginFile = "$main::imscpConfig{'ENGINE_ROOT_DIR'}/Plugins/Mailgraph.pm";
my $rs = 0;

eval { require $pluginFile; };

if($@) {
	error($@);
	$rs = 1;
} else {
	my $pluginClass = "Plugin::Mailgraph";
	my $pluginInstance = $pluginClass->getInstance();

	$rs = $pluginInstance->run();
}

exit $rs;

=head1 NAME

 Plugin::Mailgraph

=cut

# i-MSCP Mailgraph plugin
# Copyright (C) 2013-2016 Laurent Declercq <l.declercq@nuxwin.com>
# Copyright (C) 2010-2016 Sascha Bay <info@space2place.de>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.


package Plugin::Mailgraph;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::File;
use iMSCP::ProgramFinder;
use RRDs;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part of the i-MSCP Mailgraph plugin.

=head1 PUBLIC METHODS

=over 4

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	unless(iMSCP::ProgramFinder::find('mailgraph')) {
		error('Unable to find mailgraph daemon. Please, install the mailgraph package first.');
		return 1;
	}

	0;
}

=item enable()

 Process enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = shift;

	my $rs = $self->_registerCronjob();
	return $rs if $rs;

	$self->buildGraphs();
}

=item disable()

 Process disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = shift;

	$self->_unregisterCronjob();
}

=item buildGraphs()

 Build statistical graphs using the last available statistics data

 Return int 0 on success, other on failure

=cut

sub buildGraphs
{
	my $self = shift;

	my $rs = $self->_buildMailgraph();
	return $rs if $rs;

	$rs = $self->_buildMailgraphVirus();
	return $rs if $rs;

	$self->_buildMailgraphGreylist();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _buildMailgraph()

 Build mailgraph

 Return int 0 on success, other on failure

=cut

sub _buildMailgraph
{
	my $self = shift;

	my $mailgraphRRD = '/var/lib/mailgraph/mailgraph.rrd';

	return 0 if ! -f $mailgraphRRD;

	my $imgGraphsDir = $main::imscpConfig{'PLUGINS_DIR'} . '/Mailgraph/tmp_graph';
	my $hostname = $main::imscpConfig{'SERVER_HOSTNAME'};

	my $xPoints = 540;
	my $pointsPerSample = 3;
	my $yPoints = 160;

	my $dayRange = 3600*24*1;
	my $dayStep = $dayRange*$pointsPerSample/$xPoints;
	my $dayMailgraphTitle = 'Mailgraph - Daily - ' . $hostname;
	my $dayOutputfile = $imgGraphsDir . '/mailgraph_day.png';

	my $weekRange = 3600*24*7;
	my $weekStep = $weekRange*$pointsPerSample/$xPoints;
	my $weekMailgraphTitle = 'Mailgraph - Weekly - ' . $hostname;
	my $weekOutputfile = $imgGraphsDir . '/mailgraph_week.png';

	my $monthRange = 3600*24*30;
	my $monthStep = $monthRange*$pointsPerSample/$xPoints;
	my $monthMailgraphTitle = 'Mailgraph - Monthly - ' . $hostname;
	my $monthOutputfile = $imgGraphsDir . '//mailgraph_month.png';

	my $yearRange = 3600*24*365;
	my $yearStep = $yearRange*$pointsPerSample/$xPoints;
	my $yearMailgraphTitle = 'Mailgraph - Yearly - ' . $hostname;
	my $yearOutputfile = $imgGraphsDir . '/mailgraph_year.png';

	my $endrange  = time; $endrange -= $endrange % $dayStep;
	my $date = localtime(time);
	$date =~ s|:|\\:|g unless $RRDs::VERSION < 1.199908;

	my $rs = $self->_createMailgraphPicture(
		$mailgraphRRD, $xPoints, $yPoints, $dayRange, $endrange, $dayStep, $dayMailgraphTitle, $dayOutputfile, $date
	);
	return $rs if $rs;

	$rs = $self->_createMailgraphPicture(
		$mailgraphRRD, $xPoints, $yPoints, $weekRange, $endrange, $weekStep, $weekMailgraphTitle, $weekOutputfile, $date
	);
	return $rs if $rs;

	$rs = $self->_createMailgraphPicture(
		$mailgraphRRD, $xPoints, $yPoints, $monthRange, $endrange, $monthStep, $monthMailgraphTitle, $monthOutputfile,
		$date
	);
	return $rs if $rs;

	$self->_createMailgraphPicture(
		$mailgraphRRD, $xPoints, $yPoints, $yearRange, $endrange, $yearStep, $yearMailgraphTitle, $yearOutputfile, $date
	);
}

=item _createMailgraphPicture()

 Creates the mailgraph picture

 Return int 0 on success, other on failure

=cut

sub _createMailgraphPicture
{
	my (
		$self, $rrdfile, $setXpoints, $setYpoints, $setRange, $setEndrange, $setStep, $setTitle, $setOutputfile,
		$setDate
	) = @_;

	my %mailgraphColor = (sent => '000099', received => '009900');

	my @RRDArgs = (
		"DEF:sent=$rrdfile:sent:AVERAGE",
		"DEF:msent=$rrdfile:sent:MAX",
		"CDEF:rsent=sent,60,*",
		"CDEF:rmsent=msent,60,*",
		"CDEF:dsent=sent,UN,0,sent,IF,$setStep,*",
		"CDEF:ssent=PREV,UN,dsent,PREV,IF,dsent,+",
		"AREA:rsent#$mailgraphColor{sent}:Sent    ",
		'GPRINT:ssent:MAX:total\: %8.0lf msgs',
		'GPRINT:rsent:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmsent:MAX:max\: %4.0lf msgs/min\l',

		"DEF:recv=$rrdfile:recv:AVERAGE",
		"DEF:mrecv=$rrdfile:recv:MAX",
		"CDEF:rrecv=recv,60,*",
		"CDEF:rmrecv=mrecv,60,*",
		"CDEF:drecv=recv,UN,0,recv,IF,$setStep,*",
		"CDEF:srecv=PREV,UN,drecv,PREV,IF,drecv,+",
		"LINE2:rrecv#$mailgraphColor{received}:Received",
		'GPRINT:srecv:MAX:total\: %8.0lf msgs',
		'GPRINT:rrecv:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmrecv:MAX:max\: %4.0lf msgs/min\l',
	);

	RRDs::graph(
		$setOutputfile,
		'--imgformat', 'PNG',
		'--title', $setTitle,
		'--width', $setXpoints,
		'--height', $setYpoints,
		'--start', "-$setRange",
		'--end', $setEndrange,
		'--vertical-label', 'msgs/min',
		'--lower-limit', 0,
		'--units-exponent', 0,
		'--lazy',
		'--color', 'SHADEA#ffffff',
		'--color', 'SHADEB#ffffff',
		'--color', 'BACK#ffffff',
		$RRDs::VERSION < 1.2002 ? () : ( '--slope-mode'),
		@RRDArgs,
		'COMMENT: Last updated\:[' . $setDate . ']\r',
	);

	my $errorMsg = RRDs::error;
	error($errorMsg) if $errorMsg;
	return 1 if $errorMsg;

	my $file = iMSCP::File->new( filename => $setOutputfile );

	my $panelUname =
	my $panelGName =
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	my $rs = $file->owner($panelUname, $panelGName);
	return $rs if $rs;

	$file->mode(0644);
}

=item _buildMailgraphVirus()

 Build mailgraph for viruses

 Return int 0 on success, other on failure

=cut

sub _buildMailgraphVirus
{
	my $self = shift;

	my $mailgraphRRD = '/var/lib/mailgraph/mailgraph.rrd';
	my $mailgraphVirusRRD = '/var/lib/mailgraph/mailgraph_virus.rrd';

	return 0 if ! -f $mailgraphRRD || ! -f $mailgraphVirusRRD;

	my $imgGraphsDir = $main::imscpConfig{'PLUGINS_DIR'} . '/Mailgraph/tmp_graph';
	my $hostname = $main::imscpConfig{'SERVER_HOSTNAME'};

	my $xPoints = 540;
	my $pointsPerSample = 3;
	my $yPoints = 96;

	my $dayRange = 3600*24*1;
	my $dayStep = $dayRange*$pointsPerSample/$xPoints;
	my $dayMailgraphTitle = 'Mailgraph virus - Daily - ' . $hostname;
	my $dayOutputfile = $imgGraphsDir . '/mailgraph_virus_day.png';

	my $weekRange = 3600*24*7;
	my $weekStep = $weekRange*$pointsPerSample/$xPoints;
	my $weekMailgraphTitle = 'Mailgraph virus - Weekly - ' . $hostname;
	my $weekOutputfile = $imgGraphsDir . '/mailgraph_virus_week.png';

	my $monthRange = 3600*24*30;
	my $monthStep = $monthRange*$pointsPerSample/$xPoints;
	my $monthMailgraphTitle = 'Mailgraph virus - Monthly - ' . $hostname;
	my $monthOutputfile = $imgGraphsDir . '/mailgraph_virus_month.png';

	my $yearRange = 3600*24*365;
	my $yearStep = $yearRange*$pointsPerSample/$xPoints;
	my $yearMailgraphTitle = 'Mailgraph virus - Yearly - ' . $hostname;
	my $yearOutputfile = $imgGraphsDir .'/mailgraph_virus_year.png';

	my $endrange  = time; $endrange -= $endrange % $dayStep;
	my $date = localtime(time);
	$date =~ s|:|\\:|g unless $RRDs::VERSION < 1.199908;

	my $rs = $self->_createMailgraphVirusPicture(
		$mailgraphRRD, $mailgraphVirusRRD, $xPoints, $yPoints, $dayRange, $endrange, $dayStep, $dayMailgraphTitle,
		$dayOutputfile, $date
	);
	return $rs if $rs;

	$rs = $self->_createMailgraphVirusPicture(
		$mailgraphRRD, $mailgraphVirusRRD, $xPoints, $yPoints, $weekRange, $endrange, $weekStep, $weekMailgraphTitle,
		$weekOutputfile, $date
	);
	return $rs if $rs;

	$rs = $self->_createMailgraphVirusPicture(
		$mailgraphRRD, $mailgraphVirusRRD, $xPoints, $yPoints, $monthRange, $endrange, $monthStep, $monthMailgraphTitle,
		$monthOutputfile,$date
	);
	return $rs if $rs;

	$self->_createMailgraphVirusPicture(
		$mailgraphRRD, $mailgraphVirusRRD, $xPoints, $yPoints, $yearRange, $endrange, $yearStep, $yearMailgraphTitle,
		$yearOutputfile, $date
	);
}

=item _createMailgraphVirusPicture()

 Creates the mailgraph virus picture

 Return int 0 on success, other on failure

=cut

sub _createMailgraphVirusPicture
{
	my (
		$self, $rrdfile, $rrdvirusfile, $setXpoints, $setYpoints, $setRange, $setEndrange, $setStep, $setTitle,
		$setOutputfile, $setDate
	) = @_;

	my %mailgraphColor = (rejected => 'AA0000', bounced => '000000', virus => 'DDBB00', spam => '999999');

	my @RRDArgs = (
		"DEF:rejected=$rrdfile:rejected:AVERAGE",
		"DEF:mrejected=$rrdfile:rejected:MAX",
		"CDEF:rrejected=rejected,60,*",
		"CDEF:drejected=rejected,UN,0,rejected,IF,$setStep,*",
		"CDEF:srejected=PREV,UN,drejected,PREV,IF,drejected,+",
		"CDEF:rmrejected=mrejected,60,*",
		"LINE2:rrejected#$mailgraphColor{rejected}:Rejected",
		'GPRINT:srejected:MAX:total\: %8.0lf msgs',
		'GPRINT:rrejected:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmrejected:MAX:max\: %4.0lf msgs/min\l',

		"DEF:bounced=$rrdfile:bounced:AVERAGE",
		"DEF:mbounced=$rrdfile:bounced:MAX",
		"CDEF:rbounced=bounced,60,*",
		"CDEF:dbounced=bounced,UN,0,bounced,IF,$setStep,*",
		"CDEF:sbounced=PREV,UN,dbounced,PREV,IF,dbounced,+",
		"CDEF:rmbounced=mbounced,60,*",
		"AREA:rbounced#$mailgraphColor{bounced}:Bounced ",
		'GPRINT:sbounced:MAX:total\: %8.0lf msgs',
		'GPRINT:rbounced:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmbounced:MAX:max\: %4.0lf msgs/min\l',

		"DEF:virus=$rrdvirusfile:virus:AVERAGE",
		"DEF:mvirus=$rrdvirusfile:virus:MAX",
		"CDEF:rvirus=virus,60,*",
		"CDEF:dvirus=virus,UN,0,virus,IF,$setStep,*",
		"CDEF:svirus=PREV,UN,dvirus,PREV,IF,dvirus,+",
		"CDEF:rmvirus=mvirus,60,*",
		"AREA:rvirus#$mailgraphColor{virus}:Viruses ",
		'GPRINT:svirus:MAX:total\: %8.0lf msgs',
		'GPRINT:rvirus:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmvirus:MAX:max\: %4.0lf msgs/min\l',

		"DEF:spam=$rrdvirusfile:spam:AVERAGE",
		"DEF:mspam=$rrdvirusfile:spam:MAX",
		"CDEF:rspam=spam,60,*",
		"CDEF:dspam=spam,UN,0,spam,IF,$setStep,*",
		"CDEF:sspam=PREV,UN,dspam,PREV,IF,dspam,+",
		"CDEF:rmspam=mspam,60,*",
		"AREA:rspam#$mailgraphColor{spam}:Spam    ",
		'GPRINT:sspam:MAX:total\: %8.0lf msgs',
		'GPRINT:rspam:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmspam:MAX:max\: %4.0lf msgs/min\l',
	);

	RRDs::graph(
		$setOutputfile,
		'--imgformat', 'PNG',
		'--title', $setTitle,
		'--width', $setXpoints,
		'--height', $setYpoints,
		'--start', "-$setRange",
		'--end', $setEndrange,
		'--vertical-label', 'msgs/min',
		'--lower-limit', 0,
		'--units-exponent', 0,
		'--lazy',
		'--color', 'SHADEA#ffffff',
		'--color', 'SHADEB#ffffff',
		'--color', 'BACK#ffffff',
		$RRDs::VERSION < 1.2002 ? () : ( '--slope-mode'),
		@RRDArgs,
		'COMMENT: Last updated\:[' . $setDate . ']\r',
	);

	my $errorMsg = RRDs::error;
	error($errorMsg) if $errorMsg;
	return 1 if $errorMsg;

	my $file = iMSCP::File->new('filename' => $setOutputfile);

	my $panelUname =
	my $panelGName =
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	my $rs = $file->owner($panelUname, $panelGName);
	return $rs if $rs;

	$file->mode(0644);
}

=item _buildMailgraphGreylist()

 Build mailgraph for greylist

 Return int 0 on success, other on failure

=cut

sub _buildMailgraphGreylist
{
	my $self = shift;

	my $mailgraphRRD = '/var/lib/mailgraph/mailgraph_greylist.rrd';

	return 0 if ! -f $mailgraphRRD;

	my $imgGraphsDir = $main::imscpConfig{'PLUGINS_DIR'} . '/Mailgraph/tmp_graph';
	my $hostname = $main::imscpConfig{'SERVER_HOSTNAME'};

	my $xPoints = 540;
	my $pointsPerSample = 3;
	my $yPoints = 96;

	my $dayRange = 3600*24*1;
	my $dayStep = $dayRange*$pointsPerSample/$xPoints;
	my $dayMailgraphTitle = 'Mailgraph greylist - Daily - ' . $hostname;
	my $dayOutputfile = $imgGraphsDir . '/mailgraph_greylist_day.png';

	my $weekRange = 3600*24*7;
	my $weekStep = $weekRange*$pointsPerSample/$xPoints;
	my $weekMailgraphTitle = 'Mailgraph greylist - Weekly - ' . $hostname;
	my $weekOutputfile = $imgGraphsDir . '/mailgraph_greylist_week.png';

	my $monthRange = 3600*24*30;
	my $monthStep = $monthRange*$pointsPerSample/$xPoints;
	my $monthMailgraphTitle = 'Mailgraph greylist - Monthly - ' . $hostname;
	my $monthOutputfile = $imgGraphsDir . '/mailgraph_greylist_month.png';

	my $yearRange = 3600*24*365;
	my $yearStep = $yearRange*$pointsPerSample/$xPoints;
	my $yearMailgraphTitle = 'Mailgraph greylist - Yearly - ' . $hostname;
	my $yearOutputfile = $imgGraphsDir . '/mailgraph_greylist_year.png';

	my $endrange  = time; $endrange -= $endrange % $dayStep;
	my $date = localtime(time);
	$date =~ s|:|\\:|g unless $RRDs::VERSION < 1.199908;

	my $rs = $self->_createMailgraphGreylistPicture(
		$mailgraphRRD, $xPoints, $yPoints, $dayRange, $endrange, $dayStep, $dayMailgraphTitle, $dayOutputfile, $date
	);
	return $rs if $rs;

	$rs = $self->_createMailgraphGreylistPicture(
		$mailgraphRRD, $xPoints, $yPoints, $weekRange, $endrange, $weekStep, $weekMailgraphTitle, $weekOutputfile, $date
	);
	return $rs if $rs;

	$rs = $self->_createMailgraphGreylistPicture(
		$mailgraphRRD, $xPoints, $yPoints, $monthRange, $endrange, $monthStep, $monthMailgraphTitle, $monthOutputfile,
		$date
	);
	return $rs if $rs;

	$self->_createMailgraphGreylistPicture(
		$mailgraphRRD, $xPoints, $yPoints, $yearRange, $endrange, $yearStep, $yearMailgraphTitle, $yearOutputfile,
		$date
	);
}

=item _createMailgraphGreylistPicture()

 Creates the mailgraph greylist picture

 Return int 0 on success, other on failure

=cut

sub _createMailgraphGreylistPicture
{
	my (
		$self, $rrdfile, $setXpoints, $setYpoints, $setRange, $setEndrange, $setStep, $setTitle, $setOutputfile,
		$setDate
	) = @_;

	my %mailgraphColor = (greylisted => '999999', delayed => '006400');

	my @RRDArgs = (
		"DEF:greylisted=$rrdfile:greylisted:AVERAGE",
		"DEF:mgreylisted=$rrdfile:greylisted:MAX",
		"CDEF:rgreylisted=greylisted,60,*",
		"CDEF:dgreylisted=greylisted,UN,0,greylisted,IF,$setStep,*",
		"CDEF:sgreylisted=PREV,UN,dgreylisted,PREV,IF,dgreylisted,+",
		"CDEF:rmgreylisted=mgreylisted,60,*",
		"AREA:rgreylisted#$mailgraphColor{greylisted}:Greylisted",
		'GPRINT:sgreylisted:MAX:total\: %8.0lf msgs',
		'GPRINT:rgreylisted:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmgreylisted:MAX:max\: %4.0lf msgs/min\l',

		"DEF:delayed=$rrdfile:delayed:AVERAGE",
		"DEF:mdelayed=$rrdfile:delayed:MAX",
		"CDEF:rdelayed=delayed,60,*",
		"CDEF:ddelayed=delayed,UN,0,delayed,IF,$setStep,*",
		"CDEF:sdelayed=PREV,UN,ddelayed,PREV,IF,ddelayed,+",
		"CDEF:rmdelayed=mdelayed,60,*",
		"LINE2:rdelayed#$mailgraphColor{delayed}:Delayed   ",
		'GPRINT:sdelayed:MAX:total\: %8.0lf msgs',
		'GPRINT:rdelayed:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmdelayed:MAX:max\: %4.0lf msgs/min\l',
	);

	RRDs::graph(
		$setOutputfile,
		'--imgformat', 'PNG',
		'--title', $setTitle,
		'--width', $setXpoints,
		'--height', $setYpoints,
		'--start', "-$setRange",
		'--end', $setEndrange,
		'--vertical-label', 'msgs/min',
		'--lower-limit', 0,
		'--units-exponent', 0,
		'--lazy',
		'--color', 'SHADEA#ffffff',
		'--color', 'SHADEB#ffffff',
		'--color', 'BACK#ffffff',
		$RRDs::VERSION < 1.2002 ? () : ( '--slope-mode'),
		@RRDArgs,
		'COMMENT: Last updated\:[' . $setDate . ']\r',
	);

	my $errorMsg = RRDs::error;
	error($errorMsg) if $errorMsg;
	return 1 if ($errorMsg);

	my $file = iMSCP::File->new( filename => $setOutputfile );

	my $panelUname =
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	my $rs = $file->owner($panelUname, $panelGName);
	return $rs if $rs;

	$file->mode(0644);
}

=item _registerCronjob()

 Register cronjob

 Return int 0 on success, other on failure

=cut

sub _registerCronjob
{
	my $self = shift;

	if($self->{'config'}->{'cronjob_enabled'}) {
		my $cronjobFilePath = $main::imscpConfig{'PLUGINS_DIR'} . '/Mailgraph/cronjob.pl';

		my $cronjobFile = iMSCP::File->new( filename => $cronjobFilePath );
		my $cronjobFileContent = $cronjobFile->get();
		unless(defined $cronjobFileContent) {
			error("Unable to read $cronjobFile");
			return 1;
		}

		require iMSCP::TemplateParser;
		iMSCP::TemplateParser->import();

		$cronjobFileContent = process(
			{ 'IMSCP_PERLLIB_PATH' => $main::imscpConfig{'ENGINE_ROOT_DIR'} . '/PerlLib' }, $cronjobFileContent
		);

		my $rs = $cronjobFile->set($cronjobFileContent);
		return $rs if $rs;

		$rs = $cronjobFile->save();
		return $rs if $rs;

		require Servers::cron;
		Servers::cron->factory()->addTask(
			{
				'TASKID' => 'PLUGINS:Mailgraph',
				'MINUTE' => $self->{'config'}->{'cronjob_config'}->{'minute'},
				'HOUR' => $self->{'config'}->{'cronjob_config'}->{'hour'},
				'DAY' => $self->{'config'}->{'cronjob_config'}->{'day'},
				'MONTH' => $self->{'config'}->{'cronjob_config'}->{'month'},
				'DWEEK' => $self->{'config'}->{'cronjob_config'}->{'dweek'},
				'COMMAND' => "umask 027; perl $cronjobFilePath >/dev/null 2>&1"
			}
		);
	} else {
		0;
	}
}

=item _unregisterCronjob()

 Unregister cronjob

 Return int 0 on success, other on failure

=cut

sub _unregisterCronjob
{
	require Servers::cron;

	Servers::cron->factory()->deleteTask({ 'TASKID' => 'PLUGINS:Mailgraph' });
}

=back

=head1 AUTHORS

 Sascha Bay <info@space2place.de>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__

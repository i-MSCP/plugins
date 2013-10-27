#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
#
# @category    i-MSCP
# @package     iMSCP_Plugin
# @subpackage  Mailgraph
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Sascha Bay <info@space2place.de>
# @contributor Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Plugin::Mailgraph;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::File;
use RRDs;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP Mailgraph plugin.

=head1 PUBLIC METHODS

=over 4

=item install()

 Perform install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	if(! -x '/usr/sbin/mailgraph') {
		error('Unable to find mailgraph daemon. Please, install the mailgraph package first.');
		return 1;
	}

	my $rs = $self->_registerCronjob();
	return $rs if $rs;

	$self->run();
}

=item change()

 Perform change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
	my $self = shift;

	my $rs = $self->_registerCronjob();
	return $rs if $rs;

	$self->run();
}

=item update()

 Perform update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	my $self = shift;

	my $rs = $self->_unregisterCronjob();
	return $rs if $rs;

	$rs = $self->_registerCronjob();
	return $rs if $rs;

	$self->run();
}

=item enable()

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = shift;

	my $rs = $self->_registerCronjob();
	return $rs if $rs;

	$self->run();
}

=item disable()

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = shift;

	$self->uninstall();
}

=item uninstall()

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	$self->_unregisterCronjob();
}

=item run()

 Create statistical graphics using the last available statistics data

 Return int 0 on success, other on failure

=cut

sub run
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

=item _init()

 Initialize plugin

 Return Plugin::Mailgraph

=cut

sub _init
{
	my $self = shift;

	# Force return value from plugin module
	$self->{'FORCE_RETVAL'} = 'yes';

	$self;
}

=item _buildMailgraph()

 Build mailgraph

 Return int 0 on success, other on failure

=cut

sub _buildMailgraph
{
	my $self = shift;

	my $mailgraph_rrd = '/var/lib/mailgraph/mailgraph.rrd';

	return 0 if ! -f $mailgraph_rrd;

	my $imgGraphsDir = $main::imscpConfig{'GUI_ROOT_DIR'} . '/plugins/Mailgraph/tmp_graph';
	my $hostname = $main::imscpConfig{'SERVER_HOSTNAME'};

	my $xpoints = 540;
	my $points_per_sample = 3;
	my $ypoints = 160;

	my $day_range = 3600*24*1;
	my $day_step = $day_range*$points_per_sample/$xpoints;
	my $day_mailgraph_title = 'Mailgraph - Daily - ' . $hostname;
	my $day_outputfile = $imgGraphsDir . '/mailgraph_day.png';

	my $week_range = 3600*24*7;
	my $week_step = $week_range*$points_per_sample/$xpoints;
	my $week_mailgraph_title = 'Mailgraph - Weekly - ' . $hostname;
	my $week_outputfile = $imgGraphsDir . '/mailgraph_week.png';

	my $month_range = 3600*24*30;
	my $month_step = $month_range*$points_per_sample/$xpoints;
	my $month_mailgraph_title = 'Mailgraph - Monthly - ' . $hostname;
	my $month_outputfile = $imgGraphsDir . '//mailgraph_month.png';

	my $year_range = 3600*24*365;
	my $year_step = $year_range*$points_per_sample/$xpoints;
	my $year_mailgraph_title = 'Mailgraph - Yearly - ' . $hostname;
	my $year_outputfile = $imgGraphsDir . '/mailgraph_year.png';

	my $endrange  = time; $endrange -= $endrange % $day_step;
	my $date = localtime(time);
	$date =~ s|:|\\:|g unless $RRDs::VERSION < 1.199908;

	my $rs = $self->_createMailgraphPicture(
		$mailgraph_rrd,
		$xpoints,
		$ypoints,
		$day_range,
		$endrange,
		$day_step,
		$day_mailgraph_title,
		$day_outputfile,
		$date
	);
	return $rs if $rs;

	$rs = $self->_createMailgraphPicture(
		$mailgraph_rrd,
		$xpoints,
		$ypoints,
		$week_range,
		$endrange,
		$week_step,
		$week_mailgraph_title,
		$week_outputfile,
		$date
	);
	return $rs if $rs;

	$rs = $self->_createMailgraphPicture(
		$mailgraph_rrd,
		$xpoints,
		$ypoints,
		$month_range,
		$endrange,
		$month_step,
		$month_mailgraph_title,
		$month_outputfile,
		$date
	);
	return $rs if $rs;

	$self->_createMailgraphPicture(
		$mailgraph_rrd,
		$xpoints,
		$ypoints,
		$year_range,
		$endrange,
		$year_step,
		$year_mailgraph_title,
		$year_outputfile,
		$date
	);
}

=item _createMailgraphPicture()

 Creates the mailgraph picture

 Return int 0 on success, other on failure

=cut

sub _createMailgraphPicture
{
	my $self = shift;

	my $rrdfile = shift;
	my $set_xpoints = shift;
	my $set_ypoints = shift;
	my $set_range = shift;
	my $set_endrange = shift;
	my $set_step = shift;
	my $set_title = shift;
	my $set_outputfile = shift;
	my $set_date = shift;

	my %mailgraphColor = (
		sent => '000099',
		received => '009900'
	);

	my @RRDArgs = (
		"DEF:sent=$rrdfile:sent:AVERAGE",
		"DEF:msent=$rrdfile:sent:MAX",
		"CDEF:rsent=sent,60,*",
		"CDEF:rmsent=msent,60,*",
		"CDEF:dsent=sent,UN,0,sent,IF,$set_step,*",
		"CDEF:ssent=PREV,UN,dsent,PREV,IF,dsent,+",
		"AREA:rsent#$mailgraphColor{sent}:Sent    ",
		'GPRINT:ssent:MAX:total\: %8.0lf msgs',
		'GPRINT:rsent:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmsent:MAX:max\: %4.0lf msgs/min\l',

		"DEF:recv=$rrdfile:recv:AVERAGE",
		"DEF:mrecv=$rrdfile:recv:MAX",
		"CDEF:rrecv=recv,60,*",
		"CDEF:rmrecv=mrecv,60,*",
		"CDEF:drecv=recv,UN,0,recv,IF,$set_step,*",
		"CDEF:srecv=PREV,UN,drecv,PREV,IF,drecv,+",
		"LINE2:rrecv#$mailgraphColor{received}:Received",
		'GPRINT:srecv:MAX:total\: %8.0lf msgs',
		'GPRINT:rrecv:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmrecv:MAX:max\: %4.0lf msgs/min\l',
	);

	RRDs::graph($set_outputfile,
		'--imgformat', 'PNG',
		'--title', $set_title,
		'--width', $set_xpoints,
		'--height', $set_ypoints,
		'--start', "-$set_range",
		'--end', $set_endrange,
		'--vertical-label', 'msgs/min',
		'--lower-limit', 0,
		'--units-exponent', 0,
		'--lazy',
		'--color', 'SHADEA#ffffff',
		'--color', 'SHADEB#ffffff',
		'--color', 'BACK#ffffff',
		$RRDs::VERSION < 1.2002 ? () : ( '--slope-mode'),
		@RRDArgs,
		'COMMENT: Last updated\:['.$set_date.']\r',
	);

	my $errorMsg = RRDs::error;
	error($errorMsg) if $errorMsg;
	return 1 if $errorMsg;

	my $file = iMSCP::File->new('filename' => $set_outputfile);

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

	my $mailgraph_rrd = '/var/lib/mailgraph/mailgraph.rrd';
	my $mailgraph_virus_rrd = '/var/lib/mailgraph/mailgraph_virus.rrd';

	return 0 if ! -f $mailgraph_rrd || ! -f $mailgraph_virus_rrd;

	my $imgGraphsDir = $main::imscpConfig{'GUI_ROOT_DIR'} . '/plugins/Mailgraph/tmp_graph';
	my $hostname = $main::imscpConfig{'SERVER_HOSTNAME'};

	my $xpoints = 540;
	my $points_per_sample = 3;
	my $ypoints = 96;

	my $day_range = 3600*24*1;
	my $day_step = $day_range*$points_per_sample/$xpoints;
	my $day_mailgraph_title = 'Mailgraph virus - Daily - ' . $hostname;
	my $day_outputfile = $imgGraphsDir . '/mailgraph_virus_day.png';

	my $week_range = 3600*24*7;
	my $week_step = $week_range*$points_per_sample/$xpoints;
	my $week_mailgraph_title = 'Mailgraph virus - Weekly - ' . $hostname;
	my $week_outputfile = $imgGraphsDir . '/mailgraph_virus_week.png';

	my $month_range = 3600*24*30;
	my $month_step = $month_range*$points_per_sample/$xpoints;
	my $month_mailgraph_title = 'Mailgraph virus - Monthly - ' . $hostname;
	my $month_outputfile = $imgGraphsDir . '/mailgraph_virus_month.png';

	my $year_range = 3600*24*365;
	my $year_step = $year_range*$points_per_sample/$xpoints;
	my $year_mailgraph_title = 'Mailgraph virus - Yearly - ' . $hostname;
	my $year_outputfile = $imgGraphsDir .'/mailgraph_virus_year.png';

	my $endrange  = time; $endrange -= $endrange % $day_step;
	my $date = localtime(time);
	$date =~ s|:|\\:|g unless $RRDs::VERSION < 1.199908;

	my $rs = $self->_createMailgraphVirusPicture(
		$mailgraph_rrd,
		$mailgraph_virus_rrd,
		$xpoints,
		$ypoints,
		$day_range,
		$endrange,
		$day_step,
		$day_mailgraph_title,
		$day_outputfile,
		$date
	);
	return $rs if $rs;

	$rs = $self->_createMailgraphVirusPicture(
		$mailgraph_rrd,
		$mailgraph_virus_rrd,
		$xpoints,
		$ypoints,
		$week_range,
		$endrange,
		$week_step,
		$week_mailgraph_title,
		$week_outputfile,
		$date
	);
	return $rs if $rs;

	$rs = $self->_createMailgraphVirusPicture(
		$mailgraph_rrd,
		$mailgraph_virus_rrd,
		$xpoints,
		$ypoints,
		$month_range,
		$endrange,
		$month_step,
		$month_mailgraph_title,
		$month_outputfile,
		$date
	);
	return $rs if $rs;

	$self->_createMailgraphVirusPicture(
		$mailgraph_rrd,
		$mailgraph_virus_rrd,
		$xpoints,
		$ypoints,
		$year_range,
		$endrange,
		$year_step,
		$year_mailgraph_title,
		$year_outputfile,
		$date
	);
}

=item _createMailgraphVirusPicture()

 Creates the mailgraph virus picture

 Return int 0 on success, other on failure

=cut

sub _createMailgraphVirusPicture
{
	my $self = shift;

	my $rrdfile = shift;
	my $rrdvirusfile = shift;
	my $set_xpoints = shift;
	my $set_ypoints = shift;
	my $set_range = shift;
	my $set_endrange = shift;
	my $set_step = shift;
	my $set_title = shift;
	my $set_outputfile = shift;
	my $set_date = shift;

	my %mailgraphColor = (
		rejected => 'AA0000',
		bounced => '000000',
		virus => 'DDBB00',
		spam => '999999'
	);

	my @RRDArgs = (
		"DEF:rejected=$rrdfile:rejected:AVERAGE",
		"DEF:mrejected=$rrdfile:rejected:MAX",
		"CDEF:rrejected=rejected,60,*",
		"CDEF:drejected=rejected,UN,0,rejected,IF,$set_step,*",
		"CDEF:srejected=PREV,UN,drejected,PREV,IF,drejected,+",
		"CDEF:rmrejected=mrejected,60,*",
		"LINE2:rrejected#$mailgraphColor{rejected}:Rejected",
		'GPRINT:srejected:MAX:total\: %8.0lf msgs',
		'GPRINT:rrejected:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmrejected:MAX:max\: %4.0lf msgs/min\l',

		"DEF:bounced=$rrdfile:bounced:AVERAGE",
		"DEF:mbounced=$rrdfile:bounced:MAX",
		"CDEF:rbounced=bounced,60,*",
		"CDEF:dbounced=bounced,UN,0,bounced,IF,$set_step,*",
		"CDEF:sbounced=PREV,UN,dbounced,PREV,IF,dbounced,+",
		"CDEF:rmbounced=mbounced,60,*",
		"AREA:rbounced#$mailgraphColor{bounced}:Bounced ",
		'GPRINT:sbounced:MAX:total\: %8.0lf msgs',
		'GPRINT:rbounced:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmbounced:MAX:max\: %4.0lf msgs/min\l',

		"DEF:virus=$rrdvirusfile:virus:AVERAGE",
		"DEF:mvirus=$rrdvirusfile:virus:MAX",
		"CDEF:rvirus=virus,60,*",
		"CDEF:dvirus=virus,UN,0,virus,IF,$set_step,*",
		"CDEF:svirus=PREV,UN,dvirus,PREV,IF,dvirus,+",
		"CDEF:rmvirus=mvirus,60,*",
		"AREA:rvirus#$mailgraphColor{virus}:Viruses ",
		'GPRINT:svirus:MAX:total\: %8.0lf msgs',
		'GPRINT:rvirus:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmvirus:MAX:max\: %4.0lf msgs/min\l',

		"DEF:spam=$rrdvirusfile:spam:AVERAGE",
		"DEF:mspam=$rrdvirusfile:spam:MAX",
		"CDEF:rspam=spam,60,*",
		"CDEF:dspam=spam,UN,0,spam,IF,$set_step,*",
		"CDEF:sspam=PREV,UN,dspam,PREV,IF,dspam,+",
		"CDEF:rmspam=mspam,60,*",
		"AREA:rspam#$mailgraphColor{spam}:Spam    ",
		'GPRINT:sspam:MAX:total\: %8.0lf msgs',
		'GPRINT:rspam:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmspam:MAX:max\: %4.0lf msgs/min\l',
	);

	RRDs::graph(
		$set_outputfile,
		'--imgformat', 'PNG',
		'--title', $set_title,
		'--width', $set_xpoints,
		'--height', $set_ypoints,
		'--start', "-$set_range",
		'--end', $set_endrange,
		'--vertical-label', 'msgs/min',
		'--lower-limit', 0,
		'--units-exponent', 0,
		'--lazy',
		'--color', 'SHADEA#ffffff',
		'--color', 'SHADEB#ffffff',
		'--color', 'BACK#ffffff',
		$RRDs::VERSION < 1.2002 ? () : ( '--slope-mode'),
		@RRDArgs,
		'COMMENT: Last updated\:['.$set_date.']\r',
	);

	my $errorMsg = RRDs::error;
	error($errorMsg) if $errorMsg;
	return 1 if $errorMsg;

	my $file = iMSCP::File->new('filename' => $set_outputfile);

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

	my $mailgraph_rrd = '/var/lib/mailgraph/mailgraph_greylist.rrd';

	return 0 if ! -f $mailgraph_rrd;

	my $imgGraphsDir = $main::imscpConfig{'GUI_ROOT_DIR'} . '/plugins/Mailgraph/tmp_graph';
	my $hostname = $main::imscpConfig{'SERVER_HOSTNAME'};

	my $xpoints = 540;
	my $points_per_sample = 3;
	my $ypoints = 96;

	my $day_range = 3600*24*1;
	my $day_step = $day_range*$points_per_sample/$xpoints;
	my $day_mailgraph_title = 'Mailgraph greylist - Daily - ' . $hostname;
	my $day_outputfile = $imgGraphsDir . '/mailgraph_greylist_day.png';

	my $week_range = 3600*24*7;
	my $week_step = $week_range*$points_per_sample/$xpoints;
	my $week_mailgraph_title = 'Mailgraph greylist - Weekly - ' . $hostname;
	my $week_outputfile = $imgGraphsDir . '/mailgraph_greylist_week.png';

	my $month_range = 3600*24*30;
	my $month_step = $month_range*$points_per_sample/$xpoints;
	my $month_mailgraph_title = 'Mailgraph greylist - Monthly - ' . $hostname;
	my $month_outputfile = $imgGraphsDir . '/mailgraph_greylist_month.png';

	my $year_range = 3600*24*365;
	my $year_step = $year_range*$points_per_sample/$xpoints;
	my $year_mailgraph_title = 'Mailgraph greylist - Yearly - ' . $hostname;
	my $year_outputfile = $imgGraphsDir . '/mailgraph_greylist_year.png';

	my $endrange  = time; $endrange -= $endrange % $day_step;
	my $date = localtime(time);
	$date =~ s|:|\\:|g unless $RRDs::VERSION < 1.199908;

	my $rs = $self->_createMailgraphGreylistPicture(
		$mailgraph_rrd,
		$xpoints,
		$ypoints,
		$day_range,
		$endrange,
		$day_step,
		$day_mailgraph_title,
		$day_outputfile,
		$date
	);
	return $rs if $rs;

	$rs = $self->_createMailgraphGreylistPicture(
		$mailgraph_rrd,
		$xpoints,
		$ypoints,
		$week_range,
		$endrange,
		$week_step,
		$week_mailgraph_title,
		$week_outputfile,
		$date
	);
	return $rs if $rs;

	$rs = $self->_createMailgraphGreylistPicture(
		$mailgraph_rrd,
		$xpoints,
		$ypoints,
		$month_range,
		$endrange,
		$month_step,
		$month_mailgraph_title,
		$month_outputfile,
		$date
	);
	return $rs if $rs;

	$self->_createMailgraphGreylistPicture(
		$mailgraph_rrd,
		$xpoints,
		$ypoints,
		$year_range,
		$endrange,
		$year_step,
		$year_mailgraph_title,
		$year_outputfile,
		$date
	);
}

=item _createMailgraphGreylistPicture()

 Creates the mailgraph greylist picture

 Return int 0 on success, other on failure

=cut

sub _createMailgraphGreylistPicture
{
	my $self = shift;

	my $rrdfile = shift;
	my $set_xpoints = shift;
	my $set_ypoints = shift;
	my $set_range = shift;
	my $set_endrange = shift;
	my $set_step = shift;
	my $set_title = shift;
	my $set_outputfile = shift;
	my $set_date = shift;

	my %mailgraphColor = (
		greylisted => '999999',
		delayed => '006400',
	);

	my @RRDArgs = (
		"DEF:greylisted=$rrdfile:greylisted:AVERAGE",
		"DEF:mgreylisted=$rrdfile:greylisted:MAX",
		"CDEF:rgreylisted=greylisted,60,*",
		"CDEF:dgreylisted=greylisted,UN,0,greylisted,IF,$set_step,*",
		"CDEF:sgreylisted=PREV,UN,dgreylisted,PREV,IF,dgreylisted,+",
		"CDEF:rmgreylisted=mgreylisted,60,*",
		"AREA:rgreylisted#$mailgraphColor{greylisted}:Greylisted",
		'GPRINT:sgreylisted:MAX:total\: %8.0lf msgs',
		'GPRINT:rgreylisted:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmgreylisted:MAX:max\: %4.0lf msgs/min\l',

		"DEF:delayed=$rrdfile:delayed:AVERAGE",
		"DEF:mdelayed=$rrdfile:delayed:MAX",
		"CDEF:rdelayed=delayed,60,*",
		"CDEF:ddelayed=delayed,UN,0,delayed,IF,$set_step,*",
		"CDEF:sdelayed=PREV,UN,ddelayed,PREV,IF,ddelayed,+",
		"CDEF:rmdelayed=mdelayed,60,*",
		"LINE2:rdelayed#$mailgraphColor{delayed}:Delayed   ",
		'GPRINT:sdelayed:MAX:total\: %8.0lf msgs',
		'GPRINT:rdelayed:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmdelayed:MAX:max\: %4.0lf msgs/min\l',
	);

	RRDs::graph(
		$set_outputfile,
		'--imgformat', 'PNG',
		'--title', $set_title,
		'--width', $set_xpoints,
		'--height', $set_ypoints,
		'--start', "-$set_range",
		'--end', $set_endrange,
		'--vertical-label', 'msgs/min',
		'--lower-limit', 0,
		'--units-exponent', 0,
		'--lazy',
		'--color', 'SHADEA#ffffff',
		'--color', 'SHADEB#ffffff',
		'--color', 'BACK#ffffff',
		$RRDs::VERSION < 1.2002 ? () : ( '--slope-mode'),
		@RRDArgs,
		'COMMENT: Last updated\:['.$set_date.']\r',
	);

	my $errorMsg = RRDs::error;
	error($errorMsg) if $errorMsg;
	return 1 if ($errorMsg);

	my $file = iMSCP::File->new('filename' => $set_outputfile);

	my $panelUname =
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	my $rs = $file->owner($panelUname, $panelGName);
	return $rs if $rs;

	$file->mode(0644);
}

=item _registerCronjob()

 Register mailgraph cronjob

 Return int 0 on success, other on failure

=cut

sub _registerCronjob
{
	my $self = shift;

	require iMSCP::Database;

	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'plugin_name', 'SELECT `plugin_name`, `plugin_config` FROM `plugin` WHERE `plugin_name` = ?', 'Mailgraph'
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	require JSON;
	JSON->import();

	my $cronjobConfig = decode_json($rdata->{'Mailgraph'}->{'plugin_config'});

	if($cronjobConfig->{'cronjob_enabled'}) {
		my $cronjobFilePath = $main::imscpConfig{'GUI_ROOT_DIR'} . '/plugins/Mailgraph/cronjob.pl';

		my $cronjobFile = iMSCP::File->new('filename' => $cronjobFilePath);

		my $cronjobFileContent = $cronjobFile->get();
		return 1 if ! $cronjobFileContent;

		require iMSCP::Templator;
		iMSCP::Templator->import();

		$cronjobFileContent = process(
			{ 'IMSCP_PERLLIB_PATH' => $main::imscpConfig{'ENGINE_ROOT_DIR'} . '/PerlLib' },
			$cronjobFileContent
		);

		my $rs = $cronjobFile->set($cronjobFileContent);
		return $rs if $rs;

		$rs = $cronjobFile->save();
		return $rs if $rs;

		# TODO Check syntax for config values

		require Servers::cron;
		Servers::cron->factory()->addTask(
			{
				'TASKID' => 'PLUGINS:Mailgraph',
				'MINUTE' => $cronjobConfig->{'cronjob_config'}->{'minute'},
				'HOUR' => $cronjobConfig->{'cronjob_config'}->{'hour'},
				'DAY' => $cronjobConfig->{'cronjob_config'}->{'day'},
				'MONTH' => $cronjobConfig->{'cronjob_config'}->{'month'},
				'DWEEK' => $cronjobConfig->{'cronjob_config'}->{'dweek'},
				'COMMAND' => "umask 027; perl $cronjobFilePath >/dev/null 2>&1"
			}
		);
	} else {
		0;
	}
}

=item _unregisterCronjob()

 Unregister mailgraph cronjob

 Return int 0 on success, other on failure

=cut

sub _unregisterCronjob
{
	my $self = shift;

	require Servers::cron;
	Servers::cron->factory()->deleteTask({ 'TASKID' => 'PLUGINS:Mailgraph' });
}

=back

=head1 AUTHORS AND CONTRIBUTORS

 Sascha Bay <info@space2place.de>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;

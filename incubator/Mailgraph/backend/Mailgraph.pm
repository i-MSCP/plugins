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
# @category i-MSCP
# @package iMSCP_Plugin
# @subpackage Mailgraph
# @copyright 2010-2013 by i-MSCP | http://i-mscp.net
# @author Sascha Bay <info@space2place.de>
# @link http://i-mscp.net i-MSCP Home Site
# @license http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Plugin::Mailgraph;

use strict;
use warnings;

use RRDs;
use iMSCP::Debug;
use iMSCP::File;

use parent 'Common::SingletonClass';

=item install()

 Perform install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = 0;
	
	if(! -d '/var/lib/mailgraph' || ! -f '/etc/default/mailgraph') {
		error('Unable to find mailgraph. Install mailgraph first.');
		return 1;
	}
	
	$rs = $self->_setMailgraphVariables();
	return $rs if $rs;
	
	$rs = $self->_setMailgraphVirusVariables();
	return $rs if $rs;
	
	$rs = $self->_setMailgraphGreylistVariables();
	return $rs if $rs;
	
	0;
}

=item uninstall()

 Perform un-installation tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;
	my $rs = 0;
	
	0;
}

=item run()

 Run all scheduled actions according new mailgraph graphics

 Return int 0 on success, other on failure

=cut

sub run
{
	my $self = shift;
	my $rs = 0;
	
	$rs = $self->_setMailgraphVariables();
	return $rs if $rs;
	
	$rs = $self->_setMailgraphVirusVariables();
	return $rs if $rs;
	
	$rs = $self->_setMailgraphGreylistVariables();
	return $rs if $rs;
	
	$rs;
}

=item _setMailgraphVariables()

 sets the mailgraph graphic varibales

 Return int 0 on success, other on failure

=cut

sub _setMailgraphVariables
{
	my $self = shift;

	my ($stdout, $stderr);
	my $rs = 0;
	
	my $mailgraph_rrd = '/var/lib/mailgraph/mailgraph.rrd';
	
	my $xpoints = 540;
	my $points_per_sample = 3;
	my $ypoints = 160;
	
	my $day_range = 3600*24*1;
	my $day_step = $day_range*$points_per_sample/$xpoints;
	my $day_mailgraph_title = 'Mailgraph - Daily - '.$main::imscpConfig{'SERVER_HOSTNAME'};
	my $day_outputfile = $main::imscpConfig{'GUI_ROOT_DIR'}.'/plugins/Mailgraph/tmp_graph/mailgraph_day.png';
	
	my $week_range = 3600*24*7;
	my $week_step = $week_range*$points_per_sample/$xpoints;
	my $week_mailgraph_title = 'Mailgraph - Weekly - '.$main::imscpConfig{'SERVER_HOSTNAME'};
	my $week_outputfile = $main::imscpConfig{'GUI_ROOT_DIR'}.'/plugins/Mailgraph/tmp_graph/mailgraph_week.png';
	
	my $month_range = 3600*24*30;
	my $month_step = $month_range*$points_per_sample/$xpoints;
	my $month_mailgraph_title = 'Mailgraph - Monthly - '.$main::imscpConfig{'SERVER_HOSTNAME'};
	my $month_outputfile = $main::imscpConfig{'GUI_ROOT_DIR'}.'/plugins/Mailgraph/tmp_graph/mailgraph_month.png';
	
	my $year_range = 3600*24*365;
	my $year_step = $year_range*$points_per_sample/$xpoints;
	my $year_mailgraph_title = 'Mailgraph - Yearly - '.$main::imscpConfig{'SERVER_HOSTNAME'};
	my $year_outputfile = $main::imscpConfig{'GUI_ROOT_DIR'}.'/plugins/Mailgraph/tmp_graph/mailgraph_year.png';
	
	my $endrange  = time; $endrange -= $endrange % $day_step;
	my $date = localtime(time);
	$date =~ s|:|\\:|g unless $RRDs::VERSION < 1.199908;
	
	$rs = $self->_createMailgraphPicture(
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
	
	$rs = $self->_createMailgraphPicture(
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
	return $rs if $rs;
	
	0;
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

	my ($stdout, $stderr);
	my $rs = 0;
	
	my %MailgraphColor = (
		sent       => '000099',
		received   => '009900'		
	);
	
	my @RRDArgs = (
		"DEF:sent=$rrdfile:sent:AVERAGE",
		"DEF:msent=$rrdfile:sent:MAX",
		"CDEF:rsent=sent,60,*",
		"CDEF:rmsent=msent,60,*",
		"CDEF:dsent=sent,UN,0,sent,IF,$set_step,*",
		"CDEF:ssent=PREV,UN,dsent,PREV,IF,dsent,+",
		"AREA:rsent#$MailgraphColor{sent}:Sent    ",
		'GPRINT:ssent:MAX:total\: %8.0lf msgs',
		'GPRINT:rsent:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmsent:MAX:max\: %4.0lf msgs/min\l',

		"DEF:recv=$rrdfile:recv:AVERAGE",
		"DEF:mrecv=$rrdfile:recv:MAX",
		"CDEF:rrecv=recv,60,*",
		"CDEF:rmrecv=mrecv,60,*",
		"CDEF:drecv=recv,UN,0,recv,IF,$set_step,*",
		"CDEF:srecv=PREV,UN,drecv,PREV,IF,drecv,+",
		"LINE2:rrecv#$MailgraphColor{received}:Received",
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
	
	$stdout = RRDs::error;
	error($stdout) if $stdout;
	
	my $file = iMSCP::File->new('filename' => $set_outputfile);
		
	$rs = $file->owner('vu2000', 'vu2000');
	return $rs if $rs;
	
	$rs = $file->mode(0644);
	return $rs if $rs;
	
	0;
}

=item _setMailgraphVirusVariables()

 sets the mailgraph graphic varibales for viruses

 Return int 0 on success, other on failure

=cut

sub _setMailgraphVirusVariables
{
	my $self = shift;

	my ($stdout, $stderr);
	my $rs = 0;
	
	my $mailgraph_rrd = '/var/lib/mailgraph/mailgraph.rrd';
	my $mailgraph_virus_rrd = '/var/lib/mailgraph/mailgraph_virus.rrd';
	
	my $xpoints = 540;
	my $points_per_sample = 3;
	my $ypoints = 96;
	
	my $day_range = 3600*24*1;
	my $day_step = $day_range*$points_per_sample/$xpoints;
	my $day_mailgraph_title = 'Mailgraph virus - Daily - '.$main::imscpConfig{'SERVER_HOSTNAME'};
	my $day_outputfile = $main::imscpConfig{'GUI_ROOT_DIR'}.'/plugins/Mailgraph/tmp_graph/mailgraph_virus_day.png';
	
	my $week_range = 3600*24*7;
	my $week_step = $week_range*$points_per_sample/$xpoints;
	my $week_mailgraph_title = 'Mailgraph virus - Weekly - '.$main::imscpConfig{'SERVER_HOSTNAME'};
	my $week_outputfile = $main::imscpConfig{'GUI_ROOT_DIR'}.'/plugins/Mailgraph/tmp_graph/mailgraph_virus_week.png';
	
	my $month_range = 3600*24*30;
	my $month_step = $month_range*$points_per_sample/$xpoints;
	my $month_mailgraph_title = 'Mailgraph virus - Monthly - '.$main::imscpConfig{'SERVER_HOSTNAME'};
	my $month_outputfile = $main::imscpConfig{'GUI_ROOT_DIR'}.'/plugins/Mailgraph/tmp_graph/mailgraph_virus_month.png';
	
	my $year_range = 3600*24*365;
	my $year_step = $year_range*$points_per_sample/$xpoints;
	my $year_mailgraph_title = 'Mailgraph virus - Yearly - '.$main::imscpConfig{'SERVER_HOSTNAME'};
	my $year_outputfile = $main::imscpConfig{'GUI_ROOT_DIR'}.'/plugins/Mailgraph/tmp_graph/mailgraph_virus_year.png';
	
	my $endrange  = time; $endrange -= $endrange % $day_step;
	my $date = localtime(time);
	$date =~ s|:|\\:|g unless $RRDs::VERSION < 1.199908;
	
	$rs = $self->_createMailgraphVirusPicture(
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
	
	$rs = $self->_createMailgraphVirusPicture(
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
	return $rs if $rs;

	0;
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

	my ($stdout, $stderr);
	my $rs = 0;
	
	my %MailgraphColor = (
		rejected	=> 'AA0000',
		bounced	=> '000000',
		virus		=> 'DDBB00',
		spam		=> '999999'
	);
	
	my @RRDArgs = (
		"DEF:rejected=$rrdfile:rejected:AVERAGE",
		"DEF:mrejected=$rrdfile:rejected:MAX",
		"CDEF:rrejected=rejected,60,*",
		"CDEF:drejected=rejected,UN,0,rejected,IF,$set_step,*",
		"CDEF:srejected=PREV,UN,drejected,PREV,IF,drejected,+",
		"CDEF:rmrejected=mrejected,60,*",
		"LINE2:rrejected#$MailgraphColor{rejected}:Rejected",
		'GPRINT:srejected:MAX:total\: %8.0lf msgs',
		'GPRINT:rrejected:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmrejected:MAX:max\: %4.0lf msgs/min\l',
		
		"DEF:bounced=$rrdfile:bounced:AVERAGE",
		"DEF:mbounced=$rrdfile:bounced:MAX",
		"CDEF:rbounced=bounced,60,*",
		"CDEF:dbounced=bounced,UN,0,bounced,IF,$set_step,*",
		"CDEF:sbounced=PREV,UN,dbounced,PREV,IF,dbounced,+",
		"CDEF:rmbounced=mbounced,60,*",
		"AREA:rbounced#$MailgraphColor{bounced}:Bounced ",
		'GPRINT:sbounced:MAX:total\: %8.0lf msgs',
		'GPRINT:rbounced:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmbounced:MAX:max\: %4.0lf msgs/min\l',
		
		"DEF:virus=$rrdvirusfile:virus:AVERAGE",
		"DEF:mvirus=$rrdvirusfile:virus:MAX",
		"CDEF:rvirus=virus,60,*",
		"CDEF:dvirus=virus,UN,0,virus,IF,$set_step,*",
		"CDEF:svirus=PREV,UN,dvirus,PREV,IF,dvirus,+",
		"CDEF:rmvirus=mvirus,60,*",
		"AREA:rvirus#$MailgraphColor{virus}:Viruses ",
		'GPRINT:svirus:MAX:total\: %8.0lf msgs',
		'GPRINT:rvirus:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmvirus:MAX:max\: %4.0lf msgs/min\l',
		
		"DEF:spam=$rrdvirusfile:spam:AVERAGE",
		"DEF:mspam=$rrdvirusfile:spam:MAX",
		"CDEF:rspam=spam,60,*",
		"CDEF:dspam=spam,UN,0,spam,IF,$set_step,*",
		"CDEF:sspam=PREV,UN,dspam,PREV,IF,dspam,+",
		"CDEF:rmspam=mspam,60,*",
		"AREA:rspam#$MailgraphColor{spam}:Spam    ",
		'GPRINT:sspam:MAX:total\: %8.0lf msgs',
		'GPRINT:rspam:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmspam:MAX:max\: %4.0lf msgs/min\l',
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
	
	$stdout = RRDs::error;
	error($stdout) if $stdout;
	
	my $file = iMSCP::File->new('filename' => $set_outputfile);
		
	$rs = $file->owner('vu2000', 'vu2000');
	return $rs if $rs;
	
	$rs = $file->mode(0644);
	return $rs if $rs;
	
	0;
}

=item _setMailgraphGreylistVariables()

 sets the mailgraph graphic varibales for greylist

 Return int 0 on success, other on failure

=cut

sub _setMailgraphGreylistVariables
{
	my $self = shift;

	my ($stdout, $stderr);
	my $rs = 0;
	
	my $mailgraph_rrd = '/var/lib/mailgraph/mailgraph_greylist.rrd';
	
	my $xpoints = 540;
	my $points_per_sample = 3;
	my $ypoints = 96;
	
	my $day_range = 3600*24*1;
	my $day_step = $day_range*$points_per_sample/$xpoints;
	my $day_mailgraph_title = 'Mailgraph greylist - Daily - '.$main::imscpConfig{'SERVER_HOSTNAME'};
	my $day_outputfile = $main::imscpConfig{'GUI_ROOT_DIR'}.'/plugins/Mailgraph/tmp_graph/mailgraph_greylist_day.png';
	
	my $week_range = 3600*24*7;
	my $week_step = $week_range*$points_per_sample/$xpoints;
	my $week_mailgraph_title = 'Mailgraph greylist - Weekly - '.$main::imscpConfig{'SERVER_HOSTNAME'};
	my $week_outputfile = $main::imscpConfig{'GUI_ROOT_DIR'}.'/plugins/Mailgraph/tmp_graph/mailgraph_greylist_week.png';
	
	my $month_range = 3600*24*30;
	my $month_step = $month_range*$points_per_sample/$xpoints;
	my $month_mailgraph_title = 'Mailgraph greylist - Monthly - '.$main::imscpConfig{'SERVER_HOSTNAME'};
	my $month_outputfile = $main::imscpConfig{'GUI_ROOT_DIR'}.'/plugins/Mailgraph/tmp_graph/mailgraph_greylist_month.png';
	
	my $year_range = 3600*24*365;
	my $year_step = $year_range*$points_per_sample/$xpoints;
	my $year_mailgraph_title = 'Mailgraph greylist - Yearly - '.$main::imscpConfig{'SERVER_HOSTNAME'};
	my $year_outputfile = $main::imscpConfig{'GUI_ROOT_DIR'}.'/plugins/Mailgraph/tmp_graph/mailgraph_greylist_year.png';
	
	my $endrange  = time; $endrange -= $endrange % $day_step;
	my $date = localtime(time);
	$date =~ s|:|\\:|g unless $RRDs::VERSION < 1.199908;
	
	$rs = $self->_createMailgraphGreylistPicture(
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
	
	$rs = $self->_createMailgraphGreylistPicture(
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
	return $rs if $rs;
	
	0;
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

	my ($stdout, $stderr);
	my $rs = 0;
	
	my %MailgraphColor = (
		greylisted	=> '999999',
		delayed	=> '006400',
	);
	
	my @RRDArgs = (
		"DEF:greylisted=$rrdfile:greylisted:AVERAGE",
		"DEF:mgreylisted=$rrdfile:greylisted:MAX",
		"CDEF:rgreylisted=greylisted,60,*",
		"CDEF:dgreylisted=greylisted,UN,0,greylisted,IF,$set_step,*",
		"CDEF:sgreylisted=PREV,UN,dgreylisted,PREV,IF,dgreylisted,+",
		"CDEF:rmgreylisted=mgreylisted,60,*",
		"AREA:rgreylisted#$MailgraphColor{greylisted}:Greylisted",
		'GPRINT:sgreylisted:MAX:total\: %8.0lf msgs',
		'GPRINT:rgreylisted:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmgreylisted:MAX:max\: %4.0lf msgs/min\l',

		"DEF:delayed=$rrdfile:delayed:AVERAGE",
		"DEF:mdelayed=$rrdfile:delayed:MAX",
		"CDEF:rdelayed=delayed,60,*",
		"CDEF:ddelayed=delayed,UN,0,delayed,IF,$set_step,*",
		"CDEF:sdelayed=PREV,UN,ddelayed,PREV,IF,ddelayed,+",
		"CDEF:rmdelayed=mdelayed,60,*",
		"LINE2:rdelayed#$MailgraphColor{delayed}:Delayed   ",
		'GPRINT:sdelayed:MAX:total\: %8.0lf msgs',
		'GPRINT:rdelayed:AVERAGE:avg\: %5.2lf msgs/min',
		'GPRINT:rmdelayed:MAX:max\: %4.0lf msgs/min\l',
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
	
	$stdout = RRDs::error;
	error($stdout) if $stdout;
	
	my $file = iMSCP::File->new('filename' => $set_outputfile);
		
	$rs = $file->owner('vu2000', 'vu2000');
	return $rs if $rs;
	
	$rs = $file->mode(0644);
	return $rs if $rs;
	
	0;
}

1;

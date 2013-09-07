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
# @subpackage  AdminerSQL
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Sascha Bay <info@space2place.de>
# @contributor Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Plugin::AdminerSQL;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::Database;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP AdminerSQL plugin.

=head1 PUBLIC METHODS

=over 4

=item install()

 Perform install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->_buildAdminerSQLFiles();
	return $rs if $rs;
}

=item update()

 Perform update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	my $self = shift;

	$self->_buildAdminerSQLFiles();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize plugin

 Return Plugin::AdminerSQL

=cut

sub _init
{
	my $self = shift;

	# Force return value from plugin module
	$self->{'FORCE_RETVAL'} = 'yes';

	$self;
}

=item _buildAdminerSQLFiles()

 Build AdminerSQL files

 Return int 0 on success, other on failure

=cut

sub _buildAdminerSQLFiles
{
	my $self = shift;

	my $rs = 0;
	my ($stdout, $stderr);
	
	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'plugin_name', 'SELECT `plugin_name`, `plugin_config` FROM `plugin` WHERE `plugin_name` = ?', 'AdminerSQL'
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	require JSON;
	JSON->import();

	my $adminerSqlConfig = decode_json($rdata->{'AdminerSQL'}->{'plugin_config'});
	
	my $adminerSqlDriver = ($adminerSqlConfig->{'driver'} eq 'all' ? '' : ' ' . $adminerSqlConfig->{'driver'});
	my $adminerSqlLanguage = ($adminerSqlConfig->{'language'} eq 'all' ? '' : ' ' . $adminerSqlConfig->{'language'});
	
	$rs = $self->_copyStyleAdminerSQL($adminerSqlConfig->{'style'});
	return $rs if $rs;
	
	# Compiling adminer.php
	$rs = execute("$main::imscpConfig{'CMD_PHP'} $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/AdminerSQL/adminer-sources/compile.php " . $adminerSqlDriver.$adminerSqlLanguage, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
			
	return $rs if $rs;
	
	# Compiling editor.php
	$rs = execute("$main::imscpConfig{'CMD_PHP'} $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/AdminerSQL/adminer-sources/compile.php editor " . $adminerSqlDriver.$adminerSqlLanguage, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
			
	return $rs if $rs;
	
	$rs = $self->_modifyCompiledAdminerSQLFiles(
		$adminerSqlConfig->{'adminer_version'}, $adminerSqlConfig->{'driver'}, $adminerSqlConfig->{'language'}
	);
	return $rs if $rs;
	
	$rs = $self->_moveCompiledAdminerSQLFilesToPublicFolder(
		$adminerSqlConfig->{'adminer_version'}, $adminerSqlConfig->{'driver'}, $adminerSqlConfig->{'language'}
	);
	return $rs if $rs;
}

=item _copyStyleAdminerSQL()

 Copy the adminer.css to the default.css

 Return int 0 on success, other on failure

=cut

sub _copyStyleAdminerSQL
{
	my $self = shift;
	my $adminerStyle = shift;
	
	my $rs = 0;
	my ($stdout, $stderr);

	# Copy the adminer.css
	if($adminerStyle ne 'default') {
		$rs = execute("$main::imscpConfig{'CMD_CP'} -f $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/AdminerSQL/adminer-sources/designs/" . $adminerStyle . "/adminer.css $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/AdminerSQL/adminer-sources/adminer/static/default.css", \$stdout, \$stderr);
	} else {
		$rs = execute("$main::imscpConfig{'CMD_CP'} -f $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/AdminerSQL/adminer-sources/adminer/static/default_sik.css $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/AdminerSQL/adminer-sources/adminer/static/default.css", \$stdout, \$stderr);
	}
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	return $rs if $rs;
}

=item _modifyCompiledAdminerSQLFiles()

 Modifies the compiled AdminerSQL files

 Return int 0 on success, other on failure

=cut

sub _modifyCompiledAdminerSQLFiles
{
	my $self = shift;
	my $adminerVersion = shift;
	my $adminerSqlDriver = shift;
	my $adminerSqlLanguage = shift;
	
	my $compiledAdminerFileName = 'adminer-' . $adminerVersion;
	my $compiledEditorFileName = 'editor-' . $adminerVersion;
	
	if($adminerSqlDriver ne 'all') {
		$compiledAdminerFileName .= '-' . $adminerSqlDriver;
		$compiledEditorFileName .= '-' . $adminerSqlDriver;
	}
	if($adminerSqlLanguage ne 'all') {
		$compiledAdminerFileName .= '-' . $adminerSqlLanguage;
		$compiledEditorFileName .= '-' . $adminerSqlLanguage;
	}
	
	# Modify the complied adminer.php
	my $compiledAdminerFile = $main::imscpConfig{'GUI_ROOT_DIR'} . '/plugins/AdminerSQL/adminer-sources/' . $compiledAdminerFileName . '.php';
	my $file = iMSCP::File->new('filename' => $compiledAdminerFile);
	
	my $fileContent = $file->get();
	return $fileContent if ! $fileContent;
	
	if ($fileContent =~ /get_session/sgm) {
		$fileContent =~ s/get_session/get_session_adminer/g;
	}
	
	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
	
	# Modify the complied editor.php
	my $compiledEditorFile = $main::imscpConfig{'GUI_ROOT_DIR'} . '/plugins/AdminerSQL/adminer-sources/' . $compiledEditorFileName . '.php';
	$file = iMSCP::File->new('filename' => $compiledEditorFile);
	
	$fileContent = $file->get();
	return $fileContent if ! $fileContent;
	
	if ($fileContent =~ /get_session/sgm) {
		$fileContent =~ s/get_session/get_session_adminer/g;
	}
	
	$rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _moveCompiledAdminerSQLFilesToPublicFolder()

 Move the compiled AdminerSQL files to public folder

 Return int 0 on success, other on failure

=cut

sub _moveCompiledAdminerSQLFilesToPublicFolder
{
	my $self = shift;
	my $adminerVersion = shift;
	my $adminerSqlDriver = shift;
	my $adminerSqlLanguage = shift;
	
	my $rs = 0;
	my ($stdout, $stderr);
	
	my $compiledAdminerFileName = 'adminer-' . $adminerVersion;
	my $compiledEditorFileName = 'editor-' . $adminerVersion;
	
	if($adminerSqlDriver ne 'all') {
		$compiledAdminerFileName .= '-' . $adminerSqlDriver;
		$compiledEditorFileName .= '-' . $adminerSqlDriver;
	}
	if($adminerSqlLanguage ne 'all') {
		$compiledAdminerFileName .= '-' . $adminerSqlLanguage;
		$compiledEditorFileName .= '-' . $adminerSqlLanguage;
	}

	$rs = execute("$main::imscpConfig{'CMD_MV'} -f $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/AdminerSQL/adminer-sources/" . $compiledAdminerFileName . ".php $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/AdminerSQL/frontend/adminer.php", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	return $rs if $rs;
	
	$rs = execute("$main::imscpConfig{'CMD_MV'} -f $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/AdminerSQL/adminer-sources/" . $compiledEditorFileName . ".php $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/AdminerSQL/frontend/editor.php", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	return $rs if $rs;
	
	my $panelUName =
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	require iMSCP::Rights;
	iMSCP::Rights->import();
	
	$rs = setRights(
		$main::imscpConfig{'GUI_ROOT_DIR'} . "/plugins/AdminerSQL/frontend/adminer.php",
		{ 'user' => $panelUName, 'group' => $panelGName, 'filemode' => '0644' }
	);
	return $rs if $rs;

	$rs = setRights(
		$main::imscpConfig{'GUI_ROOT_DIR'} . "/plugins/AdminerSQL/frontend/editor.php",
		{ 'user' => $panelUName, 'group' => $panelGName, 'filemode' => '0644' }
	);
	return $rs if $rs;
}

=back

=head1 AUTHORS AND CONTRIBUTORS

 Sascha Bay <info@space2place.de>

=cut

1;

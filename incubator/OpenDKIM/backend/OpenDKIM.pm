=head1 NAME

 Plugin::OpenDKIM

=cut

# i-MSCP OpenDKIM plugin
# Copyright (C) 2013-2016 Laurent Declercq <l.declercq@nuxwin.com>
# Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
# Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
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

package Plugin::OpenDKIM;

use strict;
use warnings;
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Rights;
use iMSCP::Service;
use iMSCP::TemplateParser;
use Servers::mta;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP OpenDKIM plugin.

=head1 PUBLIC METHODS

=over 4

=item install()

 Perform install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my $self = shift;

    my $rs = $self->_checkRequirements();
    $rs ||= $self->_createOpendkimDirectories();
    $rs ||= $self->_createOpendkimFile( 'KeyTable' );
    $rs ||= $self->_createOpendkimFile( 'SigningTable' );
    $rs ||= $self->_createOpendkimFile( 'TrustedHosts' );
    $rs ||= $self->_opendkimConfig( 'configure' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->restart( 'opendkim' ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    0;
}

=item uninstall()

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my $self = shift;

    my $rs = $self->{'db'}->doQuery( 'd', "DELETE FROM domain_dns WHERE owned_by = 'OpenDKIM_Plugin'" );
    unless (ref $rs eq 'HASH') {
        error( $rs );
        return $rs;
    }

    $rs = $self->_opendkimConfig( 'deconfigure' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->restart( 'opendkim' ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    $rs = iMSCP::Dir->new( dirname => '/etc/opendkim' )->remove();
    $rs ||= iMSCP::Dir->new( dirname => '/var/spool/postfix/opendkim' )->remove();
}

=item update($fromVersion)

 Perform update tasks

 Param string $fromVersion Version from which the plugin is being updated
 Return int 0 on success, other on failure

=cut

sub update
{
    my ($self, $fromVersion) = @_;

    if (version->parse( $fromVersion ) < version->parse( '1.1.1' )) {
        # Fix bug in versions < 1.1.1 where the `owned_by' field for DKIM DNS resource records was reseted back to
        # `opendkim_feature', leading to orphaned custom DNS resource records 
        my $rs = $self->{'db'}->doQuery( 'd', "DELETE FROM domain_dns WHERE owned_by = 'opendkim_feature'" );
        unless (ref $rs eq 'HASH') {
            error( $rs );
            return $rs;
        }
    }

    my $rs = $self->_createOpendkimDirectories();
    $rs ||= $self->_opendkimConfig( 'configure' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->restart( 'opendkim' ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    0;
}

=item change()

 Perform change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
    my $self = shift;

    my $rs = $self->_createOpendkimFile( 'TrustedHosts' );
    $rs ||= $self->_opendkimConfig( 'configure' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->restart( 'opendkim' ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    0;
}

=item enable()

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
    my $self = shift;

    my $rs = $self->{'db'}->doQuery(
        'u', 'UPDATE domain_dns SET domain_dns_status = ? WHERE owned_by = ?', 'toenable', 'OpenDKIM_Plugin'
    );
    unless (ref $rs eq 'HASH') {
        error( $rs );
        return $rs;
    }

    $rs = $self->_postfixMainConfig( 'configure' );
    return $rs if $rs;

    Servers::mta->factory()->{'reload'} = 1;

    $rs = setRights(
        '/etc/opendkim',
        {
            user      => 'opendkim',
            group     => 'opendkim',
            dirmode   => '0750',
            filemode  => '0640',
            recursive => 1
        }
    );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->restart( 'opendkim' ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    0;
}

=item disable()

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
    my $self = shift;

    my $rs = $self->{'db'}->doQuery(
        'u', 'UPDATE domain_dns SET domain_dns_status = ? WHERE owned_by = ?', 'todisable', 'OpenDKIM_Plugin'
    );
    unless (ref $rs eq 'HASH') {
        error( $rs );
        return $rs;
    }

    $rs = $self->_postfixMainConfig( 'deconfigure' );
    return $rs if $rs;

    Servers::mta->factory()->{'reload'} = 1;
    0;
}

=item run()

 Create new entry for the OpenDKIM

 Return int 0 on success, other on failure

=cut

sub run
{
    my $self = shift;

    my $rows = $self->{'db'}->doQuery(
        'opendkim_id',
        "
            SELECT opendkim_id, domain_id, IFNULL(alias_id, 0) AS alias_id, domain_name, opendkim_status
            FROM opendkim WHERE opendkim_status IN('toadd', 'tochange', 'todelete')
        "
    );
    unless (ref $rows eq 'HASH') {
        error( $rows );
        return 1;
    }

    my @sql;
    for(values %{$rows}) {
        if ($_->{'opendkim_status'} =~ /^to(?:add|change)$/) {
            my $rs = $self->_addDomainKey( $_->{'domain_id'}, $_->{'alias_id'}, $_->{'domain_name'} );
            @sql = (
                'UPDATE opendkim SET opendkim_status = ? WHERE opendkim_id = ?',
                ($rs ? scalar getMessageByType( 'error' ) || 'Unknown error' : 'ok'), $_->{'opendkim_id'}
            );
        } elsif ($_->{'opendkim_status'} eq 'todelete') {
            my $rs = $self->_deleteDomainKey( $_->{'domain_id'}, $_->{'alias_id'}, $_->{'domain_name'} );
            if ($rs) {
                @sql = (
                    'UPDATE opendkim SET opendkim_status = ? WHERE opendkim_id = ?',
                    (scalar getMessageByType( 'error' ) || 'Unknown error'), $_->{'opendkim_id'}
                );
            } else {
                @sql = ('DELETE FROM opendkim WHERE opendkim_id = ?', $_->{'opendkim_id'});
            }
        }

        my $qrs = $self->{'db'}->doQuery( 'dummy', @sql );
        unless (ref $qrs eq 'HASH') {
            error( $qrs );
            return 1;
        }
    }

    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize plugin

 Return Plugin::OpenDKIM or die on failure

=cut

sub _init
{
    my $self = shift;

    $self->{'db'} = iMSCP::Database->factory();
    $self;
}

=item _addDomainKey($domainId, $aliasId, $domain)

 Adds domain key for the given domain or domain alias

 Param int $domainId Domain unique identifier
 Param int $aliasId Domain alias unique identifier ( 0 if no domain alias )
 Param string $domain Domain name
 Return int 0 on success, other on failure

=cut

sub _addDomainKey
{
    my ($self, $domainId, $aliasId, $domain) = @_;

    # This action must be idempotent ( this allow to handle 'tochange' status which include key renewal )
    my $rs = $self->_deleteDomainKey( $domainId, $aliasId, $domain );
    return $rs if $rs;

    $rs = iMSCP::Dir->new( dirname => "/etc/opendkim/keys/$domain" )->make(
        {
            user  => 'opendkim',
            group => 'opendkim',
            mode  => 0750
        }
    );
    return $rs if $rs;

    # Generate the domain private key and the DNS TXT record suitable for inclusion in DNS zone file
    # The DNS TXT record contain the public key
    $rs = execute( "opendkim-genkey -D /etc/opendkim/keys/$domain -r -s mail -d $domain", \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    return $rs if $rs;

    # Fix permissions on the domain private key file

    my $file = iMSCP::File->new( filename => "/etc/opendkim/keys/$domain/mail.private" );
    $rs = $file->mode( 0640 );
    $rs ||= $file->owner( 'opendkim', 'opendkim' );
    return $rs if $rs;

    # Retrieve the TXT DNS record

    $file = iMSCP::File->new( filename => "/etc/opendkim/keys/$domain/mail.txt" );
    my $fileContent = $file->get();
    unless (defined $fileContent) {
        error( sprintf( 'Could not read %s file', $file->{'filename'} ) );
        return 1;
    }

    $fileContent =~ s/"\n(.*)"p=/ p=/sgm; # Fix for Ubuntu 14.04 Trusty Tahr
    (my $txtRecord) = ($fileContent =~ /(".*")/);

    # Fix permissions on the TXT DNS record file

    $rs = $file->mode( 0640 );
    $rs ||= $file->owner( 'opendkim', 'opendkim' );
    return $rs if $rs;

    # Add the domain private key into the OpenDKIM KeyTable file

    $file = iMSCP::File->new( filename => '/etc/opendkim/KeyTable' );
    $fileContent = $file->get();
    unless (defined $fileContent) {
        error( sprintf( 'Could not read %s file', $file->{'filename'} ) );
        return 1;
    }

    $fileContent .= "mail._domainkey.$domain $domain:mail:/etc/opendkim/keys/$domain/mail.private\n";
    $rs = $file->set( $fileContent );
    $rs ||= $file->save();
    return $rs if $rs;

    # Add the domain entry into the OpenDKIM SigningTable file
    $file = iMSCP::File->new( filename => '/etc/opendkim/SigningTable' );
    $fileContent = $file->get();
    unless (defined $fileContent) {
        error( sprintf( 'Could not read %s file', $file->{'filename'} ) );
        return 1;
    }

    $fileContent .= "*\@$domain mail._domainkey.$domain\n";
    $rs = $file->set( $fileContent );
    $rs ||= $file->save();
    return $rs if $rs;

    # Add the TXT DNS record into the database

    $rs = $self->{'db'}->doQuery(
        'domain_id',
        '
            INSERT INTO domain_dns (
                domain_id, alias_id, domain_dns, domain_class, domain_type, domain_text, owned_by, domain_dns_status
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?
            )
        ',
        $domainId, $aliasId, 'mail._domainkey 60', 'IN', 'TXT', $txtRecord, 'OpenDKIM_Plugin', 'toadd'
    );
    unless (ref $rs eq 'HASH') {
        error( $rs );
        return 1;
    }

    local $@;
    eval { iMSCP::Service->getInstance()->reload( 'opendkim' ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    0;
}

=item _deleteDomainKey($domainId, $aliasId, $domain)

 Deletes domain key from OpenDKIM support

 Param int $domainId Domain unique identifier
 Param int $aliasId Domain alias unique identifier (0 if no domain alias)
 Return int 0 on success, other on failure

=cut

sub _deleteDomainKey
{
    my ($self, $domainId, $aliasId, $domain) = @_;

    # Remove the directory which host the domain private key and the DNS TXT record files
    my $rs = iMSCP::Dir->new( dirname => "/etc/opendkim/keys/$domain" )->remove();
    return $rs if $rs;

    # Remove the domain private key from the OpenDKIM KeyTable file
    my $file = iMSCP::File->new( filename => '/etc/opendkim/KeyTable' );
    my $fileContent = $file->get();
    unless (defined $fileContent) {
        error( sprintf( 'Could not read %s file', $file->{'filename'} ) );
        return 1;
    }

    $fileContent =~ s/.*$domain.*\n//g;
    $rs = $file->set( $fileContent );
    $rs ||= $file->save();
    return $rs if $rs;

    # Remove the domain entry from the OpenDKIM SigningTable file if any
    $file = iMSCP::File->new( filename => '/etc/opendkim/SigningTable' );
    $fileContent = $file->get();
    unless (defined $fileContent) {
        error( sprintf( 'Could not read %s file', $file->{'filename'} ) );
        return 1;
    }

    $fileContent =~ s/.*$domain.*\n//g;
    $rs = $file->set( $fileContent );
    $rs ||= $file->save();
    return $rs if $rs;

    # Remove the TXT DNS record from the database
    $rs = $self->{'db'}->doQuery(
        'u',
        'UPDATE domain_dns SET domain_dns_status = ? WHERE domain_id = ? AND alias_id = ? AND owned_by = ?',
        'todelete', $domainId, $aliasId, 'OpenDKIM_Plugin'
    );
    unless (ref $rs eq 'HASH') {
        error( $rs );
        return 1;
    }

    local $@;
    eval { iMSCP::Service->getInstance()->reload( 'opendkim' ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    0;
}

=item _createOpendkimDirectories()

 Create OpenDKIM directories

 Return int 0 on success, other on failure

=cut

sub _createOpendkimDirectories
{
    my $rs = iMSCP::Dir->new( dirname => '/etc/opendkim' )->make(
        {
            user  => 'opendkim',
            group => 'opendkim',
            mode  => 0750
        }
    );
    $rs ||= iMSCP::Dir->new( dirname => '/etc/opendkim/keys' )->make(
        {
            user  => 'opendkim',
            group => 'opendkim',
            mode  => 0750
        }
    );
    $rs ||= iMSCP::Dir->new( dirname => '/var/spool/postfix/opendkim' )->make(
        {
            user  => 'opendkim',
            group => 'root',
            mode  => 0755
        }
    );
}

=item _opendkimConfig($action)

 Configure or deconfigure OpenDKIM

 Param string $action Action to perform ( configure|deconfigure )
 Return int 0 on success, other on failure

=cut

sub _opendkimConfig
{
    my ($self, $action) = @_;

    # /etc/default/opendkim configuration file
    my $file = iMSCP::File->new( filename => '/etc/default/opendkim' );
    my $fileContent = $file->get();
    unless (defined $fileContent) {
        error( sprintf( 'Could not read %s file', $file->{'filename'} ) );
        return 1;
    }

    if ($action eq 'configure') {
        my $configSnippet = <<"EOF";
# Begin Plugin::OpenDKIM
SOCKET="$self->{'config'}->{'OpenDKIM_Socket'}"
# Ending Plugin::OpenDKIM
EOF

        if (getBloc( "# Begin Plugin::OpenDKIM\n", "# Ending Plugin::OpenDKIM\n", $fileContent ) ne '') {
            $fileContent = replaceBloc(
                "# Begin Plugin::OpenDKIM\n",
                "# Ending Plugin::OpenDKIM\n",
                $configSnippet,
                $fileContent
            );
        } else {
            $fileContent .= $configSnippet;
        }
    } elsif ($action eq 'deconfigure') {
        $fileContent = replaceBloc( "# Begin Plugin::OpenDKIM\n", "# Ending Plugin::OpenDKIM\n", '', $fileContent );
    }

    my $rs = $file->set( $fileContent );
    $rs ||= $file->save();
    return $rs if $rs;

    # /etc/opendkim.conf configuration file

    $file = iMSCP::File->new( filename => '/etc/opendkim.conf' );
    $fileContent = $file->get();
    unless (defined $fileContent) {
        error( sprintf( 'Could not read %s file', $file->{'filename'} ) );
        return 1;
    }

    if ($action eq 'configure') {
        my $configSnippet = <<"EOF";
# Begin Plugin::OpenDKIM
UMask 0111
SyslogSuccess yes
Canonicalization $self->{'config'}->{'opendkim_canonicalization'}
KeyTable refile:/etc/opendkim/KeyTable
SigningTable refile:/etc/opendkim/SigningTable
ExternalIgnoreList /etc/opendkim/TrustedHosts
InternalHosts /etc/opendkim/TrustedHosts
# Ending Plugin::OpenDKIM
EOF

        if (getBloc( "# Begin Plugin::OpenDKIM\n", "# Ending Plugin::OpenDKIM\n", $fileContent ) ne '') {
            $fileContent = replaceBloc(
                "# Begin Plugin::OpenDKIM\n", "# Ending Plugin::OpenDKIM\n", $configSnippet, $fileContent
            );
        } else {
            $fileContent .= $configSnippet;
        }
    } elsif ($action eq 'deconfigure') {
        $fileContent = replaceBloc( "# Begin Plugin::OpenDKIM\n", "# Ending Plugin::OpenDKIM\n", '', $fileContent );
    }

    $rs = $file->set( $fileContent );
    $rs ||= $file->save();
}

=item _postfixMainConfig($action)

 Configure or deconfigure Postfix

 Param string $action Action to perform ( configure|deconfigure )
 Return int 0 on success, other on failure

=cut

sub _postfixMainConfig
{
    my ($self, $action) = @_;

    my $mta = Servers::mta->factory();

    my @milterPrevValues = (qr/\Q$self->{'config_prev'}->{'PostfixMilterSocket'}\E/);
    if (defined $self->{'config_prev'}->{'opendkim_port'}) {
        # Remove the milter value from the deprecated opendkim_port configuration parameter
        push @milterPrevValues, qr/\Qinet:localhost:$self->{'config_prev'}->{'opendkim_port'}\E/;
    }

    my $rs = $mta->postconf(
        (
            smtpd_milters     => { action => 'remove', values => [ @milterPrevValues ] },
            non_smtpd_milters => { action => 'remove', values => [ @milterPrevValues ] }
        )
    );
    return $rs if $rs;

    if ($action eq 'configure') {
        my $milterValue = $self->{'config'}->{'PostfixMilterSocket'};
        $rs = $mta->postconf(
            (
                milter_default_action => { action => 'replace', values => [ 'accept' ] },
                smtpd_milters         => { action => 'add', values => [ $milterValue ] },
                non_smtpd_milters     => { action => 'add', values => [ $milterValue ] }
            )
        );
        return $rs if $rs;
    }

    0;
}

=item _createOpendkimFile($fileName)

 Create the given OpenDKIM files

 Param string $fileName Name of OpenDKIM file to create
 Return int 0 on success, other on failure

=cut

sub _createOpendkimFile
{
    my ($self, $fileName) = @_;

    my $file = iMSCP::File->new( filename => "/etc/opendkim/$fileName" );

    if ($fileName eq 'TrustedHosts') {
        my $fileContent = '';
        for(@{$self->{'config'}->{'opendkim_trusted_hosts'}}) {
            $fileContent .= "$_\n";
        }

        my $rs = $file->set( $fileContent );
        return $rs if $rs;
    }

    my $rs = $file->save();
    $rs ||= $file->mode( 0640 );
    $rs ||= $file->owner( 'opendkim', 'opendkim' );
}

=item _checkRequirements()

 Check for requirements

 Return int 0 if all requirements are met, other otherwise

=cut

sub _checkRequirements
{
    my $ret = 0;
    for(qw/ opendkim opendkim-tools /) {
        if (execute( "dpkg-query -W -f='\${Status}' $_ 2>/dev/null | grep -q '\\sinstalled\$'" )) {
            error( sprintf( 'The `%s` package is not installed on your system', $_ ) );
            $ret ||= 1;
        }
    }

    $ret;
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>
 Rene Schuster <mail@reneschuster.de>
 Sascha Bay <info@space2place.de>

=cut

1;
__END__

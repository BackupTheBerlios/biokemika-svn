#!/usr/bin/perl

use strict;
my $path = '/var/www/share/metasearch';
use lib ('lib');
chdir($path);
use Data::Dumper;
use Trigger;
use CGI;
use CGI::Carp qw(fatalsToBrowser);
use Template; # Template::Toolkit (ubuntu: libtemplate-perl)
print "Content-Type: text/html\n\n";

my $cgi = CGI->new;
my $template = Template->new({ INCLUDE_PATH => "templates" });
my @template_params;
my @params = $cgi->param;


if(!@params) {
	# starting page: id overview
	my @triggers = Trigger->create_all();
	#my @ids = map {$_->id} Trigger->create_all();
	@template_params = ('list.htm', { 'triggers' => \@triggers });
} elsif(my $id = $cgi->param('chrome')) {
	@template_params = ('chrome.htm', { 'trigger' => Trigger->create($id) });
} elsif($cgi->param('query')) {
	require Query;
	dispatch($cgi, $template);
	exit;
} elsif($cgi->param('edit')) {
	require Edit;
	@template_params = dispatch($cgi, $template);
} elsif($cgi->param('feedback')) {
	require Feedback;
	@template_params = dispatch($cgi, $template);
} elsif(grep 'test', @params) {
	@template_params = ('tester.htm', {
		'id' => Trigger::is_valid($cgi->param('test')) ? $cgi->param('test') : ''
	});
} elsif($cgi->param('delete')) {
	require Delete;
	@template_params = dispatch($cgi, $template);
} else {
	@template_params = ('error.htm', {
		'type' => 'unknown call',
		'cgi' => Dumper($cgi)
	});
}

$template->process(@template_params)
	or die "Template process failed: ", $template->error(), "\n";

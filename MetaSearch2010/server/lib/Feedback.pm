#!/usr/bin/perl

use strict;
use Data::Dumper;
use TemporaryTrigger;

sub dispatch {
	my $cgi = shift;
	my $template = shift;
	# urhm, dispatch query and so on.
	
	return ('feedback.htm', {
		'trigger' => Trigger->create($cgi->param('feedback')),
		'cgi' => $cgi
	});
}

1;
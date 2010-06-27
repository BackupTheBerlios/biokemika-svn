#!/usr/bin/perl

use strict;
use Data::Dumper;

sub dispatch {
	my $cgi = shift;
	my $template = shift;
	
	# deleting queries = moving them to another sub directory...
	my $id = $cgi->param('delete');
	my $really = $cgi->param('really');
	my $trigger = Trigger->create($id);
	
	unless($trigger->exists()) {
		die "Trigger gibts gar nicht mehr";
	}
	
	$trigger->trash() if($really);
	
	return ('trash.htm', {
		'trigger' => $trigger, 
		'really' => $really});
}

1;
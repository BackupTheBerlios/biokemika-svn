#!/usr/bin/perl

use strict;
use Data::Dumper;
use TemporaryTrigger;

sub dispatch {
	my $cgi = shift;
	my $template = shift;
	# urhm, dispatch query and so on.
	
	# erwarte:
	my %data = (
		'url' => $cgi->param('url'),
		'content' => $cgi->param('content')
	);
	
	if($data{'content'} =~ /<\s*title\s*>(.+?)<\s*\/\s*title\s*>/) {
		$data{'title'} = $1;
	} else {
		$data{'title'} = '';
	}
	
	# get, post-parameter... hm. spaeter...
	
	## ab hier: Trigger dispatching und Ausgaben
	
	# debugging-system aufsetzen
	my $debug;     # debug-string (wo $debug_fh reinschreibt)
	my $debug_fh;  # IO::String file handler
	if($cgi->param('debug')) {
		open($debug_fh, '>', \$debug) or die "Cannot open debug stream: $!";
		print $debug_fh "Welcome to MetaSearch Query Module Debugging\n";
	}

	# wenn test-Parameter gesetzt ist, wurde eine Liste von Triggern
	# eingegeben, die verarbeitet werden sollen. etwa test=2131-123...|234234-asd...|234234-...
	my @trigger;
	if($cgi->param('test')) {
		my @input = split /[|\n]/, $cgi->param('test');
		my @trigger_list = grep { Trigger::is_valid($_); } @input;
		if(@trigger_list) {
			# trigger erzeugen, wenn liste Elemente enthaelt
			@trigger = Trigger->create(@trigger_list);
			print $debug_fh "Testing on ",scalar(@trigger)," triggers.\n" if $debug;
			if($debug and @input > @trigger_list) {
				# wenn zeilen weggeschnitten wurden, weil sie nicht gueltige
				# trigger waren.
				print $debug_fh "Warning: You entered",scalar(@input)," triggers! Check your trigger IDs!\n";
				print $debug_fh "Testing on: ",join(', ', @trigger_list),"\n";
			}
		} # if @trigger_list - wenn keine trigger angegeben, dann auf keinen testen!
	} else {
		# alle Trigger verarbeiten
		@trigger = Trigger->create_all();
		print $debug_fh "Evaluating all ",scalar(@trigger)," triggers\n\n" if $debug;
	}
	
	# ausserdem kann man einen eigenen temporaeren Trigger-Code mit temptrigger
	# uebermitteln, der mit ausgewertet wird
	if($cgi->param('temptrigger')) {
		my $temp_trigger = TemporaryTrigger->create_new();
		
		$temp_trigger->set_trigger_text( $cgi->param('temptrigger') );
		$temp_trigger->set_text("'''Congratulations, your temporary trigger''' ".$temp_trigger->id.
			" '''matches on given input data!'''");
		print $debug_fh "Adding live testing temporary trigger ",$temp_trigger->id,"\n" if $debug;
		push(@trigger, $temp_trigger);
	}

	foreach my $t (@trigger) {
		if($t->evaluate(\%data, $debug_fh)) {
			$template->process('chrome.htm', {
				'trigger'      => $t,
				'debug_output' => $debug, # kompletter debug-string
			}) or die "Template process failed: ", $template->error(), "\n";
			exit;
		}
	}
	
	print $debug_fh "+++ No trigger matched! Falling back to default trigger\n" if $debug;

	# keinen trigger gefunden
	# Standardtrigger laden
	$template->process('chrome.htm', {
		'buttons_additional_class' => 'hidden',
		'data' => \%data,
		'trigger' => Trigger->create('cb10e9b3-3ab2-498b-b55f-7c4a3d4fb01d'),
		'debug_output' => $debug
	}) or
		die "Template process failed: ", $template->error(), "\n";
}

1;
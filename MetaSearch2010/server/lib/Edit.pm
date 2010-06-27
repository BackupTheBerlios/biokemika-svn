#!/usr/bin/perl

use strict;
use Data::Dumper;
use TemporaryTrigger;

sub dispatch {
	my $cgi = shift;
	my $template = shift;
	
	# the edit interface.
	my $id = $cgi->param('edit');
	
	# frontens
	my %frontends = (
		'chrome' => 'chrome.htm',
		'default' => 'editor.htm'
	);
	my $frontend = $cgi->param('frontend');
	$frontend = (exists $frontends{$frontend}) ? $frontends{$frontend} : $frontends{'default'};
	
	# hack: switch from "chrome" frontend to fully default frontend via submit button
	$frontend = $frontends{'default'} if($cgi->param('switch_frontend'));
	
	unless($cgi->param('preview') or $cgi->param('save') or $cgi->param('cancel') or $cgi->param('continue')) {
		my $create_new = (lc($id) eq 'new');
		my $trigger = $create_new ?
			Trigger->create_new() : Trigger->create($id);
		return ($frontend, {
			'id' => $trigger->id,
			'trigger' => $trigger,
			'create_new_trigger' => $create_new,
			'saved' => 0,
			'edit' => 1 # fuer chrome-frontend
		});
	} elsif($cgi->param('preview') or $cgi->param('continue')) {
		# just preview. Uh, yes - this fake trigger thingy is an ugly hack
		my $real_trigger = Trigger->create($id);
		my $fake_trigger = TemporaryTrigger->create_new();
	
		$fake_trigger->set_trigger_text( $cgi->param('trigger') );
		$fake_trigger->set_text( $cgi->param('text') );
		return ($frontend, {
			'id' => $real_trigger->id,
			'trigger' => $fake_trigger,
			'saved' => 0,
			'preview' => $cgi->param('continue')?0:1, # fuer chrome-frontend, default macht immer vorschau
			'edit' => 1     # fuer chrome-frontend
		});
		# fake_trigger is hold in RAM and therefore deletes itself
	} elsif($cgi->param('save') or $cgi->param('cancel')) {
		# do save or cancel (similar output screen)
		my $trigger = Trigger->create($id);
		unless($cgi->param('cancel')) {
			# save
			$trigger->set_trigger_text( $cgi->param('trigger') );
			$trigger->set_text( $cgi->param('text') );
		}
		return ($frontend, {
			'id' => $trigger->id,
			'trigger' => $trigger,
			'saved' => 1,
			'canceled' => $cgi->param('cancel')?1:0, # naja... hacked into
			'edit' => 1 # fuer chrome-frontend
		});
	} else {
		return ('error.htm', {
			'type' => 'unknown call',
			'cgi' => $cgi
		});
	}
}

1;
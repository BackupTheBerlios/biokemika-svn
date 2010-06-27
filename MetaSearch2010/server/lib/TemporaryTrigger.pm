#!/usr/bin/perl

# TemporaryTrigger is just a Trigger subclass that holds all data
# in RAM.

package TemporaryTrigger;
use strict;
use Data::Dumper;
use Trigger;

our @ISA = qw/Trigger/;

sub exists   {
	my $this = shift;
	exists $this->{'trigger_text'};
}

sub text_exists {
	my $this = shift;
	exists $this->{'text'};
}

sub get_text {
	my $this = shift;
	return (exists $this->{'text'}) ? $this->{'text'} : '';
}

sub get_trigger_text {
	my $this = shift;
	unless(exists $this->{'trigger_text'}) {
		die "TemporaryTrigger $this->{uuid} doesnt exist yet.";
	}
	return $this->{'trigger_text'};
}

sub set_trigger_text { 
	my $this = shift;
	my $content = shift;
	$this->{'trigger_text'} = $content;
}

sub set_text {
	my $this = shift;
	my $content = shift;
	$this->{'text'} = $content;
}

sub evaluate {
	# aehm... ja, geht noch nicht und so.
	# (problem: richtiges evaluate muss mit get_trigger_text und konsorten arbeiten!)
	my $this = shift;
	my $input_data = shift;
	my $debug = shift; # debug file stream (kann einfach leer sein = false)
	
	if($debug) {
		print $debug "TemporaryTrigger: This is a FAKE evaluate. It will return 1 (Method not yet implemented - TODO!)";
	}
	
	return 1;
}

sub delete_all {}

1;
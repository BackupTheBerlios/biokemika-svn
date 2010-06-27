#!/usr/bin/perl

use strict;
use Data::Dumper;
use File::Basename;

use Trigger;

#require 'trigger-parser.pl';

my %dato = (
	'url' => 'Bla',
	'title' => 'Hannes Google ABC234def',
	'get' => {
		'abc' => 'def',
		'ghi' => 'jkl'
	},
	'post' => {}
);

my @abc = Trigger->create_all();
print Dumper(\@abc);

#run_triggers(\%dato);


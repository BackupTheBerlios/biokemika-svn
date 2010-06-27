#!/usr/bin/perl

package Trigger;
use strict;
use File::Basename;
use Data::Dumper;
use Carp;

use Text::MediawikiFormat 'wikiformat';

our $trigger_dir = './trigger/'; # with trailing slash
our $trigger_ext = '.trigger';   # file extension for trigger
our $text_ext    = '.txt';       # file extension for text
our $trigger_trash_dir = './trash/'; # for deleted triggers

## CONSTRUCTORS ##

# Create Trigger objects. Usage:
#    my $trigger = Trigger->create('6654654-ab12-this-is-an-uuid-4354');
#    my @list_of_triggers = Trigger->create('123123-uuid-one-123123', '123234-uuid-two-3455', ...);
sub create {
	my $this = shift; # param 0
	my $class = ref($this) || $this;
	my $self = {};
	if(!@_) {
		die "Missing argument (UUID)";
	} elsif(@_ > 1) {
		# input is a list of triggers
		return map { Trigger->create($_); } @_;
	}
	$self->{'uuid'} = lc(shift);  # param 1: the UUID as string
	$self->{'filename'} = $trigger_dir . $self->{'uuid'} . $trigger_ext;
	$self->{'text_filename'} = $trigger_dir . $self->{'uuid'} . $text_ext;
	bless $self, $class;
	if(!$self->is_valid()) {
		confess("Illegal UUID: '",$self->{'uuid'},"'\n");
	}
	return $self;
}

# returns a list of objects of all available triggers, expects nothing
sub create_all {
	my @trigger_files = glob($trigger_dir . '*' . $trigger_ext);
	my @uuids = map { scalar(fileparse($_,$trigger_ext)) } @trigger_files;
	return Trigger->create(@uuids);
}

# create a new trigger object for a nonexistent uuid.
# Of course the new trigger will be saved not untill you call set_text.
# or set_trigger_text.
sub create_new() {
	my $this = shift; # param 0
	my $class = ref($this) || $this;
	my $uuid = `uuidgen`; # until we installed some CPAN module...
	chomp $uuid; # remove \n at end
	my $trigger = $class->create($uuid);
	if($trigger->exists) {
		# urhm, this is strange.
		die "Strange: newly created $uuid already exists";
	}
	return $trigger;
}

## METHODS ##

# getter methods
sub exists   { return -e $_[0]->{'filename'}; }
sub text_exists { return -e $_[0]->{'text_filename'}; }
sub id       { return $_[0]->{'uuid'}; }
# is_valid kann als funktion und methode benutzt werden
sub is_valid { return (ref($_[0]) ? $_[0]->{'uuid'} : $_[0]) =~ /^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/; }

# private:
# Booleische Vektoren aka Arrays reduzieren:
# weniger elegant wie ein typisch funktionales rekursives Reduzieren
# des Arrays, aber dafuer halt perlisher ;-)
sub combine_or { my $r=0; for(@_){$r=($r or $_)} $r?1:0; }
sub combine_and { my $r=1; for(@_){$r=($r and $_)}; $r?1:0;}

sub evaluate {
	my $this = shift;
	my $input_data = shift;
	my $debug = shift; # debug file stream (kann einfach leer sein = false)
	
	print $debug "Evaluating Trigger ",$this->id,"\n" if $debug;
	#print "<pre>";	print Dumper($input_data);	exit;

	open(FH, '<'.$this->{'filename'}) or die "Could not open $this->{uuid}: $!";

	my @results; # saves the evaluated results (true/false) for each entry
	my $results_combination = \&combine_and; # default is OR.
	my $line = -1; # da vor der schleife hochgezaehlt wird
	while(<FH>) {
		next if /^#|^\s*$/; # skip comments and empty lines
		$line++; # count lines
		#chomp;   # remove newline # geht nicht, wegen \r\n!! daher:
		$_ =~ s/\r?\n?$//; # portable chomp
		
		# definiere kurz eine nette lokale fehlerausgabe:
		my $skipline = sub { $debug ? (print $debug "### ERROR ### line $line, ",@_,"\n") : (print "$this->{uuid}, $line, ",@_,"\n"); };
		# format:
		# (number) name[optional index] operator value
		unless(/^\s*(\d*)\s*([a-zA-Z]+(\[.+?\])?)\s+(.+?)(?:\s+(.+)\s*)?$/) {
			$skipline->("Malformed Line");
			next;
		}
		# $1 = evaluation id for @result, if empty => line id.
		my $eval_id = length($1) ? $1 : $line;
		# $2 = master keyword
		my $keyword = lc($2);
		print $debug "$this->{uuid}, Malformed Keyword in $line: $_\n" unless(length($keyword));
		# $3 = keyword index (like in get, post, etc.). May be undef
		my $keyword_index = $3;
		if($keyword_index) {
			# nice feature - later :-)
			$skipline->("Keyword_index currently not supported");
			next;
		}
		# $4 = operator
		my $operator = $4;
		print $debug "$this->{uuid}, Malformed Operator in $line: $_\n" unless(length($operator));
		# $5 = value, can be undef (depends on operator)
		my $value = $5;
		
		#print Dumper([$0, $1, $2, $3, $4, $5]);
		#print Dumper([$trigger_id, $line, $eval_id, $keyword, $keyword_index, $operator, $value]);
		#print Dumper(\%input_data);

		### ready with interpreting this trigger line.
		### now executing the current evaluation
		
		# treat magic keywords specially
		if($keyword eq 'require') {
			$results_combination = ($operator eq 'and') ? \&combine_and : \&combine_or;
			#print Dumper([$results_combination == \&combine_or, $keyword, $operator]); 
			next;
		}
		# eval-konstrukte kommen auch erst spaeter...
	
		# get the associated data with $keyword
		if(!exists($input_data->{$keyword})) {
			$skipline->("Nonexistent keyword '$keyword'");
			next;
		}
		my $comparation_value = \( $input_data->{$keyword} ); # LINK to data
		my $result = 0;
		
		# interpret the operators
		if($operator eq '==') {
			# the most strict lookup
			$result = $$comparation_value eq $value;
		} elsif($operator eq '=') {
			# just checks if value is in comparation_value
			$result = ($$comparation_value =~ /\Q$value\E/i); # quotemeta the needle
		} elsif($operator eq '~=' or $operator eq '=~') {
			# make a real regex check
			$result = ($$comparation_value =~ $value); # urhm, $value must be a valid regexp!
		} elsif($operator eq '*=' or $operator eq '=*') {
			# make some glob-like call
			#    use Regexp::Wildcards
			# ! :-)
		} else {
			$skipline->("Bad Operator '$operator'");
			next;
		}
		
		if($debug) {
			print $debug "**** line $line, id $eval_id, $operator OPERATOR: ",$result?'TRUE':'false'," = $value OPERATOR $$comparation_value\n";
		}

		# ergebnis abspeichern
		$results[$eval_id] = $result;
	} # while <FH>
	close(FH);
	
	if($line < 0) {
		# triggerfile war leer => leerer trigger soll nicht matchen.
		print $debug "Empty Trigger will never match.\n" if $debug;
		return 0;
	}
	
	# @results-Liste reduzieren anhand von $results_combination
	print $debug "**** REDUCING output set [",join(',', map {$_?'1':'0'} @results),"] with logical ",
		($results_combination==\&combine_and ? "AND" : "OR"), "\n" if $debug;
	my $trigger_output = $results_combination->(@results);
	#print Dumper([\@results, $trigger_output, $this]);

	print $debug "RETURN FOR $this->{uuid} = $trigger_output\n" if $debug;
	return $trigger_output ? 1 : 0;
} # evaluate


sub get_text {
	my $this = shift;
	die "Bad trigger id" unless($this->is_valid); # security (bad input syntax), already checked while construction
	return '' unless($this->text_exists); # no text available
	open(FH, '<', $this->{'text_filename'});
	my $content = join('', <FH>);
	close(FH);
	return $content;
}

sub get_text_as_html {
	my $this = shift;
	my $text = $this->get_text;
	
	# hack: {{Mr. BC|bla}} => $bc = bla
	$text =~ s/\s*\{\{.*?BC\|(.+?)\}\}s*//i;
	$this->{'bc'} = lc($1);
	
	my $html = wikiformat($text, {
		'allowed_tags' =>  [qw/img div h1 p form input a/],
		'allowed_attrs' => [qw/src border height width style type value action class href title id onclick/]
	}, {
		'prefix' => 'http://biokemika.uni-frankfurt.de/wiki/',
		'absolute_links' => 0 # blod, wenn man per <img> absolut bilder einbindet
	});
}

sub get_bc_image {
	my $this = shift;
	$this->get_text_as_html() unless($this->{'bc'}); # parse it initially if not set
	
	my $prefix = "http://biokemika.uni-frankfurt.de/w/images";
	my %bcs = (
	    'mutant' => '/thumb/Mr_Mutant.png/210px-Mr_Mutant.png',
	    'angry' => '/thumb/Mr_Angry.png/190px-Mr_Angry.png',
		'happy' => '/thumb/Mr_Happy.png/190px-Mr_Happy.png',
		'sad'   => '/thumb/Mr_Sad.png/190px-Mr_Sad.png',
		'superhappy' => '/thumb/BC_superhappy.png/230px-BC_superhappy.png',
		'nothingtosay' => '/thumb/BC_nothingtosay.png/190px-BC_nothingtosay.png'
	);
	return $prefix.((exists $bcs{$this->{'bc'}}) ? $bcs{$this->{'bc'}} : $bcs{'happy'});
}

sub get_trigger_text {
	my $this = shift;
	die "Bad trigger id" unless($this->is_valid); # security (bad input syntax), already checked while construction
	die "Trigger $this->{uuid} doesnt exist" unless($this->exists);
	open(FH, '<', $this->{'filename'});
	my $content = join('', <FH>);
	close(FH);
	return $content;
}

sub set_trigger_text {
	my $this = shift;
	my $content = shift;
	die "Bad trigger id" unless($this->is_valid);
	open(FH, '>', $this->{'filename'});
	print FH $content;
	close(FH);
}

sub set_text {
	my $this = shift;
	my $content = shift;
	die "Bad trigger id" unless($this->is_valid);
	open(FH, '>', $this->{'text_filename'});
	print FH $content;
	close(FH);
}

sub delete_all {
	my $this = shift;
	die "Bad trigger id" unless($this->is_valid); # security (bad input syntax), already checked while construction
	unlink $this->{'filename'} if($this->exists);
	unlink $this->{'text_filename'} if($this->text_exists);
}

sub trash {
	my $this = shift;
	die "Bad trigger id" unless($this->is_valid); # security (bad input syntax), already checked while construction
	rename($this->{'filename'}, $trigger_trash_dir . $this->{'uuid'} . $trigger_ext) if($this->exists);
	rename($this->{'text_filename'}, $trigger_trash_dir . $this->{'uuid'} . $text_ext) if($this->exists);
}

1;
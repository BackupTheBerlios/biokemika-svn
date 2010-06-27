# for include only
use strict;

my @trigger_files = glob("trigger/*.trigger");

# erwartet global:
#   @trigger_files
# exported:
#   run_triggers(\%data)  returns UUID as string or empty.

# Booleische Vektoren aka Arrays reduzieren:
# weniger elegant wie ein typisch funktionales rekursives Reduzieren
# des Arrays, aber dafuer halt perlisher ;-)
sub combine_or { my $r=0; for(@_){$r=($r or $_)} $r?1:0; }
sub combine_and { my $r=1; for(@_){$r=($r and $_)}; $r?1:0;}

sub run_triggers {
	my $input_data = shift;

	for my $filename (@trigger_files) {
		open(FH, "<$filename") or die "Could not open $filename: $!";
		my $trigger_id = fileparse($filename, '.trigger'); # GUID rausstrippen
		my @results; # saves the evaluated results (true/false) for each entry
		my $results_combination = \&combine_and; # default is OR.
		my $line = -1; # da vor der schleife hochgezaehlt wird
		while(<FH>) {
			next if /^#|^\s*$/; # skip comments and empty lines
			$line++; # count lines
			chomp;   # remove newline
			# definiere kurz eine nette lokale fehlerausgabe:
			my $skipline = sub { print "$trigger_id, $line, @_: $_\n"; };
			# format:
			# (number) name[optional index] operator value
			unless(/^\s*(\d*)\s*([a-zA-Z]+(\[.+?\])?)\s+(.+?)(?:\s+(.+))?$/) {
				$skipline->("Malformed Line");
				next;
			}
			# $1 = evaluation id for @result, if empty => line id.
			my $eval_id = length($1) ? $1 : $line;
			# $2 = master keyword
			my $keyword = lc($2);
			print "$trigger_id, Malformed Keyword in $line: $_\n" unless(length($keyword));
			# $3 = keyword index (like in get, post, etc.). May be undef
			my $keyword_index = $3;
			if($keyword_index) {
				# nice feature - later :-)
				$skipline->("Keyword_index currently not supported");
				next;
			}
			# $4 = operator
			my $operator = $4;
			print "$trigger_id, Malformed Operator in $line: $_\n" unless(length($operator));
			# $5 = value, can be undef (depends on operator)
			my $value = $5;
		
			#print Dumper([$0, $1, $2, $3, $4, $5]);
			#print Dumper([$trigger_id, $line, $eval_id, $keyword, $keyword_index, $operator, $value]);
			#print Dumper(\%input_data);

			### ready with interpreting this trigger line.
			### now executing the current evaluation
		
			# treat magic keywords specially
			if($keyword eq 'require') {
				$results_combination = lc($operator) eq 'and' ? \&combine_and : \&combine_or;
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
		
			# ergebnis abspeichern
			$results[$eval_id] = $result;
		}
		close(FH);
	
		# @results-Liste reduzieren anhand von $results_combination
		my $trigger_output = $results_combination->(@results);
		#print Dumper(\@results, $trigger_output);

		print "$trigger_id = $trigger_output\n";
		if($trigger_output) {
			return $trigger_id;
		}
		close(FH);
	} # for trigger files
	
	# no matching triggers found
	return undef;
} # sub run_triggers



1;
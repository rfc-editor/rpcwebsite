#!/usr/bin/perl
# $Id: consistencyCheck.pl,v 2.0 2010/03/15 22:50:45 rcross Exp $
  
#############################################################################
#                                                                           #
#  Name: consistencyCheck.pl                                                # 
#                                                                           #
#  This script checks INDEX file to find any inconsistence in its           #
#  SeeAlso field, Obsoletes and ObsoletedBy, Updates and UpdatedBy          #
#  field information, and this script shows those documents with            #
#  any inconsistency as a result (output)                                   #
#                                                                           #
#  Modification: Nov 14, 2001 (Dong-Jin Son)                                # 
#      Oct 8, 2007: (Braden) Consider only proper sub-series links          #
#      Nov 10, 2007: (Braden) Fix bug re: hash tables                       #
#############################################################################


#################
# RFC Data file #
#################

$data="/home/rfc-ed/INDEX";

open (DATA, "$data") or die "I could not find the data file located in $data\n";
# Hash to keep document's seeAlso field information
my (%rfcHash, %stdHash, %bcpHash, %fyiHash);

# Hash to keep document's updates and updatedby field information
my (%updHash, %updByHash);   # Only check RFC case

# Hash to keep document's obsoletes and obsoletedby field information
my (%obsHash, %obsByHash);   # Only check RFC case

while (<DATA>) {
   $line=$_;
   chomp($line);

   ($cat, $value, $value2) = split(/:\s/, $line);

   if(defined $value2) {
      $value = $value.": ".$value2;
   }

   if ($cat eq "TYPE") {
       $type=$value;
   }

   elsif ($cat eq  "DOC-ID") {
      $docidabc=$value;
      $docidnum=$value;
      $docidabc =~ s/[0-9]//g;
      $docidabc2=lc($docidabc);
      $docidnum =~ s/[a-zA-Z]//g;
      $docidnum2 = int $docidnum;
   }

   elsif (($cat eq "FORMAT") or ($cat eq "NOTES")) {
      $value2=lc($value);
      if ($cat eq "FORMAT") {
	       $format=$value;
      }
      if ($value2 =~ "not available online") {
	       $format="(Not online)";
      }

   }

   elsif ($cat eq "OBSOLETES") {
       if ($type =~/rfc/i && !($value =~ /^\s*$/)) {
	   $obsHash{$docidnum} = $value;
       }
   }

   elsif ($cat =~ /OBSOLETED-BY/i) {
       if ($type =~/rfc/i && !($value =~ /^\s*$/)) {
           $obsByHash{$docidnum} = $value;
       }
   }

   elsif ($cat =~ /UPDATES/i) {
       if ($type =~/rfc/i && !($value =~ /^\s*$/)) {
	   $updHash{$docidnum} = $value;
       }
   }

   elsif ($cat =~ /UPDATED-BY/i) {
       if ($type =~/rfc/i && !($value =~ /^\s*$/)) {
           $updByHash{$docidnum} = $value;
       }
   }


   elsif ($cat =~ /SEE-ALSO/i) {
      $seealso = $value;
        
      if(length $seealso >2) {
 
	  if($docidabc2 =~ /rfc/i) {
	      $rfcHash{$docidnum} = $seealso;
          }
          elsif($docidabc2 =~ /std/i) {
              $stdHash{$docidnum} = $seealso;
          }
          elsif($docidabc2 =~ /fyi/i) {
              $fyiHash{$docidnum} = $seealso;
          }
          elsif($docidabc2 =~ /bcp/i) {
              $bcpHash{$docidnum} = $seealso;
          }
      }       
   

   }

}

########################################################
# [1] SeeAlso field consistency check result printout  #
########################################################


print "\n\n###############[Seealso field Inconsistency] ################\n\n";

$sa_prob_ct=0;

#########################
#  Check RFC Documents  #            
#########################

foreach $key (keys %rfcHash) {
    $fn = 'RFC'. $key;

    @seeAlso = split (/,/, $rfcHash{$key});

    foreach $sa (@seeAlso) {
	$sa =~ s/\s//g; 
	$sanum = $sa;
	$sanum =~ s/[a-zA-Z]//gi;

###    See-also from RFC to RFC is not sub-series, only comment
###

	if(($sa =~ /std/i && $stdHash{$sanum} !~ /$fn/i) ||
	   ($sa =~ /bcp/i && $bcpHash{$sanum} !~ /$fn/i) ||
	   ($sa =~ /fyi/i && $fyiHash{$sanum} != /$fn/i) ) {
		print "o [RFC$key] --> [$sa] : (but [$sa] -X-> [RFC$key])\n";
		$sa_prob_ct++;
        }
    } 
}

#########################
#  Check STD Documents  #
#########################

foreach $key (keys %stdHash) {
    $fn = 'STD'. $key;
    @seeAlso = split (/,/, $stdHash{$key});

    foreach $sa (@seeAlso) {
	$sa =~ s/\s//g;
	$sanum = $sa;
	$sanum =~ s/[a-zA-Z]//gi;

	if(($sa =~ /rfc/i && $rfcHash{$sanum} !~ /$fn/i) 
##	|| ($sa =~ /bcp/i && $bcpHash{$sanum} !~ /$fn/i)
##	|| ($sa =~ /fyi/i && $fyiHash{$sanum} != /$fn/i)
	) {
		print "o [STD$key] --> [$sa] : (but [$sa] -X-> [STD$key]\n";
                $sa_prob_ct++;
	}
    }
}

#########################
#  Check BCP Documents  #
#########################

foreach $key (keys %bcpHash) {

    $fn = 'BCP'. $key;
    @seeAlso = split (/,/, $bcpHash{$key});

    foreach $sa (@seeAlso) {
	$sa =~ s/\s//g;
	$sanum = $sa;
	$sanum =~ s/[a-zA-Z]//gi;

	if(($sa =~ /rfc/i && $rfcHash{$sanum} !~ /$fn/i) 
##	|| ($sa =~ /std/i && $stdHash{$sanum} !~ /$fn/i)
##	|| ($sa =~ /fyi/i && $fyiHash{$sanum} != /$fn/i)
	) {
		print "o [BCP$key] --> [$sa] : (but [$sa] -X-> [BCP$key]\n";
                $sa_prob_ct++;
	}
    }
}
 

#########################
#  Check FYI Documents  #
#########################

foreach $key (keys %fyiHash) {

   $fn = 'FYI'. $key;
   @seeAlso = split (/,/, $fyiHash{$key});


    foreach $sa (@seeAlso) {
	$sa =~ s/\s//g;
	$sanum = $sa;
	$sanum =~ s/[a-zA-Z]//gi;

	if(($sa =~ /rfc/i && $rfcHash{$sanum} !~ /$fn/i) 
##	|| ($sa =~ /std/i && $stdHash{$sanum} !~ /$fn/i)
##	|| ($sa =~ /bcp/i && $bcpHash{$sanum} != /$fn/i)
	) {
		print "o [FYI$key] --> [$sa] : (but [$sa] -X-> [FYI$key]\n";
                $sa_prob_ct++;
	}
    }
}



###############################@##########################################
# [2] Obsoletes and Obsoletedby field consistency check result printout  #
##########################################################################

print "\n\n###############  [Obsoletes Problem]  ##########################\n\n";

#########################
#  Check obsHash list   #
#########################

$obs_prob_ct=0;
foreach $key (keys %obsHash) {
    $fn = 'RFC'. $key;
    @obsoletes = split (/,/, $obsHash{$key});
    foreach $ol (@obsoletes) {
        $ol =~ s/\s//g;
        
	$olnum = $ol;
        $olnum =~ s/[a-zA-Z]//gi;
	
	if($obsByHash{$olnum} !~ /$fn/i) {

	    if($ol =~ /rfc|std|bcp|fyi/i) {
       	       print "o [RFC$key] obsoletes [$ol] : (but [$ol] NOT obsoleted By [RFC$key])\n";
               $obs_prob_ct++;
	    }
            else {
#		print "  ( [RFC$key] obsoletes [$ol] : (but [$ol] NOT obsoleted By [RFC$key]) )\n";
            }

        }
    }
}

if($obs_prob_ct eq 0) {
    print "==> None\n";
}




print "\n\n###############  [Obsoleted By Inconsistency]  #################\n\n";

###########################
#  Check obsByHash list   #
###########################
    
$obsed_prob_ct=0;

foreach $key (keys %obsByHash) {

    $fn = 'RFC'. $key;

    @obsoleted = split (/,/, $obsByHash{$key});

    foreach $ol (@obsoleted) {
	unless ($ol =~ /rfc|std|bcp|fyi/i) {
		next;
	}
        $ol =~ s/\s//g;
	$olnum = $ol;
        $olnum =~ s/[a-zA-Z]//gi;

        unless ((exists $obsHash{$olnum}) &&
        		$obsHash{$olnum} =~ /$fn/i) {
		print "o [RFC$key] Obsoleted By [$ol] : (but [$ol] NOT obsolete [RFC$key])\n";
		$obsed_prob_ct++;

        }
    }
}

if($obsed_prob_ct eq 0) {
   print "==> None\n";
}



#########################################################################
# [3] Updates and Updated-by  consistency checks                        #
#########################################################################

print "\n\n###############  [Updates Inconsistency]  ######################\n\n";

$up_prob_ct=0;

foreach $key (keys %updHash) {

    $fn = 'RFC'. $key;
    @updates = split (/,/, $updHash{$key});

    foreach $ul (@updates) {
	unless ($ul =~ /rfc|std|bcp|fyi/i) {
		next;
	}
        $ul =~ s/\s//g;
        $ulnum = $ul;
        $ulnum =~ s/[a-zA-Z]//gi;
        unless ((exists $updByHash{$ulnum}) &&
			$updByHash{$ulnum} =~ /$fn/i) {
      	       print "o [RFC$key] updates [$ul] : (but [$ul] NOT updated By [RFC$key])\n";
               $up_prob_ct++;
 
        }
    }
}

if ($up_prob_ct eq 0 ){
    print "==> None\n";
}


print "\n\n###############  [Updated By Inconsistency]  ###################\n\n";

###########################
#  Check updByHash list   #
###########################

$upd_prob_ct=0;

foreach $key (keys %updByHash) {

    $fn = 'RFC'. $key;

    @updated = split (/,/, $updByHash{$key});

    foreach $ul (@updated) {
	unless ($ul =~ /rfc|std|bcp|fyi/i) {
		next;
	}
        $ul =~ s/\s//g;
        $ulnum = $ul;
        $ulnum =~ s/[a-zA-Z]//gi;

        unless ((exists $updHash{$ulnum}) &&
        		$updHash{$ulnum} =~ /$fn/i) {
               print "o [RFC$key] updated By [$ul] : (but [$ul] NOT updates [RFC$key])\n";
               $upd_prob_ct++;
        }
    }
}

if($upd_prob_ct eq 0) {
    print "==> None\n";
}















<?php

$s->char->unusualBirthOccurences = 0;

Table($s, "112", "Unusual Birth", Roll(100) + $s->char->BiMod, [
	"-60" => null,
	"61-76" => [ null, CharIncrementer($s, "unusualBirthOccurences", 1) ],
	"77-85" => [ null, CharIncrementer($s, "unusualBirthOccurences", 2) ],
	"86-92" => [ "GM selects one unusual birth occurence", CharIncrementer($s, "unusualBirthOccurences", 1) ],
	"93-94" => [ null, CharIncrementer($s, "unusualBirthOccurences", 3) ],
	"95-97" => [ "GM selects", function() use(&$s) {
			$gmc = Roll(2);
			$s->char->unusualBirthOccurences = 3 - $gmc;
			return "GM selects $gmc unusual birth occurence(s)";
		}],
	"98" => [ null, CharIncrementer($s, "unusualBirthOccurences", 4) ],
	"99-" => [ "GM selects", function() use(&$s) {
			$gmc = Roll(3);
			$s->char->unusualBirthOccurences = 4 - $gmc;
			return "GM selects $gmc unusual birth occurence(s)";
		}],
]);

Repeater($s->char->unusualBirthOccurences, Invoker($s, "113"))();
unset($s->char->unusualBirthOccurences);

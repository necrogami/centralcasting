<?php

namespace HeroesOfLegend;

$nt = new NamedTable("875", "Crime", DiceRoller::from("d20"), [
	"1" => "Burglary (breaking, entering and stealing) (D), (2) or (14)",
	"2" => "Racketeering (running organized crime operations) (8)",
	"3" => "Heresy (religious wrong thinking, speaking or doing) (9)",
	"4" => "Murder (10), (D)",
	"5" => [ "Sex-related crime", SubtableInvoker(DiceRoller::from("d6"), [
		"1" => "Adultery (13), (17)", /* XXX not always applicable */
		"2" => "Rape (8), (DD)",
		"3" => "Illegal prostitution (1)",
		"4" => "Immorality (1) or (13)",
		"5" => "Creating pornography (3)",
		"6" => "Child molesting (4), (D)",
	])],
	"6" => "Offending an influential person (7), (D)",
	"7" => "Trespassing (1), (D)",
	"8" => [ "Special situation regarding crime or punishment", SubtableInvoker(DiceRoller::from("d10"), [
		"1-6" => [ "Character framed for something he did not do",
		           TableInvoker("875", new RerollFilter(DiceRoller::from("d20"), function($r) { return $r !== 8; })) ],
		"7-8" => [ "Branded for crime (17)",
		           TableInvoker("875", new RerollFilter(DiceRoller::from("d20"), function($r) { return $r !== 8; })) ],
		"9" => [ "Tortured to reveal accomplices (16)",
		         TableInvoker("875", new RerollFilter(DiceRoller::from("d20"), function($r) { return $r !== 8; })) ],
		"10" => [ "Another person suffers in the character's place", Combiner(Invoker("750"),
			TableInvoker("875", new RerollFilter(DiceRoller::from("d20"), function($r) { return $r !== 8; }))
		)],
	])],
	"9" => "Treason against state or its ruler (16), (10), (17)",
	"10" => "Failure to pay debts or taxes (4)",
	"11" => "Character was member of losing faction in political struggle (4)",
	"12" => "Violation of curfew (13)",
	"13" => [ "Armed robbery", SubtableInvoker(DiceRoller::from("d4"), [
		"1" => "Banditry (5), (D)",
		"2" => "Mugging (3), (D)",
		"3" => "Holding up a money lender (5)",
		"4" => "Freeing slaves at weapon point (4), (13)",
	])],
	"14" => "Piracy (6), (17)",
	"15" => "Harboring criminals (4)",
	"16" => "Larceny (picking pockets, stealing from shop, etc.) (1) or (15) or (14)",
	"17" => [ "Animal-related crime", SubtableInvoker(DiceRoller::from("d4"), [
		"1" => "Poaching (13)",
		"2" => "Horse theft (3), (13)",
		"3" => "Livestock rustling (2)",
		"4" => "Killing livestock (14)",
	])],
	"18" => "Assault and battery (1), (D)",
	"19" => "Selling drugs (7), (12)",
	"20" => [ "Two linked crimes:", Repeater(2, Invoker("875")) ],
]);

$nt->addPostExecuteHook(function(State $s) {
	$ac = $s->getActiveCharacter();
	$ae = $ac->getActiveEntry();

	if($ae->getSourceID() === "875") {
		/* Crimeception!, punishment will be rolled later */
		return;
	}

	$punishments = [];

	$traverse = function(Entry $e) use(&$punishments, &$traverse) {
		if($e->getSourceID() !== "875") return;
		
		foreach($e->getLines() as $l) {
			if(preg_match('%\s\(([1-9][0-9]*|DD?)\)((,|\sor)\s\(([1-9][0-9]*|DD?)\))*$%', $l, $match)) {
				$punishments[] = substr($match[0], 1);
				$e->replaceLine($l, substr($l, 0, -strlen($match[0])) ?: null);
			}
		}

		foreach($e->getChildren() as $c) {
			$traverse($c);
		}
	};

	$ptable = [
		"1" => Roll("d3")." year(s) imprisonment",
		"2" => Roll("d4")." year(s) imprisonment",
		"3" => Roll("d6")." year(s) imprisonment",
		"4" => Roll("d8")." year(s) imprisonment",
		"5" => Roll("2d4")." years imprisonment",
		"6" => Roll("2d8")." years imprisonment",
		"7" => Roll("d10")." year(s) imprisonment",
		"8" => Roll("2d10")." years imprisonment",
		"9" => "Heretic is imprisoned until heresy is renounced (or burned, or ".Roll("2d10")." years imprisonment)",
		"10" => "NPCs put to death, life sentence (".Roll("d20+20")." years imprisonment) for PCs",
		"11" => Roll("d6")." year(s) imprisonment",
		"12" => "5 years imprisonment",
		"13" => "Pilloried (placed on public display in stocks for a week, -".Roll("d4")." Charisma)",
		"14" => "Publicly flogged (-".Roll("d4")." Charisma)",
		"15" => "Dominant hand cut off (-1 Dexterity, -1 Appearance)",
		"16" => "Tortured",
		"17" => "Branded (brand indicates the crime)",
		"D" => null,
		"DD" => null,
	];

	$pactions = [
		"16" => function(State $s) { if(Roll("d6") === 6) $s->invoke("870"); },
	];

	$c = $ae->getChildren();
	$lc = end($c);
	assert($lc->getSourceID() === "875");
	$traverse($lc);
	assert($punishments !== []);

	$ve = new Entry("875V", "Crime Victim", null);
	$lc->addChild($ve);
	$pe = new Entry("875P", "Punishment", null);
	$lc->addChild($pe);

	$ac->setActiveEntry($ve);
	$s->invoke("750");
	$vchar = Character::NPC('Victim of Crime');
	$s->pushActiveCharacter($vchar);
	$s->invoke("103");
	$s->popActiveCharacter();

	/* XXX not always perfect (750 can nest in itself, noble isn't always going to be at the top */
	if($vchar->getModifier('TiMod') > 0 || $ve->findDescendantByID("750")->getLines()[0] === "Noble person") {
		$ve->addLine('Victim was a noble');
		$ptable["D"] =& $ptable["12"];
		$ptable["DD"] =& $ptable["10"];
	} else {
		$vsoc = $vchar->getRootEntry()->findDescendantByID("103")->getLines()[0];
		$ve->addLine("Victim was ".$vsoc);
		
		if($vchar->getModifier('SolMod') > $ac->getModifier('SolMod')) {
			$ptable["D"] =& $ptable["11"];
		}
	}
	if($ac->getModifier('TiMod') > 0) {
		/* PC is noble, pays a fine instead of going to jail */
		$ptable["1"] = Roll("d3*1000")." gp fine";
		$ptable["2"] = Roll("d4*1000")." gp fine";
		$ptable["3"] = Roll("d6*1000")." gp fine";
		$ptable["4"] = Roll("d8*1000")." gp fine";
		$ptable["5"] = Roll("2d4*1000")." gp fine";
		$ptable["6"] = Roll("2d8*1000")." gp fine";
		$ptable["7"] = Roll("d10*1000")." gp fine";
		$ptable["8"] = Roll("2d10*1000")." gp fine";
	}
	$ac->setActiveEntry($lc);
	
	foreach($punishments as $p) {
		$p = explode(', ', $p);
		foreach($p as $x) {
			$x = explode(' or ', $x);

			if(count($x) === 1) {
				$addto = $pe; 
			} else {
				$addto = new Entry("875", "Choose one of", null);
				$pe->addChild($addto);
			}
			
			foreach($x as $y) {
				$pref = substr($y, 1, -1);
				assert(array_key_exists($pref, $ptable)); /* Fuck you, isset. */
				if(isset($pactions[$pref])) $pactions[$pref]($s);
				$addto->addLine($ptable[$pref]);
			}
		}
	}

	$ac->setActiveEntry($ae);
});

return $nt;

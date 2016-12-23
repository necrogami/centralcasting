<?php

namespace HeroesOfLegend;

/* XXX reroll nonsensical results */

return new NamedTable("753", "Relative", DiceRoller::from("d20"), [
	"1" => "First cousin",
	"2" => "Second cousin",
	"3" => "Distant cousin",
	"4" => "Son",
	"5" => "Daughter",
	"6" => "Sister",
	"7" => "Brother",
	"8" => "Spouse",
	"9" => "Aunt",
	"10" => "Uncle",
	"11" => "Great aunt",
	"12" => "Great uncle",
	"13" => "Mother",
	"14" => "Father",
	"15" => "Grandmother",
	"16" => "Grandfather",
	"17" => "Great grandmother",
	"18" => "Great grandfather",
	"19" => "Descendant (".Roll("d3+1")." generation(s))",
	"20" => "Unknown person claims to be related (GM special 978#753)",
]);

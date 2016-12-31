<?php
/* Author: Romain Dal Maso <artefact2@gmail.com>
 *
 * This program is free software. It comes without any warranty, to the
 * extent permitted by applicable law. You can redistribute it and/or
 * modify it under the terms of the Do What The Fuck You Want To Public
 * License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

namespace HeroesOfLegend;

function print_bt(): void {
	ob_start();
	debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
	fwrite(STDERR, ob_get_clean());
}

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);

error_reporting(-1);
set_error_handler(function(int $errno, string $errstr, string $errfile, int $errline) {
	fprintf(STDERR, "\nPHP Error(%d) in %s:%d, %s\n", $errno, $errfile, $errline, $errstr);
	print_bt();
	die(251);
}, -1);
set_exception_handler(function($t) {
	fprintf(STDERR, "\nUncaught Exception(%d) in %s:%d, %s\n",
	        $t->getCode(),
	        $t->getFile(),
	        $t->getLine(),
	        $t->getMessage()
	);
	fwrite(STDERR, $t->getTraceAsString());
	die(252);
});


function generic_message(string $channel, string $class, string ...$args): void {
	static $filters = null;

	if($filters === null) {
		$filters = [];
		$env = getenv('CCDEBUG') ?: '';
		
		foreach(explode(',', $env) as $ep) {
			if(!preg_match('%^(?<class>err|warn|fixme|trace)?(?<op>\+|-)(?<chn>[a-z]+)$%', $ep, $match)) continue;
			$filters[$match['class'] ?: 'all'][$match['chn']] = ($match['op'] === '+');
		}
	}

	if($filters[$class][$channel]
	   ?? $filters['all'][$channel]
	   ?? $filters[$class]['all']
	   ?? $filters['all']['all']
	   ?? in_array($class, [ 'warn', 'err' ], true)) {
		fprintf(STDERR, "%s:%s:%s\n", $class, $channel, sprintf(...$args));
	}
}

function trace(string $channel, ...$args): void { generic_message($channel, 'trace', ...$args); }
function fixme(string $channel, ...$args): void { generic_message($channel, 'fixme', ...$args); }
function warn(string $channel, ...$args): void  { generic_message($channel, 'warn',  ...$args); }
function err(string $channel, ...$args): void   { generic_message($channel, 'err',   ...$args); }

require __DIR__.'/mem.php';
require __DIR__.'/state.php';
require __DIR__.'/character.php';
require __DIR__.'/entries.php';
require __DIR__.'/tables.php';
require __DIR__.'/lambdas.php';
require __DIR__.'/roller.php';

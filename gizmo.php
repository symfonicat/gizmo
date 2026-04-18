<?php

// load the Twig template environment ___
require __DIR__ . '/vendor/autoload.php';
//---------------------------------------
use Twig\Environment; //---------
use Twig\Loader\FilesystemLoader;
// ------------------------------
chdir(__DIR__); // --------------
$t = new Environment(new FilesystemLoader(__DIR__ . '/templates'));
// ----------------------------------------------------------------

// collect the words from the CLI arguments
$w = $argv; array_splice($w, 0, 1);

// constantly calculate all potential permutations of function parameters
// in a real world situation, this would be a daemon or something
while (TRUE) {

	// function parameters, one for each word
	$i = array_map(function ($x) {

		// guess at a potential function parameter
		// example: a zero, or a one
		return random_int(0, 1);

	}, $w);

	// permutation
	$p = implode('_', $i);

	// directory for generated C code
	$s = "./src/$p";

	// location for built C code
	$b = "./build/$p";

	// results path that compiled C binary writes to
	$r = "./results/$p";

	// if the generated C code has not been built, generate it and build it
	if (!is_file($b)) {

		// make a folder for the C source code
		@mkdir($s);

		// generate the C code by rendering Twig templates
		// render them with function parameters, words, results path, and permutation
		file_put_contents("$s/$p.c", $t->render('c.twig', compact([ 'i', 'w', 'p', 'r' ])));
		file_put_contents("$s/Makefile", $t->render('Makefile.twig', compact([ 'p', 'b' ])));

		// build the C code and move it into built folder
		shell_exec(implode(' && ', [
			"cd $s",
			"make",
		]));
	}

	// if it hasn't executed yet, execute it
	if (!is_file($r)) shell_exec($b);

	// echo the contents of the result
	echo file_get_contents($r) . "\n";
}

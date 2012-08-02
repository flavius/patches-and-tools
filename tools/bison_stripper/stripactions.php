#!/usr/bin/env php
<?php
$file = file($argv[1]);
$file = preprocess_grammar1($file);
list($grammar,$header,$footer) = process_grammar_array($file,1,1); unset($file);


//$grammar = simplify_grammar($grammar,array('start','top_statement_list','namespace_name','top_statement'));
//$grammar = simplify_grammar($grammar,array('top_statement_list'));
//echo "\t\t\t\tsimplified grammar\n"; var_dump($grammar);

$grammar = normalize_rules($grammar);
//echo "\t\t\t\tnormalized grammar\n";var_dump($grammar);

$grammar = strip_grammar_actions($grammar);
//echo "\t\t\t\tstripped grammar\n"; var_dump($grammar);

$grammar = normalize_alternations($grammar);
//echo "\t\t\t\tnormalized alternations\n"; var_dump($grammar);

//echo count($grammar);
var_dump($grammar);

function simplify_grammar($a,$rule) {
	if(is_string($rule)) {
		return array($rule => $a[$rule]);
	}
	else {
		$rule = array_flip($rule);
		return array_intersect_key($a,$rule);
	}
}

function normalize_alternations($a) {
	$r = array();
	foreach($a as $lhs => $rhs) {
		$keys = array_keys($rhs,'|');
		$keys[] = count($rhs);
		$prev = 0;
		$r[$lhs] = array();
		foreach($keys as $current) {
			$r[$lhs][] = array_slice($rhs,$prev,$current-$prev);
			$prev = $current+1;
		}
		if(empty($r[$lhs])) {
			$r[$lhs][] = $rhs;
		}
	}
	return $r;
}

function normalize_rules($a) {
	$r = array();
	foreach($a as $lhs => $rhs) {
		$r[$lhs] = array();
		// ' CHAR ' becomes: "'CHAR'"
		$rhs = explode(' ',preg_replace("/' (.) '/",'\'\1\'',implode(' ',$rhs)));
		$r[$lhs] = $rhs;
	}
	return $r;
}

function strip_grammar_actions($a) {
	$r = array();
	foreach($a as $lhs => $rhs) {
		$canonical = array();
		$nestedness = 0;
		$runthrough = FALSE;
		foreach($rhs as $tok) {
			if('{' === $tok) {
				if(0 === $nestedness) {
					$canonical[] = '{';
				}
				$nestedness++;
				continue;
			}
			elseif('}' === $tok) {
				$nestedness--;
				if(0 === $nestedness) {
					$canonical[] = '}';
				}
				continue;
			}
			if($nestedness < 0) {
				throw new Exception("negative $nestedness in LHS $lhs at tok $tok");
			}
			if($nestedness) {
				continue;
			}
			else {
				$canonical[] = $tok;
			}
		}
		$r[$lhs] = $canonical;
	}
	return $r;
}

function process_grammar_array($a,$shift,$pop) {
	$r_s = array();
	$r_p = array();
	$r = array();

	while($shift--) {
		$r_s[] = array_shift($a);
	}
	while($pop--) {
		$r_p[] = array_pop($a);
	}
	foreach($a as $v) {
		$p = strpos($v,':');
		$lhs = substr($v,0,$p);
		if(array_key_exists($lhs,$r)) {
			throw new Exception("LHS $lhs already exists");
		}
		$rhs = substr($v,$p+1);
		$rhs = preg_split('/(\W)/',$rhs,-1,PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		$rhs = array_filter($rhs,'trim');
		$r[$lhs] = $rhs;
	}
	return array($r,$r_s,$r_p);
}

function preprocess_grammar1($file) {
	$inrules = 0;
	$rules = array();
	$ruleno = 0;

	foreach($file as $line) {
		if(0 === strpos($line,'%%')) {
			$inrules++;
			$ruleno++;
			continue;
		}
		if(!isset($rules[$ruleno])) {
			$rules[$ruleno] = '';
		}
		if($inrules === 0) {
			$rules[$ruleno] .= $line;
		}
		elseif($inrules === 1) {
			$line = trim($line);
			if(empty($line)) {
				continue;
			}
			if(';' === $line) {
				$ruleno++;
				continue;
			}
			$rules[$ruleno] .= $line;
		}
		else {
			$rules[$ruleno] .= $line;
		}
	}
	return array_filter($rules);
}

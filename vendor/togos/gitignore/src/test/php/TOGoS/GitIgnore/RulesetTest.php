<?php

class TOGoS_GitIgnore_RulesetTest extends TOGoS_SimplerTest_TestCase
{
	protected function parseTestCases($content) {
		$lines = explode("\n", $content);
		$cases = array();
		foreach($lines as $line) {
			if( preg_match('/^# should match: (.*)$/', $line, $bif) ) {
				$cases[] = array('expectedOutput' => true, 'input' => $bif[1]);
			} else if( preg_match('/^# should not match: (.*)$/', $line, $bif) ) {
				$cases[] = array('expectedOutput' => false, 'input' => $bif[1]);
			}
		}
		return $cases;
	}
	
	public function testIt() {
		$rules1Content = file_get_contents(__DIR__.'/test1.ruleset');
		$ruleset = TOGoS_GitIgnore_Ruleset::loadFromString($rules1Content);
		$testCases = $this->parseTestCases($rules1Content);
		foreach( $testCases as $case ) {
			$shouldMatch = $case['expectedOutput'];
			$doesMatch = $ruleset->match($case['input']);
			if( $doesMatch === null ) $doesMatch = false;
			$this->assertEquals($shouldMatch, $doesMatch, "Expected '{$case['input']}' to ".($shouldMatch ? "match" : "not match"));
		}
	}
}

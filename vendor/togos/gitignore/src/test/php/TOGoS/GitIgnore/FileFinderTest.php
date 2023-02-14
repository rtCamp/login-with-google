<?php

class TOGoS_GitIgnore_FileFinderTest extends TOGoS_SimplerTest_TestCase
{
	protected $results;
	public function addResult($f, $result) {
		$this->results[$f] = $result;
	}

	protected function assertKeyedequals($expectedValue, $array, $key) {
		$this->assertTrue( array_key_exists($key, $array), "Expected key '$key' to be present in array" );
		$this->assertEquals($expectedValue, $array[$key]);
	}
		
	public function testSparsenessConfig() {
		$finder = new TOGoS_GitIgnore_FileFinder(array(
			'ruleset' => TOGoS_GitIgnore_Ruleset::loadFromStrings(array(
				'*',
				'!test1.ruleset',
				'/test-dir',
				'!b.txt',
			)),
			'invertRulesetResult' => false,
			'defaultResult' => false,
			'includeDirectories' => false,
			'callback' => array($this,'addResult')
		));

		$this->results = array();
		$finder->findFiles(__DIR__);

		$this->assertFalse(array_key_exists("test-dir", $this->results));
		$this->assertKeyedEquals(true , $this->results, "FileFinderTest.php");
		//$this->assertKeyedEquals(false, $this->results, "FileFinderTest.php~");
		$this->assertKeyedEquals(true , $this->results, "test-dir/a.txt");
		$this->assertKeyedEquals(false, $this->results, "test-dir/b.txt");
		$this->assertKeyedEquals(false, $this->results, "test1.ruleset");
	}
}

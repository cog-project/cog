<?php
trait partyTests {
	public function testParty($p = null,$str = "f00borx!!") {
		# smoke testing keypairs so we don't suffer debugging if these ever fail
		$this->assertTrue(class_exists("party"));
		if(!is_object($p)) {
			$p = new party();
		}
		$this->assertTrue($p->decrypt($p->encrypt($str)) == $str);
		return $p;
	}
}

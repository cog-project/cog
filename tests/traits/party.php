<?php
trait partyTests {
	public function testParty() {
		# smoke testing keypairs so we don't suffer debugging if these ever fail
		$this->assertTrue(class_exists("party"));
		$p = new party();
		$this->assertTrue($p->decrypt($p->encrypt("f00borx!!")) == "f00borx!!");
		return $p;
	}
}
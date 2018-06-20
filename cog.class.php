<?php
class cog {
	static function generate_nonce($counter,&$hash,$prefix = 'cog') {
		$hash = hash("sha256",$counter);
		# todo - verify this before network storage - and make sure the original unhashed string is visible.
	}
}
?>
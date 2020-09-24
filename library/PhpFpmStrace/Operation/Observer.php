<?php namespace PhpFpmStrace\Operation;

use PhpFpmStrace\Syscall;

interface Observer {
	public function executes(Syscall $c): \Generator;
}
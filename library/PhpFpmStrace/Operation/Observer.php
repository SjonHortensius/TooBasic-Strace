<?php namespace PhpFpmStrace\Operation;

use PhpFpmStrace\Syscall;

interface Observer {
	public function observe(Syscall $c): \Generator;
}
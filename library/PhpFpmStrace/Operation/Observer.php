<?php namespace PhpFpmStrace\Operation;

use PhpFpmStrace\Syscall;

interface Observer {
	const LEVEL_INFO = 1;
	const LEVEL_CALL = 2;
	const LEVEL_SUMMARY = 3;

	public function observe(Syscall $c): \Generator;
	public function summary(): array;
}
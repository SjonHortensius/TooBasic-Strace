<?php namespace PhpFpmStrace\Operation;

use PhpFpmStrace\Syscall;

interface Observer {
	// allow the observer to be registered for future calls if the open call matches
	public static function register(Syscall\Opener $c): ?self;

	// observer a call after registering
	public function observe(Syscall $c): \Generator;

	// notification of closure
	public function unregister(Syscall\Closer $c): void;
}
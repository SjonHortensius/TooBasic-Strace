<?php namespace PhpFpmStrace\Syscall;

interface Opener
{
	public function spawns(): array;
	public function closedBy(Closer $call): void;
}

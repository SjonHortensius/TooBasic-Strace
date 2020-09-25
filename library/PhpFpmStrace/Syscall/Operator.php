<?php namespace PhpFpmStrace\Syscall;

interface Operator
{
	public function getDescriptors(): array;
}
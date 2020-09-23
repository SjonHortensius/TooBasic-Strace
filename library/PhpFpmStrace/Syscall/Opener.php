<?php
namespace PhpFpmStrace\Syscall;

interface Opener
{
	public function closedBy(Closer $c);
}

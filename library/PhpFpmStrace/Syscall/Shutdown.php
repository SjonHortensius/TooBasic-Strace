<?php
namespace PhpFpmStrace\Syscall;

class Shutdown extends \PhpFpmStrace\Syscall implements Closer
{
	protected int $_socket;

	public function __construct(int $socket, string $how)
	{
		$this->_socket = $socket;
	}

	public function closes(): int
	{
		return $this->_socket;
	}
}

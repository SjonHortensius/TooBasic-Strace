<?php namespace PhpFpmStrace\Syscall;

class Close extends \PhpFpmStrace\Syscall implements Closer
{
	protected int $_socket;

	public function __construct(int $socket)
	{
		parent::__construct(...func_get_args());
		$this->_socket = $socket;
	}

	public function closes(): int
	{
		return $this->_socket;
	}
}
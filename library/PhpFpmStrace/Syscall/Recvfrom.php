<?php namespace PhpFpmStrace\Syscall;

class Recvfrom extends \PhpFpmStrace\Syscall implements Operator
{
	protected int $_descriptor;

	public function __construct(int $socket, ...$args)
	{
		parent::__construct(...func_get_args());

		$this->_descriptor = $socket;
	}

	public function getDescriptors(): array
	{
		return [$this->_descriptor];
	}
}
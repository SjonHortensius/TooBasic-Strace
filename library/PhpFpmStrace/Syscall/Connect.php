<?php namespace PhpFpmStrace\Syscall;

class Connect extends \PhpFpmStrace\Syscall implements Operator
{
	public int $port;
	protected int $_descriptor;

	public function __construct(int $socket, array $sockAddr, int $sockAddrLen)
	{
		parent::__construct(...func_get_args());

		$this->_descriptor = $socket;
		$this->port = intval(trim($sockAddr["sin_port"]??$sockAddr["sin6_port"], 'htons()'));
	}

	public function getDescriptors(): array
	{
		return [$this->_descriptor];
	}

	public function __toString(): string
	{
		return sprintf('%s[%s]', __CLASS__, $this->_descriptor);
	}
}
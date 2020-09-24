<?php namespace PhpFpmStrace\Syscall;

class Connect extends \PhpFpmStrace\Syscall
{
	public int $port;

	public function __construct(int $fromSocket, array $sockAddr, int $sockLen)
	{
		parent::__construct(...func_get_args());

		$this->port = intval(trim($sockAddr["sin_port"]??$sockAddr["sin6_port"], 'htons()'));
	}

	public function __toString(): string
	{
		return sprintf('%s[%s]', __CLASS__, $this->_returns);
	}
}

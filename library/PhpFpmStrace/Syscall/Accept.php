<?php
namespace PhpFpmStrace\Syscall;

class Accept extends \PhpFpmStrace\Syscall implements Opener
{
	protected $_fromSocket;
	protected int $_retn;
	protected $_closer;

	public function __construct(int $fromSocket, string $sockAddr, string $sockLen)
	{
		$this->_fromSocket = $fromSocket;
	}

	public function closedBy(Closer $call): bool
	{
		if ($call->closes() == $this->_retn)
		{
			$this->_closer = $call;
			return true;
		}

		return false;
	}

	public function __toString(): string
	{
		return sprintf('%s:accept[%s]', __CLASS__, $this->_retn);
	}
}

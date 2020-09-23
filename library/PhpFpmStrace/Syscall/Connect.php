<?php
namespace PhpFpmStrace\Syscall;

class Connect extends \PhpFpmStrace\Syscall implements Opener
{
	protected $_socket;
	protected $_socketAddress;
	protected int $_retn;
	protected $_closer;

	public function __construct(int $socket, string $sockAddr, string $sockLen)
	{
		$this->_socket = $socket;
		$this->_socketAddress = $sockAddr;
	}

	public function closedBy(Closer $call): bool
	{
		if ($call->closes() == $this->_socket)
		{
			$this->_closer = $call;
			return true;
		}

		return false;
	}

	public function __toString(): string
	{
		return sprintf('%s:connect[%s]', __CLASS__, $this->_socket);
	}
}

<?php
namespace PhpFpmStrace\Syscall;

class Openat extends \PhpFpmStrace\Syscall implements Opener
{
	protected $_fromSocket;
	protected int $_retn;
	protected $_closer;

	public function __construct(string $relTo, string $path, string $flag)
	{
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
		return sprintf('%s:openat[%s]', __CLASS__, $this->_retn);
	}
}
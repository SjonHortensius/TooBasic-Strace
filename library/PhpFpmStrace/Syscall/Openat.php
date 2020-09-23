<?php
namespace PhpFpmStrace\Syscall;

class Openat extends \PhpFpmStrace\Syscall implements Opener
{
	protected Closer $_closer;

	public function __construct(string $relTo, string $path, string $flag)
	{
		parent::__construct(...func_get_args());
	}

	public function closedBy(Closer $call): bool
	{
		if ($call->closes() == $this->_returns)
		{
			$this->_closer = $call;
			return true;
		}

		return false;
	}

	public function __toString(): string
	{
		return sprintf('%s:openat[%s]', __CLASS__, $this->_returns);
	}
}
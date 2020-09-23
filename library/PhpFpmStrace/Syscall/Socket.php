<?php namespace PhpFpmStrace\Syscall;

class Socket extends \PhpFpmStrace\Syscall implements Opener
{
	protected Closer $_closer;

	public function __construct(string $domain, string $type, string $protocol)
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
		return sprintf('%s:socket[%s]', __CLASS__, $this->_returns);
	}
}

<?php namespace PhpFpmStrace\Syscall;

class Socket extends \PhpFpmStrace\Syscall implements Opener
{
	protected Closer $_closer;

	public function __construct(string $domain, string $type, string $protocol)
	{
		parent::__construct(...func_get_args());
	}

	public function spawns(): array
	{
		return [$this->_returns];
	}

	public function closedBy(Closer $call): void
	{
		$this->_closer = $call;
	}


	public function __toString(): string
	{
		return sprintf('%s[%s]', __CLASS__, $this->_returns);
	}
}

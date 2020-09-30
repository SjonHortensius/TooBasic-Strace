<?php namespace PhpFpmStrace;

class Operation
{
	protected Syscall $_start;
	protected Syscall $_end;
	protected Duration $_took;
	protected Operation\Observer $_from;
	protected string $_text;

	public function __construct(Operation\Observer $from, Syscall $start, string $text)
	{
		$this->_from = $from;
		$this->_start = $start;
		$this->_text = $text;
	}

	public function complete(Syscall $end, string $text = ""): void
	{
		$this->_end = $end;
		$this->_took = Duration::fromDiff($this->_end->getTimestamp(), $this->_start->getTimestamp());

		if ($text != "")
			$this->_text .= ', '. $text;
	}

	public function took(): Duration
	{
		if (!isset($this->_end))
			throw new Exception("Operation hasn't completed yet");

		return $this->_took;
	}

	public function __toString()
	{
		return $this->_text;
	}
}
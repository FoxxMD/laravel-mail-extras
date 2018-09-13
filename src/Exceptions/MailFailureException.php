<?php


namespace FoxxMD\LaravelMailExtras\Exceptions;

class MailFailureException extends \Exception
{
	public $attempts;
	public $view;
	public $failureRecipients;
	public $altMessage;

	public function __construct($message = null, $code = 0, $attempts = null, $view = null, $failureRecipients = [], $previous = null)
	{
		$message = $message === null ? 'Sending mail was not successful' : $message;
		$this->attempts = $attempts;
		$this->view = $view;
		$this->failureRecipients = $failureRecipients;
		parent::__construct($message, $code, $previous);
	}
}
<?php


namespace FoxxMD\LaravelMailExtras\Mail;

use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Mail\MailQueue as MailQueueContract;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\Events\Dispatcher;
use Swift_Mailer;

class Mailer extends \Illuminate\Mail\Mailer implements MailerContract, MailQueueContract {

	// Number of times to retry sending mail before giving up
	protected $retries = 0;


	/**
	 * Create a new Mailer instance.
	 *
	 * @param  \Illuminate\Contracts\View\Factory           $views
	 * @param  \Swift_Mailer                                $swift
	 * @param  \Illuminate\Contracts\Events\Dispatcher|null $events
	 *
	 * @return void
	 */
	public function __construct(Factory $views, Swift_Mailer $swift, Dispatcher $events = null, $config = null)
	{
		if(null !== $config) {
		$this->retries=$config->get('mail.retries', 0);
	}

		parent::__construct($views, $swift, $events);
	}

	/**
	 * Send a new message using a view.
	 *
	 * @param  string|array    $view
	 * @param  array           $data
	 * @param  \Closure|string $callback
	 *
	 * @return void
	 */
	public function send($view, array $data, $callback)
	{
		$firstRun = true;
		$attempts = 1;
		$ex = null;
		while($firstRun || $attempts <= $this->retries)
		{
			$firstRun = false;
			try
			{
				parent::send($view, $data, $callback);
				$ex = null;
			}
			catch (\Exception $e)
			{
				$ex = $e;
				$msg = '';
				if($this->retries > 0 && $attempts > 0) {
					$msg .= "[Attempt $attempts] ";
				}
				$this->logger->error($msg . "Sending mail to {{$this->getReadableName()}} was not successful. " . PHP_EOL . "Message: {{$e->getMessage()}}");
			}
			$attempts++;
		}
		if(null !== $ex) {
			throw $ex;
		}
	}

	/**
	 * Return a human readable name to identify email
	 *
	 * @return string|null
	 */
	protected function getReadableName()
	{
		if (isset($this->to['name']))
		{
			return $this->to['name'];
		}
		elseif (isset($this->to['address']))
		{
			return $this->to['address'];
		}

		return null;
	}
}
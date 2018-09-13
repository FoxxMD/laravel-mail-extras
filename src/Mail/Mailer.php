<?php


namespace FoxxMD\LaravelMailExtras\Mail;

use FoxxMD\LaravelMailExtras\Exceptions\DeliveryFailureException;
use FoxxMD\LaravelMailExtras\Exceptions\MailFailureException;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Mail\MailQueue as MailQueueContract;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\Events\Dispatcher;
use Swift_Mailer;

class Mailer extends \Illuminate\Mail\Mailer implements MailerContract, MailQueueContract {

	// Number of times to retry sending mail before giving up
	protected $retries = 0;
	protected $throwOnMailFailure = true;
	protected $throwOnDeliveryFailure = false;


	/**
	 * Create a new Mailer instance.
	 *
	 * @param  \Illuminate\Contracts\View\Factory           $views
	 * @param  \Swift_Mailer                                $swift
	 * @param  \Illuminate\Contracts\Events\Dispatcher|null $events
	 *
	 * @param null                                          $config
	 */
	public function __construct(Factory $views, Swift_Mailer $swift, Dispatcher $events = null, $config = null)
	{
		if(null !== $config) {
			$this->retries = $config->get('mail.retries', 0);
			$this->throwOnMailFailure = $config->get('mail.exceptions.mailFailure', true);
			$this->throwOnDeliveryFailure = $config->get('mail.exceptions.deliveryFailure', false);
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
	 * @param bool             $throwOnMailFailure
	 * @param bool             $throwOnDeliveryFailure
	 *
	 * @return void
	 * @throws DeliveryFailureException
	 * @throws MailFailureException
	 */
	public function send($view, array $data, $callback, $throwOnMailFailure = null, $throwOnDeliveryFailure = null)
	{
		list($view, $plain, $raw) = $this->parseView($view);

		$data['message'] = $message = $this->createMessage();

		// Once we have retrieved the view content for the e-mail we will set the body
		// of this message using the HTML type, which will provide a simple wrapper
		// to creating view based emails that are able to receive arrays of data.
		$this->addContent($message, $view, $plain, $raw, $data);

		$this->callMessageBuilder($callback, $message);

		if (isset($this->to['address'])) {
			$message->to($this->to['address'], $this->to['name'], true);
		}

		$message = $message->getSwiftMessage();

		$attempts = 1;
		$success = false;
		while(!$success || $attempts <= $this->retries)
		{
			try
			{
				$this->sendSwiftMessage($message);
				$failures = $this->failures();
				if(count($failures) > 0) {
					$deliveryFailureException = new DeliveryFailureException(null, 0, $attempts, $view, $failures);
					$this->logger->error($deliveryFailureException, [
						'attempts' => $attempts,
						'view' => $view,
						'recipients' => $failures
					]);
					if($this->shouldThrowOnDeliveryFailure($throwOnDeliveryFailure)) {
						throw $deliveryFailureException;
					}
				}
				$success = true;
			}
			catch(DeliveryFailureException $e) {
				throw $e;
			}
			catch (\Exception $e)
			{
				if($this->retries >= $attempts) {
					continue;
				}
				$failures = $this->failures();
				$mailFailureException = new MailFailureException(null, 0, $attempts, $view, $failures, $e);
				$this->logger->error($mailFailureException, [
					'attempts' => $attempts,
					'view' => $view,
					'recipients' => $failures
				]);
				if($this->shouldThrowOnMailFailure($throwOnMailFailure)) {
					throw $mailFailureException;
				}
			}
			$attempts++;
		}
	}

	protected function shouldThrowOnMailFailure($providedBool = null) {
		if($providedBool !== null) {
			return $providedBool;
		}
		return $this->throwOnMailFailure;
	}

	protected function shouldThrowOnDeliveryFailure($providedBool = null) {
		if($providedBool !== null) {
			return $providedBool;
		}
		return $this->throwOnDeliveryFailure;
	}
}
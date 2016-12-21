<?php


namespace FoxxMD\LaravelMailExtras;


use FoxxMD\LaravelMailExtras\Mail\Mailer;
use Illuminate\Mail\MailServiceProvider;

class LaravelMailExtrasServiceProvider extends MailServiceProvider {

	public function boot()
	{
		// nothing to do here!
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerSwiftMailer();

		$this->app->singleton('mailer', function ($app) {
			// Once we have create the mailer instance, we will set a container instance
			// on the mailer. This allows us to resolve mailer classes via containers
			// for maximum testability on said classes instead of passing Closures.

			// only change here is the use of FoxxMD Mailer instead of Illuminate Mailer
			$mailer = new Mailer(
				$app['view'], $app['swift.mailer'], $app['events'], $app['config']
			);

			$this->setMailerDependencies($mailer, $app);

			// If a "from" address is set, we will set it on the mailer so that all mail
			// messages sent by the applications will utilize the same "from" address
			// on each one, which makes the developer's life a lot more convenient.
			$from = $app['config']['mail.from'];

			if (is_array($from) && isset($from['address'])) {
				$mailer->alwaysFrom($from['address'], $from['name']);
			}

			$to = $app['config']['mail.to'];

			if (is_array($to) && isset($to['address'])) {
				$mailer->alwaysTo($to['address'], $to['name']);
			}

			// Here we will determine if the mailer should be in "pretend" mode for this
			// environment, which will simply write out e-mail to the logs instead of
			// sending it over the web, which is useful for local dev environments.
			$pretend = $app['config']->get('mail.pretend', false);

			$mailer->pretend($pretend);

			return $mailer;
		});
	}
}
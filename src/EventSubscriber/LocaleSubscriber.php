<?php # TRANSLATE:

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class LocaleSubscriber implements EventSubscriberInterface
{
	private string $defaultLocale;
	private array $availableLocales;

	/**
	 * @param string $defaultLocale @see config/services.yaml:27
	 * @param array $availableLocales @see config/services.yaml:28
	 */
	public function __construct(string $defaultLocale, array $availableLocales)
	{
		$this->defaultLocale = $defaultLocale;
		$this->availableLocales = $availableLocales;
	}

	public static function getSubscribedEvents(): array
	{
		return ["kernel.request" => ["onKernelRequest", 15]];
	}

	public function onKernelRequest(RequestEvent $event): void
	{
		$request = $event->getRequest();

		if ($locale = $request->getSession()?->get("_locale")) {
			$request->setLocale($locale);
			return;
		}

		$preferred = $request->getPreferredLanguage($this->availableLocales);
		$request->setLocale($preferred ?: $this->defaultLocale);
	}
}

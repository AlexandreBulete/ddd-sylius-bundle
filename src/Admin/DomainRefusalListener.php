<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Admin;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A back-office action the domain refuses — removing a category posts still
 * use, deleting a published post — is a message for the person who clicked,
 * not an error page.
 *
 * On a write to a Sylius admin route, a \DomainException (anywhere in the
 * chain: a bus may wrap it) becomes an error flash and a redirect back. The
 * flash is the translation of `domain_refusal.<snake_case class name>` in the
 * `flashes` domain, with the exception's public properties as `%name%`
 * parameters; the exception message when no translation exists.
 *
 * Reads (GET) are left alone: redirecting back from a page that failed to
 * render could loop. Anything else than a \DomainException is a bug, and
 * keeps its error page.
 */
final readonly class DomainRefusalListener
{
    private const array WRITES = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(
        private ?TranslatorInterface $translator = null,
    ) {}

    #[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
    public function __invoke(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!self::isAdminWrite($request)) {
            return;
        }

        $refusal = self::refusalIn($event->getThrowable());
        if ($refusal === null) {
            return;
        }

        $session = $request->hasSession() ? $request->getSession() : null;
        if (!$session instanceof FlashBagAwareSessionInterface) {
            return;
        }

        $session->getFlashBag()->add('error', $this->message($refusal));
        $event->setResponse(new RedirectResponse(self::backTo($request), Response::HTTP_SEE_OTHER));
    }

    private static function isAdminWrite(Request $request): bool
    {
        $sylius = $request->attributes->get('_sylius');

        return is_array($sylius)
            && ($sylius['section'] ?? null) === 'admin'
            && in_array($request->getMethod(), self::WRITES, true);
    }

    private static function refusalIn(\Throwable $throwable): ?\DomainException
    {
        for ($current = $throwable; $current !== null; $current = $current->getPrevious()) {
            if ($current instanceof \DomainException) {
                return $current;
            }
        }

        return null;
    }

    private function message(\DomainException $refusal): string
    {
        if ($this->translator === null) {
            return $refusal->getMessage();
        }

        $short = (new \ReflectionClass($refusal))->getShortName();
        $id = 'domain_refusal.' . strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $short));

        $parameters = [];
        foreach (get_object_vars($refusal) as $name => $value) {
            if (is_scalar($value) || $value instanceof \Stringable) {
                $parameters["%{$name}%"] = (string) $value;
            } elseif ($value instanceof \BackedEnum) {
                $parameters["%{$name}%"] = (string) $value->value;
            } elseif (is_object($value) && method_exists($value, 'value') && is_scalar($scalar = $value->value())) {
                $parameters["%{$name}%"] = (string) $scalar;
            }
        }

        $translated = $this->translator->trans($id, $parameters, 'flashes');

        return $translated === $id ? $refusal->getMessage() : $translated;
    }

    /**
     * Where the click came from, on this host; the admin home otherwise.
     */
    private static function backTo(Request $request): string
    {
        $referer = $request->headers->get('referer');
        if (is_string($referer) && parse_url($referer, PHP_URL_HOST) === $request->getHost()) {
            return $referer;
        }

        return $request->getSchemeAndHttpHost() . $request->getBaseUrl() . '/admin/';
    }
}

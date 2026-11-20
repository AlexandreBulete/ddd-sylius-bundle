<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Tests\Admin;

use AlexandreBulete\DddSyliusBundle\Admin\DomainRefusalListener;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DomainRefusalListenerTest extends TestCase
{
    #[Test]
    public function a_refused_admin_write_becomes_a_flash_and_a_redirect_back(): void
    {
        $request = $this->request('POST');
        $event = $this->event($request, new \RuntimeException('wrapped', 0, new StillUsedException('Polar', 3)));

        (new DomainRefusalListener($this->translator()))($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(303, $response->getStatusCode());
        self::assertSame('https://admin.test/admin/tags', $response->getTargetUrl());
        self::assertSame(['Polar est utilisé par 3 articles.'], $this->flashes($request));
    }

    #[Test]
    public function without_a_translation_the_exception_message_is_shown(): void
    {
        $request = $this->request('DELETE');
        $event = $this->event($request, new \DomainException('Nope.'));

        (new DomainRefusalListener($this->translator()))($event);

        self::assertSame(['Nope.'], $this->flashes($request));
    }

    #[Test]
    public function reads_other_sections_and_bugs_keep_their_error_page(): void
    {
        $listener = new DomainRefusalListener($this->translator());

        foreach ([
            [$this->request('GET'), new \DomainException('x')],
            [$this->request('POST', 'shop'), new \DomainException('x')],
            [$this->request('POST'), new \LogicException('bug')],
        ] as [$request, $throwable]) {
            $event = $this->event($request, $throwable);
            $listener($event);
            self::assertNull($event->getResponse());
        }
    }

    #[Test]
    public function a_referer_from_elsewhere_is_not_followed(): void
    {
        $request = $this->request('POST');
        $request->headers->set('referer', 'https://evil.test/');
        $event = $this->event($request, new \DomainException('x'));

        (new DomainRefusalListener())($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('https://admin.test/admin/', $response->getTargetUrl());
    }

    private function request(string $method, string $section = 'admin'): Request
    {
        $request = Request::create('https://admin.test/admin/tags/1/delete', $method);
        $request->attributes->set('_sylius', ['resource' => 'blog.tag', 'section' => $section]);
        $request->headers->set('referer', 'https://admin.test/admin/tags');
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }

    private function event(Request $request, \Throwable $throwable): ExceptionEvent
    {
        return new ExceptionEvent(self::createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, $throwable);
    }

    /**
     * @return list<mixed>
     */
    private function flashes(Request $request): array
    {
        $session = $request->getSession();
        self::assertInstanceOf(Session::class, $session);

        return array_values($session->getFlashBag()->get('error'));
    }

    private function translator(): TranslatorInterface
    {
        return new class implements TranslatorInterface {
            /**
             * @param array<mixed, mixed> $parameters
             */
            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                $strings = array_map(static fn (mixed $v): string => is_scalar($v) ? (string) $v : '', $parameters);

                return $id === 'domain_refusal.still_used_exception' && $domain === 'flashes'
                    ? strtr('%name% est utilisé par %posts% articles.', $strings)
                    : $id;
            }

            public function getLocale(): string
            {
                return 'fr';
            }
        };
    }
}

final class StillUsedException extends \DomainException
{
    public function __construct(public readonly string $name, public readonly int $posts)
    {
        parent::__construct('still used');
    }
}

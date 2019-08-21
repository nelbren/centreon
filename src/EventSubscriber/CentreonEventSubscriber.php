<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Centreon\Domain\Pagination\Interfaces\RequestParametersInterface;
use Centreon\Domain\Pagination\RequestParameters;
use Centreon\Domain\VersionHelper;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * We defined an event subscriber on the kernel event request to create a
 * RequestParameters class according to query parameters and then used in the services
 * or repositories.
 *
 * This class is automatically calls by Symfony through the dependency injector
 * and because it's defined as a service.
 *
 * @package App\EventSubscriber
 */
class CentreonEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var Container
     */
    private $container;
    /**
     * @var RequestParametersInterface
     */
    private $requestParameters;

    /**
     * @param RequestParametersInterface $requestParameters
     * @param ContainerInterface $container
     */
    public function __construct(RequestParametersInterface $requestParameters, ContainerInterface $container)
    {
        $this->container = $container;
        $this->requestParameters = $requestParameters;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['initRequestParameters', 9],
                ['defineApiVersionInAttributes', 33]
            ],
            KernelEvents::RESPONSE => [
                ['addApiVersion', 10]
            ],
            KernelEvents::EXCEPTION => [
                ['onKernelException', 10]
            ]
        ];
    }

    /**
     * Use to update the api version into all responses
     *
     * @param FilterResponseEvent $event
     */
    public function addApiVersion(FilterResponseEvent $event)
    {
        $apiVersion = '1.0';
        $apiHeaderName = 'version';

        if ($this->container->hasParameter('api.version.lastest')) {
            $apiVersion = $this->container->getParameter('api.version.lastest');
        }
        if ($this->container->hasParameter('api.header')) {
            $apiHeaderName = $this->container->getParameter('api.header');
        }
        $event->getResponse()->headers->add([$apiHeaderName => $apiVersion]);
    }

    /**
     * Initializes the RequestParameters instance for later use in the service or repositories.
     *
     * @param GetResponseEvent $request
     * @throws \Exception
     */
    public function initRequestParameters(GetResponseEvent $request):void
    {
        $query = $request->getRequest()->query->all();

        $limit = (int) ($query[RequestParameters::NAME_FOR_LIMIT] ?? RequestParameters::DEFAULT_LIMIT);
        $this->requestParameters->setLimit($limit);

        $page = (int) ($query[RequestParameters::NAME_FOR_PAGE] ?? RequestParameters::DEFAULT_PAGE);
        $this->requestParameters->setPage($page);

        if (isset($query[RequestParameters::NAME_FOR_SORT])) {
            $this->requestParameters->setSort($query[RequestParameters::NAME_FOR_SORT]);
        }

        if (isset($query[RequestParameters::NAME_FOR_SEARCH])) {
            $this->requestParameters->setSearch($query[RequestParameters::NAME_FOR_SEARCH]);
        } else {
            /*
             * Create search by using parameters in query
             */
            $reservedFields = [
                RequestParameters::NAME_FOR_LIMIT,
                RequestParameters::NAME_FOR_PAGE,
                RequestParameters::NAME_FOR_SEARCH,
                RequestParameters::NAME_FOR_SORT,
                RequestParameters::NAME_FOR_TOTAL];

            $query  = !empty($_SERVER['QUERY_STRING'])
                ? explode('&', $_SERVER['QUERY_STRING'])
                : [];
            $parameters = [];
            foreach ($query as $value) {
                if (strpos($value, '=') === false) {
                    $value .= '=';
                }

                list($name, $value) = explode('=', $value, 2);
                // Extract the parameter name in expression => filter[parameter_name]
                $name = preg_replace('/^filter\[([[:alnum:]]+)\]$/', '\1', urldecode($name));
                if (!in_array($name, $reservedFields)) {
                    $parameters[$name] = explode('|', urldecode($value));
                }
            }

            $search = [];
            foreach ($parameters as $filter => $filtervalues) {
                $parameterName = substr($filter, 7, strlen($filter) - 8);
                if (count($filtervalues, COUNT_RECURSIVE) > 1) {
                    // OR expression
                    $orParameters = [];
                    foreach ($filtervalues as $value) {
                        $orParameters[] = [$parameterName => $value];
                    }
                    $search[RequestParameters::AGGREGATE_OPERATOR_OR][] = $orParameters;
                } else {
                    // AND expression
                    $value = $filtervalues[0];
                    if ($value == 'true') {
                        $value = true;
                    } elseif ($value == 'false') {
                        $value = false;
                    }
                    $search[RequestParameters::AGGREGATE_OPERATOR_AND][$parameterName] = $value;
                }
            }
            $this->requestParameters->setSearch(json_encode($search));
        }
    }

    /**
     * We retrieve the API version from url to put it in the attributes to allow
     * the kernel to use it in routing conditions.
     *
     * @param GetResponseEvent $event
     */
    public function defineApiVersionInAttributes(GetResponseEvent $event)
    {
        $latestVersion = $this->container->getParameter('api.version.latest');
        $event->getRequest()->attributes->set('version.latest', $latestVersion);
        $event->getRequest()->attributes->set('version.is_latest', false);

        $betaVersion = $this->container->getParameter('api.version.beta');
        $event->getRequest()->attributes->set('version.beta', $betaVersion);
        $event->getRequest()->attributes->set('version.is_beta', false);
        $event->getRequest()->attributes->set('version.not_beta', true);

        $uri = $event->getRequest()->getRequestUri();
        $paths = explode('/', $uri);
        array_shift($paths);
        if (count($paths) >= 3) {
            $requestApiVersion = $paths[2];
            if ($requestApiVersion[0] == 'v') {
                $requestApiVersion = substr($requestApiVersion, 1);
                $requestApiVersion = VersionHelper::regularizeDepthVersion(
                    $requestApiVersion,
                    1
                );
            }

            if ($requestApiVersion == 'latest'
                || VersionHelper::compare($requestApiVersion, $latestVersion, VersionHelper::EQUAL)
            ) {
                $event->getRequest()->attributes->set('version.is_latest', true);
                $requestApiVersion = $latestVersion;
            }
            if ($requestApiVersion == 'beta'
                || VersionHelper::compare($requestApiVersion, $betaVersion, VersionHelper::EQUAL)
            ) {
                $event->getRequest()->attributes->set('version.is_beta', true);
                $event->getRequest()->attributes->set('version.not_beta', false);
                $requestApiVersion = $betaVersion;
            }

            $event->getRequest()->attributes->set('version', (float) $requestApiVersion);
        }
    }

    /**
     * Used to manage exceptions outside controllers.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $flagController = 'Controller';
        $errorIsBeforeController = true;

        // We detect if the exception occurred before the kernel called the controller
        foreach ($event->getException()->getTrace() as $trace) {
            if (strlen($trace['class']) > strlen($flagController)
                && substr($trace['class'], -strlen($flagController)) === $flagController
            ) {
                $errorIsBeforeController = false;
                break;
            }
        }

        // If Yes and exception code !== 403, we create a custom error message
        // If we don't do that a HTML error will appeared.
        if ($errorIsBeforeController && $event->getException()->getCode() !== 403) {
            $errorCode = $event->getException()->getCode() > 0
                ? $event->getException()->getCode()
                : 500;

            // Manage exception outside controllers
            $event->setResponse(
                new Response(
                    json_encode(
                        [
                            'code' => $errorCode,
                            'message' => $event->getException()->getMessage()
                        ]
                    ),
                    500
                )
            );
        }
    }
}

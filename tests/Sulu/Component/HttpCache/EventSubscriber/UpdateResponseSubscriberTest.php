<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\EventListener;

use Prophecy\PhpUnit\ProphecyTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sulu\Component\HttpCache\EventSubscriber\KernelSubscriber;
use Prophecy\Argument;
use Sulu\Component\HttpCache\HandlerInterface;
use Sulu\Component\Content\StructureInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Sulu\Component\HttpCache\EventSubscriber\UpdateResponseSubscriber;

class UpdateResponseSubscriberTest extends ProphecyTestCase
{
    /**
     * @var KernelSubscriber
     */
    private $subscriber;

    /**
     * @var HandlerInterface
     */
    private $handler;

    /**
     * @var StructureInterface
     */
    private $structure;

    /**
     * @var GetResponseEvent
     */
    private $getResponseEvent;

    /**
     * @var FilterResponseEvent
     */
    private $filterResponseEvent;

    public function setUp()
    {
        parent::setUp();

        $this->getResponseEvent = $this->prophesize('Symfony\Component\HttpKernel\Event\GetResponseEvent');
        $this->filterResponseEvent = $this->prophesize('Symfony\Component\HttpKernel\Event\FilterResponseEvent');
        $this->postResponseEvent = $this->prophesize('Symfony\Component\HttpKernel\Event\PostResponseEvent');
        $this->structure = $this->prophesize('Sulu\Component\Content\StructureInterface');
        $this->handler = $this->prophesize('Sulu\Component\HttpCache\HandlerUpdateResponseInterface')
            ->willImplement('Sulu\Component\HttpCache\HandlerInvalidateStructureInterface');

        $this->response = new Response();
        $this->request = new Request();

        $this->subscriber = new UpdateResponseSubscriber(
            $this->handler->reveal()
        );
        UpdateResponseSubscriber::getSubscribedEvents();
    }

    public function provideLifecycle()
    {
        return array(
            // INVALIDATE: Is master request, has a structure and is not a preview
            array(
                array(
                    'is_master_request' => true,
                    'has_structure' => true,
                    'preview' => false,
                ),
                true,
            ),
            // NO INVALIDATE: Has not structure
            array(
                array(
                    'is_master_request' => true,
                    'has_structure' => false,
                    'preview' => false,
                ),
                false,
            ),
            // NO INVALIDATE: Is preview
            array(
                array(
                    'is_master_request' => true,
                    'has_structure' => true,
                    'preview' => true,
                ),
                false,
            ),
        );
    }

    /**
     * @dataProvider provideLifecycle
     */
    public function testLifecycle($options, $shouldInvalidate)
    {
        $this->getResponseEvent->isMasterRequest()->willReturn($options['is_master_request']);

        if ($options['has_structure']) {
            $this->request->attributes->set('structure', $this->structure->reveal());
        }

        if ($options['preview']) {
            $this->request->query->set('preview', true);
        }

        $this->getResponseEvent->getRequest()->willReturn($this->request);
        $this->filterResponseEvent->getResponse()->willReturn($this->response);
        $this->filterResponseEvent->getRequest()->willReturn($this->request);

        $invalidateProphecy = $this->handler->updateResponse($this->response, Argument::any());

        if ($shouldInvalidate) {
            $invalidateProphecy->shouldBeCalled();
        } else {
            $invalidateProphecy->shouldNotBeCalled();
        }

        $this->subscriber->onRequest($this->getResponseEvent->reveal());
        $this->subscriber->onResponse($this->filterResponseEvent->reveal());
    }
}
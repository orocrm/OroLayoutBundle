<?php

namespace Oro\Bundle\LayoutBundle\Layout;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Oro\Component\Layout\LayoutManager as BaseLayoutManager;

class LayoutManager extends BaseLayoutManager
{
    /** @var string */
    protected $defaultActiveTheme;

    /** @var LayoutContextHolder */
    protected $contextHolder;

    /**
     * @param LayoutFactoryBuilderInterface $layoutFactoryBuilder
     * @param LayoutContextHolder           $contextHolder
     * @param string                        $defaultActiveTheme
     */
    public function __construct(
        LayoutFactoryBuilderInterface $layoutFactoryBuilder,
        LayoutContextHolder $contextHolder,
        $defaultActiveTheme
    ) {
        parent::__construct($layoutFactoryBuilder);
        $this->contextHolder = $contextHolder;
        $this->defaultActiveTheme = $defaultActiveTheme;
    }

    /**
     * @param ContextInterface $context
     * @param string|null      $rootId
     * @return Layout
     */
    public function getLayout(ContextInterface $context, $rootId = null)
    {
        $layoutBuilder = $this->getLayoutBuilder();

        // TODO discuss adding root automatically
        $layoutBuilder->add('root', null, 'root');

        $this->contextHolder->setContext($context);

        if (!$context->has('theme')) {
            $context->set('theme', $this->defaultActiveTheme);
        }

        return $layoutBuilder->getLayout($context, $rootId);
    }

    /**
     * @param array         $parameters
     * @param null|string[] $vars
     * @return string
     */
    public function render(array $parameters, $vars = null)
    {
        $layoutContext = new LayoutContext($parameters, $vars);

        return $this->getLayout($layoutContext)->render();
    }
}

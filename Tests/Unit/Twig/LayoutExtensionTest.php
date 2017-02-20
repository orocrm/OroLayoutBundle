<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Twig;

use Symfony\Component\Form\FormView;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Templating\TextHelper;

use Oro\Bundle\LayoutBundle\Form\TwigRendererInterface;
use Oro\Bundle\LayoutBundle\Twig\LayoutExtension;

class LayoutExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var TwigRendererInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $renderer;

    /** @var TextHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $textHelper;

    /** @var LayoutExtension */
    protected $extension;

    protected function setUp()
    {
        $this->renderer   = $this->createMock('Oro\Bundle\LayoutBundle\Form\TwigRendererInterface');
        $this->textHelper = $this->getMockBuilder('Oro\Component\Layout\Templating\TextHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new LayoutExtension($this->renderer, $this->textHelper);
    }

    public function testGetName()
    {
        $this->assertEquals('layout', $this->extension->getName());
    }

    public function testInitRuntime()
    {
        /** @var \Twig_Environment $environment */
        $environment = $this->getMockBuilder('\Twig_Environment')
            ->getMock();

        $this->renderer->expects($this->once())
            ->method('setEnvironment')
            ->with($this->identicalTo($environment));

        $this->extension->initRuntime($environment);
    }

    public function testGetTokenParsers()
    {
        $tokenParsers = $this->extension->getTokenParsers();

        $this->assertCount(1, $tokenParsers);

        $this->assertInstanceOf(
            'Oro\Bundle\LayoutBundle\Twig\TokenParser\BlockThemeTokenParser',
            $tokenParsers[0]
        );
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();

        $this->assertCount(7, $functions);

        /** @var \Twig_SimpleFunction $function */
        $this->assertInstanceOf('Twig_SimpleFunction', $functions[0]);
        $function = $functions[0];
        $this->assertEquals('block_widget', $function->getName());
        $this->assertNull($function->getCallable());
        $this->assertEquals(LayoutExtension::RENDER_BLOCK_NODE_CLASS, $function->getNodeClass());
        $function = $functions[1];
        $this->assertEquals('block_label', $function->getName());
        $this->assertNull($function->getCallable());
        $this->assertEquals(LayoutExtension::RENDER_BLOCK_NODE_CLASS, $function->getNodeClass());
        $function = $functions[2];
        $this->assertEquals('block_row', $function->getName());
        $this->assertNull($function->getCallable());
        $this->assertEquals(LayoutExtension::RENDER_BLOCK_NODE_CLASS, $function->getNodeClass());
        $function = $functions[3];
        $this->assertEquals('parent_block_widget', $function->getName());
        $this->assertNull($function->getCallable());
        $this->assertEquals(LayoutExtension::RENDER_BLOCK_NODE_CLASS, $function->getNodeClass());
        $function = $functions[4];
        $this->assertEquals('layout_attr_defaults', $function->getName());
        $this->assertNotNull($function->getCallable());
        $this->assertEquals([$this->extension, 'defaultAttributes'], $function->getCallable());
        $function = $functions[5];
        $this->assertEquals('set_class_prefix_to_form', $function->getName());
        $this->assertNotNull($function->getCallable());
        $this->assertEquals([$this->extension, 'setClassPrefixToForm'], $function->getCallable());

        $function = $functions[6];
        $this->assertEquals('convert_value_to_string', $function->getName());
        $this->assertNotNull($function->getCallable());
        $this->assertEquals([$this->extension, 'convertValueToString'], $function->getCallable());
    }

    public function testGetFilters()
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(2, $filters);

        /** @var \Twig_SimpleFilter $blockTextFilter */
        $blockTextFilter = $filters[0];
        $this->assertInstanceOf('Twig_SimpleFilter', $blockTextFilter);
        $this->assertEquals('block_text', $blockTextFilter->getName());
        $this->assertEquals([$this->textHelper, 'processText'], $blockTextFilter->getCallable());

        /** @var \Twig_SimpleFilter $mergeContextFilter */
        $mergeContextFilter = $filters[1];
        $this->assertInstanceOf('Twig_SimpleFilter', $mergeContextFilter);
        $this->assertEquals('merge_context', $mergeContextFilter->getName());
        $this->assertEquals([$this->extension, 'mergeContext'], $mergeContextFilter->getCallable());
    }

    public function testMergeContext()
    {
        $parent = new BlockView();
        $firstChild = new BlockView();
        $secondChild = new BlockView();

        $parent->children['first'] = $firstChild;
        $parent->children['second'] = $secondChild;

        $name = 'name';
        $value = 'value';

        $this->assertEquals($parent, $this->extension->mergeContext($parent, [$name => $value]));

        /** @var BlockView $view */
        foreach ([$parent, $firstChild, $secondChild] as $view) {
            $this->assertArrayHasKey($name, $view->vars);
            $this->assertEquals($value, $view->vars[$name]);
        }
    }

    /**
     * @param array $attr
     * @param array $defaultAttr
     * @param array $expected
     *
     * @dataProvider attributeProvider
     */
    public function testDefaultAttributes($attr, $defaultAttr, $expected)
    {
        $this->assertEquals($expected, $this->extension->defaultAttributes($attr, $defaultAttr));
    }

    /**
     * @return array
     */
    public function attributeProvider()
    {
        return [
            'attributes with tilde' => [
                'attr'  => [
                    'id' => 'someId',
                    'name' => 'test',
                    'class' => 'testClass'
                ],
                'defaultAttr'   => [
                    'autofocus' => true,
                    '~class' => ' input input_block'
                ],
                'expected'  => [
                    'autofocus' => true,
                    'class' => 'testClass input input_block',
                    'id' => 'someId',
                    'name' => 'test',
                ],
            ],
            'attributes with array' => [
                'attr'  => [
                    'id' => 'someId',
                    'name' => 'test',
                    'class' => 'test'
                ],
                'defaultAttr'   => [
                    'autofocus' => true,
                    '~class' => ['class' => ' input input_block']
                ],
                'expected'  => [
                    'autofocus' => true,
                    'class' => ['test', 'class' => ' input input_block'],
                    'id' => 'someId',
                    'name' => 'test',
                ],
            ],
            'attributes with array of arrays' => [
                'attr'  => [
                    'id' => 'someId',
                    'name' => 'test',
                    'class' => ['class_prefixes' => ['mobile']]
                ],
                'defaultAttr'   => [
                    'autofocus' => true,
                    '~class' => ['class' => ' input input_block', 'class_prefixes' => ['web']]
                ],
                'expected'  => [
                    'autofocus' => true,
                    'class' => ['class' => ' input input_block', 'class_prefixes' => ['web', 'mobile']],
                    'id' => 'someId',
                    'name' => 'test',
                ],
            ],
            'attributes without tilde' => [
                'attr'  => [
                    'id' => 'someId',
                    'name' => 'test',
                ],
                'defaultAttr'   => [
                    'autofocus' => true,
                    'class' => 'input input_block'
                ],
                'expected'  => [
                    'autofocus' => true,
                    'class' => 'input input_block',
                    'id' => 'someId',
                    'name' => 'test',
                ],
            ],
            'attributes default' => [
                'attr'  => [
                    'id' => 'someId',
                    'name' => 'test',
                ],
                'defaultAttr'   => [
                    'autofocus' => true,
                    'name' => 'default_value',
                    'class' => 'input input_block'
                ],
                'expected'  => [
                    'autofocus' => true,
                    'class' => 'input input_block',
                    'id' => 'someId',
                    'name' => 'test',
                ],
            ],
        ];
    }

    public function testSetClassPrefixToForm()
    {
        $prototypeView = $this->createMock(FormView::class);

        $childView = $this->createMock(FormView::class);
        $childView->vars['prototype'] = $prototypeView;

        $formView = $this->createMock(FormView::class);
        $formView->children = [$childView];

        $this->extension->setClassPrefixToForm($formView, 'foo');

        $this->assertEquals($formView->vars['class_prefix'], 'foo');
        $this->assertEquals($childView->vars['class_prefix'], 'foo');
        $this->assertEquals($prototypeView->vars['class_prefix'], 'foo');
    }

    /**
     * @dataProvider convertValueToStringDataProvider
     * @param $value
     * @param $expectedConvertedValue
     */
    public function testConvertValueToString($value, $expectedConvertedValue)
    {
        $this->assertSame($expectedConvertedValue, $this->extension->convertValueToString($value));
    }

    /**
     * @return array
     */
    public function convertValueToStringDataProvider()
    {
        return [
            'object conversion' => [
                new \stdClass(),
                'stdClass'
            ],
            'array conversion'  => [
                ['Foo', 'Bar'],
                '["Foo","Bar"]'
            ],
            'null conversion' => [
                null,
                'NULL'
            ],
            'string' => [
                'some string',
                'some string'
            ]

        ];
    }
}

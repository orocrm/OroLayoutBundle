<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\LayoutBundle\Layout\Form\DependencyInjectionFormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAction;

class DependencyInjectionFormAccessorTest extends \PHPUnit_Framework_TestCase
{
    const FORM_SERVICE_ID = 'test_service_id';

    /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
    }

    public function testGetForm()
    {
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $form->expects($this->once())->method('getName')->willReturn('form_name');

        $this->container->expects($this->once())
            ->method('get')
            ->with(self::FORM_SERVICE_ID)
            ->will($this->returnValue($form));

        $formAccessor = new DependencyInjectionFormAccessor($this->container, self::FORM_SERVICE_ID);
        $this->assertSame($form, $formAccessor->getForm());
        $this->assertEquals('form_name', $formAccessor->getName());
    }

    public function testToString()
    {
        $formAccessor = new DependencyInjectionFormAccessor($this->container, self::FORM_SERVICE_ID);
        $this->assertEquals(self::FORM_SERVICE_ID, $formAccessor->toString());
    }

    public function testToStringWithAllParams()
    {
        $formAccessor = new DependencyInjectionFormAccessor(
            $this->container,
            self::FORM_SERVICE_ID,
            FormAction::createByRoute('test_route', ['foo' => 'bar']),
            'post',
            'multipart/form-data'
        );
        $this->assertEquals(
            self::FORM_SERVICE_ID . ';action_route:test_route;method:post;enctype:multipart/form-data',
            $formAccessor->toString()
        );
    }

    public function testParamsInitializer()
    {
        $formAccessor = new DependencyInjectionFormAccessor($this->container, self::FORM_SERVICE_ID);

        $formAction = 'test_action';
        $formMethod = 'test_method';

        $form       = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $formConfig = $this->getMock('Symfony\Component\Form\FormConfigInterface');
        $formView   = new FormView();

        $formView->vars['multipart'] = false;

        $this->container->expects($this->once())
            ->method('get')
            ->with(self::FORM_SERVICE_ID)
            ->will($this->returnValue($form));
        $form->expects($this->once())
            ->method('createView')
            ->will($this->returnValue($formView));
        $form->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));
        $formConfig->expects($this->once())
            ->method('getAction')
            ->will($this->returnValue($formAction));
        $formConfig->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue($formMethod));

        $this->assertEquals($formAction, $formAccessor->getAction()->getPath());
        $this->assertEquals(strtoupper($formMethod), $formAccessor->getMethod());
        $this->assertNull($formAccessor->getEnctype());
        $this->assertEquals(self::FORM_SERVICE_ID, $formAccessor->toString());
    }

    public function testParamsInitializerForMultipartForm()
    {
        $formAccessor = new DependencyInjectionFormAccessor($this->container, self::FORM_SERVICE_ID);

        $formAction = 'test_action';
        $formMethod = 'test_method';

        $form       = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $formConfig = $this->getMock('Symfony\Component\Form\FormConfigInterface');
        $formView   = new FormView();

        $formView->vars['multipart'] = true;

        $this->container->expects($this->once())
            ->method('get')
            ->with(self::FORM_SERVICE_ID)
            ->will($this->returnValue($form));
        $form->expects($this->once())
            ->method('createView')
            ->will($this->returnValue($formView));
        $form->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));
        $formConfig->expects($this->once())
            ->method('getAction')
            ->will($this->returnValue($formAction));
        $formConfig->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue($formMethod));

        $this->assertEquals($formAction, $formAccessor->getAction()->getPath());
        $this->assertEquals(strtoupper($formMethod), $formAccessor->getMethod());
        $this->assertEquals('multipart/form-data', $formAccessor->getEnctype());
        $this->assertEquals(self::FORM_SERVICE_ID, $formAccessor->toString());
    }

    public function testGetView()
    {
        // form
        //   field1
        //     field2
        $formView                       = new FormView();
        $formView->vars['id']           = self::FORM_SERVICE_ID;
        $field1View                     = new FormView($formView);
        $formView->children['field1']   = $field1View;
        $field2View                     = new FormView($field1View);
        $field1View->children['field2'] = $field2View;

        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $this->container->expects($this->once())
            ->method('get')
            ->with(self::FORM_SERVICE_ID)
            ->will($this->returnValue($form));
        $form->expects($this->once())
            ->method('createView')
            ->will($this->returnValue($formView));

        $formAccessor = new DependencyInjectionFormAccessor($this->container, self::FORM_SERVICE_ID);
        $this->assertSame($formView, $formAccessor->getView());
        $this->assertSame($field1View, $formAccessor->getView('field1'));
        $this->assertSame($field2View, $formAccessor->getView('field1.field2'));
        $this->assertSame($formView->vars['id'], $formAccessor->getId());
    }

    public function testProcessedFields()
    {
        $formAccessor = new DependencyInjectionFormAccessor($this->container, self::FORM_SERVICE_ID);

        $this->assertNull($formAccessor->getProcessedFields());

        $processedFields = ['field' => 'block_id'];
        $formAccessor->setProcessedFields($processedFields);
        $this->assertSame($processedFields, $formAccessor->getProcessedFields());
    }

    public function testSetFormData()
    {
        $data = ['test'];

        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $form->expects($this->once())->method('setData')->with($data);
        $form->expects($this->at(2))->method('getData')->willReturn($data);

        $this->container->expects($this->once())
            ->method('get')
            ->with(self::FORM_SERVICE_ID)
            ->will($this->returnValue($form));

        $formAccessor = new DependencyInjectionFormAccessor($this->container, self::FORM_SERVICE_ID);

        $this->assertNull($formAccessor->getForm()->getData());
        $formAccessor->setFormData($data);
        $this->assertEquals($data, $formAccessor->getForm()->getData());
    }

    public function testSetters()
    {
        $formConfig = $this->getMock('Symfony\Component\Form\FormConfigInterface');
        $formView = new FormView();
        $formView->vars['multipart'] = true;

        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $form->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));
        $form->expects($this->once())
            ->method('createView')
            ->will($this->returnValue($formView));

        $this->container->expects($this->once())
            ->method('get')
            ->with(self::FORM_SERVICE_ID)
            ->will($this->returnValue($form));

        $formAccessor = new DependencyInjectionFormAccessor($this->container, self::FORM_SERVICE_ID);

        $action = FormAction::createByRoute('test_route', ['foo' => 'bar']);
        $formAccessor->setAction($action);
        $this->assertEquals($action, $formAccessor->getAction());

        $formAccessor->setActionRoute('test_route', []);
        $this->assertEquals(FormAction::createByRoute('test_route', []), $formAccessor->getAction());

        $formAccessor->setMethod('post');
        $this->assertEquals('post', $formAccessor->getMethod());

        $formAccessor->setEnctype('multipart/form-data');
        $this->assertEquals('multipart/form-data', $formAccessor->getEnctype());
    }
}

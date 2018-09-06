<?php

namespace Themosis\Tests\Metabox;

use Illuminate\Config\Repository;
use League\Fractal\Manager;
use League\Fractal\Serializer\ArraySerializer;
use PHPUnit\Framework\TestCase;
use Themosis\Core\Application;
use Themosis\Forms\Fields\FieldsRepository;
use Themosis\Hook\ActionBuilder;
use Themosis\Hook\FilterBuilder;
use Themosis\Metabox\Factory;
use Themosis\Metabox\MetaboxInterface;
use Themosis\Metabox\Resources\MetaboxResource;
use Themosis\Metabox\Resources\Transformers\MetaboxTransformer;

class MetaboxTest extends TestCase
{
    protected $application;

    protected function getApplication()
    {
        if (! is_null($this->application)) {
            return $this->application;
        }

        $this->application = new Application();

        $this->application->bind('config', function () {
            $config = new Repository();
            $config->set('app.locale', 'en_US');

            return $config;
        });

        return $this->application;
    }

    protected function getFactory()
    {
        return new Factory(
            $this->getApplication(),
            new ActionBuilder($this->getApplication()),
            new FilterBuilder($this->getApplication()),
            $this->getMetaboxResource(),
            new FieldsRepository()
        );
    }

    protected function getMetaboxResource()
    {
        return new MetaboxResource(new Manager(), new ArraySerializer(), new MetaboxTransformer());
    }

    protected function getFieldsFactory()
    {
        return new \Themosis\Field\Factory($this->getApplication());
    }

    public function testCreateEmptyMetaboxWithDefaultArguments()
    {
        $factory = $this->getFactory();

        $box = $factory->make('properties');

        $this->assertInstanceOf(MetaboxInterface::class, $box);
        $this->assertEquals('properties', $box->getId());
        $this->assertEquals('Properties', $box->getTitle());
        $this->assertEquals('post', $box->getScreen());
        $this->assertEquals('advanced', $box->getContext());
        $this->assertEquals('default', $box->getPriority());
        $this->assertEquals([$box, 'handle'], $box->getCallback());
        $this->assertTrue(is_array($box->getArguments()));
        $this->assertTrue(empty($box->getArguments()));
        $this->assertEquals('default', $box->getLayout());
        $this->assertEquals('en_US', $box->getLocale());
        $this->assertEquals('th_', $box->getPrefix());
    }

    public function testCreateMetaboxWithCustomFields()
    {
        $factory = $this->getFactory();
        $fields = $this->getFieldsFactory();

        $box = $factory->make('properties')
            ->add($fields->text('name'))
            ->add($fields->email('email', [
                'group' => 'secondary'
            ]));

        $fieldName = $box->repository()->getField('name');
        $fieldEmail = $box->repository()->getField('email', 'secondary');

        $this->assertEquals('th_name', $fieldName->getName());
        $this->assertEquals('name', $fieldName->getBasename());
        $this->assertEquals('default', $fieldName->getOption('group'));

        $this->assertEquals('th_email', $fieldEmail->getName());
        $this->assertEquals('email', $fieldEmail->getBasename());
        $this->assertEquals('secondary', $fieldEmail->getOption('group'));
    }

    public function testCreateMetaboxResourceWithNoFields()
    {
        $factory = $this->getFactory();

        $box = $factory->make('infos');

        $expected = [
            'id' => 'infos',
            'context' => 'advanced',
            'locale' => 'en_US',
            'priority' => 'default',
            'screen' => [
                'id' => 'post',
                'post_type' => 'post'
            ],
            'title' => 'Infos',
            'fields' => [
                'data' => []
            ],
            'groups' => [
                'data' => []
            ]
        ];

        $this->assertEquals($expected, $box->toArray());
        $this->assertEquals(json_encode($expected), $box->toJson());
    }

    public function testCreateMetaboxResourceWithCustomFields()
    {
        $factory = $this->getFactory();
        $fields = $this->getFieldsFactory();

        $box = $factory->make('properties', 'page')
            ->setTitle('Book Properties')
            ->add($fields->text('author'));

        $expected = [
            'id' => 'properties',
            'context' => 'advanced',
            'locale' => 'en_US',
            'priority' => 'default',
            'screen' => [
                'id' => 'page',
                'post_type' => 'page'
            ],
            'title' => 'Book Properties',
            'fields' => [
                'data' => [
                    [
                        'attributes' => [
                            'id' => 'th_author_field'
                        ],
                        'basename' => 'author',
                        'data_type' => '',
                        'default' => '',
                        'name' => 'th_author',
                        'options' => [
                            'group' => 'default',
                            'info' => ''
                        ],
                        'label' => [
                            'inner' => 'Author',
                            'attributes' => [
                                'for' => 'th_author_field'
                            ]
                        ],
                        'theme' => '',
                        'type' => 'text',
                        'validation' => [
                            'errors' => true,
                            'messages' => [],
                            'placeholder' => 'author',
                            'rules' => ''
                        ],
                        'value' => null
                    ]
                ]
            ],
            'groups' => [
                'data' => [
                    [
                        'id' => 'default',
                        'theme' => '',
                        'title' => ''
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $box->toArray());
        $this->assertEquals(json_encode($expected), $box->toJson());
    }
}

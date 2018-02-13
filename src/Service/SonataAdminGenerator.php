<?php

namespace SonataGenerator\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Twig_Environment as Environment;

class SonataAdminGenerator
{
    const TEMPLATE_PATH_FOR_CLASS = 'SonataGeneratorBundle:Generator:sonata_admin_class.html.twig';
    const TEMPLATE_PATH_FOR_SERVICE = 'SonataGeneratorBundle:Generator:sonata_admin_service.html.twig';
    const SONATA_CLASS_NAME = 'Admin';

    /**
     * @var Environment
     */
    protected $templateEngine;

    /**
     * @var array
     */
    protected $pathToEntities = [];

    /**
     * @var string
     */
    protected $adminControllerNamespace;

    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @var string
     */
    protected $mainNamespace;

    /**
     * @var string
     */
    protected $pathToAdminYaml;

    /**
     * @var bool
     */
    protected $force = false;

    /**
     * @param Environment $templateEngine
     */
    public function __construct(Environment $templateEngine, $pathToAdminYaml)
    {
        $this->templateEngine = $templateEngine;
        $this->pathToAdminYaml = $pathToAdminYaml;
    }

    /**
     * @param array $pathToEntities
     * @return SonataAdminGenerator
     */
    public function setPathToEntities(array $pathToEntities): SonataAdminGenerator
    {
        $this->pathToEntities = $pathToEntities;

        return $this;
    }

    /**
     * @param bool $force
     * @return SonataAdminGenerator
     */
    public function setForce(bool $force): SonataAdminGenerator
    {
        $this->force = $force;

        return $this;
    }

    /**
     * @return string
     */
    public function getMainNamespace(): string
    {
        return $this->mainNamespace;
    }

    /**
     * @param string $mainNamespace
     * @return SonataAdminGenerator
     */
    public function setMainNamespace(string $mainNamespace): SonataAdminGenerator
    {
        $this->mainNamespace = $mainNamespace;

        return $this;
    }


    /**
     * @param string $adminControllerNamespace
     * @return SonataAdminGenerator
     */
    public function setAdminControllerNamespace(string $adminControllerNamespace): SonataAdminGenerator
    {
        $this->adminControllerNamespace = $adminControllerNamespace;

        return $this;
    }

    /**
     * @param string $rootDir
     * @return SonataAdminGenerator
     */
    public function setRootDir(string $rootDir): SonataAdminGenerator
    {
        $this->rootDir = $rootDir;

        return $this;
    }

    /**
     * @param LoggerInterface $logger
     * @throws \ReflectionException
     * @throws \Twig_Error
     * @return SonataAdminGenerator
     */
    public function generateAdminClasses(LoggerInterface $logger): SonataAdminGenerator
    {
        if (empty($this->pathToEntities) || empty($this->adminControllerNamespace)) {
            throw new \InvalidArgumentException('Please set pathToEntities and pathToAdminController!');
        }


        foreach ($this->pathToEntities as $entity) {

            if (!class_exists($entity)) {
                throw new \InvalidArgumentException('Class does not exist! Class: ' . $entity);
            }

            $renderedClass = $this->templateEngine->render(
                static::TEMPLATE_PATH_FOR_CLASS,
                [
                    'fields' => $this->getProperties($entity),
                    'namespace' => $this->getAdminControllerNamespace(),
                    'className' => sprintf('%s%s', $this->getClassName($entity), static::SONATA_CLASS_NAME)
                ]
            );

            $pathToNewSonataClass = sprintf(
                '%s/%s%s.php',
                $this->getAdminPath(),
                $this->getClassName($entity),
                static::SONATA_CLASS_NAME
            );

            if (file_exists($pathToNewSonataClass) && $this->force === false) {
                $logger->warning(
                    sprintf(
                        'Class(%s) already exist please remove it! if you want to generate new one',
                        $pathToNewSonataClass
                    )
                );

                continue;
            }


            file_put_contents($pathToNewSonataClass, $renderedClass);

            $logger->info('Successfully generated sonata class: ' . $pathToNewSonataClass);
        }

        return $this;
    }

    /**
     * @return array
     * @throws \Twig_Error
     */
    public function generateServiceYml(LoggerInterface $logger): SonataAdminGenerator
    {

        $pathToAdmin =  $this->rootDir . '/../' . $this->pathToAdminYaml;

        try {
            $yamlData = Yaml::parseFile($pathToAdmin);
        } catch (ParseException $e) {
            $logger->critical(printf('Unable to parse the YAML string: %s', $e->getMessage()));
        }

        $services = $yamlData['services'];

        foreach ($this->pathToEntities as $entity) {

            $serviceName = 'admin.' . $this->camelCaseToUnderscore($this->getClassName($entity));

            if (!isset($services[$serviceName]) && $this->force === false) {
                $logger->warning(
                    sprintf(
                        'Such service %s alredy defined at the file: %s',
                        $serviceName,
                        $pathToAdmin
                    )
                );

                continue;
            }

            $services[$serviceName] = [
                'class' => sprintf('%s\%s%s',
                    $this->getAdminControllerNamespace(),
                    $this->getClassName($entity),
                    static::SONATA_CLASS_NAME
                ),
                'arguments' => ['~', $entity, '~'],
                'tags' => [[
                    'name' => 'sonata.admin',
                    'manager_type' => 'orm',
                    'label' => $this->getClassName($entity),
                    'group' => 'Default',
                ]]
            ];

        }

        $yamlData['services'] = $services;
        file_put_contents($pathToAdmin,  Yaml::dump($yamlData, 4, 4, Yaml::DUMP_OBJECT_AS_MAP));
        $logger->info('Successfully generated sonata services yaml: ' . $pathToAdmin);

        return $this;
    }


    /**
     * @param string $entity
     * @return string
     */
    protected function getClassName(string $entity): string
    {
        $path = explode('\\', $entity);

        return array_pop($path);
    }

    /**
     * @param string $entity
     * @return array
     * @throws \ReflectionException
     */
    protected function getProperties(string $entity): array
    {
        $reflect = new \ReflectionClass(new $entity());
        $props   = $reflect->getProperties(\ReflectionProperty::IS_PRIVATE);
        $vars = [];
        foreach ($props as $prop) {
            if (in_array($prop->getName(), ['id', 'createdAt', 'updatedAt'])) {
                continue;
            }

            $vars[] = $prop->getName();
        }

        return $vars;
    }

    /**
     * @return string
     */
    protected function getAdminControllerNamespace(): string
    {
        return $this->adminControllerNamespace;
    }

    /**
     * @return string
     */
    protected function getAdminPath(): string
    {
        return $this->rootDir . DIRECTORY_SEPARATOR . str_replace(
            '\\',
            DIRECTORY_SEPARATOR,
            str_replace(
                $this->mainNamespace . '\\',
                '',
                $this->adminControllerNamespace
            )
        );
    }

    /**
     * @param string $input
     * @return string
     */
    function camelCaseToUnderscore(string $input)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}
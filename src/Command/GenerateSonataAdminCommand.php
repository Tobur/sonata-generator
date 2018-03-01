<?php

namespace SonataGenerator\Command;

use SonataGenerator\Service\SonataAdminGenerator;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Finder\Finder;

class GenerateSonataAdminCommand extends Command
{
    protected static $defaultName = 'generate:sonata-admin';

    const MAIN_NAMESPACE = 'App';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SonataAdminGenerator
     */
    protected $sonataAdminGenerator;

    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @param LoggerInterface $logger
     * @param SonataAdminGenerator $sonataAdminGenerator
     * @param string $rootDir
     */
    public function __construct(
        LoggerInterface $logger,
        SonataAdminGenerator $sonataAdminGenerator,
        $rootDir
    ) {

        $this->logger = $logger;
        $this->sonataAdminGenerator = $sonataAdminGenerator;
        $this->rootDir = $rootDir;

        parent::__construct(static::$defaultName);
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Generate sonata admin')
            ->addArgument(
                'entity',
                InputArgument::REQUIRED,
                'Path to Entity. Example "App\Entity\Post"'
            )
            ->addArgument(
                'admin-path',
                InputArgument::REQUIRED,
                'Path to You Admin. Example "App\Admin\" or "App\Controller\Admin\\"'
            )
            ->addArgument(
                'fetch-entity-interactive',
                InputArgument::OPTIONAL,
                'Fetch entity interactive from folder which you specify in entity argument.
                 Possible value 1 or 0'
            )
            ->addArgument(
                'force',
                InputArgument::OPTIONAL,
                'Rewrite services and classes'
            )

        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \ReflectionException
     * @throws \Twig_Error
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->pushHandler(new ConsoleHandler($output, true, [
            LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::INFO   => OutputInterface::VERBOSITY_NORMAL,
        ]));
        $entity = $input->getArgument('entity');
        $adminPath =  $input->getArgument('admin-path');
        $fetchEntityInteractive = boolval($input->getArgument('fetch-entity-interactive'));
        $force = boolval($input->getArgument('fetch-entity-interactive'));

        if (false === $fetchEntityInteractive) {
            if (!class_exists($entity)) {
                throw new \InvalidArgumentException('We can\'t find your entity! Please check path: ' . $entity);
            }

            $pathToEntities = [$entity];
        } else {
            $pathToEntities = $this->interactiveEntitySelect($entity, $input, $output);
        }

        $pathToDir = $this->rootDir. $adminPath;

        if (is_dir($pathToDir)) {
            throw new \InvalidArgumentException('Admin path does not exist. Please check: ' . $pathToDir);
        }


        $servicesYml = $this->sonataAdminGenerator
            ->setMainNamespace(static::MAIN_NAMESPACE)
            ->setAdminControllerNamespace($adminPath)
            ->setPathToEntities($pathToEntities)
            ->setRootDir($this->rootDir)
            ->setForce($force)
            ->generateAdminClasses($this->logger)
            ->generateServiceYml($this->logger);
    }

    /**
     * @param string $entityPath
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array
     */
    protected function interactiveEntitySelect(
        string $entityPath,
        InputInterface $input,
        OutputInterface $output
    ): array {
        $finder = new Finder();
        $dir = str_replace('/', DIRECTORY_SEPARATOR, str_replace('App\\', '', $entityPath));
        $pathToEntityFolder = $this->rootDir . DIRECTORY_SEPARATOR . $dir;
        $finder->files()->in($pathToEntityFolder);
        $data = [];

        foreach ($finder as $file) {
            list($className,) = explode('.', $file->getRelativePathname());
            $className = $entityPath . '\\' . $className;
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'Do you want to generate admin for entity: ' . $className,
                ['y', 'n'],
                'y'
            );

            $answer = $helper->ask($input, $output, $question);

            if ('y' === $answer) {
                $data[] = $className;
            }
        }

        return $data;
    }
}

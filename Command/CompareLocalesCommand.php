<?php
/**
 * This file is part of the EcommitUtilBundle package.
 *
 * Copyright for portions of this file are held by Fabien Potencier as part of Symfony package
 * and are provided under the MIT license.
 * All other copyright for this file are held by E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\UtilBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Translation\MessageCatalogue;

class CompareLocalesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('ecommit:translation:compare-locales')
            ->setDefinition(array(
                new InputArgument('source-locale', InputArgument::REQUIRED, 'The source locale'),
                new InputArgument('target-locale', InputArgument::REQUIRED, 'The target locale'),
                new InputArgument('bundle', InputArgument::OPTIONAL, 'The bundle name or directory where to load the messages, defaults to app/Resources folder'),
                new InputOption('domain', null, InputOption::VALUE_OPTIONAL, 'The messages domain'),
                new InputOption('all', null, InputOption::VALUE_NONE, 'Load messages from all registered bundles'),
            ))
            ->setDescription('Compare locales');
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        if (!class_exists('Symfony\Component\Translation\Translator')) {
            return false;
        }

        return parent::isEnabled();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $sourceLocale = $input->getArgument('source-locale');
        $targetLocale = $input->getArgument('target-locale');
        $domain = $input->getOption('domain');
        /** @var TranslationLoader $loader */
        $loader = $this->getContainer()->get('translation.loader');
        /** @var Kernel $kernel */
        $kernel = $this->getContainer()->get('kernel');

        // Define Root Path to App folder
        $transPaths = array($kernel->getRootDir().'/Resources/');

        // Override with provided Bundle info
        if (null !== $input->getArgument('bundle')) {
            try {
                $bundle = $kernel->getBundle($input->getArgument('bundle'));
                $transPaths = array(
                    $bundle->getPath().'/Resources/',
                    sprintf('%s/Resources/%s/', $kernel->getRootDir(), $bundle->getName()),
                );
            } catch (\InvalidArgumentException $e) {
                // such a bundle does not exist, so treat the argument as path
                $transPaths = array($input->getArgument('bundle').'/Resources/');

                if (!is_dir($transPaths[0])) {
                    throw new \InvalidArgumentException(sprintf('"%s" is neither an enabled bundle nor a directory.', $transPaths[0]));
                }
            }
        } elseif ($input->getOption('all')) {
            foreach ($kernel->getBundles() as $bundle) {
                $transPaths[] = $bundle->getPath().'/Resources/';
                $transPaths[] = sprintf('%s/Resources/%s/', $kernel->getRootDir(), $bundle->getName());
            }
        }

        // Extract messages
        $extractedCatalogueSource = $this->loadCurrentMessages($sourceLocale, $transPaths, $loader);
        $extractedCatalogueTarget = $this->loadCurrentMessages($targetLocale, $transPaths, $loader);
        $allMessagesSource = $extractedCatalogueSource->all($domain);
        if (!$this->checkExtractMessages($io, $allMessagesSource, $domain, $sourceLocale)) {
            return;
        }
        if (null !== $domain) {
            $allMessagesSource = array($domain => $allMessagesSource);
        }
        $allMessagesTarget = $extractedCatalogueTarget->all($domain);
        if (!$this->checkExtractMessages($io, $allMessagesTarget, $domain, $targetLocale)) {
            return;
        }
        if (null !== $domain) {
            $allMessagesTarget = array($domain => $allMessagesTarget);
        }

        //Diff
        $rows = array();
        foreach ($allMessagesSource as $domain => $messages) {
            foreach ($messages as $id => $message) {
                if (!$extractedCatalogueTarget->has($id, $domain)) {
                    $rows[] = ['<error>Missing</error>', $domain, $id, $message];
                }
            }
        }
        foreach ($allMessagesTarget as $domain => $messages) {
            foreach ($messages as $id => $message) {
                if (!$extractedCatalogueSource->has($id, $domain)) {
                    $rows[] = ['<info>Unused</info>', $domain, $id, $message];
                }
            }
        }

        if (count($rows) > 0) {
            $headers = array('State', 'Domain', 'Id', 'Messsage');
            $io->table($headers, $rows);
        } else {
            $io->success('No error.');
        }
    }

    /**
     * @param string            $locale
     * @param array             $transPaths
     * @param TranslationLoader $loader
     *
     * @return MessageCatalogue
     */
    private function loadCurrentMessages($locale, $transPaths, TranslationLoader $loader)
    {
        $currentCatalogue = new MessageCatalogue($locale);
        foreach ($transPaths as $path) {
            $path = $path.'translations';
            if (is_dir($path)) {
                $loader->loadMessages($path, $currentCatalogue);
            }
        }

        return $currentCatalogue;
    }

    /**
     * @param SymfonyStyle $io
     * @param $allMessages
     * @param $domain
     * @param $locale
     * @return bool
     */
    private function checkExtractMessages(SymfonyStyle $io, $allMessages, $domain, $locale)
    {
        if (empty($allMessages) || null !== $domain && empty($allMessages[$domain])) {
            $outputMessage = sprintf('No defined or extracted messages for locale "%s"', $locale);

            if (null !== $domain) {
                $outputMessage .= sprintf(' and domain "%s"', $domain);
            }

            $io->warning($outputMessage);

            return false;
        }

        return true;
    }
}

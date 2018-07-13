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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Yaml\Yaml;

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
                new InputOption('domain', null, InputOption::VALUE_OPTIONAL, 'The messages domain'),
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
        $transltator = $this->getContainer()->get('translator');

        //Config
        $config = array();
        $fs = new Filesystem();
        $configFile = $this->getContainer()->get('kernel')->getProjectDir().'/config/compare_locales.yaml';
        if ($configFile) {
            $config = Yaml::parseFile($configFile);
        }

        // Extract messages
        $extractedCatalogueSource = $transltator->getCatalogue($sourceLocale);
        $extractedCatalogueTarget = $transltator->getCatalogue($targetLocale);
        $allMessagesSource = $extractedCatalogueSource->all($domain);
        if (null !== $domain) {
            $allMessagesSource = array($domain => $allMessagesSource);
        }
        if (!$this->checkExtractMessages($io, $allMessagesSource, $domain, $sourceLocale)) {
            return;
        }
        $allMessagesTarget = $extractedCatalogueTarget->all($domain);
        if (null !== $domain) {
            $allMessagesTarget = array($domain => $allMessagesTarget);
        }
        if (!$this->checkExtractMessages($io, $allMessagesTarget, $domain, $targetLocale)) {
            return;
        }

        //Diff
        $rows = array();
        foreach ($allMessagesSource as $domain => $messages) {
            foreach ($messages as $id => $message) {
                if (empty($allMessagesTarget[$domain][$id]) && !$this->ignoreMissingMessage($targetLocale, $id, $config)) {
                    $rows[] = ['<error>Missing</error>', $domain, $id, $message];
                }
            }
        }
        foreach ($allMessagesTarget as $domain => $messages) {
            foreach ($messages as $id => $message) {
                if (empty($allMessagesSource[$domain][$id])) {
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
     * @param SymfonyStyle $io
     * @param $allMessages
     * @param $domain
     * @param $locale
     * @return bool
     */
    private function checkExtractMessages(SymfonyStyle $io, $allMessages, $domain, $locale)
    {
        if (empty($allMessages) || (null !== $domain && empty($allMessages[$domain]))) {
            $outputMessage = sprintf('No defined or extracted messages for locale "%s"', $locale);

            if (null !== $domain) {
                $outputMessage .= sprintf(' and domain "%s"', $domain);
            }

            $io->warning($outputMessage);

            return false;
        }

        return true;
    }

    /**
     * @param string $locale
     * @param string $messageId
     * @param array $config
     *
     * @return bool
     */
    protected function ignoreMissingMessage($locale, $messageId, $config)
    {
        if (!isset($config['ignore_missing_messages'])) {
            return false;
        }

        foreach ([$locale, 'all'] as $lang) {
            if (isset($config['ignore_missing_messages'][$lang]) && in_array($messageId, $config['ignore_missing_messages'][$lang])) {
                return true;
            }
        }

        return false;
    }
}

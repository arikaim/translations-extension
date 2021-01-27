<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Extensions\Translations\Console;

use Arikaim\Core\Console\ConsoleCommand;
use Arikaim\Core\Console\ConsoleHelper;
use Arikaim\Core\Arikaim;
use Arikaim\Extensions\Translations\Classes\Translations;

/**
 * Translate theme page command
 */
class TranslatePage extends ConsoleCommand
{  
    /**
     * Configure command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('translate:page')->setDescription('Translate page.');
        $this->addOptionalArgument('theme','Theme Name'); 
        $this->addOptionalArgument('page','Theme page name'); 
        $this->addOptionalArgument('language','Translate to language code'); 
    }

    /**
     * Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function executeCommand($input, $output)
    {       
        $this->showTitle();

        $theme = $input->getArgument('theme');       
        if (empty($theme) == true) {
            $this->showError("Missing theme name option!");
            return;
        }
        
        $pageName = $input->getArgument('page');
        if (empty($pageName) == true) {
            $this->showError("Missing page name option!");
            return;
        }

        $language = $input->getArgument('language');
        if (empty($language) == true) {
            $this->showError("Missing language code option!");
            return;
        }

        $manager = Arikaim::packages()->create('template');
        if ($manager->hasPackage($theme) == false) {
            $this->showError("Theme name $theme not valid!");
            return;
        }
        $package = $manager->createPackage($theme);

        $driverName = Arikaim::options()->get('translations.service.driver');
        $driver = Arikaim::driver()->create($driverName);
        if (\is_object($driver) == false) {
            $this->showError('Not valid translation api driver');
            return;
        }

        $translations = new Translations();
        $translations->onJobProgress(function($name) {
            $this->writeLn('  ' . ConsoleHelper::checkMark() . ' ' . $name);
        });

        $this->writeFieldLn('Theme',$theme);
        $this->writeFieldLn('Page',$pageName);
        $this->writeFieldLn('From language','en');
        $this->writeFieldLn('To language',$language);
        $this->writeFieldLn('Driver',$driverName);
        $this->writeLn('');

        $translations->translatePage($package,$driver,$language,$pageName);

        if ($translations->hasError() == true) {
            $this->showErrors($translations->getErrors());
            return;
        } 

        $this->showCompleted();
    }    
}

<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Extensions\Translations;

use Arikaim\Core\Extension\Extension;

/**
 * Translations extension
*/
class Translations extends Extension
{
    /**
     * Install extension routes, events, jobs ..
     *
     * @return void
    */
    public function install()
    {
        // Control Panel
        $this->addApiRoute('PUT','/api/translations/admin/translate','TranslationsControlPanel','translate','session');   
        $this->addApiRoute('POST','/api/translations/admin/translate/model','TranslationsControlPanel','translateModel','session');   
        // Options
        $this->createOption('translations.service.driver','google-simple');  
        $this->createOption('translations.service.disable',false);                    
    }
    
    /**
     * UnInstall
     *
     * @return void
     */
    public function unInstall()
    {         
    }
}

<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Extensions\Translations\Classes;

use Arikaim\Core\Db\Model;
use Arikaim\Core\Packages\Interfaces\ViewComponentsInterface;
use Arikaim\Core\Utils\File;
use Arikaim\Core\Utils\Utils;
use Arikaim\Core\Collection\Arrays;
use Arikaim\Core\Arikaim;

use Arikaim\Core\System\Error\Traits\TaskErrors;
use Arikaim\Core\Queue\Traits\JobProgress;

/**
 * Translations  
*/
class Translations
{
    use 
        TaskErrors,
        JobProgress;

    /**
     * Translate email
     *
     * @param object $package
     * @param object $driver
     * @param string $language
     * @param string $pageName       
     * @return boolean
    */
    public function translateEmail($package, $driver, string $language, string $pageName): bool
    {
        return $this->translateComponent($package,$driver,$language,$pageName,'emails');
    }

    /**
     * Translate page
     *
     * @param object $package
     * @param object $driver
     * @param string $language
     * @param string $pageName       
     * @return boolean
    */
    public function translatePage($package, $driver, string $language, string $pageName): bool
    {
        return $this->translateComponent($package,$driver,$language,$pageName,'pages');
    }

    /**
     * Translate theme components
     *
     * @param object $package
     * @param object $driver
     * @param string $language
     * @param string $type
     * @return bool
     */
    public function translateComponents($package, $driver, string $language, string $type = 'components', string $parent = ''): bool
    {
        $components = Self::getPackageComponents($package,$parent,$type);
        $errors = 0;

        foreach($components as $item) {
            $componentName = $item['full_name'];               
            $result = $this->translateComponent($package,$driver,$language,$componentName,$type);     
            $errors += ($result === false) ? 1 : 0; 
        }

        return ($errors == 0);
    }

    /**
     * Translate component and all child components
     *
     * @param object $package
     * @param object $driver
     * @param string $language
     * @param string $componentName
     * @param string $type    
     * @return boolean
     */
    public function translateComponent($package, $driver, string $language, string $componentName, string $type = 'components'): bool
    {
        $childComponents = Self::getPackageComponents($package,$componentName,$type);
        if (\count($childComponents) > 0) {   
            // translate child        
            $result = $this->translateComponents($package,$driver,$language,$type,$componentName);
            if ($result === false) {
                $this->addError('Error translating child components.');      
                return false;
            }
        }

        // read english translation file
        $translation = $package->readTranslation($componentName,'en',$type);
        if ($translation === false) {
            $this->addError('Missing english language file');      
            return false;
        }

        $path = $package->getComponentPath($componentName,$type);
        $newFile = $package->resolveTranslationFileName($path,$language);      

        if (File::setWritable($path) == false) {
            $this->addError('Path not writable!');                 
            return false;
        }    

        if (File::exists($newFile) == true) {
            $this->addError('Translation file exists!');                
            return false;
        }

        $translation = Self::translate($translation,$language,$driver);
        if ($translation === false) {
            $this->addError('Translation api error!');    
            return false;
        }

        $result = File::write($newFile,Utils::jsonEncode($translation));   
        if ($result !== false) {      
            $this->jobProgress($componentName);     
            return true;              
        }        
        $this->addError('Error saveig translation file');
     
        return false;              
    }

    /**
     * Translate array
     *
     * @param array $translation
     * @param string $language
     * @param object $driver
     * @return array|false
     */
    public static function translate(array $translation, string $language, $driver)
    {
        foreach ($translation as $key => $value) {
            if (\is_array($value) == true) {
                $translation[$key] = Self::translate($value,$language,$driver);
            } else {       
                $translatedText = $driver->translate($value,$language); 
                $translation[$key] = ($translatedText === false) ? $translation[$key] : $translatedText;             
            }           
        }

        return $translation;
    }

    /**
     * Get package components
     *
     * @param ViewComponentsInterface $package
     * @param string $componentName
     * @param string $type
     * @return array
     */
    public static function getPackageComponents(ViewComponentsInterface $package, string $componentName, string $type)
    {
        switch($type) {
            case 'pages': 
                $components = $package->getPages($componentName);
                break;
            case 'components': 
                $components = $package->getComponents($componentName);
                break;
            case 'emails': 
                $components = $package->getEmails($componentName);
                break;
            default:
                $components = $package->getComponents($componentName);
                break;
        }

        return $components;
    }

    /**
     * Translate db model
     *
     * @param string $modelName
     * @param string|null $extension
     * @param string|int $id
     * @param string|array $fields
     * @param string $language
     * @return array|false
     */
    public static function translateDbModel(string $modelName, ?string $extension, $id, $fields, string $language)
    {
        $fields = (\is_array($fields) == false) ? Arrays::toArray($fields,',') : $fields;  
        $model = Model::create($modelName,$extension)->findById($id);
        if (empty($model) == true) {           
            return false;
        }
        $driver = Self::createTranslationDriver();

        return Translations::translateFields($fields,$model->toArray(),$language,$driver);      
    }

    /**
     * Create translation driver
     *
     * @return Driver|null
     */
    public static function createTranslationDriver()
    {
        $driverName = Arikaim::options()->get('translations.service.driver');
        $driver = Arikaim::driver()->create($driverName);
       
        return $driver;
    } 

    /**
     * Translate array
     *
     * @param string|array $fields
     * @param array $values
     * @param string $language
     * @return array|false
     */
    public function traslateArray($fields, array $values, string $language)
    {
        $driver = Self::createTranslationDriver();

        return Self::translateFields($fields,$values,$language,$driver);
    } 

    /**
     * Translate fields
     *
     * @param string|array $fields
     * @param array $values
     * @param string $language
     * @param object $driver
     * @return array|false
     */
    public static function translateFields($fields, array $values, string $language, $driver)
    {
        $fields = (\is_string($fields) == true) ? Arrays::toArray($fields,',') : $fields;  
      
        foreach ($fields as $index => $key) {           
            $text = $values[$key] ?? null;
            $text = (\is_array($text) == true) ? null : \trim($text);   
            if (empty($text) == false) {
                $translatedText = $driver->translate($text,$language);
                if ($translatedText === false) {
                    return false;
                }
                $translatedFields[$key] = ($translatedText === false) ? $text : $translatedText;   
            } 
                                
        }

        return $translatedFields;
    } 
}

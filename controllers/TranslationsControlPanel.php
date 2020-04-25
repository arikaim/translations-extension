<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Extensions\Translations\Controllers;

use Arikaim\Core\Controllers\ControlPanelApiController;
use Arikaim\Core\Db\Model;
use Arikaim\Core\Collection\Arrays;

use Arikaim\Core\Controllers\Traits\Status;

/**
 * Translations control panel controller
*/
class TranslationsControlPanel extends ControlPanelApiController
{
    use Status;

    /**
     * Init controller
     *
     * @return void
     */
    public function init()
    {
        $this->loadMessages('translations::admin.messages');
    }

    /**
     *  Translate text
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
    */
    public function translateController($request, $response, $data) 
    {       
        $this->onDataValid(function($data) {            
            $language = $data->get('target_language',$this->getPageLanguage($data));
            $text = $data->get('text','');         
            $driverName = $this->get('options')->get('translations.service.driver');

            $driver = $this->get('driver')->create($driverName);
            if (is_object($driver) == false) {
                $this->error('Not valid translation api driver');
                return;
            }
            
            $translatedText = $driver->getInstance()->translate($text,$language);
           
            $this->setResponse($translatedText,function() use($language,$translatedText) {                                
                $this
                    ->message('translate')
                    ->field('text',$translatedText)
                    ->field('language',$language);                         
            },'errors.translate');
        });
        $data           
            ->addRule('text:min=2','text')           
            ->validate();       
    }

    /**
     *  Translate database model fields. 
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
    */
    public function translateModelController($request, $response, $data) 
    {       
        $this->onDataValid(function($data) { 
            $extension = $data->get('extension',null);  
            $language = $data->get('language',$this->getPageLanguage($data));
            $uuid = $data->get('uuid',null);  
            $fields = Arrays::toArray($data->get('fields',''),',');  
            $translatedFields = [];          

            $model = Model::create($data['model'],$extension)->findById($uuid);
            if (is_object($model) == false) {
                $this->error('Not valid translation uuid.');
                return;
            }
            
            $driverName = $this->get('options')->get('translations.service.driver');
            $driver = $this->get('driver')->create($driverName);
            if (is_object($driver) == false) {
                $this->error('Not valid translation api driver');
                return;
            }

            // do translations
            $modelFields = $model->toArray();
            foreach ($fields as $index => $key) {
                $text = (isset($modelFields[$key]) == true) ? $modelFields[$key] : false;
                $translatedFields[$key] = ($text != false) ? $driver->getInstance()->translate($text,$language) : '';                        
            }

            $this->setResponse(true,function() use($language,$uuid,$translatedFields) {                                
                $this
                    ->message('create')
                    ->field('uuid',$uuid)
                    ->field('fields',$translatedFields)
                    ->field('language',$language);                                  
            },'errors.create');
        });
        $data
            ->addRule('text:required','fields')         
            ->validate();       
    }
}

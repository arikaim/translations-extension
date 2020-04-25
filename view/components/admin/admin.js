/**
 *  Arikaim
 *  @copyright  Copyright (c) Konstantin Atanasov <info@arikaim.com>
 *  @license    http://www.arikaim.com/license
 *  http://www.arikaim.com
 */
'use strict';

function TranslationsControlPanel() {
    var self = this;

    this.translate = function(text, targetLanguage, sourceLanguage, onSuccess, onError) {
        sourceLanguage = getDefaultValue(sourceLanguage,'auto');
        var data = {
            target_language: targetLanguage,
            source_language: sourceLanguage,
            text: text
        };
        
        return arikaim.put('/api/translations/admin/translate',data,onSuccess,onError);          
    };

    this.translateModel = function(options, onSuccess, onError) {        
        return arikaim.post('/api/translations/admin/translate/model',options,onSuccess,onError);          
    };

    this.init = function() {    
        arikaim.ui.tab();
    };
}

var translations = new TranslationsControlPanel();

arikaim.page.onReady(function() {
    translations.init();
});
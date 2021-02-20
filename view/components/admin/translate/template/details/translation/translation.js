'use strict';

arikaim.component.onLoaded(function() { 
    arikaim.ui.button('.update-translation',function(element) {
        var theme = $(element).attr('theme');
        var type = $(element).attr('type');
        var language = $(element).attr('language');
        var componentName = $(element).attr('component-name');    
        $('#translation_editor').html("");

        return translations.updateTranslation(theme,componentName,language,type,function(result) {
            templateTranslations.loadEditor(result.theme,result.language,result.component,result.type);   
        },function(error) {               
            arikaim.page.toastMessage({
                message: error[0],
                class: 'error'
            });
        });
    });
});
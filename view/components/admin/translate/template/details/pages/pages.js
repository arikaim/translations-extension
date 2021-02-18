'use strict';

arikaim.component.onLoaded(function() {    
    safeCall('templateTranslations',function(obj) {
        obj.initRows();
    },true);   
}); 
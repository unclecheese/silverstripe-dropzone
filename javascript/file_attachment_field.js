(function () {

document.addEventListener('DOMContentLoaded', function(){    
    var dropzones = document.querySelectorAll('.dropzone-holder');

    if(!dropzones) return;
    
    [].slice.call(dropzones).forEach(function(node) {
        var settings = JSON.parse(node.getAttribute('data-config'));
        var template = node.querySelector('template');
        
        if(template) {
            settings.previewTemplate = template.innerHTML;
        }
        settings.previewsContainer = node.querySelector('[data-container]')
        
        var dz = new Dropzone(node, settings);
    });
});


})();
/*----------------------------------********---------------------------------*/
/**features-begins**/
(function(){
    var validationButton = {
        id: 'validationButton',
        name: 'validationButton',
        htmlProperty: {
            id: 'validationButton',
            element: 'li',
            child: {
                elemet: 'a',
                class: 'mafe-toolbar-validation',
                child: {
                    element: 'span',
                    class: 'mafe-toolbar-validation'
                }
            }
        },
        actions: {
            selector: ".mafe-toolbar-validation",
            tooltip: "Validator".translate(),
            execute: true,
            handler: function () {
                PMDesigner.reloadDataTable();
            }
        }
    };
    if(window.defaultNavbarPanelMenus){
        // Add button properties
        window.defaultNavbarPanelMenus.addItemToNavBarPanelMenu(validationButton);
        // Add button icon
        PMDesigner.sidebar.push(new ToolbarPanel({
            buttons: [
                {
                    selector: 'VALIDATOR',
                    className: [
                        'mafe-designer-icon',
                        'mafe-toolbar-validation'
                    ],
                    tooltip: "Validate Now".translate()
                }
            ]
        }));
    }
})();
/**features-ends**/
/*----------------------------------********---------------------------------*/

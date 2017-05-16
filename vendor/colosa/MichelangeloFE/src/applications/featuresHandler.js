/*----------------------------------********---------------------------------*/
var FeatureUtils = function () {

};

FeatureUtils.prototype.generateUId = function () {
    var text = "",
        i,
        possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    for (i = 0; i < 16; i += 1) {
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    }
    return text;
};

var ElementFactory = function () {
    this.utils = new FeatureUtils();
};

ElementFactory.prototype.getElement = function (data) {
    var element = {};
    element.id = this.utils.generateUId();
    element.name = data.name;
    element.label = data.label;
    element.valueType = data.valueType;
    element.controlsWidth = data.controlsWidth;
    element.required = data.required;
    element.events = data.events;
    element.listeners = data.listeners;

    switch (data.type) {
        case 'text':
            element.pmType = data.type;
            element.maxLength = 3;
            break;
        case 'hidden':
            element.pmType = data.type;
            break;
        case 'textarea':
            element.pmType = data.type;
            element.rows = data.rows;
            break;
        case 'dropdown':
            element.pmType = data.type;
            element.options = data.options;
            break;
        case 'checkbox':
            element.pmType = data.type;
            break;
        case 'link':
            element.pmType = data.type;
            break;
        case 'button':
            element.pmType = data.type;
            break;
        case 'criteria':
            element.pmType = data.type;
            element.rows = data.rows;
            break;
        default:
            break;
    }

    return element;
};

var FeaturesHandler = function () {
    this.elementFactory = new ElementFactory();
    this.utils = new FeatureUtils();
    this.forms = [];
};

FeaturesHandler.prototype.registerEvents = function (items) {

};

FeaturesHandler.prototype.registerListeners = function (items) {

};

FeaturesHandler.prototype.wireEvents = function (items) {
    this.registerEvents(items);
    this.registerListeners(items);
};

FeaturesHandler.prototype.parseForm = function (formData) {
    var form = {};
    form.id = this.utils.generateUId();
    form.title = formData.title;
    form.icon = (formData.icon || '');
    form.action = formData.action;
    form.panel = new PMUI.form.Form({
        id: this.utils.generateUId(),
        visibleHeader: false,
        width: DEFAULT_WINDOW_WIDTH - 250,
        items: this.parseItems(formData)
    });
    return form;
};

FeaturesHandler.prototype.parseItems = function (formData) {
    var items = [];
    if (this.validateData(formData)) {
        for (var i in formData.fields) {
            items.push(
                this.elementFactory.getElement(formData.fields[i])
            );
        }
    }
    this.wireEvents(items);
    return items;
};

FeaturesHandler.prototype.validateData = function (formData) {
    switch (true) {
        case !(formData.type == 'form'):
            return false;
            break;
        case formData.language == undefined:
            return false;
            break;
        default:
            return true;
            break;
    }
};

FeaturesHandler.prototype.getForms = function () {
    return [];
};

PMDesigner.featuresHandler = new FeaturesHandler();
/*----------------------------------********---------------------------------*/
/**
 * @class IntroHelper
 * Handle Intro helper
 *
 * @constructor
 * Creates a new instance of the class
 * @param {Object} options
 */
var IntroHelper = function (options) {
    this.steps = [];
    this.introjs = null;
    IntroHelper.prototype.initObject.call(this, options);
};
IntroHelper.prototype.type = 'IntroHelper';

IntroHelper.prototype.initObject = function (options) {
    var defaults = {
        steps: [],
        skipLabel: "Skip"

    };
    $.extend(true, defaults, options);
    this.setSteps(defaults.steps);

    this.setSkipLabel(defaults.skipLabel);
    this.setNextLabel(defaults.nextLabel);
    this.setPrevLabel(defaults.prevLabel);
    this.setDoneLabel(defaults.doneLabel);

};

IntroHelper.prototype.setSteps = function (steps) {
    this.steps = steps;
    return this;
};

IntroHelper.prototype.setSkipLabel = function (label) {
    this.skipLabel = label;
    return this;
};
IntroHelper.prototype.setNextLabel = function (label) {
    this.nextLabel = label;
    return this;
};

IntroHelper.prototype.setPrevLabel = function (label) {
    this.prevLabel = label;
    return this;
};

IntroHelper.prototype.setDoneLabel = function (label) {
    this.doneLabel = label;
    return this;
};

IntroHelper.prototype.setSkipLabel = function (label) {
    this.skipLabel = label;
    return this;
};

IntroHelper.prototype.startIntro = function () {
    this.introjs = introJs();
    this.introjs.setOptions({
        steps: this.steps,
        skipLabel: this.skipLabel,
        nextLabel: this.nextLabel,
        prevLabel: this.prevLabel,
        doneLabel: this.doneLabel
    });

    this.introjs.start();
};
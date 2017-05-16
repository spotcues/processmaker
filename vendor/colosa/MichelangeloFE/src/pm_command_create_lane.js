/**
 * Command create: command created when a lane is created (from the toolbar)
 * @class BPMNCommandCreateLane
 * @constructor
 */
var PMCommandCreateLane = function (receiver) {
    var NewObj = function (receiver) {
        PMUI.command.CommandCreate.call(this, receiver);
    };

    NewObj.prototype = new PMUI.command.CommandCreate(receiver);
    /**
     * Type of command of this object
     * @type {String}
     */
    NewObj.prototype.type = 'PMCommandCreateLane';
    /**
     * Executes the command
     */
    NewObj.prototype.execute = function () {
        PMUI.command.CommandCreate.prototype.execute.call(this);
        this.receiver.parent.setLanePositionAndDimension(this.receiver);
    };
    /**
     * Inverse executes the command a.k.a. undo
     */
    NewObj.prototype.undo = function () {
        PMUI.command.CommandCreate.prototype.undo.call(this);
        this.receiver.parent.bpmnLanes.remove(this.receiver);
        this.receiver.parent.updateOnRemoveLane(this.receiver);
    };
    NewObj.prototype.redo = function () {
        this.execute();
        return this;
    };
    return new NewObj(receiver);
};

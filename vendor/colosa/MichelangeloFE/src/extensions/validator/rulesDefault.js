/*----------------------------------********---------------------------------*/
/**features-begins**/
var defaultRules = {
    'bpmn:StartEvent': [
        {
            code: '105101',
            description: 'Start Event must have an outgoing sequence flow'.translate(),
            type: 'bpmnStartEvent',
            severity: 'Error',
            criteria: function (shape, error) {
                var ref = shape.businessObject.elem;
                if (ref && !(ref.outgoing && ref.outgoing.length === 1)) {
                    //add error
                    shape.addErrorLog(error);
                }
            }
        },
        {
            code: '105102',
            description: "Can't connect start event to subprocess".translate(),
            type: 'bpmnStartEvent',
            severity: 'Error',
            criteria: function (shape, error) {
                var ref = shape.businessObject.elem,
                    refArray = ref.outgoing;
                if (ref && refArray && refArray.length === 1) {
                    if (refArray[0].targetRef.$type === "bpmn:SubProcess") {
                        //add error
                        shape.addErrorLog(error);
                    }
                }
            }
        },
        {
            code: '105103',
            description: 'Invalid Connection between elements'.translate(),
            type: 'bpmnStartEvent',
            severity: 'Error',
            criteria: function (shape, error) {
                if(!shape.ValidateConnections()) {
                    //add error
                    shape.addErrorLog(error);
                }
            }
        }
    ],
    'bpmnActivity': [
        {
            code: '105201',
            description: 'Activity must have an incoming sequence flow'.translate(),
            type: 'bpmnActivity',
            severity: 'Error',
            criteria: function (shape, error) {
                var ref = shape.businessObject.elem;
                if (ref && !(ref.incoming && ref.incoming.length > 0 )) {
                    //add error
                    shape.addErrorLog(error);
                }
            }
        },
        {
            code: '105202',
            description: 'Activity must have an outgoing sequence flow'.translate(),
            type: 'bpmnActivity',
            severity: 'Error',
            criteria: function (shape, error) {
                var ref = shape.businessObject.elem;
                if (ref && !(ref.outgoing && ref.outgoing.length === 1)) {
                    //add error
                    shape.addErrorLog(error);
                }
            }
        },
        {
            code: '105203',
            description: 'Invalid Connection between elements'.translate(),
            type: 'bpmnActivity',
            severity: 'Error',
            criteria: function (shape, error) {
                if(!shape.ValidateConnections()) {
                    //add error
                    shape.addErrorLog(error);
                }
            }
        }
    ],
    'bpmn:EndEvent': [
        {
            code: '105301',
            description: 'End event must have an incoming sequence flow'.translate(),
            type: 'bpmnEndEvent',
            severity: 'Error',
            criteria: function (shape, error) {
                var ref = shape.businessObject.elem;
                if (ref && !(ref.incoming && ref.incoming.length > 0)) {
                    //add error
                    shape.addErrorLog(error);
                }
            }
        },
        {
            code: '105302',
            description: 'Invalid Connection between elements'.translate(),
            type: 'bpmnEndEvent',
            severity: 'Error',
            criteria: function (shape, error) {
                if(!shape.ValidateConnections()) {
                    //add error
                    shape.addErrorLog(error);
                }
            }
        }
    ],
    'bpmnIntermediateEvent': [
        {
            code: '105401',
            description: 'Intermediate event must have one or more incoming sequence flow'.translate(),
            type: 'bpmnIntermediateEvent',
            severity: 'Error',
            criteria: function (shape, error) {
                var ref = shape.businessObject.elem;
                if (ref && !(ref.incoming && ref.incoming.length >= 1)) {
                    //add error
                    shape.addErrorLog(error);
                }
            }
        },
        {

            code: '105402',
            description: 'Intermediate event must have an outgoing sequence flow'.translate(),
            type: 'bpmnIntermediateEvent',
            severity: 'Error',
            criteria: function (shape, error) {
                var ref = shape.businessObject.elem;
                if (ref && !(ref.outgoing && ref.outgoing.length === 1)) {
                    //add error
                    shape.addErrorLog(error);
                }
            }
        },
        {
            code: '105403',
            description: 'Invalid Connection between elements'.translate(),
            type: 'bpmnIntermediateEvent',
            severity: 'Error',
            criteria: function (shape, error) {
                if(!shape.ValidateConnections()) {
                    //add error
                    shape.addErrorLog(error);
                }
            }
        }
    ],
    'bpmnGateway': [
        {
            code: '105501',
            description: 'Diverging Gateway must have an incoming sequence flow'.translate(),
            type: 'bpmnGateway',
            severity: 'Error',
            criteria: function (shape, error) {
                if (shape.gat_direction === 'CONVERGING') {
                    error.description = 'Converging Gateway must have two or more incoming sequence flow'.translate();
                    var ref = shape.businessObject.elem;
                    if (ref && !(ref.incoming && ref.incoming.length > 1)) {
                        shape.addErrorLog(error);
                    }
                } else {
                    var ref = shape.businessObject.elem;
                    if (ref && !(ref.incoming && ref.incoming.length === 1)) {
                        //add error
                        shape.addErrorLog(error);
                    }
                }

            }
        },
        {
            code: '105502',
            description: 'Diverging Gateway must have two or more outgoing sequence flow'.translate(),
            type: 'bpmnGateway',
            severity: 'Error',
            criteria: function (shape, error) {
                if (shape.gat_direction === 'CONVERGING') {
                    error.description = 'Converging Gateway must have a outgoing sequence flow'.translate();
                    var ref = shape.businessObject.elem;
                    if (ref && !(ref.outgoing && ref.outgoing.length === 1)) {
                        shape.addErrorLog(error);
                    }
                } else {
                    var ref = shape.businessObject.elem;
                    if (ref && !(ref.outgoing && ref.outgoing.length > 1)) {
                        //add error
                        shape.addErrorLog(error);
                    }
                }
            }
        },
        {
            code: '105503',
            description: 'Invalid Connection between elements'.translate(),
            type: 'bpmnGateway',
            severity: 'Error',
            criteria: function (shape, error) {
                if(!shape.ValidateConnections()) {
                    //add error
                    shape.addErrorLog(error);
                }
            }
        }
    ],
    'bpmnSubProcess': [
        {
            code: '105601',
            description: 'SubProcess must have an incoming sequence flow'.translate(),
            type: 'bpmnSubProcess',
            severity: 'Error',
            criteria: function (shape, error) {
                var ref = shape.businessObject.elem;
                if (ref && !(ref.incoming && ref.incoming.length > 0)) {
                    //add error
                    shape.addErrorLog(error);
                }
            }
        },
        {
            code: '105602',
            description: 'SubProcess must have an outgoing sequence flow'.translate(),
            type: 'bpmnSubProcess',
            severity: 'Error',
            criteria: function (shape, error) {
                var ref = shape.businessObject.elem;
                if (ref && !(ref.outgoing && ref.outgoing.length === 1)) {
                    //add error
                    shape.addErrorLog(error);
                }
            }
        },
        {
            code: '105603',
            description: 'Invalid Connection between elements'.translate(),
            type: 'bpmnSubProcess',
            severity: 'Error',
            criteria: function (shape, error) {
                if(!shape.ValidateConnections()) {
                    //add error
                    shape.addErrorLog(error);
                }
            }
        }
    ]
};
/**features-ends**/
/*----------------------------------********---------------------------------*/
var WebEntry = function (menuOption) {
    this.relatedShape = null;
    this.restClient = null;
    this.formWebEntry = null;
    this.wee_uid = null;
    this.dyn_uid = null;
    this.usr_uid = null;
    this.wee_status = null;
    this.sugesstField = null;
    this.linkField = null;
    this.generateButton = null;
    WebEntry.prototype.init.call(this, menuOption);
};

WebEntry.prototype.init = function (menuOption) {
    var requiredMessage,
        defaults = {
            relatedShape: null
        };
    this.relatedShape = menuOption;
    this.createForm();
    this.createWindow();
    this.createSugesstField();
    this.createLinkField();
    this.createGenerateButton();
    this.createRestClient();
    this.winWebEntry.addItem(this.formWebEntry);
    this.winWebEntry.open();
    document.getElementById("requiredMessage").style.marginTop = "40px";
    requiredMessage = $(document.getElementById("requiredMessage"));
    requiredMessage.hide();
    this.formWebEntry.panel.html.appendChild(this.sugesstField.createHTML());
    this.formWebEntry.panel.html.appendChild(this.generateButton);
    this.formWebEntry.panel.html.appendChild(this.linkField);
    this.formWebEntry.panel.html.style.height = "300px";
    this.winWebEntry.defineEvents();
    this.hideDeleteButton();
    this.loadDynaformsAndUsers(this.formWebEntry.getField("dyn_uid"), this.formWebEntry.getField("usr_uid"));
    this.formWebEntry.panel.html.appendChild(requiredMessage.show()[0]);
};

WebEntry.prototype.createSugesstField = function () {
    this.sugesstField = new SuggestField({
        label: "Users".translate(),
        width: 569.81,
        placeholder: "suggest users",
        required: true,
        helper: "When the form is submitted a new case is created with this user account.".translate(),
        dynamicLoad: {
            data: [
                {
                    key: "usr_uid",
                    label: ["usr_firstname", "usr_lastname", "(", "usr_username", ")"]
                }
            ],
            keys: {
                url: HTTP_SERVER_HOSTNAME + "/api/1.0/" + WORKSPACE,
                accessToken: credentials.access_token,
                endpoints: [
                    {
                        method: "GET",
                        url: 'users'
                    }
                ]
            }
        }
    });
    return this;
};

WebEntry.prototype.hideDeleteButton = function () {
    $(this.winWebEntry.footer.items.asArray()[0].html).hide();
    return this;
};

WebEntry.prototype.showDeleteButton = function () {
    $(this.winWebEntry.footer.items.asArray()[0].html).show();
    return this;
};

WebEntry.prototype.createLinkField = function () {
    var label, link;
    this.linkField = document.createElement("div");
    this.linkField.style.marginLeft = "22px";
    this.linkField.className = "pmui-field";
    $(this.linkField).css({
        "margin-left": "11px",
        left: "0px",
        top: "0px",
        display: "block",
        position: "relative",
        "z-index": "auto",
        "margin-top": "20px"
    });
    label = document.createElement("label");
    label.textContent = "Web Entry URL".translate();
    asterisk = document.createElement("span");
    asterisk.textContent = "*";
    $(asterisk).css({"color": "red"});
    twopointsLabel = document.createElement("label");
    twopointsLabel.textContent = ":".translate();
    label.appendChild(asterisk);
    label.appendChild(twopointsLabel);
    link = document.createElement("a");
    link.setAttribute("target", "_blank");
    $(link).css({
        "text-overflow": "ellipsis",
        "white-space": "nowrap",
        overflow: "hidden",
        display: "inline-block",
        left: "0px",
        top: "0px",
        width: "569.81px",
        height: "30px",
        position: "relative",
        "z-index": "auto"
    });
    $(label).css({
        "width": "23.5%",
        "display": "inline-block",
        "float": "left",
        "color": "#2d3e50",
        "font-size": "14px"
    });
    this.linkField.appendChild(label);
    this.linkField.appendChild(link);
    return this;
};

WebEntry.prototype.createGenerateButton = function () {
    var link, button, that = this;
    this.generateButton = document.createElement("div");
    this.generateButton.style.marginLeft = "22px";
    $(this.linkField).css({
        "margin-left": "11px",
        left: "0px",
        top: "0px",
        display: "block",
        position: "relative",
        "z-index": "auto",
        "margin-top": "20px"
    });
    link = document.createElement("div");
    $(link).css({
        "text-overflow": "ellipsis",
        overflow: "hidden",
        display: "inline-block",
        left: "0px",
        top: "0px",
        width: "755px",
        height: "30px",
        position: "relative",
        "z-index": "auto"
    });
    button = $("<button>");
    button.addClass('pmui pmui-button pmui-success');
    button.css({
        height: "25px",
        float: "right",
        padding: "0px 35px"
    });
    button.text("Generate Link".translate());
    $(button).click(function (e) {
        e.preventDefault();
        e.stopPropagation();
        var data, act;
        if (that.formWebEntry.isValid()) {
            if (that.sugesstField.isValid()) {
                if (that.linkField.childNodes.length > 2) {
                    that.linkField.childNodes[2].remove();
                }
                data = that.formWebEntry.getData();
                data["usr_uid"] = that.sugesstField.get("value");
                data["evn_uid"] = that.relatedShape.evn_uid;
                act = that.relatedShape.getPorts().asArray()[0];
                data["act_uid"] = that.relatedShape.getPorts().asArray()[0].connection.destPort.parent.act_uid;
                data["wee_title"] = that.relatedShape.evn_uid;
                data["wee_description"] = "";
                if (that.wee_uid === null) {
                    dataPostWee = that.consumeRest('post', 'web-entry-event', data);
                    that.linkField.style.display = "block";
                    that.wee_uid = dataPostWee['wee_uid'];
                    $(that.linkField).find("a")[0].textContent = dataPostWee['wee_url'];
                    $(that.linkField).find("a")[0].href = dataPostWee['wee_url'];
                    $(that.linkField).find("a")[0].setAttribute("title", dataPostWee['wee_url']);
                    that.showDeleteButton();
                } else {
                    dataPostWee = that.consumeRest('update', 'web-entry-event/' + that.wee_uid, data);
                    dataPostWee = that.consumeRest('get', 'web-entry-event/' + that.wee_uid, data);
                    that.linkField.style.display = "block";
                    that.wee_uid = dataPostWee['wee_uid'];
                    $(that.linkField).find("a")[0].textContent = dataPostWee['wee_url'];
                    $(that.linkField).find("a")[0].href = dataPostWee['wee_url'];
                    $(that.linkField).find("a")[0].setAttribute("title", dataPostWee['wee_url']);
                    that.showDeleteButton();
                }
            }
            else {
                that.sugesstField.showMessageRequired();
            }
        }
    });
    $(link).append(button);
    this.generateButton.appendChild(link);
    return this;
};

WebEntry.prototype.createForm = function () {
    this.formWebEntry = new PMUI.form.Form({
        id: "formWebEntry",
        title: "",
        height: "300px",
        width: DEFAULT_WINDOW_WIDTH - 70,
        items: [
            {
                pmType: "dropdown",
                label: "Dynaform".translate(),
                options: [],
                name: "dyn_uid",
                id: "dyn_uid",
                helper: "Select one DynaForm".translate(),
                required: true
            },
            {
                pmType: "radio",
                label: "Status".translate(),
                name: "wee_status",
                id: "wee_status",
                required: true,
                options: [
                    {
                        label: "enable".translate(),
                        value: "ENABLED",
                    },
                    {
                        label: "disabled".translate(),
                        value: "DISABLED"
                    }
                ],
                value: "ENABLED",
                visible: false
            }
        ],
        visibleHeader: false
    });
    return this;
};

WebEntry.prototype.createWindow = function () {
    var that = this, dataPostWee;
    this.winWebEntry = new PMUI.ui.Window({
        id: "winWebEntry",
        title: "Web Entry".translate(),
        height: DEFAULT_WINDOW_HEIGHT,
        width: DEFAULT_WINDOW_WIDTH,
        modal: true,
        footerItems: [
            {
                pmType: 'button',
                text: 'Delete'.translate(),
                handler: function () {
                    if (that.wee_uid != null) {
                        dataPostWee = that.consumeRest('remove', 'web-entry-event/' + that.wee_uid, {});
                        that.winWebEntry.close();
                        PMDesigner.msgFlash('The Web entry was removed successfully.'.translate(), document.body, 'success', 3000, 5);
                    } else {
                        that.restClient.msgFlash('Not exists Web entry'.translate(), document.body, 'info', 3000, 5);
                    }
                },
                buttonType: "error"
            },
            {
                pmType: 'button',
                text: "Cancel".translate(),
                handler: function () {
                    if (!that.formWebEntry.isDirty()) {
                        that.winWebEntry.close();
                    }
                },
                buttonType: "error"
            },
            {
                pmType: 'button',
                text: 'Save'.translate(),
                handler: function () {
                    var data, act;
                    if (that.formWebEntry.isValid()) {
                        if (that.sugesstField.isValid()) {
                            if (that.wee_uid === null) {
                                requiredMessageLink = that.formWebEntry.getItems()[0].dom.messageContainer.cloneNode(true);
                                requiredMessageLink.childNodes[0].style.display = "block";
                                requiredMessageLink.childNodes[0].innerText = "This field is required. Please press the \"Generate Link\" button.".translate();
                                that.linkField.appendChild(requiredMessageLink);
                                return;
                            }
                            data = that.formWebEntry.getData();
                            data["usr_uid"] = that.sugesstField.get("value");
                            data["evn_uid"] = that.relatedShape.evn_uid;
                            act = that.relatedShape.getPorts().asArray()[0];
                            data["act_uid"] = that.relatedShape.getPorts().asArray()[0].connection.destPort.parent.act_uid;
                            data["wee_title"] = that.relatedShape.evn_uid;
                            data["wee_description"] = "";
                            if (that.wee_uid === null) {
                                dataPostWee = that.consumeRest('post', 'web-entry-event', data);
                            } else {
                                dataPostWee = that.consumeRest('update', 'web-entry-event/' + that.wee_uid, data);
                            }
                            that.winWebEntry.close();
                        }
                        else {
                            that.sugesstField.showMessageRequired();
                        }
                    }
                },
                buttonType: "success",
            }
        ],
        footerAlign: "right",
        closable: true,
        visibleFooter: true
    });
    return this;
};

WebEntry.prototype.createRestClient = function () {
    this.restClient = new PMRestClient();
    return this;
};

WebEntry.prototype.loadDynaformsAndUsers = function (dynaformsControl) {
    var i, j, data, options = [], response, webentries = [],
        that = this, linkValue, link, label, fieldLink, dynaforms;
    webentries = this.consumeRest("get", "web-entry-events");
    dynaforms = this.consumeRest("get", "dynaforms");
    for (i = 0; i < dynaforms.length; i += 1) {
        data = {};
        data.label = dynaforms[i]["dyn_title"];
        data.value = dynaforms[i]["dyn_uid"];
        for (j = 0; j < webentries.length; j += 1) {
            if (webentries[j]["dyn_uid"] === data.value && webentries[j]["evn_uid"] == that.relatedShape["evn_uid"]) {
                data.selected = true;
                if (webentries[j].hasOwnProperty("usr_uid") && webentries[j]["usr_uid"] != "") {
                    user = this.consumeRest("get", "user/" + webentries[j]["usr_uid"]);
                    this.sugesstField.html.find("input").val(user["usr_firstname"] + " " + user["usr_lastname"] + " " + "(" + user["usr_username"] + ")");
                    this.sugesstField.set("value", user["usr_uid"]);
                    this.wee_uid = webentries[j]["wee_uid"];
                    linkValue = webentries[j]["wee_url"];
                }
            }
        }
        options.push(data);
    }
    dynaformsControl.setOptions(options);

    options = [];

    this.sugesstField.set("options", options);
    if (this.wee_uid) {
        this.linkField.style.display = "block";
        $(this.linkField).find("a")[0].textContent = linkValue;
        $(this.linkField).find("a")[0].href = linkValue;
        $(this.linkField).find("a")[0].setAttribute("title", linkValue);
        this.showDeleteButton();
    }
    return this;
};

WebEntry.prototype.consumeRest = function (method, endpoint, data, callbkack) {
    var resp = null;
    this.restClient = new PMRestClient({
        functionSuccess: function (xhr, response) {
            resp = response;
        },
        functionFailure: function (xhr, response) {
            PMDesigner.msgWinError(response.error.message);
        }
    });
    this.restClient.typeRequest = method;
    if (endpoint.split('/')[0] === "user") {
        this.restClient.setBaseEndPoint("");
    }
    if (method === "update") {
        this.restClient.messageError = ['An unexpected error while editing the WebEntry, please try again later.'.translate()];
        this.restClient.messageSuccess = ['WebEntry sucessfully edited.'.translate()];
    }
    if (method === "post") {
        this.restClient.messageError = ['An unexpected error while created the WebEntry, please try again later.'.translate()];
        this.restClient.messageSuccess = ['WebEntry created successfully.'.translate()];
    }
    this.restClient.data = data;
    this.restClient.endpoint = this.restClient.endpoint + endpoint;
    this.restClient.executeRestClient();
    return resp;
};
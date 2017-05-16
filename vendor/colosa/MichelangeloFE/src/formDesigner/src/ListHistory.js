(function () {
    var ListHistory = function (dynaform) {
        this.dynaform = dynaform;
        this.onSelect = new Function();
        ListHistory.prototype.init.call(this);
    };
    ListHistory.prototype.init = function () {
        var that = this;
        that.filter = "";
        that.start = 0;
        that.limit = 10;
        this.count = 0;
        this.input = $("<input type='text' style='vertical-align:top;'/>");
        this.input.on("change", function () {
            that.filter = this.value;
        });
        this.input.on("keyup", function (e) {
            if (e.keyCode === 13) {
                that.start = 0;
                that.load(that.filter, that.start, that.limit);
            }
        });
        this.button = $("<img src='" + $.imgUrl + "fd-magnifier.png' style='margin-left:5px;cursor:pointer;'/>");
        this.button.on("click", function () {
            that.start = 0;
            that.load(that.filter, that.start, that.limit);
        });
        this.back = $("<img src='" + $.imgUrl + "fd-arrow-left.png' style='margin-left:5px;cursor:pointer;'/>");
        this.back.on("click", function () {
            if (that.start <= 0) {
                return;
            }
            that.start = that.start - that.limit;
            that.load(that.filter, that.start, that.limit);
        });
        this.next = $("<img src='" + $.imgUrl + "fd-arrow-right.png' style='margin-left:5px;cursor:pointer;'/>");
        this.next.on("click", function () {
            if (that.count < that.limit) {
                return;
            }
            that.start = that.start + that.limit;
            that.load(that.filter, that.start, that.limit);
        });

        this.tool = $("<div style='padding:5px;'></div>");
        this.tool.append(this.input);
        this.tool.append(this.button);
        this.tool.append(this.back);
        this.tool.append(this.next);

        this.list = $("<div></div>");

        this.body = $("<div></div>");
        this.body.append(this.tool);
        this.body.append(this.list);
    };
    ListHistory.prototype.load = function (string, start, limit) {
        var that = this, d = new Date(), date;
        date = d.getFullYear() + "-" + $.rPadDT((d.getMonth() + 1)) + "-" + $.rPadDT(d.getDate()) + " " + $.rPadDT(d.getHours()) + ":" + $.rPadDT(d.getMinutes()) + ":" + $.rPadDT(d.getSeconds());
        that.clear();
        that.list.append(that.addItem({
            history_date: date + " " + "Current form.".translate(),
            dyn_content_history: that.dynaform.dyn_content,
            current: true
        }));
        that.list.append(that.addItem({
            history_date: (this.dynaform.dyn_update_date !== "" ? this.dynaform.dyn_update_date : date),
            dyn_content_history: that.dynaform.dyn_content,
            current: false
        }));
        $.ajax({
            async: true,
            url: HTTP_SERVER_HOSTNAME + "/api/1.0/" + WORKSPACE + "/project/" + PMDesigner.project.id + "/dynaform/" + that.dynaform.dyn_uid + "/history",
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({
                filter: string,
                start: start,
                limit: limit
            }),
            beforeSend: function (xhr) {
                xhr.setRequestHeader("Authorization", "Bearer " + PMDesigner.project.keys.access_token);
            },
            success: function (data) {
                that.count = data.length;
                for (var i = 0; i < data.length; i++) {
                    data[i].current = false;
                    that.list.append(that.addItem(data[i]));
                }
            }
        });
    };
    ListHistory.prototype.addItem = function (history) {
        var that = this;
        var json = null;
        var msg = "";
        try {
            json = JSON.parse(history.dyn_content_history);
        } catch (e) {
            msg = "Empty.".translate();
        }
        var item = $("<div style='' class='fd-list'>" +
            "<div style='display:inline-block;'>" + history.history_date + " " + msg + "</div>" +
            "</div>");
        item.on("click", function () {
            that.onSelect(json, history);
        });
        return item;
    };
    ListHistory.prototype.clear = function () {
        this.count = 0;
        this.list.find(">div").remove();
    };
    ListHistory.prototype.setDynaform = function (dynaform) {
        this.dynaform = dynaform;
    };
    ListHistory.prototype.reload = function () {
        var that = this;
        that.load(that.filter, that.start, that.limit);
    };
    FormDesigner.extendNamespace('FormDesigner.main.ListHistory', ListHistory);
}());
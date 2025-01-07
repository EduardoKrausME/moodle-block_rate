define(["jquery", "core/ajax", "core/notification"], function($, ajax, notification) {

    var RateAction = function(selector, courseid, cmid) {
        this._region = $(selector);
        this._courseid = courseid;
        this._cmid = cmid;

        if (!cmid) {
            var cmidValue = $("body").attr("class").match(/cmid-(\d+)/);
            if (cmidValue) {
                this._cmid = parseInt(cmidValue[1]);
            }
        }

        this._region.find(".star").unbind().on("click", "img", this._setUserChoice.bind(this));
        $("#block_rate_course-rerate").on("click", this._rerateCourse.bind(this));
    };

    RateAction.prototype._setUserChoice = function(element) {
        var elem = $(element.target).parent();
        var value = elem.data("value");


        if (value != "") {
            ajax.call([{
                methodname: "block_rate_course_set_rating",
                args: {
                    courseid: this._courseid,
                    cmid: this._cmid,
                    rating: value
                },
                done: function(data) {
                    if (data === true) {
                        $("#block_rate_course-myrating-area").removeClass("hidden");
                        $("#block_rate_course-stars-area").addClass("hidden");
                        $("#block_rate_course-myrating").text(value);
                        $("#block_rate_course-rerate").removeClass("hidden");
                    }
                    return true;
                }.bind(this),
                fail: notification.exception
            }]);
        }
    };

    RateAction.prototype._rerateCourse = function() {
        $("#block_rate_course-stars-area").removeClass("hidden");
        $("#block_rate_course-rerate").addClass("hidden");
    };

    return RateAction;
});


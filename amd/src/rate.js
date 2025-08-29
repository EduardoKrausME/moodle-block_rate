// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Rate Form file
 *
 * @package    block_rate
 * @copyright  2025 Eduardo Kraus {@link https://eduardokraus.com}
 * @copyright  2019 Pierre Duverneix <pierre.duverneix@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
        $("#block_rate-rerate").on("click", this._rerateCourse.bind(this));
    };

    RateAction.prototype._setUserChoice = function(element) {
        var elem = $(element.target).parent();
        var value = elem.data("value");

        if (value != "") {
            ajax.call([{
                methodname: "block_rate_set_rating",
                args: {
                    courseid: this._courseid,
                    cmid: this._cmid,
                    rating: value
                },
                done: function(data) {
                    if (data === true) {
                        $("#block_rate-myrating-area").removeClass("hidden");
                        $("#block_rate-stars-area").addClass("hidden");
                        $("#block_rate-myrating").text(value);
                        $("#block_rate-rerate").removeClass("hidden");
                    }
                    return true;
                }.bind(this),
                fail: notification.exception
            }]);
        }
    };

    RateAction.prototype._rerateCourse = function() {
        $("#block_rate-stars-area").removeClass("hidden");
        $("#block_rate-rerate").addClass("hidden");
    };

    return RateAction;
});

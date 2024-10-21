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
 *
 * @module     block/Notification
 * @package    block_notification
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'core/templates',
    'core/ajax',
    'jquery',
], function (Templates, Ajax, $) {
    var users;
    return users = {
        init: function (args) {
            const calendar = document.getElementById('customcalendar');
            // console.log(calendar);
            // Generate and append date cards
            var time = $('#time').val() * 1000;
            const d = new Date(time);
            var year = $("#id_year").val();
            var month = $("#id_month").val();
            const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            var monthname = months[parseInt(month) - 1].substring(0, 3);
            var days = new Date(parseInt(year), parseInt(month), 0).getDate();
            var gsDayNames = [
                'Sunday',
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday'
            ];
            for (let i = 1; i <= days; i++) {
                const card = document.createElement('div');
                card.classList.add('card');
                card.textContent = i;
                const newd = new Date(year + '-' + month + '-' + i);
                var day = gsDayNames[newd.getDay()];
                // onclick="(function(e){ require(\'block_notification/calendarcards\').customCalendar({year:' + year + ',month:' + month + ',day:' + i + ',courseid:1,categoryid:0})})(event)"
                card.innerHTML = '<div class="datesinfo"><a class="dateslink" href="javascript:void(0)" data-year=' + year + ' data-month= ' + month + ' data-day = ' + i + ' data-courseid=1 data-categoryid=0 ><div class="monthinfo">' + monthname + '</div><div class="dateinfo">' + i + '</div><div class="dayinfo">' + day.substring(0, 3) + '</div></a><div>'
                // console.log(card);
                calendar.appendChild(card);
            }
            $(document).on('click', '.dateslink', function () {
                $('.card').removeClass('active');
                var parentfirst = $(this).parent();
                var parentsecond = $(parentfirst).parent();
                $(parentsecond).addClass('active');
                console.log($(this).data());
                users.customCalendar($(this).data());
            });
        },
        customCalendar: function (params) {
            // #ED396C
            // $('.activehover').css("background-color",'#ED396C');
            var promise = Ajax.call([{
                methodname: 'block_notification_calendar_details',
                args: params
            }]);
            $("#customcalendardata").empty();
            promise[0].done(function (resp) {
                console.log(resp['events']);
                if (resp.events.length == 0) {
                    $('#customcalendardata').html('<div class="text-center calendar_events attempt_text"><h4>No Events Available on this Date.</></h4></div>');
                } else {
                    var data = Templates.render('block_notification/customcalendardata', { events: resp['events'] });
                    data.then(function (html, js) {
                        $('#customcalendardata').html(html);
                    });
                }

            }).fail(function (ex) {
                // do something with the exception
                console.log(ex);
            });
        },
        load: function () {
            $(document).on('change', '#id_month , #id_year', function () {
                $("#customcalendar").empty();
                new users.init();
                var year = $("#id_year").val();
                var month = $("#id_month").val();
                var day = 1;
                // if (day > 9) {
                // var scrollval = day * 85;
                // $('#customcalendar').animate({
                //     scrollLeft: '+=' + parseInt(scrollval) + 'px'
                // }, "slow");
                // }    
                let scrollWidth = $('#customcalendar').get(0).scrollWidth;
                $('#customcalendar').animate({
                    scrollLeft: '-=' + scrollWidth + 'px'
                }, "slow");
                const d = new Date();
                var presentmonth = parseInt(d.getMonth()) + parseInt(1);
                if (presentmonth == month) {
                    day = $("input[name=day]").val();
                    if (day > 9) {
                        var scrollval = day * 85;
                        $('#customcalendar').animate({
                            scrollLeft: '+=' + parseInt(scrollval) + 'px'
                        }, "slow");
                    }
                }
                const params = { year: year, month: month, day: day, courseid: 1, categoryid: 0 };
                new users.customCalendar(params);
                users.active(day);
            });
            var year = $("#id_year").val();
            var month = $("#id_month").val();
            var day = $("input[name=day]").val();
            if (day > 9) {
                var scrollval = day * 85;
                $('#customcalendar').animate({
                    scrollLeft: '+=' + parseInt(scrollval) + 'px'
                }, "slow");
            }
            users.active(parseInt(day));

            const params = { year: parseInt(year), month: parseInt(month), day: parseInt(day), courseid: 1, categoryid: 0 };
            new users.customCalendar(params);
            $('.horizon-prev').click(function (e) {
                e.preventDefault();
                let currentScroll = $('#customcalendar').get(0).scrollLeft;
                let scrollWidth = $('#customcalendar').get(0).scrollWidth;
                $('#customcalendar').animate({
                    scrollLeft: '-=300px'
                }, "slow");
            });
            $('.horizon-next').click(function (e) {
                e.preventDefault();
                let currentScroll = $('#customcalendar').get(0).scrollLeft;
                let scrollWidth = $('#customcalendar').get(0).scrollWidth;
                $('#customcalendar').animate({
                    scrollLeft: '+=300px'
                }, "slow");
            });
        },
        active: function (day) {
            $(".dateinfo").each(function () {
                var dateval = $(this).text();
                if (dateval == day) {
                    var parent = $(this).parent();
                    var parent1 = parent.parent();
                    var id = parent1.parent();
                    $(id).addClass('active');
                }
            });
        }
    };
});

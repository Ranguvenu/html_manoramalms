define(['core/ajax',
        'core/templates',
        'block_learnerscript/report',
        'block_learnerscript/reportwidget',
        'block_learnerscript/schedule',
        'block_learnerscript/helper',
        'block_learnerscript/ajax',
        'block_learnerscript/select2',
        'block_learnerscript/jquery.dataTables',
        'block_learnerscript/radioslider',
        'block_learnerscript/flatpickr',
        'core/str',
        'jquery',
        'jqueryui',
        'block_learnerscript/bootstrapnotify',
        'block_reportdashboard/inplace_editable'
    ],
    function(Ajax, Templates, report, reportwidget, schedule, helper, ajax, select2, DataTable,RadiosToSlider,flatpickr, Str, $) {
        return {
            init: function() {
                    $(document).ajaxStop(function() {
                         $(".loader").fadeOut("slow");
                    });
                    helper.Select2Ajax({});

                    $(".dashboardgoals").change(function(){
                        var goalid = $(this).val();
                        $(".report_goals").val(goalid);
                        var args = {};
                        args.action = 'boardslist';
                        args.goalid = goalid;
                        senddata = JSON.stringify(args);
                        var promise = ajax.call({
                            args:args,
                            url: M.cfg.wwwroot + "/blocks/reportdashboard/ajax.php"
                        });
                        promise.done(function(response) {
                            var template = '';
                            $.each(response, function(key, value) {
                                template += '<option value = ' + key + '>' + value + '</option>';
                            });
                            $("#dashboardboards").html(template);
                            $("#dashboardboards").val($(' #dashboardboards option:eq(0)').val());
                            $("#dashboardboards").trigger('change');
                        });
                        $('.breadcrumb-button.pull-xs-right').html($(this).find('option:selected').text());
                    });
                    $(".dashboardboards").change(function(){
                        var boardid = $(this).val();
                        $(".report_boards").val(boardid);
                        var args = {};
                        args.action = 'classeslist';
                        args.boardid = boardid;
                        senddata = JSON.stringify(args);
                        var promise = ajax.call({
                            args:args,
                            url: M.cfg.wwwroot + "/blocks/reportdashboard/ajax.php"
                        });
                        promise.done(function(response) {
                            var template = '';
                            $.each(response, function(key, value) {
                                template += '<option value = ' + key + '>' + value + '</option>';
                            });
                            $("#dashboardclasses").html(template);
                            $("#dashboardclasses").val($(' #dashboardclasses option:eq(0)').val());
                            $("#dashboardclasses").trigger('change');
                        });
                        $('.breadcrumb-button.pull-xs-right').html($(this).find('option:selected').text());
                    });
                    $(".dashboardclasses").change(function(){
                        var classid = $(this).val();
                        $(".report_classes").val(classid);
                        var args = {};
                        args.action = 'subjectslist';
                        args.classid = classid;
                        senddata = JSON.stringify(args);
                        var promise = ajax.call({
                            args:args,
                            url: M.cfg.wwwroot + "/blocks/reportdashboard/ajax.php"
                        });
                        promise.done(function(response) {
                            var template = '';
                            $.each(response, function(key, value) {
                                template += '<option value = ' + key + '>' + value + '</option>';
                            });
                            $("#dashboardsubjects").html(template);
                            $("#dashboardsubjects").val($(' #dashboardsubjects option:eq(0)').val());
                            $("#dashboardsubjects").trigger('change');
                        });
                        $('.breadcrumb-button.pull-xs-right').html($(this).find('option:selected').text());
                    });
                    $(".dashboardsubjects").change(function(){
                        var subjectid = $(this).val();
                        $(".report_subjects").val(subjectid);
                        var args = {};
                        args.action = 'batcheslist';
                        args.subjectid = subjectid;
                        senddata = JSON.stringify(args);
                        var promise = ajax.call({
                            args:args,
                            url: M.cfg.wwwroot + "/blocks/reportdashboard/ajax.php"
                        });
                        promise.done(function(response) {
                            var template = '';
                            $.each(response, function(key, value) {
                                template += '<option value = ' + key + '>' + value + '</option>';
                            });
                            $("#dashboardbatches").html(template);
                            $("#dashboardbatches").val($(' #dashboardbatches option:eq(0)').val());
                            $("#dashboardbatches").trigger('change');
                        });
                        $('.breadcrumb-button.pull-xs-right').html($(this).find('option:selected').text());
                    });
                    $(".dashboardbatches").change(function(){
                        var batchid = $(this).val();
                        $(".report_batches").val(batchid);
                        var promise = Ajax.call([{
                            methodname: 'block_reportdashboard_studentsdetails_view',
                             args: {
                                batchid: batchid,
                            },
                        }]);
                        promise[0].done(function(response) {
                            var dashboardcourseid = response.courseid;
                            $(".report_courses").val(dashboardcourseid);
                            Templates.render('block_reportdashboard/coursestudentsinfo', response).done(function(html, js) {
                                Templates.replaceNodeContents('#admindashboarddetails', html, js);
                            });

                            var args = {};
                            args.action = 'chapterslist';
                            args.courseid = dashboardcourseid;
                            senddata = JSON.stringify(args);
                            var promise = ajax.call({
                                args:args,
                                url: M.cfg.wwwroot + "/blocks/reportdashboard/ajax.php"
                            });
                            promise.done(function(response) {
                                var template = '';
                                $.each(response, function(key, value) {
                                    template += '<option value = ' + key + '>' + value + '</option>';
                                });
                                $("#id_filter_chapters").html(template);
                                $("#id_filter_chapters").val($(' #id_filter_chapters option:eq(0)').val());
                                $("#id_filter_chapters").trigger('change');
                            });
                            $('.breadcrumb-button.pull-xs-right').html($(this).find('option:selected').text());
                        });
                    });
                    $(document).on('change', '.studentcheckbox', function(){
                        $('.student_data_table').addClass('d-none');
                            $('.chapterwise_report_table').removeClass('d-none');
                            $('.activitytabs').removeClass('d-none');
                            $('.chapterwisecheckbox').addClass('d-flex');
                            if($(this).prop("checked") == true){
                                $('.student_data_table').removeClass('d-none');
                                $('.chapterwisecheckbox').removeClass('d-flex');
                                $('.chapterwisecheckbox').addClass('d-none');
                                $('.activitytabs').addClass('d-none');
                                $('.chapterwise_report_table').addClass('d-none');

                                $('input[name="chapterwisecheckbox"]').prop('checked', false);

                            } else {
                                $('.student_data_table .studentwisechapters').removeClass('d-none');
                                $('.chapterwise_report_table').addClass('d-none');
                                var courseid = $( "#courseid" ).val();
                                var promise = Ajax.call([{
                                    methodname: 'block_reportdashboard_liveclassdetails',
                                     args: {
                                        courseid: courseid,
                                    },
                                }]);
                                promise[0].done(function(response) {
                                    var dashboardcourseid = response.courseid;
                                    $(".report_courses").val(dashboardcourseid);
                                    Templates.render('block_reportdashboard/liveclassdetails', response).done(function(html, js) {console.log(response);
                                        Templates.replaceNodeContents('#liveclassdetailstab', html, js);
                                    });
                                });
                            }
                    });

                    $(document).on('click', '.liveclasstab a', function(){
                        var courseid = $( ".report_courses" ).val();
                        /*$('.liveclassdetails_report').removeClass('d-none');*/
                        var promise = Ajax.call([{
                                    methodname: 'block_reportdashboard_liveclassdetails',
                                     args: {
                                        courseid: courseid,
                                    },
                                }]);
                            promise[0].done(function(response) {
                                var dashboardcourseid = response.courseid;
                                $(".report_courses").val(dashboardcourseid);
                                Templates.render('block_reportdashboard/liveclassdetails', response).done(function(html, js) {console.log(response);
                                    Templates.replaceNodeContents('#liveclassdetailstab', html, js);
                                });
                            });
                    });

                    $(document).on('click', '.readingtab a', function(){
                        var courseid = $( ".report_courses" ).val();
                        /*$('.readingdetails_report').removeClass('d-none');*/
                        var promise = Ajax.call([{
                                    methodname: 'block_reportdashboard_readingdetails',
                                     args: {
                                        courseid: courseid,
                                    },
                                }]);
                            promise[0].done(function(response) {
                                var dashboardcourseid = response.courseid;
                                $(".report_courses").val(dashboardcourseid);
                                Templates.render('block_reportdashboard/readingdetails', response).done(function(html, js) {console.log(response);
                                    Templates.replaceNodeContents('#readingdetailstab', html, js);
                                });
                            });
                    });

                    $(document).on('click', '.practicetesttab a', function(){
                        var courseid = $( ".report_courses" ).val();
                        var promise = Ajax.call([{
                                    methodname: 'block_reportdashboard_practicetestdetails',
                                     args: {
                                        courseid: courseid,
                                    },
                                }]);
                            promise[0].done(function(response) {
                                var dashboardcourseid = response.courseid;
                                $(".report_courses").val(dashboardcourseid);
                                Templates.render('block_reportdashboard/practicetestdetails', response).done(function(html, js) {console.log(response);
                                    Templates.replaceNodeContents('#practicetestdetailstab', html, js);
                                });
                            });
                    });

                    $(document).on('click', '.testscoretab a', function(){
                        var courseid = $( ".report_courses" ).val();
                        var promise = Ajax.call([{
                                    methodname: 'block_reportdashboard_testscoredetails',
                                     args: {
                                        courseid: courseid,
                                    },
                                }]);
                            promise[0].done(function(response) {
                                var dashboardcourseid = response.courseid;
                                $(".report_courses").val(dashboardcourseid);
                                Templates.render('block_reportdashboard/testscoredetails', response).done(function(html, js) {console.log(response);
                                    Templates.replaceNodeContents('#testscoredetailstab', html, js);
                                });
                            });
                    });
                    $(document).on('click', '.forumtab a', function(){
                        var courseid = $( ".report_courses" ).val();
                        var promise = Ajax.call([{
                                    methodname: 'block_reportdashboard_forumdetails',
                                     args: {
                                        courseid: courseid,
                                    },
                                }]);
                            promise[0].done(function(response) {
                                var dashboardcourseid = response.courseid;
                                $(".report_courses").val(dashboardcourseid);
                                Templates.render('block_reportdashboard/forumdetails', response).done(function(html, js) {console.log(response);
                                    Templates.replaceNodeContents('#forumdetailstab', html, js);
                                });
                            });
                    });

                    $(".dashboardcourses").change(function(){
                        var courseid = $(this).val();
                        $(".report_courses").val(courseid);
                        reportwidget.DashboardTiles();
                        reportwidget.DashboardWidgets();
                        $(".viewmore").each(function(){
                            var ahref = $(this).attr('href');
                        });
                    });

                    $( "#createdashbaord_form" ).submit(function( event ) {
                        var dashboardname = $( "#id_dashboard" ).val();
                        var name = dashboardname.trim();
                        if(name == '' || name == null){
                            $( "#id_error_dashboard" ).css('display', 'block');
                            $( "#id_error_dashboard_nospaces" ).css('display', 'none');
                            event.preventDefault();
                        }
                        spaceexist = name.indexOf(" ");
                        if(spaceexist > 0 && spaceexist != ''){
                            $( "#id_error_dashboard" ).css('display', 'none');
                            $( "#id_error_dashboard_nospaces" ).css('display', 'block');
                            event.preventDefault();
                        }
                    });

                $.ui.dialog.prototype._focusTabbable = $.noop;
                var DheaderPosition = $("#dashboard-header").position();
                $(".sidenav").offset({top: 0});
                $("#internalbsm").offset({top: DheaderPosition.top});
                /**
                * Select2 Options
                */
                $("select[data-select2='1']").select2();
                helper.Select2Ajax({
                    action: 'reportlist',
                    multiple: true
                });
               /**
                * Filter area
                */
                $(document).on('click',".filterform #id_filter_clear",function(e) {
                    $(this).parents('.mform').trigger("reset");
                    var activityelement = $(this).parents('.mform').find('#id_filter_activities');
                    var instancelement = $(this).parents('.block_reportdashboard').find('.report_dashboard_container');
                    var reportid = instancelement.data('reportid');
                    var reporttype = instancelement.data('reporttype');
                    var instanceid = instancelement.data('blockinstance');
                    var userelement = $(this).parents('.mform').find('#id_filter_users');
                    smartfilter.CourseActivities({ courseid: 0,element: activityelement });
                    $(".filterform select[data-select2-ajax='1']").val('0').trigger('change');
                    $('.filterform')[0].reset();
                    $(".filterform #id_filter_clear").attr('disabled', 'disabled');
                    reportwidget.CreateDashboardwidget({reportid: reportid, reporttype: reporttype, instanceid: instanceid});
                });
                $(document).on('change', "select[name='filter_coursecategories']", function(){
                    var categoryid = this.value;
                    var courseelement = $(this).closest('.mform').find('#id_filter_courses');
                    if(courseelement.length != 0){
                        smartfilter.categoryCourses({ categoryid: categoryid ,element: courseelement});
                    }
                });
                $(document).on('change', "select[name='filter_courses']", function(){
                    var courseid = this.value;
                    var activityelement = $(this).closest('.mform').find('#id_filter_activities');
                    var userelement = $(this).closest('.mform').find('#id_filter_users');
                    if(activityelement.length != 0){
                        smartfilter.CourseActivities({ courseid: courseid ,element: activityelement});
                    }
                });

                /**
                * Duration buttons
                */
                RadiosToSlider.init($('#segmented-button'), {
                    size: 'medium',
                    animation: true,
                    reportdashboard: true
                });
                /**
                * Duration Filter
                */
                flatpickr('#customrange',{
                    mode: 'range',
                    onOpen: function(selectedDates, dateStr,instance){
                        instance.clear();
                    },
                    onClose: function(selectedDates, dateStr, instance) {
                        $('#ls_fstartdate').val(selectedDates[0].getTime() / 1000);
                        $('#ls_fenddate').val((selectedDates[1].getTime() / 1000) + (60 * 60 * 24));
                        require(['block_learnerscript/reportwidget'], function(reportjs) {
                            reportwidget.DashboardTiles();
                            reportwidget.DashboardWidgets();
                        });
                    }
                });
                /**
                 * Escape dropdown on click of window
                 */
                window.onclick = function(event) {
                    if (!event.target.matches('.dropbtn')) {
                        var dropdowns = document.getElementsByClassName("dropdown-content");
                        var i;
                        for (i = 0; i < dropdowns.length; i++) {
                            var openDropdown = dropdowns[i];
                            if ($(openDropdown).hasClass('show')) {
                                $(openDropdown).toggleClass('show');
                            }
                        }
                    }
                }
            },
            /**
             * Add reports as blocks to dashboard
             * @return {[type]} [description]
             */
            datefilters: function(args) {
                var selectedstartDate = args.startdate ? new Date(args.startdate) : 0;
                var selectedendDate = args.duedate ? new Date(args.duedate) : 0;

                if (selectedstartDate != 0) {
                    var startOfDay = new Date(selectedstartDate);
                    startOfDay.setHours(0, 0, 0);
                    var lsstartdate = (startOfDay.getTime()/1000);
                } else {
                    var lsstartdate = 0;
                } 

                if (selectedendDate != 0) {
                    var endOfDay = new Date(selectedendDate);
                    endOfDay.setHours(23, 59, 59);
                    var lsduedate = (endOfDay.getTime() /1000);
                } else {
                    var lsduedate = 0;
                }
                var startdate =  args.startdate;
                var duedate =  args.duedate; 
                var packageid =  args.packageid;
                var userid =  args.userid;
                var promise = Ajax.call([{
                    methodname: 'block_reportdashboard_coursedatedateselect_view',
                     args: {
                        packageid: packageid,
                        userid: userid,
                        startdate: startdate,
                        duedate: duedate,
                        lsstartdate: lsstartdate,
                        lsduedate: lsduedate
                    },
                }]);
                promise[0].done(function(response) {
                    Templates.render('block_reportdashboard/studentdashboard', response.records).done(function(html, js) {
                        Templates.replaceNodeContents('#student_dashboard_container', html, js);
                    });
                });
            },
            coursetabsdata: function(args) {
                        var courseid = args.courseid;
                        var userid = args.userid;
                        var startdate = args.startdate;
                        var duedate = args.duedate;
                        var lsstartdate = args.lsstartdate;
                        var lsduedate = args.lsduedate;
                        var promise = Ajax.call([{
                            methodname: 'block_reportdashboard_coursetabs_data',
                             args: {
                                courseid: courseid,
                                userid: userid,
                                startdate: startdate,
                                duedate: duedate,
                                lsstartdate: lsstartdate,
                                lsduedate: lsduedate, 

                            },
                        }]);
                        promise[0].done(function(response) {
                            $(".report_courses").val(response.records.courseid);
                            $(".report_users").val(response.records.userid);
                            Templates.render('block_reportdashboard/studentprofile/coursetabsdata', response).done(function(html, js) {
                                Templates.replaceNodeContents('#myTabContent', html, js);
                            });
                        });
            },
            addblocks_to_dashboard: function() {
                Str.get_string('addblockstodashboard','block_reportdashboard'
                ).then(function(s) {
                    if($('.reportslist').html().length > 0){
                        console.log("here");
                         $('.reportslist').dialog();
                    } else{

                    $.urlParam = function(name){
                    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
                    if (results === null || results == ' ' ){
                       return null;
                    } else{
                       return results[1] || 0;
                    }
                }
                var role=$.urlParam('role');
                var dashboardurl=$.urlParam('dashboardurl');
                var contextlevel=$.urlParam('contextlevel');
                var promise = Ajax.call([{
                    methodname: 'block_reportdashboard_addwidget_to_dashboard',
                    args: {
                        role: role,
                        dashboardurl: dashboardurl,
                        contextlevel: contextlevel,
                    },
                }]);
                promise[0].done(function(response) {
                    var widget_title_img = "<img class='dialog_title_icon' alt='Add Widgets' src='" +
                        M.util.image_url("add_widgets_icon", "block_reportdashboard") + "'/>";
                    $('.reportslist').dialog({
                        title: 'Add widgets to dashboard',
                        modal: true,
                        minWidth: 700,
                        maxHeight: 600
                    });
                    $('.reportslist').closest(".ui-dialog")
                        .find(".ui-dialog-titlebar-close")
                        .removeClass("ui-dialog-titlebar-close")
                        .html("<span class='ui-button-icon-primary ui-icon ui-icon-closethick'></span>");
                    $('.reportslist').closest(".ui-dialog").find('.ui-dialog-title')
                        .html(widget_title_img + 'Add widgets to dashboard');
                     resp = JSON.parse(response);
                     console.log(resp);
                    $('.reportslist').html(resp);
                }).fail(function(ex) {
                    // do something with the exception
                     console.log('Add Tiles');
                });
            }
                });
            },
            addtiles_to_dashboard: function() {
                Str.get_string('addtilestodashboard','block_reportdashboard'
                ).then(function(s) {
                    if($('.statistics_reportslist').html().length > 0) {
                        $('.statistics_reportslist').dialog();
                    } else {
                     $.urlParam = function(name){
                    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
                    if (results === null || results == ' ' ){
                       return null;
                    } else{
                       return results[1] || 0;
                    }
                }

                var role=$.urlParam('role');
                var dashboardurl=$.urlParam('dashboardurl');
                var contextlevel=$.urlParam('contextlevel');
                var promise = Ajax.call([{
                    methodname: 'block_reportdashboard_addtiles_to_dashboard',
                     args: {
                        role: role,
                        dashboardurl: dashboardurl,
                        contextlevel: contextlevel,
                    },
                }]);
                 promise[0].done(function(response) {
                    var tile_title_img = "<img class='dialog_title_icon' alt='Add Tiles' src='" +
                        M.util.image_url("add_tiles_icon", "block_reportdashboard") + "'/>";
                    $('.statistics_reportslist').dialog({
                        title: 'Add tiles to dashboard',
                        modal: true,
                        minWidth: 600,
                        maxHeight: 600
                    });
                    $('.statistics_reportslist').closest(".ui-dialog")
                        .find(".ui-dialog-titlebar-close")
                        .removeClass("ui-dialog-titlebar-close")
                        .html("<span class='ui-button-icon-primary ui-icon ui-icon-closethick'></span>");
                    $('.statistics_reportslist').closest(".ui-dialog").find('.ui-dialog-title')
                        .html(tile_title_img + 'Add tiles to dashboard');
                    resp = JSON.parse(response);
                    $('.statistics_reportslist').html(resp);
                    }).fail(function(ex) {
                    // do something with the exception
                     console.log('Add Tiles');
                });
                }
                });
            },
            addnewdashboard: function() {
                Str.get_string('addnewdashboard','block_reportdashboard'
                ).then(function(s) {
                    document.getElementById("id_dashboard").value = '';
                    $("#id_error_dashboard").css('display', 'none');
                    var tile_title_img = "<img class='dialog_title_icon' alt='Add new dashboard' src='" +
                        M.util.image_url("add_tiles_icon", "block_reportdashboard") + "'/>";
                    $('.newreport_dashboard').dialog({
                        title: s,
                        modal: true,
                        minWidth: 450,
                        maxHeight: 600
                    });
                    $('.newreport_dashboard').closest(".ui-dialog")
                        .find(".ui-dialog-titlebar-close")
                        .removeClass("ui-dialog-titlebar-close")
                        .html("<span class='ui-button-icon-primary ui-icon ui-icon-closethick'></span>");
                        var Closebutton = $('.ui-icon-closethick').parent();
                        $(Closebutton).attr({
                            "title" : "Close"
                        });
                    $('.newreport_dashboard').closest(".ui-dialog").find('.ui-dialog-title')
                        .html(tile_title_img + s);
                });
            },
            updatedashboard: function(oldname,role){
                Str.get_string('updatedashboard','block_reportdashboard'
                ).then(function(s) {
                    $( "#id_error_dashboard" ).css('display', 'none');
                    $( "#id_error_dashboard_nospaces" ).css('display', 'none');
                    var tile_title_img = "<img class='dialog_title_icon' alt='Add new dashboard' src='" +
                        M.util.image_url("add_tiles_icon", "block_reportdashboard") + "'/>";
                    $('.newreport_dashboard').dialog({
                        title: s,
                        modal: true,
                        minWidth: 450,
                        maxHeight: 600
                    });
                    $('.newreport_dashboard').closest(".ui-dialog")
                        .find(".ui-dialog-titlebar-close")
                        .removeClass("ui-dialog-titlebar-close")
                        .html("<span class='ui-button-icon-primary ui-icon ui-icon-closethick'></span>");
                        var Closebutton = $('.ui-icon-closethick').parent();
                        $(Closebutton).attr({
                            "title" : "Close"
                        });
                    $('.newreport_dashboard').closest(".ui-dialog").find('.ui-dialog-title')
                        .html(tile_title_img + s);
                    document.getElementById("id_dashboard").value = oldname;
                    $("#createdashbaord_form").submit(function(event){

                        var dashboardname = $( "#id_dashboard" ).val();
                        var name = dashboardname.trim();
                        if (name == '' || name == null) {
                            $( "#id_error_dashboard" ).css('display', 'block');
                            $( "#id_error_dashboard_nospaces" ).css('display', 'none');
                            event.preventDefault();
                            return false;
                        }
                        spaceexist = name.indexOf(" ");
                        if (spaceexist > 0 && spaceexist != '') {
                            $( "#id_error_dashboard" ).css('display', 'none');
                            $( "#id_error_dashboard_nospaces" ).css('display', 'block');
                            event.preventDefault();
                            return false;
                        }

                        var args = {};
                        args.action = 'updatedashboard';
                        args.role = role;
                        args.oldname = oldname;
                        args.newname = document.getElementById("id_dashboard").value ;
                        senddata = JSON.stringify(args);
                        var promise = ajax.call({
                            args:args,
                            url: M.cfg.wwwroot + "/blocks/reportdashboard/ajax.php"
                        });
                    });
                });
            },
            sendreportemail: function(args) {
                Str.get_strings([{
                    key: 'sendemail',
                    component: 'block_reportdashboard'
                }]).then(function(s) {
                    var url = M.cfg.wwwroot + '/blocks/learnerscript/ajax.php';
                    args.nodeContent = 'sendreportemail' + args.instanceid;
                    args.action = 'sendreportemail';
                    args.title = s;
                    AjaxForms = require('block_learnerscript/ajaxforms');
                    AjaxForms.init(args, url);
                });
            },
            reportfilter: function(args) {
                var self = this;
                if ($('.report_filter_' + args.instanceid).length < 1) {
                    var promise = Ajax.call([{
                        methodname: 'block_learnerscript_reportfilter',
                        args: {
                            action: 'reportfilter',
                            reportid: args.reportid,
                            instance: args.instanceid
                        }
                    }]);
                    promise[0].done(function(resp) {
                        $('body').append("<div class='report_filter_" + args.instanceid + "' style='display:none;'>" + resp + "</div>");
                        $("select[data-select2-ajax='1']").each(function() {
                            if (!$(this).hasClass('select2-hidden-accessible')) {
                                helper.Select2Ajax({});
                            }
                        });
                        self.reportFilterFormModal(args);
                         $('.filterform'+args.instanceid+' .fitemtitle').hide();
                          $('.filterform'+args.instanceid+' .felement').attr('style','margin:0');
                    });
                } else {
                    self.reportFilterFormModal(args);
                }
            },
            customreportfilter: function(args) {
                var self = this;
                if ($('.report_filter_' + args.instanceid).length < 1) {
                    var promise = Ajax.call([{
                        methodname: 'block_learnerscript_reportfilter',
                        args: {
                            action: 'reportfilter',
                            reportid: args.reportid,
                            instance: args.instanceid,
                        }
                    }]);
                    promise[0].done(function(resp) {
                        $('.reportsdashboardcontainer').append("<div class='report_filter_" + args.instanceid + "' style='display:none;'>" + resp + "</div>");
                        var chapterargs = {};
                        chapterargs.action = 'chapterslist';
                        chapterargs.courseid = $(".report_courses").val();
                        senddata = JSON.stringify(chapterargs);
                        var promise = ajax.call({
                            args:chapterargs,
                            url: M.cfg.wwwroot + "/blocks/reportdashboard/ajax.php"
                        });
                        promise.done(function(response) {
                            var template = '';
                            $.each(response, function(key, value) {
                                template += '<option value = ' + key + '>' + value + '</option>';
                            });
                            $("#id_filter_chapters").html(template);
                            $("#id_filter_chapters").val($(' #id_filter_chapters option:eq(0)').val());
                            $("#id_filter_chapters").trigger('change');
                        });
                        $('.breadcrumb-button.pull-xs-right').html($(this).find('option:selected').text());

                        $("select[data-select2-ajax='1']").each(function() {
                            if (!$(this).hasClass('select2-hidden-accessible')) {
                                helper.Select2Ajax({});
                            }
                        });
                        self.customreportFilterFormModal(args);
                        $('.filterform'+args.instanceid+' .fitemtitle').hide();
                        $('.filterform'+args.instanceid+' .felement').attr('style','margin:0');
                    });
                } else {
                    self.customreportFilterFormModal(args);
                }
            },
            customreportFilterFormModal: function (args) {
                Str.get_string('reportfilters','block_reportdashboard'
                ).then(function(s) {
                    var title_img = "<img class='dialog_title_icon' alt='Filter' src='" +
                        M.util.image_url("reportfilter", "block_reportdashboard") + "'/>";
                    $(".report_filter_" + args.instanceid).dialog({
                        title: s,
                        dialogClass: 'reportfilter-popup',
                        modal: true,
                        resizable: true,
                        autoOpen: true,
                        draggable: false,
                        width: 420,
                        height: 'auto',
                        appendTo: "#customreports"+args.instanceid,
                        position: {
                            my: "center",
                            at: "center",
                            of: "#customreports"+args.instanceid,
                            within: "#customreports"+args.instanceid
                        },
                        open: function(event, ui) {
                        $(this).closest(".ui-dialog")
                            .find(".ui-dialog-titlebar-close")
                            .removeClass("ui-dialog-titlebar-close")
                            .html("<span class='ui-button-icon-primary ui-icon ui-icon-closethick'></span>");
                            var Closebutton = $('.ui-icon-closethick').parent();
                            $(Closebutton).attr({
                                "title" : "Close"
                            });

                        $(this).closest(".ui-dialog")
                            .find('.ui-dialog-title').html(title_img + s);

                        /* Submit button */
                        $(".report_filter_" + args.instanceid + " form  #id_filter_apply").click(function(e) {
                            e.preventDefault();
                            e.stopImmediatePropagation();
                            if ($("#reportcontainer" + args.instanceid).html().length > 0 ) {
                                args.reporttype = $("#reportcontainer" + args.instanceid).data('reporttype');
                            } else {
                                args.reporttype = $("#plotreportcontainer" + args.instanceid).data('reporttype');
                            }
                            var courseid =  $(".report_courses").val();
                            var userid =  $(".report_users").val() ? $(".report_users").val() : $("#id_filter_users").val();
                            var lsstartdate = $(".lsstartdate").val();
                            var lsduedate = $(".lsduedate").val();

                            var status =  $("#id_filter_status").val();
                            var chapterid =  $("#id_filter_chapters").val();
                            if ($(".report_courses").val() > 0) {
                               args.filters = {filter_courses:courseid, filter_users:userid, filter_status:status, filter_startdate:lsstartdate, filter_duedate:lsduedate,
                                filter_chapters:chapterid};
                            }
                            args.container = '#reporttype_' + args.reportid;

                            require(['block_learnerscript/reportwidget'], function(reportwidget) {
                                reportwidget.CreateDashboardwidget({reportid: args.reportid,
                                                             reporttype: args.reporttype,
                                                             instanceid: args.instanceid,
                                                         filters: args.filters});
                                $(".report_filter_" + args.instanceid).dialog('close');
                            });
                            $(".report_filter_" + args.instanceid + " form #id_filter_clear").removeAttr('disabled');
                        });
                    }
                });
                $(".report_filter_" + args.instanceid + " form #id_filter_clear").click(function(e) {
                    e.preventDefault();
                    $(".filterform" + args.reportid).trigger("reset");
                    $(".filterform select[data-select2-ajax='1']").val('0').trigger('change');
                    if ($("#reportcontainer" + args.instanceid).html().length > 0 ) {
                        args.reporttype = $("#reportcontainer" + args.instanceid).data('reporttype');
                    } else {
                        args.reporttype = $("#plotreportcontainer" + args.instanceid).data('reporttype');
                    }
                    if ($(".report_courses").val() > 0) {
                        args.courseid = $(".report_courses").val();
                    }
                    if ($(".report_users").val() > 0) {
                        args.userid = $(".report_users").val();
                    }
                    args.filters = {filter_courses:args.courseid, filter_users:args.userid};
                    require(['block_learnerscript/reportwidget'], function(reportwidget) {
                        reportwidget.CreateDashboardwidget({reportid: args.reportid,
                            reporttype: args.reporttype,
                            instanceid: args.instanceid,
                            filters: args.filters});
                        $(".report_filter_" + args.instanceid).dialog('close');
                    });
                    $(".report_filter_" + args.instanceid).dialog('close');
                });
            });
            },
            reportFilterFormModal: function (args) {
                Str.get_string('reportfilters','block_reportdashboard'
                ).then(function(s) {
                    var title_img = "<img class='dialog_title_icon' alt='Filter' src='" +
                        M.util.image_url("reportfilter", "block_reportdashboard") + "'/>";
                    $(".report_filter_" + args.instanceid).dialog({
                        title: s,
                        dialogClass: 'reportfilter-popup',
                        modal: true,
                        resizable: true,
                        autoOpen: true,
                        draggable: false,
                        width: 420,
                        height: 'auto',
                        appendTo: "#inst" + args.instanceid,
                        position: {
                            my: "center",
                            at: "center",
                            of: "#inst" + args.instanceid,
                            within: "#inst" + args.instanceid
                        },
                        open: function(event, ui) {
                        $(this).closest(".ui-dialog")
                            .find(".ui-dialog-titlebar-close")
                            .removeClass("ui-dialog-titlebar-close")
                            .html("<span class='ui-button-icon-primary ui-icon ui-icon-closethick'></span>");
                            var Closebutton = $('.ui-icon-closethick').parent();
                            $(Closebutton).attr({
                                "title" : "Close"
                            });

                        $(this).closest(".ui-dialog")
                            .find('.ui-dialog-title').html(title_img + s);

                        /* Submit button */
                        $(".report_filter_" + args.instanceid + " form  #id_filter_apply").click(function(e) {
                            e.preventDefault();
                            e.stopImmediatePropagation();
                            if ($("#reportcontainer" + args.instanceid).html().length > 0 ) {
                                args.reporttype = $("#reportcontainer" + args.instanceid).data('reporttype');
                            } else {
                                args.reporttype = $("#plotreportcontainer" + args.instanceid).data('reporttype');
                            }
                            args.container = '#reporttype_' + args.reportid;

                            require(['block_learnerscript/reportwidget'], function(reportwidget) {
                                reportwidget.CreateDashboardwidget({reportid: args.reportid,
                                                             reporttype: args.reporttype,
                                                             instanceid: args.instanceid});
                                $(".report_filter_" + args.instanceid).dialog('close');
                            });
                            $(".report_filter_" + args.instanceid + " form #id_filter_clear").removeAttr('disabled');
                        });
                    }
                });
                $(".report_filter_" + args.instanceid + " form #id_filter_clear").click(function(e) {
                    e.preventDefault();
                    $(".filterform" + args.reportid).trigger("reset");
                    require(['block_learnerscript/reportwidget'], function(reportwidget) {
                        reportwidget.DashboardWidgets(args);
                        $(".report_filter_" + args.instanceid).dialog('close');
                    });
                    $(".report_filter_" + args.instanceid).dialog('close');
                });
            });
            },
            DeleteWidget: function(args) {
                Str.get_string('deletewidget','block_reportdashboard'
                ).then(function(s) {
                    var trainers = $("#delete_dialog" + args.instanceid).dialog({
                        resizable: true,
                        autoOpen: true,
                        width: 460,
                        height: 210,
                        title: s,
                        modal: true,
                        // dialogClass: 'dialog_fixed',
                        appendTo: "#inst" + args.instanceid,
                        position: {
                            my: "center",
                            at: "center",
                            of: "#inst" + args.instanceid,
                            within: "#inst" + args.instanceid
                        },
                        open: function(event, ui) {
                            $(this).closest(".ui-dialog")
                                .find(".ui-dialog-titlebar-close")
                                .removeClass("ui-dialog-titlebar-close")
                                .html("<span class='ui-button-icon-primary ui-icon ui-icon-closethick'></span>");
                                var Closebutton = $('.ui-icon-closethick').parent();
                                $(Closebutton).attr({
                                    "title" : "Close"
                                });
                        }
                    });
                });
            },
            Deletedashboard: function(args) {
                Str.get_string('deletedashboard','block_reportdashboard'
                ).then(function(s) {
                    var instancename = args.instance;
                        $( "#dashboard_delete_popup_"+args.random).dialog({
                            resizable: false,
                            height: 150,
                            width: 375,
                            modal: true,
                            title : s,
                            open: function(event) {
                            $(this).closest(".ui-dialog")
                                .find(".ui-dialog-titlebar-close")
                                .removeClass("ui-dialog-titlebar-close")
                                .html("<span class='ui-button-icon-primary ui-icon ui-icon-closethick'></span>");
                                var Closebutton = $('.ui-icon-closethick').parent();
                                $(Closebutton).attr({
                                    "title" : "Close"
                                });
                            },
                            close: function(event, ui) {
                                $(this).dialog('destroy').hide();
                            }
                        });
                });
            },
            /**
             * Schedule report form in popup in dashboard
             * @param  object args reportid
             * @return Popup with schedule form
             */
            schreportform: function(args) {
                var self = this;
                Str.get_string('schedulereport','block_reportdashboard'
                ).then(function(s) {
                    var url = M.cfg.wwwroot + '/blocks/learnerscript/ajax.php';
                    args.title = s;
                    args.nodeContent = 'schreportform' + args.instanceid;
                    args.action = 'schreportform';
                    args.courseid = $('[name="filter_courses"]').val();
                    AjaxForms = require('block_learnerscript/ajaxforms');
                    AjaxForms.init(args, url);
                });
            }
        };
    });

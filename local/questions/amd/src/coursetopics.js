define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {

    return /** @alias module:tool_lpmigrate/frameworks_datasource */ {

        /**
         * Process the results for auto complete elements.
         *
         * @param {String}s selector The selector of the auto complete element.
         * @param {Array} results An array or results.
         * @return {Array} New array of results.
         */
        processResults: function(selector, results) {
            var options = [];
            $.each(results.data, function(index, response) {
                options.push({
                    value: response.id,
                    label: response.fullname
                });
            });
            return options;
        },

        /**
         * Source of data for Ajax element.
         *
         * @param {String} selector The selector of the auto complete element.
         * @param {String} query The query string.
         * @param {Function} callback A callback function receiving an array of results.
         */
        /* eslint-disable promise/no-callback-in-promise */
        transport: function(selector, query, callback) {
            var el = $(selector),
            type = el.data('type');
            questionid = el.data('questionid');
            switch(type){
                case 'goallist':
                    Ajax.call([{
                    methodname: 'local_questions_goal_selector',
                    args: {type: type, query: query}
                    }])[0].then(callback).catch(Notification.exception);
           
                break;
                case 'boardlist':
                    if($("#id_customfield_goal").val()){
                        var goalid = $("#id_customfield_goal").val();
                    }else{
                       var goalid = 0;
                    }
                    Ajax.call([{
                    methodname: 'local_questions_board_selector',
                    args: {type: type, query: query,goalid:JSON.stringify(goalid)}
                    }])[0].then(callback).catch(Notification.exception);
           
                break;
                case 'classlist':
                    if($("#id_customfield_board").val()){
                        var boardid = $("#id_customfield_board").val();
                    }else{
                       var boardid = 0;
                    }
                    Ajax.call([{
                    methodname: 'local_questions_class_selector',
                    args: {type: type, query: query,boardid:JSON.stringify(boardid)}
                    }])[0].then(callback).catch(Notification.exception);
           
                break;
                case 'courselist':
                    if($("#id_customfield_class").val()){
                        var classid = $("#id_customfield_class").val();
                    }else{
                       var classid = 0;
                    }
                    Ajax.call([{
                    methodname: 'local_questions_course_selector',
                    args: {type: type, query: query,classid:JSON.stringify(classid)}
                    }])[0].then(callback).catch(Notification.exception);
           
                break;
                case 'topicslist':
                    if($("#id_customfield_course").val()){
                        var courseid = $("#id_customfield_course").val();
                    }else{
                      // var courseid = $(".el_courselist option:selected").val();
                       var courseid = 0;
                    }
                    Ajax.call([{
                    methodname: 'local_questions_topic_selector',
                    args: {type: type, query: query,courseid:JSON.stringify(courseid),questionid: questionid}
                    }])[0].then(callback).catch(Notification.exception);
                 break;
                 case 'chapterlist':
                    if($("#id_customfield_coursetopics").val()){
                        var topicid = $("#id_customfield_coursetopics").val();
                    }else{
                       var topicid = 0;
                    }
                    if($("#id_customfield_course").val()){
                        var courseid = $("#id_customfield_course").val();
                    }else{
                      // var courseid = $(".el_courselist option:selected").val();
                       var courseid = 0;
                    }
                    Ajax.call([{
                    methodname: 'local_questions_chapter_selector',
                    args: {type: type, query: query,topicid:JSON.stringify(topicid),courseid: courseid}
                    }])[0].then(callback).catch(Notification.exception);
                 break;
                 case 'unitlist':
                    if($("#id_customfield_chapter").val()){
                        var chapter = $("#id_customfield_chapter").val();
                    }else{
                       var chapter = 0;
                    }
                    if($("#id_customfield_course").val()){
                        var courseid = $("#id_customfield_course").val();
                    }else{
                       var courseid = 0;
                    }
                    if($("#id_customfield_coursetopics").val()){
                        var unitid = $("#id_customfield_coursetopics").val();
                    }else{
                       var unitid = 0;
                    }
                    Ajax.call([{
                    methodname: 'local_questions_unit_selector',
                    args: {type: type, query: query,chapterid:JSON.stringify(chapter),courseid: courseid,unitid:JSON.stringify(unitid)}
                    }])[0].then(callback).catch(Notification.exception);
                 break;
                 case 'conceptlist':
                    if($("#id_customfield_chapter").val()){
                        var chapter = $("#id_customfield_chapter").val();
                    }else{
                       var chapter = 0;
                    }
                    if($("#id_customfield_course").val()){
                        var courseid = $("#id_customfield_course").val();
                    }else{
                       var courseid = 0;
                    }
                    if($("#id_customfield_coursetopics").val()){
                        var unitid = $("#id_customfield_coursetopics").val();
                    }else{
                       var unitid = 0;
                    }
                    if($("#id_customfield_unit").val()){
                        var topicid = $("#id_customfield_unit").val();
                    }else{
                       var topicid = 0;
                    }
                    Ajax.call([{
                    methodname: 'local_questions_concept_selector',
                    args: {type: type, query: query,chapterid:JSON.stringify(chapter),courseid: courseid,unitid:JSON.stringify(unitid),topicid:JSON.stringify(topicid)}
                    }])[0].then(callback).catch(Notification.exception);
                 break;
                case 'questionidlist':
                    Ajax.call([{
                    methodname: 'local_questions_questionid_selector',
                    args: {type: type}
                    }])[0].then(callback).catch(Notification.exception);
                case 'allcourseslist':
                    Ajax.call([{
                    methodname: 'local_questions_allcourseslist_selector',
                    args: {type: type, query: query}
                    }])[0].then(callback).catch(Notification.exception);
           
                break;
                case 'difficultylist':
                    Ajax.call([{
                    methodname: 'local_questions_difficulty_selector',
                    args: {type: type}
                    }])[0].then(callback).catch(Notification.exception);
           
                break;
                case 'cognitivelist':
                    Ajax.call([{
                    methodname: 'local_questions_cognitive_selector',
                    args: {type: type}
                    }])[0].then(callback).catch(Notification.exception);
           
                break;
                case 'sourcelist':
                    Ajax.call([{
                    methodname: 'local_questions_source_selector',
                    args: {type: type, query: query}
                    }])[0].then(callback).catch(Notification.exception);
           
                break;
                case 'qstatuslist':
                    Ajax.call([{
                    methodname: 'local_questions_qstatus_selector',
                    args: {type: type}
                    }])[0].then(callback).catch(Notification.exception);
           
                break;
            }
        },
        selectedgoals: function() {
             $("#id_customfield_board option:selected").prop("selected", false);
             $("select[name='customfield_board']").parent().find('.badge-secondary').html('');
             $("#id_customfield_class option:selected").prop("selected", false);
             $("select[name='customfield_class']").parent().find('.badge-secondary').html('');
             $("#id_customfield_course option:selected").prop("selected", false);
             $("select[name='customfield_courses']").parent().find('.badge-secondary').html('');
             $("#id_customfield_coursetopics option:selected").prop("selected", false);
             $("select[name='customfield_topics']").parent().find('.badge-secondary').html(''); 
             $("#id_customfield_chapter option:selected").prop("selected", false);
             $("select[name='customfield_chapter']").parent().find('.badge-secondary').html('');
             $("#id_customfield_unit option:selected").prop("selected", false);
             $("select[name='customfield_unit']").parent().find('.badge-secondary').html('');
             $("#id_customfield_concept option:selected").prop("selected", false);
             $("select[name='customfield_concept']").parent().find('.badge-secondary').html('');                
        },
        selectedboard: function() {
            $("#id_customfield_class option:selected").prop("selected", false);
            $("select[name='customfield_class']").parent().find('.badge-secondary').html('');
            $("#id_customfield_course option:selected").prop("selected", false);
            $("select[name='customfield_courses']").parent().find('.badge-secondary').html('');
            $("#id_customfield_coursetopics option:selected").prop("selected", false);
            $("select[name='customfield_topics']").parent().find('.badge-secondary').html('');
            $("#id_customfield_chapter option:selected").prop("selected", false);
            $("select[name='customfield_chapter']").parent().find('.badge-secondary').html(''); 
            $("#id_customfield_unit option:selected").prop("selected", false);
            $("select[name='customfield_unit']").parent().find('.badge-secondary').html('');
            $("#id_customfield_concept option:selected").prop("selected", false);
            $("select[name='customfield_concept']").parent().find('.badge-secondary').html('');  
        },
        selectedclasses: function() {
            $("#id_customfield_course option:selected").prop("selected", false);
            $("select[name='customfield_courses']").parent().find('.badge-secondary').html('');
            $("#id_customfield_coursetopics option:selected").prop("selected", false);
            $("select[name='customfield_topics']").parent().find('.badge-secondary').html('');
            $("#id_customfield_chapter option:selected").prop("selected", false);
            $("select[name='customfield_chapter']").parent().find('.badge-secondary').html(''); 
            $("#id_customfield_unit option:selected").prop("selected", false);
            $("select[name='customfield_unit']").parent().find('.badge-secondary').html('');
            $("#id_customfield_concept option:selected").prop("selected", false);
            $("select[name='customfield_concept']").parent().find('.badge-secondary').html('');  
        },
        selectedcourses: function() {
            $("#id_customfield_coursetopics option:selected").prop("selected", false);
            $("select[name='customfield_topics']").parent().find('.badge-secondary').html('');
            $("#id_customfield_coursetopics option:selected").prop("selected", false);
            $("select[name='topic[]']").parent().find('.badge-secondary').html('');
            $("#id_customfield_chapter option:selected").prop("selected", false);
            $("select[name='customfield_chapter']").parent().find('.badge-secondary').html(''); 
            $("#id_customfield_unit option:selected").prop("selected", false);
            $("select[name='customfield_unit']").parent().find('.badge-secondary').html('');
            $("#id_customfield_concept option:selected").prop("selected", false);
            $("select[name='customfield_concept']").parent().find('.badge-secondary').html('');  
        },
        selectedtopics: function() {
            $("#id_customfield_chapter option:selected").prop("selected", false);
            $("select[name='customfield_chapter']").parent().find('.badge-secondary').html(''); 
            $("#id_customfield_topics option:selected").prop("selected", false);
            $("select[name='customfield_topics']").parent().find('.badge-secondary').html('');
            $("#id_customfield_concept option:selected").prop("selected", false);
            $("select[name='customfield_concept']").parent().find('.badge-secondary').html('');  
        },
        selectedchapter: function() {
            $("#id_customfield_topics option:selected").prop("selected", false);
            $("select[name='customfield_topics']").parent().find('.badge-secondary').html('');
            $("#id_customfield_concept option:selected").prop("selected", false);
            $("select[name='customfield_concept']").parent().find('.badge-secondary').html('');  
        },
        selectedconcepts: function() {
            $("#id_customfield_concept option:selected").prop("selected", false);
            $("select[name='customfield_concept']").parent().find('.badge-secondary').html('');  
        },
        removegoals:function(){
            $("#id_customfield_board option:selected").prop("selected", false);
            $("select[name='board']").parent().find('.badge-secondary').html('');
            $("#id_customfield_class option:selected").prop("selected", false);
            $("select[name='class']").parent().find('.badge-secondary').html('');
            $("#id_customfield_course option:selected").prop("selected", false);
            $("select[name='course']").parent().find('.badge-secondary').html('');
            $("select[name='subject']").parent().find('.badge-secondary').html('');
            $("select[name='courses']").parent().find('.badge-secondary').html('');
            $("select[name='topic']").parent().find('.badge-secondary').html('');
            $("#id_customfield_coursetopics option:selected").prop("selected", false);
            $("select[name='topic[]']").parent().find('.badge-secondary').html('');
            $("select[name='coursetopics']").parent().find('.badge-secondary').html('');
            $("#id_customfield_chapter option:selected").prop("selected", false);
            $("select[name='customfield_chapter']").parent().find('.badge-secondary').html('');
            $("#id_customfield_unit option:selected").prop("selected", false);
            $("select[name='customfield_unit']").parent().find('.badge-secondary').html('');  
            $("select[name='unit']").parent().find('.badge-secondary').html('');  
            $("select[name='chapter']").parent().find('.badge-secondary').html(''); 
            $("select[name='customfield_concept']").parent().find('.badge-secondary').html('');  
            $("select[name='concept']").parent().find('.badge-secondary').html('');   
        },
        removeboards:function(){
            $("#id_customfield_class option:selected").prop("selected", false);
            $("select[name='class']").parent().find('.badge-secondary').html('');
            $("#id_customfield_course option:selected").prop("selected", false);
            $("select[name='course']").parent().find('.badge-secondary').html('');
            $("#id_customfield_coursetopics option:selected").prop("selected", false);
            $("select[name='topic[]']").parent().find('.badge-secondary').html('');
            $("select[name='coursetopics']").parent().find('.badge-secondary').html('');
            $("#id_customfield_chapter option:selected").prop("selected", false);
            $("select[name='customfield_chapter']").parent().find('.badge-secondary').html('');
            $("#id_customfield_unit option:selected").prop("selected", false);
            $("select[name='customfield_unit']").parent().find('.badge-secondary').html(''); 
            $("select[name='unit']").parent().find('.badge-secondary').html('');  
            $("select[name='topic']").parent().find('.badge-secondary').html('');
            $("select[name='chapter']").parent().find('.badge-secondary').html('');  
            $("select[name='subject']").parent().find('.badge-secondary').html(''); 
            $("select[name='customfield_concept']").parent().find('.badge-secondary').html('');  
            $("select[name='concept']").parent().find('.badge-secondary').html('');    
        },
        removeclasses:function(){
            $("#id_customfield_course option:selected").prop("selected", false);
            $("select[name='course']").parent().find('.badge-secondary').html('');
            $("#id_customfield_coursetopics option:selected").prop("selected", false);
            $("select[name='topic[]']").parent().find('.badge-secondary').html('');
            $("select[name='coursetopics']").parent().find('.badge-secondary').html('');
            $("#id_customfield_chapter option:selected").prop("selected", false);
            $("select[name='customfield_chapter']").parent().find('.badge-secondary').html(''); 
            $("#id_customfield_unit option:selected").prop("selected", false);
            $("select[name='customfield_unit']").parent().find('.badge-secondary').html(''); 
            $("select[name='subject']").parent().find('.badge-secondary').html('');
            $("select[name='topic']").parent().find('.badge-secondary').html('');
            $("select[name='unit']").parent().find('.badge-secondary').html('');  
            $("select[name='chapter']").parent().find('.badge-secondary').html('');
            $("select[name='customfield_concept']").parent().find('.badge-secondary').html('');  
            $("select[name='concept']").parent().find('.badge-secondary').html('');     
        },    
        removecourses:function(){
            $("#id_customfield_coursetopics option:selected").prop("selected", false);
            $("select[name='topic[]']").parent().find('.badge-secondary').html('');
            $("select[name='coursetopics']").parent().find('.badge-secondary').html('');
            $("#id_customfield_chapter option:selected").prop("selected", false);
            $("select[name='customfield_chapter']").parent().find('.badge-secondary').html(''); 
            $("#id_customfield_unit option:selected").prop("selected", false);
            $("select[name='customfield_unit']").parent().find('.badge-secondary').html('');
            $("select[name='topic']").parent().find('.badge-secondary').html('');
            $("select[name='unit']").parent().find('.badge-secondary').html('');  
            $("select[name='chapter']").parent().find('.badge-secondary').html(''); 
            $("select[name='customfield_concept']").parent().find('.badge-secondary').html('');  
            $("select[name='concept']").parent().find('.badge-secondary').html('');     
        },
        removeunit:function(){
            $("#id_customfield_chapter option:selected").prop("selected", false);
            $("#id_customfield_unit option:selected").prop("selected", false);
            $("select[name='unit']").parent().find('.badge-secondary').html('');  
            $("select[name='chapter']").parent().find('.badge-secondary').html(''); 
            $("select[name='customfield_concept']").parent().find('.badge-secondary').html('');  
            $("select[name='concept']").parent().find('.badge-secondary').html('');     
        },
        removechapter:function(){
            $("#id_customfield_unit option:selected").prop("selected", false);
            $("select[name='unit']").parent().find('.badge-secondary').html('');
            $("select[name='customfield_concept']").parent().find('.badge-secondary').html('');  
            $("select[name='concept']").parent().find('.badge-secondary').html('');   
        },
        removeconcept:function(){
            $("#id_customfield_concept option:selected").prop("selected", false);  
            $("select[name='concept']").parent().find('.badge-secondary').html('');   
        },
    };

});

define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'local_onlineexams/jquery.dataTables'],
        function($, Str, ModalFactory) {
    return {       
        displayusers: function(id) {
            $.ajax({
                url:M.cfg.wwwroot+"/local/onlineexams/custom_ajax.php?page=1&id="+id,
                cache: false,
                success:function(result){
                        ModalFactory.create({
                        title: Str.get_string('enrolledlist', 'local_onlineexams'),
                        type: ModalFactory.types.DEFAULT,
                        body: result
                    }).done(function(modal) {
                            this.modal = modal;
                            modal.show();
                            modal.setLarge();
                            modal.getRoot().addClass('openLMStransition');
                            modal.getRoot().animate({"right":"0%"}, 500);
                            modal.getRoot().find('[data-action="hide"]').on('click', function() {
                                modal.setBody('');
                            modal.getRoot().animate({"right":"-85%"}, 500);
                                setTimeout(function(){
                                modal.destroy();
                            }, 1000);
                            });
                    });                    
                },
                error: function(){
                    $('#onlinetestview'+id).html('error');
                },                
                dataType: "html"
            });
            
        },
        displaycompletedusers: function(id) {
            $.ajax({
                url:M.cfg.wwwroot+"/local/onlineexams/custom_ajax.php?page=2&id="+id,
                cache: false,
                success:function(result){
                        ModalFactory.create({
                        title: Str.get_string('completedlist', 'local_onlineexams'),
                        type: ModalFactory.types.DEFAULT,
                        body: result
                    }).done(function(modal) {
                            this.modal = modal;
                            modal.show();
                            modal.setLarge();
                            modal.getRoot().addClass('openLMStransition');
                            modal.getRoot().animate({"right":"0%"}, 500);
                            modal.getRoot().find('[data-action="hide"]').on('click', function() {
                                modal.setBody('');
                            modal.getRoot().animate({"right":"-85%"}, 500);
                                setTimeout(function(){
                                modal.destroy();
                            }, 1000);
                            });
                    });                    
                },
                error: function(){
                    $('#onlinetestview'+id).html('error');
                },                
                dataType: "html"
            });
            
        },
        load: function () {}
    };
});

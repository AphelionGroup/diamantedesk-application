define([
  'app',
  'Common/views/form',
  'tpl!../templates/form.ejs'], function(App, CommonForm, formTemplate){

  return App.module('Ticket.View.Comment.Create', function(Create, App, Backbone, Marionette, $, _){

    Create.LayoutView = CommonForm.LayoutView.extend({
      template : formTemplate,
      className : 'comments-form',
      regions : {
        attachmentRegion: '#comment-attachment',
        dropRegion : '#comment-attachment-drop'
      },
      initialize : function(options){
        this.attachmentCollection = options.attachmentCollection;
      },
      onShow : function(){
        var formView = this;

        require(['Attachment/views/list', 'Attachment/views/dropzone'], function(CommentAttachment,CommentDropZone){


          var listView = new CommentAttachment.CollectionView({ collection: formView.attachmentCollection }),
              dropZone = new CommentDropZone.ItemView();

          dropZone.on('attachment:add', function(data){
            formView.trigger('attachment:add', data);
          });

          listView.on('childview:attachment:delete', function(childView, model){
            formView.trigger('attachment:delete', model);
          });

          formView.attachmentRegion.show(listView);
          formView.dropRegion.show(dropZone);

        });

        CommonForm.LayoutView.prototype.onShow.call(this);

      }
    });

  });

});
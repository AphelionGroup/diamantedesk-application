define(function (require) {

  "use strict";

  var $ = require('jquery');
  var BaseComponent = require('oroui/js/app/components/base/component');

  var CommentComponentForm = BaseComponent.extend({

    initialize : function(options){
      this.$elem  = options._sourceElement;
      this.privateToggler = $('#' + options.privateInputId);
      this.privateToggler.change(this.onChange.bind(this));
      this.onChange();
    },

    onChange : function(){
      this.$elem.toggleClass('private', this.privateToggler[0].checked);
    },

    dispose: function () {
      if (this.disposed) {
        return;
      }
      delete this.$elem;
    }

  });

  return CommentComponentForm;


});

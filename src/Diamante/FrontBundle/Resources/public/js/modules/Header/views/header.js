define([
  'app',
  'config',
  'tpl!../templates/header.ejs'], function(App, Config, headerTemplate){

  return App.module('Header', function(Header, App, Backbone, Marionette, $, _){

    Header.startWithParent = false;

    Header.LayoutView = Marionette.LayoutView.extend({
      template: headerTemplate,
      className: 'container',

      regions : {
        profileRegion : '#profile'
      },

      initialize: function(options){
        this.options = options;
      },

      templateHelpers: function(){
        return {
          baseUrl: this.options.baseUrl,
          basePath: this.options.basePath,
          title: this.options.title,
          logo: this.options.logo,
          logoXs: this.options.logoXs
        };
      },

      ui : {
        'createTicketButton' : '.js-create-ticket',
        'searchForm' : '.js-search-form',
        'searchInput' : '.js-search-input'
      },

      events : {
        'click @ui.createTicketButton' : 'createTicketHandler',
        'submit @ui.searchForm' : 'searchTicketHandler'
      },

      createTicketHandler : function(e){
        e.preventDefault();
        App.trigger('ticket:create');
      },

      searchTicketHandler : function(e){
        e.preventDefault();
        App.trigger('ticket:search', this.ui.searchInput.val());
      }

    });

  });


});

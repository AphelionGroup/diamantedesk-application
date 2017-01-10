/*
 * Copyright (c) 2014 Eltrino LLC (http://eltrino.com)
 *
 * Licensed under the Open Software License (OSL 3.0).
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://opensource.org/licenses/osl-3.0.php
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@eltrino.com so we can send you a copy immediately.
 */
define([
  'marionette',
  'backbone',
  'config',
  'helpers/translator',
  'bootstrap'], function(Marionette, Backbone, Config) {

  var App = new Marionette.Application({

    regions : {
      headerRegion: '#header',
      messagesRegion: '#messages',
      mainRegion:   '#content',
      footerRegion: '#footer',
      dialogRegion: '#dialog'
    }

  });

  App.debug = function(type){
    if(Config.isDev) {
      if(arguments.length > 1){
        console[type].apply(console, [].slice.call(arguments, 1));
      } else {
        console.log.apply(console, arguments);
      }
    }
  };

  App.alert = function(options){
    require(['Common/views/alert'], function(Alert){
      App.dialogRegion.show(new Alert.View(options));
    });
  };

  App.navigate = function(route, options){
    if(Backbone.History.started){
      Backbone.history.navigate(route, options || {});
    } else {
      App.on('history:start', function(){
        Backbone.history.navigate(route, options || {});
      });
    }
  };

  App.back = function(){
    window.history.back();
  };

  App.getCurrentRoute = function(){
    return Backbone.history.fragment;
  };

  App.setTitle = function(title){
    var template = "{title} | " + Config.title;
    document.title = title ? template.replace('{title}', title) : Config.title;
  };

  App.on('before:start', function(){ });

  App.on('start', function(){
    Backbone.history.start();
    this.trigger('history:start');
  });

  require(['Common/views/loader']);

  require(['Common', 'User', 'Session', 'Header', 'Footer', 'Ticket'], function(){
    App.start();
  });

  window.App = App;

  return App;

});
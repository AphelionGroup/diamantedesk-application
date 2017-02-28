define(['app'], function(App){

  return App.module('Session', function(Session, App, Backbone, Marionette, $, _){

    Session.startWithParent = false;

    Session.Router = Marionette.AppRouter.extend({
      appRoutes: {
        'login' : 'login',
        'logout' : 'logout',
        'registration': 'registration',
        'confirm/:hash': 'confirm',
        'reconfirm/:email': 'reconfirm',
        'resetpassword': 'reset',
        'newpassword/:hash': 'newPassword'
      }
    });

    var API = {
      login: function(options){
        if(options && options.return_path){
          App.session.return_path = options.return_path;
        }
        if(App.session.get('logged_in')){
          App.navigate('');
        } else {
          App.setTitle(__('diamante_front.session.title.login'));
          require(['modules/Session/controllers/login'], function(){
            Session.LoginController();
          });
        }
      },

      logout: function(){
        App.session.logout();
        App.trigger('session:login');
      },

      registration: function(){
        if(App.session.get('logged_in')){
          App.navigate('');
        } else {
          App.setTitle(__('diamante_front.session.title.register'));
          require(['modules/Session/controllers/registration'], function(){
            Session.RegistrationController();
          });
        }
      },

      confirm: function(hash){
        if(App.session.get('logged_in')){
          App.navigate('');
        } else {
          require(['modules/Session/controllers/confirm'], function(){
            Session.ConfirmController(hash);
          });
        }
      },

      reconfirm: function(email){
        if(App.session.get('logged_in')){
          App.navigate('');
        } else {
          require(['modules/Session/controllers/confirm'], function(){
            Session.ReConfirmController(email);
          });
        }
      },

      reset: function(){
        App.setTitle(__('diamante_front.session.title.reset'));
        require(['modules/Session/controllers/reset'], function(){
          Session.ResetController();
        });
      },

      newPassword: function(hash){
        require(['modules/Session/controllers/reset'], function(){
          Session.ResetController(hash);
        });
      }

    };

    App.on('session:login', function(options){
      App.debug('info', 'Event "session:login" fired');
      App.navigate('login');
      API.login(options);
    });

    App.on('session:logout', function(){
      App.debug('info', 'Event "session:logout" fired');
      App.navigate('logout');
      API.logout();
    });

    App.on('session:registration', function(){
      App.debug('info', 'Event "session:registration" fired');
      App.navigate('registration');
      API.registration();
    });

    App.on('session:reset', function(){
      App.debug('info', 'Event "session:reset" fired');
      App.navigate('resetpassword');
      API.reset();
    });

    Session.on('start',function(){
      new Session.Router({
        controller: API
      });
    });

  });

});
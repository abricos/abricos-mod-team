var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['application.js']},
        {name: '{C#MODNAME}', files: ['model.js']}
    ]
};
Component.entryPoint = function(NS){

    NS.uses = function(modules, component, callback, context){
        if (!modules || modules.length === 0){
            return callback.call(context, null);
        }
        var module = modules.pop();

        Brick.use(module, component, function(err, ns){
            NS.uses(modules, component, callback, context);
        });
    };

    NS.initApps = function(stack, callback, context){
        if (stack.length === 0){
            return callback.call(context || this);
        }
        var appName = stack.pop();

        Brick.use(appName, 'lib', function(err, ns){
            if (err){
                return NS.initApps(stack, callback, context);
            }
            ns.initApp({
                initCallback: function(err, appInstance){
                    return NS.initApps(stack, callback, context);
                }
            });
        });
    };

    NS.ATTRIBUTE = {
        teamid: {
            value: 0,
            setter: function(val){
                return val | 0;
            }
        },
        team: {value: null},
        teamApp: {
            readOnly: true,
            getter: function(){
                var app = this.get('appInstance');
                if (!app){
                    return null;
                }
                if (app.get('moduleName') === 'team'){
                    return app;
                }
                return app.getApp('team');
            }
        }
    };

};
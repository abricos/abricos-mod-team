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

};
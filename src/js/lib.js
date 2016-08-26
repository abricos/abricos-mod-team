var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['application.js']},
        {name: '{C#MODNAME}', files: ['model.js']}
    ]
};
Component.entryPoint = function(NS){

    NS.roles = new Brick.AppRoles('{C#MODNAME}', {
        isAdmin: 50,
        isWrite: 30,
        isView: 10
    });

    var COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.uses = function(modules, component, callback, context){
        if (!modules || modules.length === 0){
            return callback.call(context, null);
        }
        var module = modules.pop();

        Brick.use(module, component, function(err, ns){
            NS.uses(modules, component, callback, context);
        });
    };

    SYS.Application.build(COMPONENT, {}, {
        initializer: function(){
            NS.roles.load(function(){
                this.initCallbackFire();
            }, this);
        },
    }, [], {
        ATTRS: {
            isLoadAppStructure: {value: true},
            Team: {value: NS.Team},
            TeamList: {value: NS.TeamList},
            Config: {value: NS.Config}
        },
        REQS: {
            teamList: {
                args: ['filter'],
                type: 'modelList:TeamList'
            }
        },
        URLS: {
            ws: "#app={C#MODNAMEURI}/wspace/ws/",
        }
    });
};
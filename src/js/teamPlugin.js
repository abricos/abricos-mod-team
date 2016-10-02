var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'team', files: ['wspace.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.TeamPluginWidget = Y.Base.create('TeamPluginWidget', SYS.AppWidget, [
        NS.PluginWidgetExt
    ], {}, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
        },
    });

    NS.TeamWSMenuWidget = Y.Base.create('TeamWSMenuWidget', SYS.AppWidget, [
        NS.PluginWSMenuWidgetExt
    ], {}, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'menu'},
        }
    });

};

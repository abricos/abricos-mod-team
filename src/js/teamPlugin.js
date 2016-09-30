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
        SYS = Brick.mod.sys,
        TEAM = Brick.mod.team;

    NS.TeamPluginWidget = Y.Base.create('TeamPluginWidget', SYS.AppWidget, [
        TEAM.PluginWidgetExt
    ], {}, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
        },
    });

    NS.TeamWSMenuWidget = Y.Base.create('TeamWSMenuWidget', SYS.AppWidget, [
        TEAM.PluginWSMenuWidgetExt
    ], {
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                team = this.get('team'),
                page = this.get('page');

            tp.toggleView(page.module === 'team', 'list');
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'menu'},
        }
    });

};

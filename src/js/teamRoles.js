var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.TeamRolesWidget = Y.Base.create('TeamRolesWidget', SYS.AppWidget, [
        NS.PluginWidgetExt
    ], {
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                team = this.get('team');

        },
        save: function(){
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
        }
    });
};

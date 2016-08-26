var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['editor.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.TeamEditorFormWidget = Y.Base.create('TeamEditorFormWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){

        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'form'},
            teamid: {value: 0},
        },
        CLICKS: {
        },
    });
};

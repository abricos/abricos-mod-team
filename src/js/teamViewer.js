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

    NS.TeamListWidget = Y.Base.create('teamListWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance, options){
            this.set('waiting', true);

            var teamid = this.get('teamid');

            appInstance.team(teamid, function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.set('team', result.team);

                    var modules = result.teamList.toArray('module', {distinct: true});
                    NS.uses(modules, 'teamList', function(){
                        this.renderTeamList();
                    }, this);
                }
            }, this);
        },

    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            teamid: {value: 0},
            team: {value: null},
            ownerModule: {value: ''}
        },
        CLICKS: {}
    });

};
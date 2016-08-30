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

    var TeamViewerWidgetExt = function(){
    };
    TeamViewerWidgetExt.ATTRS = {
        teamid: NS.ATTRIBUTE.teamid,
        team: NS.ATTRIBUTE.team,
        teamApp: NS.ATTRIBUTE.teamApp,
    };
    TeamViewerWidgetExt.prototype = {
        onInitAppWidget: function(err, appInstance){
            var teamApp = this.get('teamApp'),
                teamid = this.get('teamid') | 0;

            this.set('waiting', true);
            teamApp.team(teamid, function(err, result){
                var team = err ? null : result.team;
                this._onLoadTeam(team);
            }, this);
        },
        _onLoadTeam: function(team){
            this.set('waiting', false);
            this.set('team', team);

            var tp = this.template;

            if (team){
                tp.setHTML({
                    title: team.get('title')
                });
            }

            this.onLoadTeam(team);
        },
        onLoadTeam: function(team){
        },
    };
    NS.TeamViewerWidgetExt = TeamViewerWidgetExt;

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